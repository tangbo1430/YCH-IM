<?php

namespace app\imcallback\service;

use think\Db;
use think\facade\Log;

class BeforePolicyService
{
    public function evaluate(array $payload)
    {
        try {
            $command = isset($payload['CallbackCommand']) ? $payload['CallbackCommand'] : '';
            $from = Payload::first($payload, ['From_Account', 'Operator_Account']);
            $to = Payload::first($payload, ['To_Account']);

            if (in_array($command, ['C2C.CallbackBeforeSendMsg', 'Group.CallbackBeforeSendMsg'], true)) {
                if ($from !== '') {
                    $loginStatus = Db::name('user')->where('uid', $from)->value('login_status');
                    if ($loginStatus !== null && (int) $loginStatus !== 1) {
                        return $this->reject('您已被平台封禁，不能发送消息');
                    }
                }
            }

            if ($command === 'C2C.CallbackBeforeSendMsg' && $from !== '' && $to !== '') {
                $blocked = Db::name('user_black')
                    ->where(['uid' => $to, 'receive_uid' => $from])
                    ->value('id');
                if ($blocked) {
                    return $this->reject('您已被对方拉黑，不能发送消息');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[IM callback] before policy fail-open: ' . $e->getMessage());
        }

        return $this->allow();
    }

    private function allow()
    {
        return ['ActionStatus' => 'OK', 'ErrorInfo' => '', 'ErrorCode' => 0];
    }

    private function reject($message)
    {
        return ['ActionStatus' => 'OK', 'ErrorInfo' => $message, 'ErrorCode' => 120001];
    }
}
