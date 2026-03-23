<?php

namespace Modules\Aichat\Utils;

class ChatModelSerializer
{
    protected ChatSensitiveDataRedactor $redactor;

    public function __construct(ChatSensitiveDataRedactor $redactor)
    {
        $this->redactor = $redactor;
    }

    public function serialize(string $entity, array $payload): array
    {
        $allowlist = array_values(array_filter((array) config('aichat.security.serializer.allowlists.' . $entity, []), function ($field) {
            return is_string($field) && trim($field) !== '';
        }));

        $strictAllowlist = (bool) config('aichat.security.serializer.strict_allowlist', true);
        $result = [];

        if (empty($allowlist)) {
            $result = $strictAllowlist ? [] : $payload;
        } else {
            foreach ($allowlist as $field) {
                if (array_key_exists($field, $payload)) {
                    $result[$field] = $payload[$field];
                }
            }
        }

        return $this->redactor->redactArray($result);
    }

    public function serializeCollection(string $entity, iterable $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = $this->serialize($entity, is_array($row) ? $row : (array) $row);
        }

        return $result;
    }

    public function redactArray(array $payload): array
    {
        return $this->redactor->redactArray($payload);
    }
}

