<?php

namespace Modules\ProjectX\Utils;

use App\Contact;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\FabricActivityLog;
use Modules\ProjectX\Entities\FabricComponentCatalog;
use Modules\ProjectX\Entities\FabricCompositionItem;
use Modules\ProjectX\Entities\FabricPantoneItem;
use Modules\ProjectX\Entities\FabricShareView;

class FabricManagerUtil
{
    protected $activityLogUtil;

    protected const COMPONENT_BULLET_CLASSES = [
        'bg-primary',
        'bg-success',
        'bg-danger',
        'bg-gray-300',
    ];

    protected const COMPONENT_CHART_COLORS = [
        '#00A3FF',
        '#50CD89',
        '#F1416C',
        '#E4E6EF',
    ];

    protected const FDS_DATALISTS = [
        'dyeing_technique' => [
            'PD',
            'YD',
            'Fiber Dye',
            'Top Dye',
            'Screen Print',
            'Digital Print',
            'Piece Dyed',
            'Yarn Dyed',
        ],
        'performance_claims' => [
            'Moisture wicking',
            'Quick dry',
            'Stretch',
            'Anti-odor',
            'UV protection',
            'Flame resistant',
        ],
        'submit_type' => [
            'Sample Yardage',
            'Handloom',
            'S/off',
            'Strike-off',
            'Lab dip',
        ],
        'construction_ypi' => [
            '23 * 20 thr/cm',
            '24 * 22 thr/cm',
            '26 * 24 thr/cm',
        ],
        'fabric_finish' => [
            '79B',
            'Soft hand',
            'Mercerized',
            'Brushed',
        ],
        'elongation' => [
            '15%',
            '25%',
            '35%',
        ],
        'growth' => [
            '5%',
            '10%',
            '15%',
        ],
        'wool_type' => [
            'Non-Mulesed Wool',
            'Virgin wool',
            'Recycle wool',
            'MOU',
            'Non-MOU',
        ],
        'dyeing_type' => [
            'Piece Dyed',
            'Yarn Dyed',
            'Print',
            'Fiber Dyed',
        ],
        'country_of_origin' => [
            'Afghanistan',
            'Armenia',
            'Azerbaijan',
            'Bahrain',
            'Bangladesh',
            'Bhutan',
            'Brunei',
            'Cambodia',
            'China',
            'Cyprus',
            'Georgia',
            'India',
            'Indonesia',
            'Iran',
            'Iraq',
            'Israel',
            'Japan',
            'Jordan',
            'Kazakhstan',
            'Kuwait',
            'Kyrgyzstan',
            'Laos',
            'Lebanon',
            'Malaysia',
            'Maldives',
            'Mongolia',
            'Myanmar',
            'Nepal',
            'North Korea',
            'Oman',
            'Pakistan',
            'Palestine',
            'Philippines',
            'Qatar',
            'Saudi Arabia',
            'Singapore',
            'South Korea',
            'Sri Lanka',
            'Syria',
            'Taiwan',
            'Tajikistan',
            'Thailand',
            'Timor-Leste',
            'Turkey',
            'Turkmenistan',
            'United Arab Emirates',
            'Uzbekistan',
            'Vietnam',
            'Yemen',
        ],
    ];

    public function __construct(FabricActivityLogUtil $activityLogUtil)
    {
        $this->activityLogUtil = $activityLogUtil;
    }

    /**
     * Get paginated fabric list with optional status filter.
     */
    public function getFabrics(int $business_id, ?string $status = null)
    {
        $query = Fabric::forBusiness($business_id)
            ->with([
                'supplier:id,name,supplier_business_name',
                'suppliers:id,name,supplier_business_name,contact_id',
                'pantoneItems',
                'compositionItems.catalogComponent:id,label',
            ])
            ->orderBy('created_at', 'desc');

        if ($status && in_array($status, Fabric::STATUSES, true)) {
            $query->where('projectx_fabrics.status', $status);
        }

        return $query->get();
    }

    /**
     * Get one fabric by id with tenant scope.
     */
    public function getFabricById(int $business_id, int $fabric_id): Fabric
    {
        return Fabric::forBusiness($business_id)
            ->with([
                'supplier:id,name,supplier_business_name',
                'suppliers:id,name,supplier_business_name,contact_id',
                'creator:id,surname,first_name,last_name',
                'pantoneItems:id,fabric_id,pantone_code,sort_order',
            ])
            ->findOrFail($fabric_id);
    }

    /**
     * Count fabrics grouped by status for the summary card.
     */
    public function getStatusCounts(int $business_id): array
    {
        $counts = Fabric::forBusiness($business_id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'active' => $counts[Fabric::STATUS_ACTIVE] ?? 0,
            'draft' => $counts[Fabric::STATUS_DRAFT] ?? 0,
            'needs_approval' => $counts[Fabric::STATUS_NEEDS_APPROVAL] ?? 0,
            'rejected' => $counts[Fabric::STATUS_REJECTED] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    /**
     * Finance aggregates for the finance card.
     */
    public function getFinanceMetrics(int $business_id): array
    {
        $metrics = Fabric::forBusiness($business_id)
            ->select([
                DB::raw('AVG(sale_price) as avg_sale_price'),
                DB::raw('AVG(purchase_price) as avg_purchase_price'),
                DB::raw('SUM(sale_price) as total_sale'),
                DB::raw('SUM(purchase_price) as total_purchase'),
            ])
            ->first();

        $avg_sale = (float) ($metrics->avg_sale_price ?? 0);
        $avg_purchase = (float) ($metrics->avg_purchase_price ?? 0);
        $margin = (float) ($metrics->total_sale ?? 0) - (float) ($metrics->total_purchase ?? 0);

        return [
            'avg_sale_price' => $avg_sale,
            'avg_purchase_price' => $avg_purchase,
            'margin' => $margin,
            'total_sale' => (float) ($metrics->total_sale ?? 0),
        ];
    }

    /**
     * Get distinct suppliers linked to fabrics for the supplier card.
     */
    public function getSupplierSnapshot(int $business_id): array
    {
        $pivot_supplier_ids = DB::table('projectx_fabric_suppliers as fabric_suppliers')
            ->join('projectx_fabrics as fabrics', 'fabrics.id', '=', 'fabric_suppliers.fabric_id')
            ->where('fabrics.business_id', $business_id)
            ->distinct()
            ->pluck('fabric_suppliers.contact_id');

        $legacy_supplier_ids = Fabric::forBusiness($business_id)
            ->whereNotNull('supplier_contact_id')
            ->distinct()
            ->pluck('supplier_contact_id');

        $supplier_ids = $pivot_supplier_ids
            ->merge($legacy_supplier_ids)
            ->filter()
            ->unique()
            ->values();

        $suppliers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['supplier', 'both'])
            ->whereIn('id', $supplier_ids)
            ->select('id', 'name', 'supplier_business_name', 'contact_id')
            ->limit(5)
            ->get();

        $total_count = Contact::where('business_id', $business_id)
            ->whereIn('type', ['supplier', 'both'])
            ->where('contact_status', 'active')
            ->count();

        return [
            'top_suppliers' => $suppliers,
            'total_supplier_count' => $total_count,
            'fabric_supplier_count' => $supplier_ids->count(),
        ];
    }

    /**
     * Store a new fabric record.
     */
    public function createFabric(int $business_id, array $data): Fabric
    {
        $data['business_id'] = $business_id;
        $supplierIds = $this->filterValidSupplierContactIds(
            $business_id,
            [(int) ($data['supplier_contact_id'] ?? 0)]
        );
        $data['supplier_contact_id'] = $supplierIds[0] ?? null;

        $fabric = Fabric::create($data);

        if (! empty($supplierIds)) {
            $fabric->suppliers()->sync([
                $supplierIds[0] => ['sort_order' => 0],
            ]);
        }

        return $fabric;
    }

    /**
     * Update one fabric settings record for a tenant.
     */
    public function updateFabricSettings(
        int $business_id,
        int $fabric_id,
        array $data,
        ?UploadedFile $image = null,
        array $attachments = [],
        ?int $user_id = null
    ): Fabric {
        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);
        $removeImage = (bool) ($data['avatar_remove'] ?? false);
        $hadImageBefore = ! empty($fabric->image_path);
        $existingSupplierIds = $fabric->suppliers()
            ->pluck('contacts.id')
            ->map(function ($supplierId) {
                return (int) $supplierId;
            })
            ->values()
            ->all();
        $supplierIds = $this->filterValidSupplierContactIds(
            $business_id,
            (array) ($data['supplier_contact_ids'] ?? [])
        );
        $storedAttachmentCount = 0;

        unset($data['avatar_remove'], $data['image'], $data['attachments'], $data['supplier_contact_ids']);

        if ($removeImage && ! empty($fabric->image_path)) {
            Storage::disk('public')->delete($fabric->image_path);
            $data['image_path'] = null;
        }

        if ($image instanceof UploadedFile) {
            if (! empty($fabric->image_path)) {
                Storage::disk('public')->delete($fabric->image_path);
            }

            $data['image_path'] = $image->store('fabric_images', 'public');
        }

        if (! empty($attachments)) {
            $storedAttachments = is_array($fabric->attachments) ? $fabric->attachments : [];

            foreach ($attachments as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $storedAttachments[] = $attachment->store('fabric_attachments', 'public');
                    $storedAttachmentCount++;
                }
            }

            $data['attachments'] = array_values($storedAttachments);
        }

        $data['supplier_contact_id'] = $supplierIds[0] ?? null;

        $fabric->fill($data);
        $dirtyColumns = array_keys($fabric->getDirty());
        $nonMediaDirtyColumns = array_values(array_diff($dirtyColumns, ['image_path', 'attachments', 'updated_at']));
        $fabric->save();

        $supplierSyncPayload = [];
        foreach ($supplierIds as $index => $supplierId) {
            $supplierSyncPayload[$supplierId] = [
                'sort_order' => $index,
            ];
        }
        $fabric->suppliers()->sync($supplierSyncPayload);

        $supplierSelectionChanged = $existingSupplierIds !== array_values($supplierIds);

        if (! is_null($user_id)) {
            if (! empty($nonMediaDirtyColumns) || $supplierSelectionChanged) {
                $this->activityLogUtil->log(
                    $business_id,
                    $fabric->id,
                    FabricActivityLog::ACTION_SETTINGS_UPDATED,
                    null,
                    $user_id,
                    [
                        'fields' => $nonMediaDirtyColumns,
                        'supplier_selection_changed' => $supplierSelectionChanged,
                    ]
                );
            }

            if ($removeImage && $hadImageBefore) {
                $this->activityLogUtil->log(
                    $business_id,
                    $fabric->id,
                    FabricActivityLog::ACTION_IMAGE_REMOVED,
                    null,
                    $user_id
                );
            }

            if ($image instanceof UploadedFile) {
                $this->activityLogUtil->log(
                    $business_id,
                    $fabric->id,
                    FabricActivityLog::ACTION_IMAGE_ADDED,
                    null,
                    $user_id
                );
            }

            if ($storedAttachmentCount > 0) {
                $this->activityLogUtil->log(
                    $business_id,
                    $fabric->id,
                    FabricActivityLog::ACTION_ATTACHMENT_ADDED,
                    null,
                    $user_id,
                    ['count' => $storedAttachmentCount]
                );
            }
        }

        return $fabric->fresh([
            'supplier:id,name,supplier_business_name',
            'suppliers:id,name,supplier_business_name,contact_id',
        ]);
    }

    /**
     * Return normalized attachment metadata for one fabric.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAttachmentListForFabric(int $business_id, int $fabric_id): array
    {
        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);
        $attachments = is_array($fabric->attachments) ? $fabric->attachments : [];
        $entries = [];

        foreach ($attachments as $storedPath) {
            $normalizedPath = $this->normalizeAttachmentPath((string) $storedPath);
            if ($normalizedPath === null) {
                continue;
            }

            $fileName = basename($normalizedPath);
            $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
            $exists = Storage::disk('public')->exists($normalizedPath);
            $sizeBytes = $exists ? (int) Storage::disk('public')->size($normalizedPath) : 0;

            $entries[] = [
                'path' => $normalizedPath,
                'hash' => $this->makeAttachmentHash($fabric_id, $normalizedPath),
                'name' => $fileName,
                'extension' => $extension,
                'size_bytes' => $sizeBytes,
                'size_display' => $this->formatBytes($sizeBytes),
                'exists' => $exists,
            ];
        }

        return $entries;
    }

    public function appendAttachmentsToFabric(
        int $business_id,
        int $fabric_id,
        array $attachments,
        ?int $user_id = null
    ): Fabric {
        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);
        $storedPaths = is_array($fabric->attachments) ? $fabric->attachments : [];
        $newCount = 0;

        foreach ($attachments as $attachment) {
            if (! ($attachment instanceof UploadedFile)) {
                continue;
            }

            $storedPaths[] = $attachment->store('fabric_attachments', 'public');
            $newCount++;
        }

        if ($newCount <= 0) {
            throw new \InvalidArgumentException(__('projectx::lang.fabric_attachment_not_found'));
        }

        $normalizedPaths = [];
        foreach ($storedPaths as $storedPath) {
            $normalizedPath = $this->normalizeAttachmentPath((string) $storedPath);
            if ($normalizedPath !== null) {
                $normalizedPaths[] = $normalizedPath;
            }
        }

        $fabric->attachments = array_values(array_unique($normalizedPaths));
        $fabric->save();

        if (! is_null($user_id)) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_ATTACHMENT_ADDED,
                null,
                $user_id,
                ['count' => $newCount]
            );
        }

        return $fabric->fresh();
    }

    public function deleteAttachmentFromFabric(
        int $business_id,
        int $fabric_id,
        string $fileHash,
        ?int $user_id = null
    ): Fabric {
        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);
        $attachments = is_array($fabric->attachments) ? $fabric->attachments : [];
        $targetPath = null;

        foreach ($attachments as $storedPath) {
            $normalizedPath = $this->normalizeAttachmentPath((string) $storedPath);
            if ($normalizedPath === null) {
                continue;
            }

            if (hash_equals($this->makeAttachmentHash($fabric_id, $normalizedPath), $fileHash)) {
                $targetPath = $normalizedPath;
                break;
            }
        }

        if ($targetPath === null) {
            throw new \InvalidArgumentException(__('projectx::lang.fabric_attachment_not_found'));
        }

        $remainingPaths = [];
        foreach ($attachments as $storedPath) {
            $normalizedPath = $this->normalizeAttachmentPath((string) $storedPath);
            if ($normalizedPath === null || $normalizedPath === $targetPath) {
                continue;
            }
            $remainingPaths[] = $normalizedPath;
        }

        $fabric->attachments = array_values(array_unique($remainingPaths));
        $fabric->save();

        if (Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->delete($targetPath);
        }

        if (! is_null($user_id)) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_SETTINGS_UPDATED,
                null,
                $user_id,
                [
                    'fields' => ['attachments'],
                    'attachment_deleted' => true,
                ]
            );
        }

        return $fabric->fresh();
    }

    /**
     * @return array{path: string, name: string}
     */
    public function getAttachmentByHashForFabric(int $business_id, int $fabric_id, string $fileHash): array
    {
        $attachments = $this->getAttachmentListForFabric($business_id, $fabric_id);

        foreach ($attachments as $attachment) {
            if (hash_equals((string) ($attachment['hash'] ?? ''), $fileHash)) {
                return [
                    'path' => (string) $attachment['path'],
                    'name' => (string) $attachment['name'],
                ];
            }
        }

        throw new \InvalidArgumentException(__('projectx::lang.fabric_attachment_not_found'));
    }

    protected function normalizeAttachmentPath(string $storedPath): ?string
    {
        $storedPath = trim(str_replace('\\', '/', $storedPath));
        if ($storedPath === '') {
            return null;
        }

        if (str_starts_with($storedPath, '/')) {
            $storedPath = ltrim($storedPath, '/');
        }

        if (str_starts_with($storedPath, 'storage/')) {
            $storedPath = substr($storedPath, strlen('storage/'));
        }

        return $storedPath === '' ? null : $storedPath;
    }

    protected function makeAttachmentHash(int $fabric_id, string $normalizedPath): string
    {
        return hash('sha256', $fabric_id . '|' . $normalizedPath);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < (count($units) - 1)) {
            $size /= 1024;
            $unitIndex++;
        }

        $precision = $unitIndex === 0 ? 0 : 2;

        return number_format($size, $precision, '.', '') . ' ' . $units[$unitIndex];
    }

    /**
     * Update chat-approved scalar fabric fields.
     */
    public function updateFabricFromChat(int $business_id, int $fabric_id, array $updates, ?int $user_id = null): Fabric
    {
        $writableFieldTypes = $this->getChatFabricWritableFieldTypes();
        $allowedFields = array_keys($writableFieldTypes);
        $providedFields = array_keys($updates);
        $invalidFields = array_values(array_diff($providedFields, $allowedFields));
        if (! empty($invalidFields)) {
            throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_fields'));
        }

        $payload = [];
        foreach ($updates as $field => $value) {
            if (! array_key_exists($field, $writableFieldTypes)) {
                continue;
            }

            $payload[$field] = $this->coerceChatFabricUpdateValue($field, $value, $writableFieldTypes[$field]);
        }

        if (empty($payload)) {
            throw new \InvalidArgumentException(__('projectx::lang.chat_apply_no_valid_updates'));
        }

        if (array_key_exists('supplier_contact_id', $payload) && ! is_null($payload['supplier_contact_id'])) {
            $isValidSupplier = Contact::where('business_id', $business_id)
                ->whereIn('type', ['supplier', 'both'])
                ->where('id', (int) $payload['supplier_contact_id'])
                ->exists();

            if (! $isValidSupplier) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }
        }

        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);
        $fabric->fill($payload);

        $dirtyColumns = array_values(array_diff(array_keys($fabric->getDirty()), ['updated_at']));
        if (empty($dirtyColumns)) {
            return $fabric->fresh();
        }

        $fabric->save();

        if (! is_null($user_id)) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_SETTINGS_UPDATED,
                null,
                $user_id,
                [
                    'fields' => $dirtyColumns,
                    'source' => 'chat_apply_updates',
                ]
            );
        }

        return $fabric->fresh();
    }

    /**
     * Update public share settings for one fabric.
     */
    public function updateShareSettings(int $business_id, int $fabric_id, array $data, ?int $user_id = null): Fabric
    {
        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);
        $shareEnabled = (bool) ($data['share_enabled'] ?? false);

        $fabric->share_enabled = $shareEnabled;

        if ($shareEnabled && empty($fabric->share_token)) {
            $fabric->share_token = $this->generateUniqueShareToken();
        }

        if ($shareEnabled && ! empty($data['regenerate_share_token'])) {
            $fabric->share_token = $this->generateUniqueShareToken();
        }

        if (! empty($data['clear_share_password'])) {
            $fabric->share_password_hash = null;
        }

        if (! empty($data['share_password'])) {
            $fabric->share_password_hash = Hash::make((string) $data['share_password']);
        }

        $fabric->share_rate_limit_per_day = $data['share_rate_limit_per_day'] ?? null;
        $fabric->share_expires_at = $data['share_expires_at'] ?? null;

        $dirtyColumns = array_keys($fabric->getDirty());
        $nonSensitiveDirtyColumns = array_values(array_diff($dirtyColumns, ['share_password_hash', 'updated_at']));
        $passwordUpdated = in_array('share_password_hash', $dirtyColumns, true);

        $fabric->save();

        if (! is_null($user_id) && (! empty($nonSensitiveDirtyColumns) || $passwordUpdated)) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_SETTINGS_UPDATED,
                null,
                $user_id,
                [
                    'fields' => $nonSensitiveDirtyColumns,
                    'share_password_updated' => $passwordUpdated,
                ]
            );
        }

        return $fabric->fresh();
    }

    /**
     * Build share settings payload for the Targets page.
     */
    public function getShareSettings(Fabric $fabric): array
    {
        $shareUrl = null;
        if (! empty($fabric->share_token)) {
            $shareUrl = route('projectx.fabric_manager.datasheet.share', ['token' => $fabric->share_token]);
        }

        return [
            'share_enabled' => (bool) $fabric->share_enabled,
            'share_token' => $fabric->share_token,
            'share_url' => $shareUrl,
            'share_expires_at' => $fabric->share_expires_at ? Carbon::parse($fabric->share_expires_at)->format('Y-m-d\\TH:i') : null,
            'share_rate_limit_per_day' => $fabric->share_rate_limit_per_day,
            'has_password' => ! empty($fabric->share_password_hash),
        ];
    }

    /**
     * Suggested datalist options for FDS input fields.
     */
    public function getFdsDatalists(): array
    {
        return self::FDS_DATALISTS;
    }

    /**
     * Transition one fabric status with allowed source states.
     */
    public function transitionStatus(
        int $business_id,
        int $fabric_id,
        array $fromStatuses,
        string $toStatus,
        ?int $user_id = null,
        ?string $activityAction = null
    ): Fabric {
        $fabric = Fabric::forBusiness($business_id)->findOrFail($fabric_id);

        if (! in_array($fabric->status, $fromStatuses, true)) {
            throw new \RuntimeException(__('projectx::lang.invalid_status_transition'));
        }

        if ($fabric->status === $toStatus) {
            return $fabric;
        }

        $fabric->status = $toStatus;
        $fabric->save();

        if (! is_null($user_id) && ! empty($activityAction)) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                $activityAction,
                null,
                $user_id
            );
        }

        return $fabric->fresh();
    }

    /**
     * Resolve one fabric by public share token.
     */
    public function getFabricByShareToken(string $token): ?Fabric
    {
        return Fabric::whereNotNull('share_token')
            ->where('share_token', $token)
            ->with([
                'supplier:id,name,supplier_business_name',
                'suppliers:id,name,supplier_business_name,contact_id',
                'pantoneItems:id,fabric_id,pantone_code,sort_order',
            ])
            ->first();
    }

    /**
     * Count successful share views for today.
     */
    public function getTodayShareViewCount(int $fabric_id): int
    {
        return FabricShareView::where('fabric_id', $fabric_id)
            ->where('viewed_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * Check whether a share link has reached today's view cap.
     */
    public function isShareRateLimitExceeded(int $fabric_id, ?int $rateLimitPerDay): bool
    {
        if (empty($rateLimitPerDay) || (int) $rateLimitPerDay < 1) {
            return false;
        }

        return $this->getTodayShareViewCount($fabric_id) >= (int) $rateLimitPerDay;
    }

    /**
     * Persist one successful share view event.
     */
    public function recordShareView(int $fabric_id, ?string $ipAddress = null): FabricShareView
    {
        return FabricShareView::create([
            'fabric_id' => $fabric_id,
            'viewed_at' => now(),
            'ip_address' => $ipAddress ? Str::limit($ipAddress, 45, '') : null,
        ]);
    }

    /**
     * Build display-ready datasheet payload for Blade/PDF.
     */
    public function buildDatasheetPayload(Fabric $fabric): array
    {
        $supplierNames = $this->formatSupplierNames($fabric);
        $widthParts = [];
        if (! is_null($fabric->width_cm)) {
            $widthParts[] = $this->formatDecimal($fabric->width_cm) . ' cm';
        }
        if (! is_null($fabric->usable_width_inch)) {
            $widthParts[] = $this->formatDecimal($fabric->usable_width_inch) . ' in';
        }
        $cuttableWidth = empty($widthParts) ? '-' : implode(' / ', $widthParts);

        $swatchPublicPath = null;
        $swatchFilePath = null;
        if (! empty($fabric->image_path)) {
            $swatchPublicPath = asset('storage/' . $fabric->image_path);
            $candidatePath = public_path('storage/' . $fabric->image_path);
            $swatchFilePath = is_file($candidatePath) ? $candidatePath : null;
        }

        $currency = trim((string) ($fabric->currency ?? ''));

        return [
            'title' => __('projectx::lang.fds_document_title'),
            'subtitle' => __('projectx::lang.fds_document_subtitle'),
            'name' => $fabric->name ?: '-',
            'component_summary' => $this->formatCompositionSummaryForDatasheet($fabric),
            'date' => $this->formatDateValue($fabric->fds_date),
            'swatch_submit_date' => $this->formatDateValue($fabric->swatch_submit_date),
            'fabric_sku' => $fabric->fabric_sku ?: '-',
            'season_department' => $fabric->season_department ?: '-',
            'suppliers' => $supplierNames,
            'mill_article_no' => $fabric->mill_article_no ?: '-',
            'pattern_color_name_number' => $fabric->pattern_color_name_number ?: '-',
            'mill_pattern_color' => $this->formatMillPatternColorForDatasheet($fabric->mill_pattern_color),
            'certifications' => $fabric->certifications ?: '-',
            'performance_claims' => $fabric->performance_claims ?: '-',
            'color_fastness' => $fabric->color_fastness ?: '-',
            'abrasion_resistance' => $fabric->abrasion_resistance ?: '-',
            'handfeel_drape' => $fabric->handfeel_drape ?: '-',
            'finish_treatments' => $fabric->finish_treatments ?: '-',
            'dyeing_technique' => $fabric->dyeing_technique ?: '-',
            'construction_type' => $fabric->construction_type ?: '-',
            'weave_pattern' => $fabric->weave_pattern ?: '-',
            'submit_type' => $fabric->submit_type ?: '-',
            'composition' => $this->formatCompositionSummaryForDatasheet($fabric),
            'yarn_count_denier' => $fabric->yarn_count_denier ?: '-',
            'construction_ypi' => $fabric->construction_ypi ?: '-',
            'weight_gsm' => $this->formatDecimal($fabric->weight_gsm),
            'cuttable_width' => $cuttableWidth,
            'fabric_finish' => $this->firstNonEmpty([$fabric->fabric_finish, $fabric->finish_treatments]) ?: '-',
            'care_label' => $fabric->care_label ?: '-',
            'shrinkage_percent' => $this->formatDecimal($fabric->shrinkage_percent),
            'country_of_origin' => $fabric->country_of_origin ?: '-',
            'elongation' => $fabric->elongation ?: '-',
            'growth' => $fabric->growth ?: '-',
            'recovery' => $fabric->recovery ?: '-',
            'elongation_25_fixed' => $fabric->elongation_25_fixed ?: '-',
            'wool_type' => $fabric->wool_type ?: '-',
            'raw_material_origin' => $fabric->raw_material_origin ?: '-',
            'bulk_lead_time_days' => $this->formatIntegerValue($fabric->bulk_lead_time_days),
            'dyeing_type' => $fabric->dyeing_type ?: '-',
            'fds_season' => $fabric->fds_season ?: '-',
            'minimum_order_quantity' => $this->formatDecimal($fabric->minimum_order_quantity),
            'minimum_color_quantity' => $this->formatDecimal($fabric->minimum_color_quantity),
            'monthly_capacity' => $this->formatDecimal($fabric->monthly_capacity),
            'price_500_yds' => $this->formatMoney($fabric->price_500_yds, $currency),
            'price_3k' => $this->formatMoney($fabric->price_3k, $currency),
            'price_10k' => $this->formatMoney($fabric->price_10k, $currency),
            'price_25k' => $this->formatMoney($fabric->price_25k, $currency),
            'price_50k_plus' => $this->formatMoney($fabric->price_50k_plus, $currency),
            'swatch_public_path' => $swatchPublicPath,
            'swatch_file_path' => $swatchFilePath,
        ];
    }

    /**
     * Generate a unique token for public share links.
     */
    protected function generateUniqueShareToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Fabric::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Build comma-separated supplier names for datasheet output.
     */
    protected function formatSupplierNames(Fabric $fabric): string
    {
        $suppliers = $fabric->suppliers;
        if ($suppliers->isEmpty() && $fabric->supplier) {
            $suppliers = collect([$fabric->supplier]);
        }

        $labels = $suppliers->map(function ($supplier) {
            $businessName = trim((string) ($supplier->supplier_business_name ?? ''));
            $name = trim((string) ($supplier->name ?? ''));

            if ($businessName !== '' && $name !== '' && strcasecmp($businessName, $name) !== 0) {
                return $businessName . ' (' . $name . ')';
            }

            return $businessName !== '' ? $businessName : ($name !== '' ? $name : null);
        })->filter()->values()->all();

        if (empty($labels)) {
            return '-';
        }

        return implode(', ', $labels);
    }

    /**
     * Public API: single-line component summary from composition items (e.g. "Cotton 60%, Polyester 40%").
     */
    public function getComponentSummaryForFabric(Fabric $fabric): string
    {
        return $this->formatCompositionSummaryForDatasheet($fabric);
    }

    /**
     * Build a single-line component summary for datasheet (e.g. "Cotton 60%, Polyester 40%").
     */
    protected function formatCompositionSummaryForDatasheet(Fabric $fabric): string
    {
        if (! $fabric->relationLoaded('compositionItems')) {
            $fabric->load(['compositionItems.catalogComponent:id,label']);
        }

        $items = $fabric->compositionItems
            ->map(function (FabricCompositionItem $item) {
                $label = ! empty($item->label_override)
                    ? $item->label_override
                    : optional($item->catalogComponent)->label;
                if (empty($label)) {
                    $label = __('projectx::lang.unknown_composition');
                }
                $percent = round((float) $item->percent, 2);

                return $label . ' ' . $percent . '%';
            })
            ->values()
            ->all();

        if (empty($items)) {
            return '-';
        }

        return implode(', ', $items);
    }

    /**
     * Format mill_pattern_color (array or legacy string) for datasheet display.
     *
     * @param  array<int, string>|string|null  $value
     */
    protected function formatMillPatternColorForDatasheet($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_array($value)) {
            $filtered = array_filter(array_map('trim', $value));
            return empty($filtered) ? '-' : implode(', ', $filtered);
        }

        return trim((string) $value) ?: '-';
    }

    /**
     * Format decimal values for display.
     */
    protected function formatDecimal($value, int $precision = 4): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $formatted = number_format((float) $value, $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * Format integer values for display.
     */
    protected function formatIntegerValue($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return (string) (int) $value;
    }

    /**
     * Format dates for FDS output.
     */
    protected function formatDateValue($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('m/d/Y');
    }

    /**
     * Format monetary values with optional currency code.
     */
    protected function formatMoney($value, string $currencyCode = ''): string
    {
        $formattedValue = $this->formatDecimal($value, 4);
        if ($formattedValue === '-') {
            return '-';
        }

        return trim($currencyCode . ' ' . $formattedValue);
    }

    /**
     * Return first non-empty value from a list.
     *
     * @param  array<int, mixed>  $values
     */
    protected function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_null($value)) {
                continue;
            }

            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    /**
     * Keep only valid supplier contact ids (current business + supplier/both type) while preserving input order.
     *
     * @param  array<int, mixed>  $supplierContactIds
     * @return array<int, int>
     */
    protected function filterValidSupplierContactIds(int $business_id, array $supplierContactIds): array
    {
        $normalizedIds = array_values(array_unique(array_filter(array_map(function ($supplierContactId) {
            return is_numeric($supplierContactId) ? (int) $supplierContactId : null;
        }, $supplierContactIds))));

        if (empty($normalizedIds)) {
            return [];
        }

        $validSupplierLookup = Contact::where('business_id', $business_id)
            ->whereIn('type', ['supplier', 'both'])
            ->whereIn('id', $normalizedIds)
            ->pluck('id')
            ->map(function ($supplierId) {
                return (int) $supplierId;
            })
            ->flip()
            ->all();

        $validSupplierIds = [];
        foreach ($normalizedIds as $normalizedId) {
            if (isset($validSupplierLookup[$normalizedId])) {
                $validSupplierIds[] = $normalizedId;
            }
        }

        return $validSupplierIds;
    }

    /**
     * Get the component catalog available to the business.
     */
    public function getComponentCatalog(int $business_id): Collection
    {
        return FabricComponentCatalog::forBusiness($business_id)
            ->orderByRaw('CASE WHEN business_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /**
     * Build composition view summary for a fabric (list/settings display).
     * Expects $fabric->compositionItems to be loaded with catalogComponent.
     */
    public function getCompositionViewForFabric(Fabric $fabric): array
    {
        $items = $fabric->compositionItems
            ->map(function (FabricCompositionItem $item, int $index) {
                $label = ! empty($item->label_override)
                    ? $item->label_override
                    : optional($item->catalogComponent)->label;

                if (empty($label)) {
                    $label = __('projectx::lang.unknown_composition');
                }

                return [
                    'label' => $label,
                    'percent' => round((float) $item->percent, 2),
                    'bullet_class' => self::COMPONENT_BULLET_CLASSES[$index % count(self::COMPONENT_BULLET_CLASSES)],
                ];
            })
            ->values()
            ->all();

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * Get a fabric composition payload for cards and AJAX responses.
     */
    public function getCompositionPayload(int $business_id, int $fabric_id): array
    {
        $fabric = Fabric::forBusiness($business_id)
            ->with([
                'compositionItems.catalogComponent:id,label',
            ])
            ->findOrFail($fabric_id);

        return $this->formatCompositionPayload($fabric);
    }

    /**
     * Replace composition rows for one fabric.
     */
    public function updateComposition(int $business_id, int $fabric_id, array $items, ?int $user_id = null): array
    {
        $fabric = $this->getFabricById($business_id, $fabric_id);
        $catalog = $this->getComponentCatalog($business_id)->keyBy('id');
        $otherCatalogId = $catalog
            ->first(function (FabricComponentCatalog $component) {
                return strtolower($component->label) === 'other';
            });

        DB::transaction(function () use ($fabric, $items, $catalog, $otherCatalogId) {
            FabricCompositionItem::where('fabric_id', $fabric->id)->delete();

            foreach ($items as $index => $item) {
                $catalogId = (int) ($item['catalog_id'] ?? 0);

                if (! $catalog->has($catalogId)) {
                    continue;
                }

                $labelOverride = trim((string) ($item['label_override'] ?? ''));
                $isOther = $otherCatalogId && $catalogId === (int) $otherCatalogId->id;

                FabricCompositionItem::create([
                    'fabric_id' => $fabric->id,
                    'fabric_component_catalog_id' => $catalogId,
                    'label_override' => $isOther ? $labelOverride : null,
                    'percent' => round((float) ($item['percent'] ?? 0), 2),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        $payload = $this->getCompositionPayload($business_id, $fabric_id);

        if (! is_null($user_id)) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_COMPOSITION_UPDATED,
                null,
                $user_id,
                ['composition_count' => (int) ($payload['composition_count'] ?? 0)]
            );
        }

        return $payload;
    }

    /**
     * Convert composition records into a view/API friendly shape.
     */
    protected function formatCompositionPayload(Fabric $fabric): array
    {
        $items = $fabric->compositionItems
            ->map(function (FabricCompositionItem $item, int $index) {
                $label = ! empty($item->label_override)
                    ? $item->label_override
                    : optional($item->catalogComponent)->label;

                if (empty($label)) {
                    $label = __('projectx::lang.unknown_composition');
                }

                return [
                    'id' => (int) $item->id,
                    'catalog_id' => $item->fabric_component_catalog_id ? (int) $item->fabric_component_catalog_id : null,
                    'label' => $label,
                    'label_override' => $item->label_override,
                    'percent' => round((float) $item->percent, 2),
                    'bullet_class' => self::COMPONENT_BULLET_CLASSES[$index % count(self::COMPONENT_BULLET_CLASSES)],
                    'color' => self::COMPONENT_CHART_COLORS[$index % count(self::COMPONENT_CHART_COLORS)],
                ];
            })
            ->values();

        $totalPercent = round((float) $items->sum('percent'), 2);

        return [
            'fabric_id' => (int) $fabric->id,
            'items' => $items->all(),
            'composition_count' => $items->count(),
            'total_percent' => $totalPercent,
            'warning_total_not_100' => abs($totalPercent - 100) > 0.01,
            'chart' => [
                'labels' => $items->pluck('label')->all(),
                'data' => $items->pluck('percent')->all(),
                'colors' => $items->pluck('color')->all(),
            ],
        ];
    }

    /**
     * Pantone TXC JSON path (relative to module).
     */
    protected static function getPantoneTcxJsonPath(): string
    {
        return base_path('Modules/ProjectX/Resources/assets/pantone-TCX.json');
    }

    /**
     * Load Pantone TCX catalog from JSON. Returns array keyed by code: [ code => ['hex' => ..., 'name' => ...], ... ]
     */
    public function getPantoneTcxCatalog(): array
    {
        $path = self::getPantoneTcxJsonPath();
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Get Pantone TXC items for a fabric, enriched with hex and name from catalog.
     *
     * @return array<int, array{id: int, code: string, hex: string, name: string}>
     */
    public function getPantoneForFabric(int $business_id, int $fabric_id): array
    {
        $fabric = Fabric::forBusiness($business_id)->with('pantoneItems')->findOrFail($fabric_id);
        $catalog = $this->getPantoneTcxCatalog();

        $items = [];
        foreach ($fabric->pantoneItems as $item) {
            $code = $item->pantone_code;
            $info = $catalog[$code] ?? null;
            $items[] = [
                'id' => (int) $item->id,
                'code' => $code,
                'hex' => $info['hex'] ?? '#000000',
                'name' => $info['name'] ?? $code,
            ];
        }

        return $items;
    }

    /**
     * Replace fabric Pantone TXC list with given codes. Codes must exist in Pantone TCX catalog.
     *
     * @param  array<int, string>  $pantone_codes  Array of pantone codes e.g. ['11-4201 TCX', '18-1649 TCX']
     * @return array<int, array{id: int, code: string, hex: string, name: string}>
     */
    public function updatePantoneList(int $business_id, int $fabric_id, array $pantone_codes, ?int $user_id = null): array
    {
        $fabric = $this->getFabricById($business_id, $fabric_id);
        $catalog = $this->getPantoneTcxCatalog();
        $existingCodes = FabricPantoneItem::where('fabric_id', $fabric->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('pantone_code')
            ->values()
            ->all();

        $validated = [];
        $seenCodes = [];
        foreach ($pantone_codes as $code) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }
            if (! isset($catalog[$code])) {
                continue;
            }
            if (isset($seenCodes[$code])) {
                continue;
            }
            $seenCodes[$code] = true;
            $validated[] = $code;
        }

        DB::transaction(function () use ($fabric, $validated) {
            FabricPantoneItem::where('fabric_id', $fabric->id)->delete();
            foreach ($validated as $index => $code) {
                FabricPantoneItem::create([
                    'fabric_id' => $fabric->id,
                    'pantone_code' => $code,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        $result = $this->getPantoneForFabric($business_id, $fabric_id);

        if (! is_null($user_id) && $existingCodes !== $validated) {
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_PANTONE_UPDATED,
                null,
                $user_id,
                [
                    'previous_count' => count($existingCodes),
                    'current_count' => count($validated),
                ]
            );
        }

        return $result;
    }

    /**
     * Build a tenant-scoped fabric context payload for AI chat usage.
     */
    public function getFabricContextForChat(int $business_id, int $fabric_id): string
    {
        $fabric = Fabric::forBusiness($business_id)
            ->with([
                'compositionItems.catalogComponent:id,label',
                'pantoneItems:id,fabric_id,pantone_code,sort_order',
                'suppliers:id,name,supplier_business_name,contact_id',
            ])
            ->findOrFail($fabric_id);

        $composition = $fabric->compositionItems->map(function (FabricCompositionItem $item) {
            $label = $item->label_override ?: optional($item->catalogComponent)->label;

            return [
                'label' => $label,
                'percent' => (float) $item->percent,
            ];
        })->values()->all();

        $pantones = $fabric->pantoneItems->map(function (FabricPantoneItem $item) {
            return (string) $item->pantone_code;
        })->values()->all();

        $suppliers = $fabric->suppliers->map(function (\App\Contact $contact) {
            return [
                'name' => $contact->name ?: $contact->supplier_business_name,
                'contact_code' => $contact->contact_id,
            ];
        })->values()->all();

        $scalarPayload = $fabric->toArray();
        $relationKeys = [
            'supplier',
            'suppliers',
            'creator',
            'business',
            'compositionItems',
            'composition_items',
            'pantoneItems',
            'pantone_items',
            'activityLogs',
            'activity_logs',
            'shareViews',
            'share_views',
        ];
        foreach ($relationKeys as $relationKey) {
            unset($scalarPayload[$relationKey]);
        }

        foreach ($this->getChatFabricContextReadExcludedColumns() as $excludedColumn) {
            unset($scalarPayload[$excludedColumn]);
        }

        unset($scalarPayload['composition'], $scalarPayload['component']);
        $scalarPayload['composition_text'] = $this->formatCompositionSummaryForDatasheet($fabric);
        ksort($scalarPayload);

        $payload = [
            'fabric' => array_merge(
                $scalarPayload,
                [
                'composition' => $composition,
                'pantone_codes' => $pantones,
                'suppliers' => $suppliers,
                ]
            ),
        ];

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encodedPayload === false ? '{}' : $encodedPayload;
    }

    /**
     * Config-driven list of writable fabric fields for chat updates.
     *
     * @return array<string, string>
     */
    protected function getChatFabricWritableFieldTypes(): array
    {
        $fieldTypes = (array) config('projectx.chat.fabric_updates.writable_field_types', []);
        $allowedTypes = ['string', 'decimal', 'integer', 'boolean', 'date'];
        $normalized = [];

        foreach ($fieldTypes as $field => $type) {
            $field = trim((string) $field);
            $type = strtolower(trim((string) $type));

            if ($field === '' || ! in_array($type, $allowedTypes, true)) {
                continue;
            }

            $normalized[$field] = $type;
        }

        return $normalized;
    }

    /**
     * Columns excluded from chat fabric context payload.
     *
     * @return array<int, string>
     */
    protected function getChatFabricContextReadExcludedColumns(): array
    {
        $excludedColumns = (array) config('projectx.chat.fabric_context.read_excluded_columns', []);

        return array_values(array_unique(array_filter(array_map(function ($column) {
            return is_string($column) ? trim($column) : null;
        }, $excludedColumns))));
    }

    /**
     * Coerce one chat-proposed value to a safe type accepted by Fabric model updates.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function coerceChatFabricUpdateValue(string $field, $value, string $type)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            $value = null;
        }

        if ($value === null) {
            if ($this->isChatFabricNonNullableField($field)) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }

            return null;
        }

        if ($type === 'string') {
            return trim((string) $value);
        }

        if ($type === 'decimal') {
            if (! is_numeric($value)) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }

            return (float) $value;
        }

        if ($type === 'integer') {
            if (! is_numeric($value)) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }

            $numeric = (float) $value;
            if ((float) ((int) $numeric) !== $numeric) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }

            return (int) $numeric;
        }

        if ($type === 'boolean') {
            $parsedBoolean = $this->parseBooleanFromMixedValue($value);
            if ($parsedBoolean === null) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }

            return $parsedBoolean;
        }

        if ($type === 'date') {
            try {
                return Carbon::parse((string) $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_values'));
            }
        }

        return $value;
    }

    protected function isChatFabricNonNullableField(string $field): bool
    {
        return in_array($field, [
            'name',
            'status',
            'purchase_price',
            'sale_price',
            'progress_percent',
            'notification_email',
            'notification_phone',
        ], true);
    }

    /**
     * @param  mixed  $value
     */
    protected function parseBooleanFromMixedValue($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            if ((int) $value === 1) {
                return true;
            }
            if ((int) $value === 0) {
                return false;
            }

            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return null;
    }
}
