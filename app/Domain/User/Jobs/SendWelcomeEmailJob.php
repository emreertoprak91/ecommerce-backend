<?php

declare(strict_types=1);

namespace App\Domain\User\Jobs;

use App\Domain\User\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Welcome Email Job
 *
 * This job will be processed by Horizon and visible in the dashboard.
 */
final class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('syslog')->info('[SendWelcomeEmailJob] Sending welcome email', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'job_id' => $this->job?->getJobId(),
        ]);

        Mail::to($this->user->email)->send(new WelcomeEmail($this->user));

        Log::channel('syslog')->info('[SendWelcomeEmailJob] Welcome email sent successfully', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('syslog')->error('[SendWelcomeEmailJob] Failed to send welcome email', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
