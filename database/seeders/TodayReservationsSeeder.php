<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TodayReservationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ✅ Postgres-safe statuses (you enforce them via CHECK)
            $STATUS_RESERVED = 'RESERVED';
            $STATUS_PENDING  = 'PENDING';
            $STATUS_REJECTED = 'REJECTED';

            // statuses that "occupy" a spot
            $OCCUPY = ['RESERVED','CHECKED_IN','CHECKED_OUT','COMPLETED','NO_SHOW'];

            // -----------------------------
            // 1) Today events only
            // -----------------------------
            $today = Carbon::today()->toDateString();

            $todayEventIds = DB::table('events')
                ->whereDate('starts_at', $today)
                ->pluck('event_id')
                ->toArray();

            if (empty($todayEventIds)) return;

            // -----------------------------
            // 2) ACTIVE workers only (join users)
            // -----------------------------
            $activeWorkers = DB::table('workers')
                ->join('users', 'users.id', '=', 'workers.user_id')
                ->where('users.role', 'WORKER')
                ->where('users.status', 'ACTIVE')
                ->select('workers.worker_id', 'workers.role_type_id')
                ->orderBy('workers.worker_id')
                ->get();

            if ($activeWorkers->isEmpty()) return;

            $workersByRoleType = [];
            $allActiveWorkerIds = [];

            foreach ($activeWorkers as $w) {
                $workersByRoleType[(int)$w->role_type_id][] = (int)$w->worker_id;
                $allActiveWorkerIds[] = (int)$w->worker_id;
            }

            $now = Carbon::now();

            // -----------------------------
            // 3) Seed reservations per event
            // -----------------------------
            foreach ($todayEventIds as $eventId) {

                $roles = DB::table('work_roles')
                    ->where('event_id', $eventId)
                    ->select('role_id', 'role_type_id', 'required_spots')
                    ->orderByDesc('required_spots')
                    ->get();

                if ($roles->isEmpty()) continue;

                // workers already having any reservation in this event (avoid duplicates)
                $existingWorkerIds = DB::table('workers_reservations')
                    ->where('event_id', $eventId)
                    ->pluck('worker_id')
                    ->map(fn($x) => (int)$x)
                    ->toArray();

                $used = array_fill_keys($existingWorkerIds, true);

                // -----------------------------
                // 3.A) Fill RESERVED per role up to required_spots
                // -----------------------------
                foreach ($roles as $role) {

                    $occupied = DB::table('workers_reservations')
                        ->where('event_id', $eventId)
                        ->where('work_role_id', $role->role_id)
                        ->whereIn('status', $OCCUPY)
                        ->count();

                    $need = (int)$role->required_spots - (int)$occupied;
                    if ($need <= 0) continue;

                    $roleTypeId = (int)$role->role_type_id;
                    $candidates = $workersByRoleType[$roleTypeId] ?? [];
                    if (empty($candidates)) continue;

                    sort($candidates);

                    foreach ($candidates as $workerId) {
                        if ($need <= 0) break;
                        if (isset($used[$workerId])) continue;

                        DB::table('workers_reservations')->updateOrInsert(
                            [
                                'event_id'     => $eventId,
                                'work_role_id' => (int)$role->role_id,
                                'worker_id'    => (int)$workerId,
                            ],
                            [
                                'reserved_at'    => $now,
                                'status'         => $STATUS_RESERVED,
                                'check_in_time'  => null,
                                'check_out_time' => null,
                                'credited_hours' => null,
                                'created_at'     => $now,
                                'updated_at'     => $now,
                            ]
                        );

                        $used[$workerId] = true;
                        $need--;
                    }
                }

                // -----------------------------
                // 3.B) Ensure exactly 2 PENDING + 2 REJECTED per event (top-up)
                // -----------------------------
                $pendingCount = DB::table('workers_reservations')
                    ->where('event_id', $eventId)
                    ->where('status', $STATUS_PENDING)
                    ->count();

                $rejectedCount = DB::table('workers_reservations')
                    ->where('event_id', $eventId)
                    ->where('status', $STATUS_REJECTED)
                    ->count();

                $pendingNeed  = max(0, 2 - (int)$pendingCount);
                $rejectedNeed = max(0, 2 - (int)$rejectedCount);

                if ($pendingNeed === 0 && $rejectedNeed === 0) continue;

                $mainRole = $roles->first();
                $mainRoleId   = (int)$mainRole->role_id;
                $mainRoleType = (int)$mainRole->role_type_id;

                // prefer same role_type candidates first
                $extraCandidates = array_values(array_filter(
                    $workersByRoleType[$mainRoleType] ?? [],
                    fn($wid) => !isset($used[(int)$wid])
                ));
                sort($extraCandidates);

                // if not enough, take any active workers not used
                if (count($extraCandidates) < ($pendingNeed + $rejectedNeed)) {
                    $fallback = array_values(array_filter(
                        $allActiveWorkerIds,
                        fn($wid) => !isset($used[(int)$wid])
                    ));
                    sort($fallback);
                    $extraCandidates = array_values(array_unique(array_merge($extraCandidates, $fallback)));
                }

                $needTotal = $pendingNeed + $rejectedNeed;
                $picked = array_slice($extraCandidates, 0, $needTotal);

                $pendingWorkers  = array_slice($picked, 0, $pendingNeed);
                $rejectedWorkers = array_slice($picked, $pendingNeed, $rejectedNeed);

                foreach ($pendingWorkers as $workerId) {
                    $workerId = (int)$workerId;

                    DB::table('workers_reservations')->updateOrInsert(
                        [
                            'event_id'     => $eventId,
                            'work_role_id' => $mainRoleId,
                            'worker_id'    => $workerId,
                        ],
                        [
                            'reserved_at'    => $now,
                            'status'         => $STATUS_PENDING,
                            'check_in_time'  => null,
                            'check_out_time' => null,
                            'credited_hours' => null,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]
                    );

                    $used[$workerId] = true;
                }

                foreach ($rejectedWorkers as $workerId) {
                    $workerId = (int)$workerId;

                    DB::table('workers_reservations')->updateOrInsert(
                        [
                            'event_id'     => $eventId,
                            'work_role_id' => $mainRoleId,
                            'worker_id'    => $workerId,
                        ],
                        [
                            'reserved_at'    => $now,
                            'status'         => $STATUS_REJECTED,
                            'check_in_time'  => null,
                            'check_out_time' => null,
                            'credited_hours' => null,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]
                    );

                    $used[$workerId] = true;
                }
            }
        });
    }
}
