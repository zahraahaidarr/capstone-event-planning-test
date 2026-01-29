<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $now = now();

            // -----------------------------
            // NO hardcoded IDs (Render-safe)
            // -----------------------------
            $adminId = DB::table('users')->where('role', 'ADMIN')->value('id');

            $workerIds = DB::table('users')
                ->where('role', 'WORKER')
                ->orderBy('id')
                ->limit(8)
                ->pluck('id')
                ->toArray();

            // Some projects use CLIENT, others use EMPLOYEE (clients)
            $clientIds = DB::table('users')
                ->whereIn('role', ['CLIENT', 'EMPLOYEE'])
                ->orderBy('id')
                ->limit(2)
                ->pluck('id')
                ->toArray();

            if (!$adminId) {
                $this->command?->warn("NotificationsSeeder: No ADMIN user found. Skipping.");
                return;
            }

            if (empty($workerIds) && empty($clientIds)) {
                $this->command?->warn("NotificationsSeeder: No WORKER/CLIENT users found. Skipping.");
                return;
            }

            // Make sure we can access specific indexes safely
            $w = array_values($workerIds);
            $c = array_values($clientIds);

            $pickWorker = fn(int $i) => $w[$i] ?? ($w[0] ?? null);
            $pickClient = fn(int $i) => $c[$i] ?? ($c[0] ?? null);

            $rows = [];

            /* 1) Workers notified when account status changes (Approved / Suspended) */
            if ($pickWorker(0)) $rows[] = ['user_id' => $pickWorker(0), 'title' => 'Account status updated', 'message' => 'Your account status was changed to APPROVED.', 'type' => 'ACCOUNT'];
            if ($pickWorker(1)) $rows[] = ['user_id' => $pickWorker(1), 'title' => 'Account status updated', 'message' => 'Your account status was changed to APPROVED.', 'type' => 'ACCOUNT'];
            if ($pickWorker(2)) $rows[] = ['user_id' => $pickWorker(2), 'title' => 'Account status updated', 'message' => 'Your account status was changed to SUSPENDED.', 'type' => 'ACCOUNT'];
            if ($pickWorker(3)) $rows[] = ['user_id' => $pickWorker(3), 'title' => 'Account status updated', 'message' => 'Your account status was changed to SUSPENDED.', 'type' => 'ACCOUNT'];

            /* 2) Users notified when sensitive credentials updated (password/email) */
            if ($pickClient(0)) $rows[] = ['user_id' => $pickClient(0), 'title' => 'Security update: Email changed', 'message' => 'Your email address was updated successfully. If you did not request this, please contact support.', 'type' => 'ACCOUNT'];
            if ($pickClient(1)) $rows[] = ['user_id' => $pickClient(1), 'title' => 'Security update: Password changed', 'message' => 'Your password was changed successfully. If you did not request this, please contact support.', 'type' => 'ACCOUNT'];

            /* 3) Workers & Clients notified when application/reservation submitted */
            if ($pickWorker(4)) $rows[] = ['user_id' => $pickWorker(4), 'title' => 'Reservation submitted', 'message' => 'Your reservation request was submitted successfully.', 'type' => 'RESERVATION'];
            if ($pickClient(0)) $rows[] = ['user_id' => $pickClient(0), 'title' => 'New reservation submitted', 'message' => 'A worker submitted a reservation for your event. Please review it.', 'type' => 'RESERVATION'];

            if ($pickWorker(5)) $rows[] = ['user_id' => $pickWorker(5), 'title' => 'Application submitted', 'message' => 'Your application was submitted successfully.', 'type' => 'RESERVATION'];
            if ($pickClient(1)) $rows[] = ['user_id' => $pickClient(1), 'title' => 'New application received', 'message' => 'A worker applied to your event. Please review the application.', 'type' => 'RESERVATION'];

            /* 4) Assigned workers notified when event edited/updated */
            if ($pickWorker(6)) $rows[] = ['user_id' => $pickWorker(6), 'title' => 'Event updated', 'message' => 'An event you are assigned to was updated. Please review the latest details.', 'type' => 'EVENT'];
            if ($pickWorker(7)) $rows[] = ['user_id' => $pickWorker(7), 'title' => 'Event updated', 'message' => 'An event you are assigned to was updated. Please review the latest details.', 'type' => 'EVENT'];

            /* 5) Assigned workers notified when event status changes */
            if ($pickWorker(0)) $rows[] = ['user_id' => $pickWorker(0), 'title' => 'Event status changed', 'message' => 'Event status has changed. Please check your dashboard for updates.', 'type' => 'EVENT'];
            if ($pickWorker(1)) $rows[] = ['user_id' => $pickWorker(1), 'title' => 'Event status changed', 'message' => 'Event status has changed. Please check your dashboard for updates.', 'type' => 'EVENT'];

            /* 6) Target users notified when announcement published */
            foreach (array_merge($workerIds, $clientIds) as $uid) {
                $rows[] = [
                    'user_id'  => $uid,
                    'title'    => 'New announcement published',
                    'message'  => 'A new announcement has been published. Please open your dashboard to read it.',
                    'type'     => 'ANNOUNCEMENT',
                ];
            }

            /* 7) Admin notified when user closes/deletes account */
            $rows[] = [
                'user_id'  => $adminId,
                'title'    => 'User account closed',
                'message'  => 'A user has closed or deleted their account. Please review the record.',
                'type'     => 'ACCOUNT',
            ];

            /* 8) Credited hours calculated for completed reservations */
            if ($pickWorker(0)) $rows[] = ['user_id' => $pickWorker(0), 'title' => 'Credited hours updated', 'message' => 'Your credited hours were calculated for a completed reservation.', 'type' => 'RESERVATION'];
            if ($pickWorker(1)) $rows[] = ['user_id' => $pickWorker(1), 'title' => 'Credited hours updated', 'message' => 'Your credited hours were calculated for a completed reservation.', 'type' => 'RESERVATION'];

            /* 9) Post-event report submission rules enforced */
            if ($pickWorker(4)) $rows[] = ['user_id' => $pickWorker(4), 'title' => 'Post-event report required', 'message' => 'You must submit your post-event report within the allowed time window.', 'type' => 'REPORT'];
            if ($pickWorker(5)) $rows[] = ['user_id' => $pickWorker(5), 'title' => 'Post-event report reminder', 'message' => 'Reminder: Your post-event report is still pending. Please submit it before the deadline.', 'type' => 'REPORT'];

            // ✅ Insert but avoid duplicates (same user_id + title + message + type)
            foreach ($rows as $r) {
                if (empty($r['user_id'])) continue;

                $exists = DB::table('notifications')
                    ->where('user_id', $r['user_id'])
                    ->where('title', $r['title'])
                    ->where('message', $r['message'])
                    ->where('type', $r['type'])
                    ->exists();

                if ($exists) continue;

                DB::table('notifications')->insert([
                    'user_id'    => $r['user_id'],
                    'title'      => $r['title'],
                    'message'    => $r['message'],
                    'type'       => $r['type'],
                    'is_read'    => 0,
                    'created_at' => $now,
                ]);
            }

            $this->command?->info("NotificationsSeeder: Inserted notifications (deduped).");
        });
    }
}
