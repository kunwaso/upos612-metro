<?php

namespace App\Utils;

use App\Contact;
use App\ContactSupplierProduct;
use App\Product;
use Illuminate\Support\Facades\DB;

class ContactSupplierProductUtil extends Util
{
    /**
     * Ensure contact belongs to business and is supplier-capable.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function assertSupplierContact(int $business_id, int $contact_id): Contact
    {
        $contact = Contact::where('business_id', $business_id)
            ->whereKey($contact_id)
            ->firstOrFail();

        if (! in_array((string) $contact->type, ['supplier', 'both'], true)) {
            throw new \InvalidArgumentException(__('lang_v1.supplier_products_invalid_supplier_contact'));
        }

        return $contact;
    }

    /**
     * Build datatable query for supplier-contact products.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForDatatable(int $business_id, int $contact_id)
    {
        $this->assertSupplierContact($business_id, $contact_id);

        return DB::table('contact_supplier_products as csp')
            ->join('products as p', 'p.id', '=', 'csp.product_id')
            ->where('csp.business_id', $business_id)
            ->where('csp.contact_id', $contact_id)
            ->where('p.business_id', $business_id)
            ->where('p.type', '!=', 'modifier')
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'csp.created_at',
            ]);
    }

    /**
     * Attach products to a supplier contact, ignoring invalid/duplicate entries.
     *
     * @return array<string, int>
     */
    public function attachProducts(int $business_id, int $contact_id, array $product_ids): array
    {
        $this->assertSupplierContact($business_id, $contact_id);

        $total_requested = count($product_ids);
        $normalized_product_ids = $this->normalizePositiveIntegerIds($product_ids);

        if (empty($normalized_product_ids)) {
            return [
                'attached_count' => 0,
                'ignored_count' => $total_requested,
                'total_requested' => $total_requested,
            ];
        }

        $valid_product_ids = Product::query()
            ->where('business_id', $business_id)
            ->where('type', '!=', 'modifier')
            ->whereIn('id', $normalized_product_ids)
            ->pluck('id')
            ->map(static function ($id) {
                return (int) $id;
            })
            ->all();

        $attached_count = 0;
        foreach ($valid_product_ids as $product_id) {
            $pivot = ContactSupplierProduct::firstOrCreate([
                'business_id' => $business_id,
                'contact_id' => $contact_id,
                'product_id' => $product_id,
            ]);

            if ($pivot->wasRecentlyCreated) {
                $attached_count++;
            }
        }

        return [
            'attached_count' => $attached_count,
            'ignored_count' => max(0, $total_requested - $attached_count),
            'total_requested' => $total_requested,
        ];
    }

    /**
     * Detach a product from a supplier contact within tenant scope.
     */
    public function detachProduct(int $business_id, int $contact_id, int $product_id): int
    {
        $this->assertSupplierContact($business_id, $contact_id);

        return ContactSupplierProduct::query()
            ->where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('product_id', $product_id)
            ->delete();
    }

    /**
     * @param  array<int, mixed>  $product_ids
     * @return array<int, int>
     */
    protected function normalizePositiveIntegerIds(array $product_ids): array
    {
        $normalized = [];

        foreach ($product_ids as $product_id) {
            $validated_id = filter_var(
                $product_id,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($validated_id === false) {
                continue;
            }

            $normalized[] = (int) $validated_id;
        }

        return array_values(array_unique($normalized));
    }
}
