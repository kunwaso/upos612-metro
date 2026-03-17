<?php

namespace App\Utils;

use App\Contact;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GlobalSearchUtil
{
    protected ProductUtil $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    public function searchContacts(User $user, int $businessId, string $term, string $type = 'both'): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        if ($type === 'both') {
            $canSearchCustomers = $this->canSearchCustomers($user);
            $canSearchSuppliers = $this->canSearchSuppliers($user);

            if (! $canSearchCustomers && ! $canSearchSuppliers) {
                $this->abortUnauthorized();
            }

            $results = collect();
            if ($canSearchCustomers) {
                $results = $results->merge($this->searchContactsByType($businessId, $term, 'customer'));
            }
            if ($canSearchSuppliers) {
                $results = $results->merge($this->searchContactsByType($businessId, $term, 'supplier'));
            }

            return $results
                ->unique('id')
                ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
                ->take(20)
                ->values()
                ->all();
        }

        if ($type === 'customer' && ! $this->canSearchCustomers($user)) {
            $this->abortUnauthorized();
        }

        if ($type === 'supplier' && ! $this->canSearchSuppliers($user)) {
            $this->abortUnauthorized();
        }

        return $this->searchContactsByType($businessId, $term, $type)
            ->take(20)
            ->values()
            ->all();
    }

    public function searchProducts(User $user, int $businessId, string $term): array
    {
        if (! $user->can('product.view')) {
            $this->abortUnauthorized();
        }

        $term = trim($term);
        if ($term === '') {
            return [];
        }

        return collect($this->productUtil->filterProduct($businessId, $term))
            ->take(20)
            ->map(function ($row) {
                $variationName = trim((string) ($row->variation ?? ''));
                $isDummyVariation = strcasecmp($variationName, 'DUMMY') === 0;

                $text = (string) ($row->name ?? '');
                if (! $isDummyVariation && $variationName !== '') {
                    $text .= ' (' . $variationName . ')';
                }
                if (! empty($row->sub_sku)) {
                    $text .= ' [' . $row->sub_sku . ']';
                }

                $subtitleParts = [];
                if (! empty($row->sub_sku)) {
                    $subtitleParts[] = 'SKU: ' . $row->sub_sku;
                }
                if (! empty($row->unit)) {
                    $subtitleParts[] = 'Unit: ' . $row->unit;
                }

                return [
                    'id' => (int) ($row->variation_id ?? $row->product_id ?? 0),
                    'text' => $text,
                    'subtitle' => implode(' | ', $subtitleParts),
                    'url' => route('product.detail', ['id' => (int) $row->product_id]),
                    'type' => 'products',
                    'meta' => [
                        'entity_id' => (int) $row->product_id,
                        'variation_id' => (int) ($row->variation_id ?? 0),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function searchSalesOrders(User $user, int $businessId, string $term): array
    {
        if (! $user->can('sell.view') && ! $user->can('direct_sell.view')) {
            $this->abortUnauthorized();
        }

        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $query = Transaction::query()
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', 'sell')
            ->join('product_quotes as pq', function ($join) use ($businessId) {
                $join->on('pq.transaction_id', '=', 'transactions.id')
                    ->where('pq.business_id', '=', $businessId);
            })
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->select([
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'contacts.name as contact_name',
                'contacts.supplier_business_name',
                'pq.quote_number',
            ])
            ->where(function ($query) use ($term) {
                $query->where('transactions.invoice_no', 'like', '%' . $term . '%')
                    ->orWhere('pq.quote_number', 'like', '%' . $term . '%')
                    ->orWhere('contacts.name', 'like', '%' . $term . '%')
                    ->orWhere('contacts.supplier_business_name', 'like', '%' . $term . '%');
            });

        $permittedLocations = $user->permitted_locations($businessId);
        if ($permittedLocations !== 'all') {
            $query->whereIn('transactions.location_id', $permittedLocations);
        }

        return $query
            ->orderByDesc('transactions.transaction_date')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $contactName = $this->resolveContactName($row->contact_name ?? null, $row->supplier_business_name ?? null);
                $subtitleParts = array_filter([
                    $contactName,
                    $this->formatDate($row->transaction_date ?? null),
                    ! empty($row->quote_number) ? 'Quote: ' . $row->quote_number : null,
                ]);

                return [
                    'id' => (int) $row->id,
                    'text' => (string) ($row->invoice_no ?: ('Sales Order #' . $row->id)),
                    'subtitle' => implode(' | ', $subtitleParts),
                    'url' => route('product.sales.orders.show', ['id' => (int) $row->id]),
                    'type' => 'sales_orders',
                    'meta' => [
                        'entity_id' => (int) $row->id,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function searchPurchases(User $user, int $businessId, string $term): array
    {
        if (! $user->can('purchase.update')) {
            $this->abortUnauthorized();
        }

        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $query = Transaction::query()
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', 'purchase')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->select([
                'transactions.id',
                'transactions.ref_no',
                'transactions.transaction_date',
                'transactions.created_by',
                'contacts.name as contact_name',
                'contacts.supplier_business_name',
                'contacts.contact_id',
            ])
            ->where(function ($query) use ($term) {
                $query->where('transactions.ref_no', 'like', '%' . $term . '%')
                    ->orWhere('contacts.name', 'like', '%' . $term . '%')
                    ->orWhere('contacts.supplier_business_name', 'like', '%' . $term . '%')
                    ->orWhere('contacts.contact_id', 'like', '%' . $term . '%');
            });

        $permittedLocations = $user->permitted_locations($businessId);
        if ($permittedLocations !== 'all') {
            $query->whereIn('transactions.location_id', $permittedLocations);
        }

        if (! $user->can('purchase.view') && $user->can('view_own_purchase')) {
            $query->where('transactions.created_by', $user->id);
        }

        return $query
            ->orderByDesc('transactions.transaction_date')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $supplierName = $this->resolveContactName($row->contact_name ?? null, $row->supplier_business_name ?? null);
                $subtitleParts = array_filter([
                    $supplierName,
                    $this->formatDate($row->transaction_date ?? null),
                ]);

                return [
                    'id' => (int) $row->id,
                    'text' => (string) ($row->ref_no ?: ('Purchase #' . $row->id)),
                    'subtitle' => implode(' | ', $subtitleParts),
                    'url' => route('purchases.edit', ['purchase' => (int) $row->id]),
                    'type' => 'purchases',
                    'meta' => [
                        'entity_id' => (int) $row->id,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    protected function searchContactsByType(int $businessId, string $term, string $type): Collection
    {
        $query = Contact::query()
            ->where('contacts.business_id', $businessId)
            ->active()
            ->select([
                'contacts.id',
                'contacts.name',
                'contacts.type',
                'contacts.contact_id',
                'contacts.supplier_business_name',
                'contacts.mobile',
                'contacts.email',
            ])
            ->where(function ($query) use ($term) {
                $query->where('contacts.name', 'like', '%' . $term . '%')
                    ->orWhere('contacts.supplier_business_name', 'like', '%' . $term . '%')
                    ->orWhere('contacts.mobile', 'like', '%' . $term . '%')
                    ->orWhere('contacts.contact_id', 'like', '%' . $term . '%')
                    ->orWhere('contacts.email', 'like', '%' . $term . '%');
            });

        if ($type === 'customer') {
            $query->onlyCustomers();
        } else {
            $query->onlySuppliers();
        }

        return $query
            ->orderBy('contacts.name')
            ->limit(20)
            ->get()
            ->unique('id')
            ->map(function ($contact) use ($type) {
                return [
                    'id' => (int) $contact->id,
                    'text' => $this->formatContactText($contact, $type),
                    'subtitle' => $this->formatContactSubtitle($contact),
                    'url' => route('contacts.show', ['contact' => (int) $contact->id]),
                    'type' => $this->resolveContactResultType($contact->type ?? null, $type),
                    'meta' => [
                        'entity_id' => (int) $contact->id,
                    ],
                ];
            });
    }

    protected function canSearchCustomers(User $user): bool
    {
        return $user->can('customer.view') || $user->can('customer.view_own');
    }

    protected function canSearchSuppliers(User $user): bool
    {
        return $user->can('supplier.view') || $user->can('supplier.view_own');
    }

    protected function formatContactText(Contact $contact, string $requestedType): string
    {
        $primaryName = trim((string) $contact->name);
        $businessName = trim((string) ($contact->supplier_business_name ?? ''));

        if ($requestedType === 'supplier' && $businessName !== '') {
            $primaryName = $businessName . ($primaryName !== '' ? ' - ' . $primaryName : '');
        } elseif ($primaryName === '' && $businessName !== '') {
            $primaryName = $businessName;
        }

        if (! empty($contact->contact_id)) {
            $primaryName .= ' (' . $contact->contact_id . ')';
        }

        return $primaryName;
    }

    protected function formatContactSubtitle(Contact $contact): string
    {
        $subtitleParts = [];
        if (! empty($contact->mobile)) {
            $subtitleParts[] = $contact->mobile;
        }
        if (! empty($contact->email)) {
            $subtitleParts[] = $contact->email;
        }

        return implode(' | ', $subtitleParts);
    }

    protected function resolveContactResultType(?string $contactType, string $requestedType): string
    {
        if ($requestedType === 'customer') {
            return 'customers';
        }

        if ($requestedType === 'supplier') {
            return 'suppliers';
        }

        if ($contactType === 'customer') {
            return 'customers';
        }

        if ($contactType === 'supplier') {
            return 'suppliers';
        }

        return 'contacts';
    }

    protected function resolveContactName(?string $name, ?string $businessName): string
    {
        $name = trim((string) $name);
        $businessName = trim((string) $businessName);

        if ($businessName !== '' && $name !== '') {
            return $businessName . ' - ' . $name;
        }

        return $businessName !== '' ? $businessName : $name;
    }

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format('M d, Y');
    }

    protected function abortUnauthorized(): void
    {
        abort(403, __('messages.unauthorized_action'));
    }
}
