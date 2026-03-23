<?php

namespace Modules\Aichat\Utils;

use Modules\Aichat\Entities\ChatAuditLog;

class ChatAuditUtil
{
    protected ChatSensitiveDataRedactor $redactor;

    public function __construct(?ChatSensitiveDataRedactor $redactor = null)
    {
        $this->redactor = $redactor ?: app(ChatSensitiveDataRedactor::class);
    }

    public function log(
        int $business_id,
        ?int $user_id,
        string $action,
        ?string $conversation_id = null,
        ?string $provider = null,
        ?string $model = null,
        array $metadata = []
    ): ChatAuditLog {
        $safeMetadata = $this->redactor->redactArray($metadata);

        return ChatAuditLog::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'conversation_id' => $conversation_id,
            'action' => $action,
            'provider' => $provider,
            'model' => $model,
            'metadata' => $safeMetadata,
        ]);
    }
}


