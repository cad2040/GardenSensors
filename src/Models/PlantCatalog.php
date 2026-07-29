<?php
namespace GardenSensors\Models;

use GardenSensors\Core\Database;

/**
 * Reference plant growing conditions used as UI presets.
 * Rows are optional: import via database/import_plant_catalog.php when ready.
 */
class PlantCatalog extends BaseModel implements \JsonSerializable {
    protected $table = 'plant_catalog';
    protected $primaryKey = 'id';
    protected $fillable = [
        'external_pid',
        'common_name',
        'scientific_name',
        'category',
        'description',
        'min_soil_moisture',
        'max_soil_moisture',
        'min_temperature',
        'max_temperature',
        'min_soil_ec',
        'max_soil_ec',
        'min_env_humidity',
        'max_env_humidity',
        'preferred_ph_min',
        'preferred_ph_max',
        'water_requirement',
        'nutrient_level',
        'sunlight',
        'watering_frequency_hours',
        'source',
        'source_url',
        'metadata_json',
        'is_active',
    ];
    protected $hidden = ['created_at', 'updated_at'];

    public function __construct($attributes = [], $db = null) {
        if ($attributes instanceof Database) {
            $db = $attributes;
            $attributes = [];
        }
        if ($db !== null) {
            $this->db = $db;
        }
        parent::__construct(is_array($attributes) ? $attributes : []);
    }

    public function fill(array $attributes) {
        parent::fill($attributes);
        // Primary key / timestamps are not fillable but required after find()
        if (isset($attributes['id'])) {
            $this->attributes['id'] = $attributes['id'];
        }
        if (isset($attributes['created_at'])) {
            $this->attributes['created_at'] = $attributes['created_at'];
        }
        if (isset($attributes['updated_at'])) {
            $this->attributes['updated_at'] = $attributes['updated_at'];
        }
    }

    /**
     * Map categorical water needs to a default watering interval (hours).
     */
    public static function wateringFrequencyFromRequirement(?string $requirement): int {
        $key = strtolower(trim((string) $requirement));
        switch ($key) {
            case 'low':
            case 'drought':
            case 'sparse':
                return 48;
            case 'high':
            case 'frequent':
            case 'wet':
                return 12;
            case 'medium':
            case 'moderate':
            default:
                return 24;
        }
    }

    /**
     * Convert a catalog row (array or model attributes) into plants-table fields.
     */
    public static function mapRowToPlantAttributes(array $row, ?string $displayName = null): array {
        $freq = isset($row['watering_frequency_hours']) && $row['watering_frequency_hours'] !== null && $row['watering_frequency_hours'] !== ''
            ? (int) $row['watering_frequency_hours']
            : self::wateringFrequencyFromRequirement($row['water_requirement'] ?? null);

        $minMoisture = isset($row['min_soil_moisture']) && $row['min_soil_moisture'] !== '' && $row['min_soil_moisture'] !== null
            ? (int) $row['min_soil_moisture']
            : 30;
        $maxMoisture = isset($row['max_soil_moisture']) && $row['max_soil_moisture'] !== '' && $row['max_soil_moisture'] !== null
            ? (int) $row['max_soil_moisture']
            : 70;

        if ($minMoisture >= $maxMoisture) {
            $maxMoisture = min(100, $minMoisture + 10);
        }

        $attrs = [
            'name' => $displayName !== null && $displayName !== ''
                ? $displayName
                : (string) ($row['common_name'] ?? 'Plant'),
            'species' => $row['scientific_name'] ?? ($row['species'] ?? null),
            'description' => $row['description'] ?? null,
            'min_soil_moisture' => $minMoisture,
            'max_soil_moisture' => $maxMoisture,
            'watering_frequency' => $freq > 0 ? $freq : 24,
            'min_temperature' => isset($row['min_temperature']) && $row['min_temperature'] !== ''
                ? (float) $row['min_temperature']
                : null,
            'max_temperature' => isset($row['max_temperature']) && $row['max_temperature'] !== ''
                ? (float) $row['max_temperature']
                : null,
            'catalog_id' => isset($row['id']) ? (int) $row['id'] : null,
            'status' => 'active',
        ];

        return $attrs;
    }

    public function toPlantAttributes(?string $displayName = null): array {
        return self::mapRowToPlantAttributes($this->attributes, $displayName);
    }

    /**
     * Active catalog entries for dropdowns, optionally filtered by search text.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listForDropdown(?string $search = null, int $limit = 200): array {
        $db = Database::getInstance();
        $limit = max(1, min(500, $limit));
        $params = [];
        $sql = "
            SELECT id, common_name, scientific_name, category,
                   min_soil_moisture, max_soil_moisture,
                   min_temperature, max_temperature,
                   watering_frequency_hours, water_requirement,
                   nutrient_level, sunlight, source
            FROM plant_catalog
            WHERE is_active = 1
        ";

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (common_name LIKE ? OR scientific_name LIKE ? OR category LIKE ?)";
            $term = '%' . trim($search) . '%';
            $params = [$term, $term, $term];
        }

        $sql .= " ORDER BY common_name ASC LIMIT {$limit}";

        return $db->query($sql, $params) ?: [];
    }

    public static function countActive(): int {
        $db = Database::getInstance();
        $rows = $db->query("SELECT COUNT(*) AS cnt FROM plant_catalog WHERE is_active = 1");
        return (int) ($rows[0]['cnt'] ?? 0);
    }

    public function jsonSerialize(): array {
        $data = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($data[$key]);
        }
        return $data;
    }
}
