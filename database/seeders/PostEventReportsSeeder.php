<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PostEventReportsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // -----------------------------
        // Helper: detect column names safely
        // -----------------------------
        $hasCol = fn(string $table, string $col) => DB::getSchemaBuilder()->hasColumn($table, $col);

        $eventsPk            = $hasCol('events', 'event_id') ? 'event_id' : 'id';
        $eventsCreatedByCol  = $hasCol('events', 'created_by') ? 'created_by' : null;

        if (!$eventsCreatedByCol) {
            $this->command?->warn("PostEventReportsSeeder: events.created_by column not found. Skipping.");
            return;
        }

        $employeesPk = $hasCol('employees', 'employee_id') ? 'employee_id' : 'id';
        $employeesUserIdCol = $hasCol('employees', 'user_id') ? 'user_id' : null;

        if (!$employeesUserIdCol) {
            $this->command?->warn("PostEventReportsSeeder: employees.user_id column not found. Skipping.");
            return;
        }

        $resPk = $hasCol('workers_reservations', 'reservation_id') ? 'reservation_id' : 'id';

        // workers_reservations columns (we will only update what exists)
        $resEventIdCol      = $hasCol('workers_reservations', 'event_id') ? 'event_id' : null;
        $resWorkRoleIdCol   = $hasCol('workers_reservations', 'work_role_id') ? 'work_role_id' : null;
        $resWorkerIdCol     = $hasCol('workers_reservations', 'worker_id') ? 'worker_id' : null;
        $resReservedAtCol   = $hasCol('workers_reservations', 'reserved_at') ? 'reserved_at' : null;
        $resStatusCol       = $hasCol('workers_reservations', 'status') ? 'status' : null;
        $resCheckInCol      = $hasCol('workers_reservations', 'check_in_time') ? 'check_in_time' : null;
        $resCheckOutCol     = $hasCol('workers_reservations', 'check_out_time') ? 'check_out_time' : null;
        $resCreditedCol     = $hasCol('workers_reservations', 'credited_hours') ? 'credited_hours' : null;

        if (!$resEventIdCol || !$resWorkRoleIdCol || !$resWorkerIdCol) {
            $this->command?->warn("PostEventReportsSeeder: workers_reservations missing required columns (event_id/work_role_id/worker_id). Skipping.");
            return;
        }

        // post_event_submissions columns
        $pesReservationFk = $hasCol('post_event_submissions', 'worker_reservation_id')
            ? 'worker_reservation_id'
            : ($hasCol('post_event_submissions', 'reservation_id') ? 'reservation_id' : null);

        if (!$pesReservationFk) {
            $this->command?->warn("PostEventReportsSeeder: post_event_submissions missing reservation FK (worker_reservation_id or reservation_id). Skipping.");
            return;
        }

        $pesCols = [
            'event_id'     => $hasCol('post_event_submissions', 'event_id'),
            'worker_id'    => $hasCol('post_event_submissions', 'worker_id'),
            'work_role_id' => $hasCol('post_event_submissions', 'work_role_id'),
            'role_slug'    => $hasCol('post_event_submissions', 'role_slug'),
            'general_notes'=> $hasCol('post_event_submissions', 'general_notes'),
            'data'         => $hasCol('post_event_submissions', 'data'),
            'status'       => $hasCol('post_event_submissions', 'status'),
            'owner_rating' => $hasCol('post_event_submissions', 'owner_rating'),
            'worker_rating'=> $hasCol('post_event_submissions', 'worker_rating'),
            'submitted_at' => $hasCol('post_event_submissions', 'submitted_at'),
            'reviewed_at'  => $hasCol('post_event_submissions', 'reviewed_at'),
            'reviewed_by'  => $hasCol('post_event_submissions', 'reviewed_by'),
            'review_notes' => $hasCol('post_event_submissions', 'review_notes'),
            'created_at'   => $hasCol('post_event_submissions', 'created_at'),
            'updated_at'   => $hasCol('post_event_submissions', 'updated_at'),
        ];

        // -----------------------------
        // 1) Get 2 employees dynamically (NO hardcoded IDs)
        // -----------------------------
        $createdByEmployeeIds = DB::table('employees')
            ->orderBy($employeesPk)
            ->limit(2)
            ->pluck($employeesPk)
            ->toArray();

        if (empty($createdByEmployeeIds)) {
            $this->command?->warn("PostEventReportsSeeder: No employees found, skipping.");
            return;
        }

        // 2) Get events created by those employees
        $events = DB::table('events')
            ->whereIn($eventsCreatedByCol, $createdByEmployeeIds)
            ->get([$eventsPk, $eventsCreatedByCol]);

        if ($events->isEmpty()) {
            $this->command?->warn("PostEventReportsSeeder: No events found for employees: " . implode(',', $createdByEmployeeIds));
            return;
        }

        $eventIds = $events->pluck($eventsPk)->map(fn($v) => (int)$v)->toArray();

        // 3) Map employee_id -> user_id (reviewed_by = users.id)
        $employeeToUser = DB::table('employees')
            ->whereIn($employeesPk, $createdByEmployeeIds)
            ->pluck($employeesUserIdCol, $employeesPk)
            ->toArray();

        foreach ($createdByEmployeeIds as $eid) {
            if (!isset($employeeToUser[$eid]) || empty($employeeToUser[$eid])) {
                $this->command?->warn("PostEventReportsSeeder: employees.user_id missing for {$employeesPk}={$eid}. Skipping.");
                return;
            }
        }

        // 4) Pull reservations for these events
        $reservations = DB::table('workers_reservations')
            ->whereIn($resEventIdCol, $eventIds)
            ->orderBy($resPk)
            ->get();

        if ($reservations->isEmpty()) {
            $this->command?->warn("PostEventReportsSeeder: No workers_reservations found for events: " . implode(',', $eventIds));
            return;
        }

        // 5) Ensure COMPLETED + set missing times (only update columns that exist)
        foreach ($reservations as $r) {
            $reservedAt = $resReservedAtCol && !empty($r->{$resReservedAtCol}) ? Carbon::parse($r->{$resReservedAtCol}) : Carbon::now();

            $checkIn  = $resCheckInCol  ? ($r->{$resCheckInCol}  ?: $reservedAt->copy()->addHours(8)->format('Y-m-d H:i:s')) : null;
            $checkOut = $resCheckOutCol ? ($r->{$resCheckOutCol} ?: Carbon::parse($checkIn ?? $reservedAt)->addHours(2)->format('Y-m-d H:i:s')) : null;

            $update = ['updated_at' => $now];

            if ($resStatusCol)   $update[$resStatusCol] = 'COMPLETED';
            if ($resCheckInCol)  $update[$resCheckInCol] = $checkIn;
            if ($resCheckOutCol) $update[$resCheckOutCol] = $checkOut;
            if ($resCreditedCol) $update[$resCreditedCol] = !empty($r->{$resCreditedCol}) ? $r->{$resCreditedCol} : 2.00;

            DB::table('workers_reservations')
                ->where($resPk, $r->{$resPk})
                ->update($update);
        }

        $reservationIds = $reservations->pluck($resPk)->map(fn($v) => (int)$v)->toArray();

        // 6) Delete old submissions for these reservations (rerun-safe)
        DB::table('post_event_submissions')
            ->whereIn($pesReservationFk, $reservationIds)
            ->delete();

        // 7) Build role map from work_roles
        $workRolesPk = $hasCol('work_roles', 'role_id') ? 'role_id' : ($hasCol('work_roles', 'work_role_id') ? 'work_role_id' : 'id');
        $workRolesName = $hasCol('work_roles', 'role_name') ? 'role_name' : ($hasCol('work_roles', 'name') ? 'name' : null);

        if (!$workRolesName) {
            $this->command?->warn("PostEventReportsSeeder: work_roles missing role_name/name column. Using fallback role slug.");
            $roleMap = [];
        } else {
            $neededWorkRoleIds = $reservations->pluck($resWorkRoleIdCol)->unique()->map(fn($v) => (int)$v)->toArray();
            $roleMap = DB::table('work_roles')
                ->whereIn($workRolesPk, $neededWorkRoleIds)
                ->pluck($workRolesName, $workRolesPk) // [pk => name]
                ->toArray();
        }

        // 8) event_id -> reviewed_by (users.id)
        $eventToReviewedByUserId = [];
        foreach ($events as $ev) {
            $employeeId = (int)$ev->{$eventsCreatedByCol};
            $eventToReviewedByUserId[(int)$ev->{$eventsPk}] = (int)$employeeToUser[$employeeId];
        }

        // ratings (mixed)
        $ownerRatings  = [5,4,3,2,4,5,3,2,5,4,3,2,4,5,3,2,5,4,3,2];
        $workerRatings = [4,5,2,3,5,3,4,2,3,5,2,4,5,3,2,4,3,5,2,4];

        $rows = [];
        $i = 0;

        foreach ($reservations as $r) {
            $roleName = $roleMap[(int)$r->{$resWorkRoleIdCol}] ?? 'Role';
            $roleSlug = $this->toSlug($roleName);

            $checkOutForSubmit = ($resCheckOutCol && !empty($r->{$resCheckOutCol})) ? Carbon::parse($r->{$resCheckOutCol}) : Carbon::now();
            $submittedAt = $checkOutForSubmit->copy()->addMinutes(20);
            $reviewedAt  = $submittedAt->copy()->addHours(2);

            $eventId = (int)$r->{$resEventIdCol};
            $reviewedByUserId = $eventToReviewedByUserId[$eventId] ?? null;

            if (!$reviewedByUserId && $pesCols['reviewed_by']) {
                $this->command?->warn("PostEventReportsSeeder: No reviewed_by mapping found for event_id={$eventId}. Skipping.");
                continue;
            }

            $row = [];
            $row[$pesReservationFk] = (int)$r->{$resPk};

            if ($pesCols['event_id'])      $row['event_id'] = $eventId;
            if ($pesCols['worker_id'])     $row['worker_id'] = (int)$r->{$resWorkerIdCol};
            if ($pesCols['work_role_id'])  $row['work_role_id'] = (int)$r->{$resWorkRoleIdCol};
            if ($pesCols['role_slug'])     $row['role_slug'] = $roleSlug;

            if ($pesCols['general_notes']) $row['general_notes'] = null;
            if ($pesCols['data'])          $row['data'] = json_encode($this->buildDataPayload($roleSlug), JSON_UNESCAPED_UNICODE);

            if ($pesCols['status'])        $row['status'] = 'APPROVED'; // safest enum-style
            if ($pesCols['owner_rating'])  $row['owner_rating'] = $ownerRatings[$i % count($ownerRatings)];
            if ($pesCols['worker_rating']) $row['worker_rating'] = $workerRatings[$i % count($workerRatings)];

            if ($pesCols['submitted_at'])  $row['submitted_at'] = $submittedAt->format('Y-m-d H:i:s');
            if ($pesCols['reviewed_at'])   $row['reviewed_at']  = $reviewedAt->format('Y-m-d H:i:s');
            if ($pesCols['reviewed_by'])   $row['reviewed_by']  = (int)$reviewedByUserId;

            if ($pesCols['review_notes'])  $row['review_notes'] = null;

            if ($pesCols['created_at'])    $row['created_at'] = $submittedAt->format('Y-m-d H:i:s');
            if ($pesCols['updated_at'])    $row['updated_at'] = $reviewedAt->format('Y-m-d H:i:s');

            $rows[] = $row;
            $i++;
        }

        if (empty($rows)) {
            $this->command?->warn("PostEventReportsSeeder: No rows built (likely missing reviewed_by mapping). Skipping insert.");
            return;
        }

        DB::table('post_event_submissions')->insert($rows);

        $this->command?->info(
            "Inserted " . count($rows) . " post_event_submissions rows for employees: " . implode(',', $createdByEmployeeIds)
        );
    }

    private function toSlug(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = preg_replace('/[^a-z0-9]+/i', '_', $name);
        return trim($name, '_') ?: 'role';
    }

    private function buildDataPayload(string $roleSlug): array
    {
        switch ($roleSlug) {
            case 'cooking_team':
                return ['meals' => "Rice plates: 180\nWater: 200", 'notes' => "Served on time."];
            case 'civil_defense':
                return ['cases' => "Minor incident handled.", 'notes' => "Safety checks completed."];
            case 'media_staff':
                return ['deliverables' => "Photos: 120\nClips: 6", 'notes' => "Captured key moments."];
            case 'tech_support':
                return ['setup' => "Audio tested, mic levels adjusted.", 'notes' => "Resolved minor issue."];
            case 'cleaner':
                return ['areas' => "Hall + entrance + restrooms", 'notes' => "Cleanup completed."];
            case 'decorator':
                return ['setup' => "Stage decor + tables", 'notes' => "Matched theme."];
            case 'security':
                return ['incidents' => "No major incidents.", 'notes' => "Managed entrance flow."];
            case 'organizer':
                return ['coordination' => "Managed timeline and tasks.", 'notes' => "Smooth operation."];
            default:
                return ['summary' => "Tasks completed.", 'notes' => "No issues."];
        }
    }
}
