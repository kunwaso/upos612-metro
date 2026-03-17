<?php

namespace Modules\ProjectX\Utils;

use App\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\ProjectX\Entities\QuoteLine;
use Modules\ProjectX\Entities\Trim;
use Modules\ProjectX\Entities\TrimCategory;
use Modules\ProjectX\Entities\TrimShareView;

class TrimManagerUtil
{
    public function getCategoriesForBusiness(int $business_id)
    {
        return TrimCategory::forBusiness($business_id)
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(int $business_id, array $data): TrimCategory
    {
        return TrimCategory::create([
            'business_id' => $business_id,
            'name' => trim((string) ($data['name'] ?? '')),
        ])->fresh();
    }

    public function deleteCategory(int $business_id, int $category_id): int
    {
        $category = TrimCategory::forBusiness($business_id)->findOrFail($category_id);

        $affectedTrimCount = Trim::forBusiness($business_id)
            ->where('trim_category_id', $category->id)
            ->count();

        $category->delete();

        return (int) $affectedTrimCount;
    }

    public function getTrims(int $business_id, ?string $status_filter = 'all', $category_filter = null)
    {
        $query = Trim::forBusiness($business_id)
            ->with([
                'trimCategory:id,name,category_group',
                'supplier:id,name,supplier_business_name',
            ])
            ->orderBy('created_at', 'desc');

        if (! empty($status_filter) && in_array($status_filter, Trim::STATUSES, true)) {
            $query->where('status', $status_filter);
        }

        if (! empty($category_filter)) {
            $query->where('trim_category_id', (int) $category_filter);
        }

        return $query->get();
    }

    public function getStatusCounts(int $business_id): array
    {
        $counts = Trim::forBusiness($business_id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            Trim::STATUS_DRAFT => (int) ($counts[Trim::STATUS_DRAFT] ?? 0),
            Trim::STATUS_SAMPLE_REQUESTED => (int) ($counts[Trim::STATUS_SAMPLE_REQUESTED] ?? 0),
            Trim::STATUS_SAMPLE_RECEIVED => (int) ($counts[Trim::STATUS_SAMPLE_RECEIVED] ?? 0),
            Trim::STATUS_APPROVED => (int) ($counts[Trim::STATUS_APPROVED] ?? 0),
            Trim::STATUS_BULK_ORDERED => (int) ($counts[Trim::STATUS_BULK_ORDERED] ?? 0),
            Trim::STATUS_BULK_RECEIVED => (int) ($counts[Trim::STATUS_BULK_RECEIVED] ?? 0),
            Trim::STATUS_QC_PASSED => (int) ($counts[Trim::STATUS_QC_PASSED] ?? 0),
            Trim::STATUS_QC_FAILED => (int) ($counts[Trim::STATUS_QC_FAILED] ?? 0),
            'total' => array_sum($counts),
        ];
    }

    public function createTrim(int $business_id, array $data): Trim
    {
        $data['business_id'] = $business_id;
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['supplier_contact_id'] = $this->resolveSupplierContactId($business_id, $data['supplier_contact_id'] ?? null);

        $status = (string) ($data['status'] ?? Trim::STATUS_DRAFT);
        if ($status === Trim::STATUS_APPROVED && empty($data['approved_at'])) {
            $data['approved_at'] = now();
        }
        if (in_array($status, [Trim::STATUS_QC_PASSED, Trim::STATUS_QC_FAILED], true) && empty($data['qc_at'])) {
            $data['qc_at'] = now();
        }

        return Trim::create($data)->fresh([
            'trimCategory:id,name,category_group',
            'supplier:id,name,supplier_business_name',
            'createdBy:id,surname,first_name,last_name',
        ]);
    }

    public function updateTrim(int $business_id, int $trim_id, array $data): Trim
    {
        $trim = $this->getTrimForBusiness($business_id, $trim_id);
        $data['supplier_contact_id'] = $this->resolveSupplierContactId($business_id, $data['supplier_contact_id'] ?? null);

        $status = (string) ($data['status'] ?? $trim->status);
        if ($status === Trim::STATUS_APPROVED && empty($trim->approved_at) && empty($data['approved_at'])) {
            $data['approved_at'] = now();
        }
        if (
            in_array($status, [Trim::STATUS_QC_PASSED, Trim::STATUS_QC_FAILED], true)
            && empty($trim->qc_at)
            && empty($data['qc_at'])
        ) {
            $data['qc_at'] = now();
        }

        $trim->fill($data);
        $trim->save();

        return $trim->fresh([
            'trimCategory:id,name,category_group',
            'supplier:id,name,supplier_business_name',
            'createdBy:id,surname,first_name,last_name',
        ]);
    }

    public function getTrimForBusiness(int $business_id, int $trim_id): Trim
    {
        return Trim::forBusiness($business_id)
            ->with([
                'trimCategory:id,name,category_group',
                'supplier:id,name,supplier_business_name',
                'createdBy:id,surname,first_name,last_name',
            ])
            ->findOrFail($trim_id);
    }

    public function getContextForChat(int $business_id, int $trim_id): string
    {
        $trim = Trim::forBusiness($business_id)
            ->with([
                'trimCategory:id,name,category_group',
                'supplier:id,name,supplier_business_name,email,contact_id',
            ])
            ->findOrFail($trim_id);

        $supplierName = trim((string) (
            optional($trim->supplier)->supplier_business_name
            ?: optional($trim->supplier)->name
            ?: '-'
        ));

        $lines = [
            'Trim context snapshot:',
            'trim_id: ' . (int) $trim->id,
            'name: ' . trim((string) ($trim->name ?: '-')),
            'part_number: ' . trim((string) ($trim->part_number ?: '-')),
            'status: ' . trim((string) ($trim->status ?: '-')),
            'status_label: ' . trim((string) ($trim->status_label ?: '-')),
            'trim_category: ' . trim((string) (optional($trim->trimCategory)->name ?: '-')),
            'category_group: ' . trim((string) ($trim->category_group ?: optional($trim->trimCategory)->category_group ?: '-')),
            'material: ' . trim((string) ($trim->material ?: '-')),
            'color_value: ' . trim((string) ($trim->color_value ?: '-')),
            'size_dimension: ' . trim((string) ($trim->size_dimension ?: '-')),
            'unit_of_measure: ' . trim((string) ($trim->unit_of_measure ?: '-')),
            'quantity_per_garment: ' . $this->formatDecimal($trim->quantity_per_garment),
            'unit_cost: ' . $this->formatDecimal($trim->unit_cost),
            'currency: ' . trim((string) ($trim->currency ?: '-')),
            'lead_time_days: ' . ($trim->lead_time_days !== null ? (string) $trim->lead_time_days : '-'),
            'supplier: ' . $supplierName,
            'supplier_email: ' . trim((string) (optional($trim->supplier)->email ?: '-')),
            'approved_at: ' . $this->formatDateTime($trim->approved_at),
            'qc_at: ' . $this->formatDateTime($trim->qc_at),
            'qc_notes: ' . trim((string) ($trim->qc_notes ?: '-')),
        ];

        return implode("\n", $lines);
    }

    public function deleteTrim(int $business_id, int $trim_id): void
    {
        $trim = Trim::forBusiness($business_id)->findOrFail($trim_id);

        $hasLinkedQuotes = QuoteLine::where('trim_id', $trim->id)->exists();
        if ($hasLinkedQuotes) {
            throw new \InvalidArgumentException(__('projectx::lang.trim_delete_blocked_by_quotes'));
        }

        $trim->delete();
    }

    public function updateShareSettings(int $business_id, int $trim_id, array $data): Trim
    {
        $trim = Trim::forBusiness($business_id)->findOrFail($trim_id);

        $shareEnabled = (bool) ($data['share_enabled'] ?? false);
        $trim->share_enabled = $shareEnabled;

        if ($shareEnabled && empty($trim->share_token)) {
            $trim->share_token = $this->generateUniqueShareToken();
        }
        if ($shareEnabled && ! empty($data['regenerate_share_token'])) {
            $trim->share_token = $this->generateUniqueShareToken();
        }
        if (! empty($data['clear_share_password'])) {
            $trim->share_password_hash = null;
        }
        if (! empty($data['share_password'])) {
            $trim->share_password_hash = Hash::make((string) $data['share_password']);
        }

        $trim->share_rate_limit_per_day = $data['share_rate_limit_per_day'] ?? null;
        $trim->share_expires_at = $data['share_expires_at'] ?? null;
        $trim->save();

        return $trim->fresh();
    }

    public function getShareSettings(Trim $trim): array
    {
        $shareUrl = null;
        if (! empty($trim->share_token)) {
            $shareUrl = route('projectx.trim_manager.datasheet.share', ['token' => $trim->share_token]);
        }

        return [
            'share_enabled' => (bool) $trim->share_enabled,
            'share_token' => $trim->share_token,
            'share_url' => $shareUrl,
            'share_expires_at' => $trim->share_expires_at ? Carbon::parse($trim->share_expires_at)->format('Y-m-d\TH:i') : null,
            'share_rate_limit_per_day' => $trim->share_rate_limit_per_day,
            'has_password' => ! empty($trim->share_password_hash),
        ];
    }

    public function getTrimByShareToken(string $token): ?Trim
    {
        return Trim::whereNotNull('share_token')
            ->where('share_token', $token)
            ->with([
                'trimCategory:id,name,category_group',
                'supplier:id,name,supplier_business_name',
            ])
            ->first();
    }

    public function getTodayShareViewCount(int $trim_id): int
    {
        return TrimShareView::where('trim_id', $trim_id)
            ->where('viewed_at', '>=', now()->startOfDay())
            ->count();
    }

    public function isShareRateLimitExceeded(int $trim_id, ?int $rateLimitPerDay): bool
    {
        if (empty($rateLimitPerDay) || (int) $rateLimitPerDay < 1) {
            return false;
        }

        return $this->getTodayShareViewCount($trim_id) >= (int) $rateLimitPerDay;
    }

    public function recordShareView(int $trim_id, ?string $ipAddress = null): TrimShareView
    {
        return TrimShareView::create([
            'trim_id' => $trim_id,
            'viewed_at' => now(),
            'ip_address' => $ipAddress ? Str::limit($ipAddress, 45, '') : null,
        ]);
    }

    public function buildTrimDatasheetPayload(Trim $trim): array
    {
        if (! $trim->relationLoaded('trimCategory')) {
            $trim->load('trimCategory:id,name,category_group');
        }
        if (! $trim->relationLoaded('supplier')) {
            $trim->load('supplier:id,name,supplier_business_name');
        }

        $imagePublicPath = null;
        $imageDiskPath = null;
        if (! empty($trim->image_path)) {
            $imagePublicPath = asset('storage/' . $trim->image_path);
            $candidatePath = public_path('storage/' . $trim->image_path);
            $imageDiskPath = is_file($candidatePath) ? $candidatePath : null;
        }

        $supplierName = trim((string) (
            optional($trim->supplier)->supplier_business_name
            ?: optional($trim->supplier)->name
        ));

        return [
            'title' => __('projectx::lang.trim_datasheet_document_title'),
            'subtitle' => __('projectx::lang.trim_datasheet_document_subtitle'),
            'date' => $this->formatDate($trim->created_at),
            'updated_at' => $this->formatDateTime($trim->updated_at),

            'name' => $trim->name ?: '-',
            'part_number' => $trim->part_number ?: '-',
            'trim_category' => optional($trim->trimCategory)->name ?: '-',
            'category_group' => $trim->category_group ?: (optional($trim->trimCategory)->category_group ?: '-'),
            'description' => $trim->description ?: '-',
            'status' => $trim->status ?: '-',
            'status_label' => $trim->status_label ?: '-',

            'material' => $trim->material ?: '-',
            'color_value' => $trim->color_value ?: '-',
            'size_dimension' => $trim->size_dimension ?: '-',
            'unit_of_measure' => $trim->unit_of_measure ?: '-',
            'placement' => $trim->placement ?: '-',
            'quantity_per_garment' => $this->formatDecimal($trim->quantity_per_garment),
            'label_sub_type' => $trim->label_sub_type ?: '-',
            'purpose' => $trim->purpose ?: '-',

            'button_ligne' => $trim->button_ligne ?: '-',
            'button_holes' => $trim->button_holes ?: '-',
            'button_material' => $trim->button_material ?: '-',
            'zipper_type' => $trim->zipper_type ?: '-',
            'zipper_slider' => $trim->zipper_slider ?: '-',
            'interlining_type' => $trim->interlining_type ?: '-',

            'supplier' => $supplierName !== '' ? $supplierName : '-',
            'unit_cost' => $this->formatMoney($trim->unit_cost, (string) ($trim->currency ?? '')),
            'currency' => $trim->currency ?: '-',
            'lead_time_days' => $trim->lead_time_days !== null ? (string) $trim->lead_time_days : '-',

            'care_testing' => $trim->care_testing ?: '-',
            'quality_notes' => $trim->quality_notes ?: '-',
            'color_fastness' => $trim->color_fastness ?: '-',
            'shrinkage' => $trim->shrinkage ?: '-',
            'rust_proof' => $trim->rust_proof ?: '-',
            'comfort_notes' => $trim->comfort_notes ?: '-',

            'approved_at' => $this->formatDateTime($trim->approved_at),
            'qc_at' => $this->formatDateTime($trim->qc_at),
            'qc_notes' => $trim->qc_notes ?: '-',

            'image_public_path' => $imagePublicPath,
            'swatch_public_path' => $imagePublicPath,
            'image_path' => $imageDiskPath,
            'context' => 'auth',
        ];
    }

    public function generateUniqueShareToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Trim::where('share_token', $token)->exists());

        return $token;
    }

    protected function resolveSupplierContactId(int $business_id, $supplier_contact_id): ?int
    {
        if (empty($supplier_contact_id)) {
            return null;
        }

        $supplierId = (int) $supplier_contact_id;
        if ($supplierId <= 0) {
            return null;
        }

        $exists = Contact::where('business_id', $business_id)
            ->whereIn('type', ['supplier', 'both'])
            ->where('id', $supplierId)
            ->exists();

        return $exists ? $supplierId : null;
    }

    protected function formatDecimal($value, int $precision = 4): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $formatted = number_format((float) $value, $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    protected function formatMoney($value, string $currencyCode = ''): string
    {
        $formattedValue = $this->formatDecimal($value, 4);
        if ($formattedValue === '-') {
            return '-';
        }

        return trim($currencyCode . ' ' . $formattedValue);
    }

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('m/d/Y');
    }

    protected function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('m/d/Y H:i');
    }
}
