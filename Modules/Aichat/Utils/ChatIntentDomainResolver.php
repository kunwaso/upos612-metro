<?php

namespace Modules\Aichat\Utils;

use Illuminate\Support\Str;

class ChatIntentDomainResolver
{
    public function resolveRequestedDomains(string $prompt): array
    {
        $normalizedPrompt = Str::lower(trim($prompt));
        if ($normalizedPrompt === '') {
            return [];
        }

        $domainMap = (array) config('aichat.chat.domain_intent_map', []);
        $requestedDomains = [];

        foreach ($domainMap as $domain => $keywords) {
            $domainKey = trim((string) $domain);
            if ($domainKey === '') {
                continue;
            }

            foreach ((array) $keywords as $keyword) {
                $normalizedKeyword = Str::lower(trim((string) $keyword));
                if ($normalizedKeyword === '') {
                    continue;
                }

                if (Str::contains($normalizedPrompt, $normalizedKeyword)) {
                    $requestedDomains[] = $domainKey;
                    break;
                }
            }
        }

        return array_values(array_unique($requestedDomains));
    }
}

