<?php

namespace Modules\ProjectX\Utils;

use Modules\ProjectX\Entities\ChatAuditLog;

class ChatAuditUtil
{
    public function log(
        int $business_id,
        ?int $user_id,
        string $action,
        ?string $conversation_id = null,
        ?string $provider = null,
        ?string $model = null,
        array $metadata = []
    ): ChatAuditLog {
        return ChatAuditLog::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'conversation_id' => $conversation_id,
            'action' => $action,
            'provider' => $provider,
            'model' => $model,
            'metadata' => $metadata,
        ]);
    }
}

