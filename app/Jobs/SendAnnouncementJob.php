<?php

namespace App\Jobs;

use App\Mail\StaffBroadcastMail;
use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Announcement $announcement)
    {
    }

    public function handle(): void
    {
        $announcement = $this->announcement;
        $announcement->update(['status' => 'sending']);

        $sent = 0;
        $failed = 0;
        $lastError = null;

        foreach ($announcement->recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            if (! $email) {
                continue;
            }

            try {
                Mail::to($email)->send(
                    new StaffBroadcastMail(
                        $announcement->subject,
                        $announcement->body,
                        $recipient['name'] ?? 'there',
                    )
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $lastError = $e->getMessage();
                report($e);
            }
        }

        $status = match (true) {
            $sent === 0            => 'failed',
            $failed > 0            => 'partial',
            default                => 'sent',
        };

        $announcement->update([
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'status'       => $status,
            'error'        => $lastError,
            'sent_at'      => now(),
        ]);
    }

    /**
     * Mark the announcement failed if the job itself blows up (e.g. retries
     * exhausted) so the history never shows a permanent "sending" state.
     */
    public function failed(\Throwable $e): void
    {
        $this->announcement->update([
            'status' => 'failed',
            'error'  => $e->getMessage(),
        ]);
    }
}
