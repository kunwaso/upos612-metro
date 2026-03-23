<?php

namespace Modules\Aichat\Utils;

use App\User;

class ChatAuthorizationPolicy
{
    public function resolveActorForBusiness(int $business_id, ?int $user_id = null, ?User $actor = null): ?User
    {
        if ($actor && (int) $actor->id > 0 && (int) $actor->business_id === $business_id) {
            return $actor;
        }

        if ($user_id !== null && $user_id > 0) {
            return User::where('business_id', $business_id)
                ->where('id', $user_id)
                ->first();
        }

        if (auth()->check()) {
            $authUser = auth()->user();
            if ((int) ($authUser->business_id ?? 0) === $business_id) {
                return $authUser;
            }
        }

        return null;
    }

    public function domainsFromEnvelope(array $capabilityEnvelope): array
    {
        if (isset($capabilityEnvelope['domains']) && is_array($capabilityEnvelope['domains'])) {
            return (array) $capabilityEnvelope['domains'];
        }

        return $capabilityEnvelope;
    }

    public function applyContactVisibilityScope($query, ?int $user_id, bool $canView, bool $canViewOwn): void
    {
        if (! $canView && ! $canViewOwn) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (! $canView && $canViewOwn) {
            if ($user_id === null || $user_id <= 0) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->leftJoin('user_contact_access as ucas', 'contacts.id', '=', 'ucas.contact_id')
                ->where(function ($subQuery) use ($user_id) {
                    $subQuery->where('contacts.created_by', $user_id)
                        ->orWhere('ucas.user_id', $user_id);
                })
                ->distinct();
        }
    }

    public function applyTransactionVisibilityScope($query, ?int $user_id, bool $canView, bool $canViewOwn): void
    {
        if (! $canView && ! $canViewOwn) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (! $canView && $canViewOwn) {
            if ($user_id === null || $user_id <= 0) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('created_by', $user_id);
        }
    }

    public function applyPermittedLocationScope($query, ?User $actor): void
    {
        if (! $actor || ! method_exists($actor, 'permitted_locations')) {
            return;
        }

        $permittedLocations = $actor->permitted_locations();
        if ($permittedLocations !== 'all') {
            $query->whereIn('id', (array) $permittedLocations);
        }
    }

    public function isDomainOperationAllowed(array $domains, string $domain, string $operation = 'view'): bool
    {
        $value = data_get($domains, $domain . '.' . $operation);
        if (is_bool($value)) {
            return $value;
        }

        return false;
    }

    public function assertChatEditCapability(array $domains): void
    {
        if (! (bool) data_get($domains, 'chat.edit', false)) {
            throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
        }
    }
}

