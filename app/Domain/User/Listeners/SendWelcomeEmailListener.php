<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\UserRegisteredEvent;
use App\Domain\User\Jobs\SendWelcomeEmailJob;
use Illuminate\Support\Facades\Log;

final class SendWelcomeEmailListener
{
    public function handle(UserRegisteredEvent $event): void
    {
        Log::channel('syslog')->info('[SendWelcomeEmailListener] Dispatching welcome email job', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Dispatch job to queue (will appear in Horizon!)
        SendWelcomeEmailJob::dispatch($event->user);
    }
}
