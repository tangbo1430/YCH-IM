<?php

return [
    'token' => getenv('IM_CALLBACK_TOKEN') ?: env('im_callback.token', ''),
    'sdk_app_id' => getenv('IM_SDK_APP_ID') ?: env('im_callback.sdk_app_id', ''),
    'retention_days' => 30,
    'queue_key' => getenv('IM_CALLBACK_QUEUE_KEY') ?: 'im:callback:queue',
    'max_retries' => (int) (getenv('IM_CALLBACK_MAX_RETRIES') ?: 5),
    'admin_ids' => getenv('IM_CALLBACK_ADMIN_IDS') ?: '',
];
