<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationsMarkedRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $userId)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notifications.marked_read';
    }

    public function broadcastWith(): array
    {
        return ['all' => true];
    }
}
