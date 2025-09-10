<?php
namespace App\Services;

use Pusher\Pusher;

class PusherService
{
    protected Pusher $pusher;

    public function __construct()
    {
        $options = [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS'  => true,
        ];

        $this->pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );
    }

    public function trigger(string $channel, string $event, array $data)
    {
        $this->pusher->trigger($channel, $event, $data);
    }
}
