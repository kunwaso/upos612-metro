<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\VasAccounting\Entities\VasInventoryDocument;

class DestroyInventoryDocumentRequest extends FormRequest
{
    protected ?VasInventoryDocument $resolvedInventoryDocument = null;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.inventory.destroy_draft');
    }

    public function rules(): array
    {
        return [];
    }

    public function inventoryDocument(): VasInventoryDocument
    {
        if ($this->resolvedInventoryDocument instanceof VasInventoryDocument) {
            return $this->resolvedInventoryDocument;
        }

        $businessId = (int) $this->session()->get('user.business_id');

        return $this->resolvedInventoryDocument = VasInventoryDocument::query()
            ->where('business_id', $businessId)
            ->findOrFail((int) $this->route('document'));
    }
}
