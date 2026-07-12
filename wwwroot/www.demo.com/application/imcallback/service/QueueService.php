<?php

namespace app\imcallback\service;

class QueueService
{
    public function enqueue($eventId)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 1.0);
        return (bool) $redis->rPush(config('im_callback.queue_key'), (string) $eventId);
    }

    public function pop()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 1.0);
        $value = $redis->lPop(config('im_callback.queue_key'));
        return $value === false ? 0 : (int) $value;
    }
}
