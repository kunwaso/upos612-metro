<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class FabricPantoneItem extends Model
{
    protected $table = 'projectx_fabric_pantone_items';

    protected $guarded = ['id'];

    /** @var array<string, array{hex: string, name: string}>|null */
    protected static ?array $pantoneTcxCatalog = null;

    public function fabric()
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }

    /**
     * Pantone TCX catalog keyed by code. Loaded once and cached.
     *
     * @return array<string, array{hex: string, name: string}>
     */
    protected static function getPantoneTcxCatalog(): array
    {
        if (self::$pantoneTcxCatalog !== null) {
            return self::$pantoneTcxCatalog;
        }

        $path = base_path('Modules/ProjectX/Resources/assets/pantone-TCX.json');
        if (! is_file($path) || ! is_readable($path)) {
            self::$pantoneTcxCatalog = [];

            return self::$pantoneTcxCatalog;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        self::$pantoneTcxCatalog = is_array($data) ? $data : [];

        return self::$pantoneTcxCatalog;
    }

    /**
     * Display name from Pantone TCX catalog; falls back to pantone_code.
     */
    public function getPantoneNameAttribute(): string
    {
        $catalog = self::getPantoneTcxCatalog();
        $info = $catalog[$this->pantone_code] ?? null;

        return $info['name'] ?? $this->pantone_code;
    }

    /**
     * Hex color from Pantone TCX catalog; falls back to #000000.
     */
    public function getPantoneHexAttribute(): string
    {
        $catalog = self::getPantoneTcxCatalog();
        $info = $catalog[$this->pantone_code] ?? null;
        $hex = $info['hex'] ?? '#000000';

        return is_string($hex) ? $hex : '#000000';
    }
}
