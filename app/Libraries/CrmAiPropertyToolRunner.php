<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\Properties;

/**
 * Ejecuta herramientas del agente ATC contra la BD (solo lectura acotada).
 */
class CrmAiPropertyToolRunner
{
    /**
     * @return list<array{type:string, function:array{name:string, description:string, parameters:array<string, mixed>}}>
     */
    public static function toolDefinitions(): array
    {
        return [
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'search_properties',
                    'description' => 'Busca propiedades aprobadas en el catálogo con filtros opcionales (precio, dormitorios, ubicación por texto en municipio/estado/dirección).',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'min_price'         => ['type' => 'number', 'description' => 'Precio mínimo'],
                            'max_price'         => ['type' => 'number', 'description' => 'Precio máximo'],
                            'min_bedrooms'      => ['type' => 'integer', 'description' => 'Dormitorios mínimos'],
                            'location_keyword'  => ['type' => 'string', 'description' => 'Texto para buscar en municipio, estado o dirección'],
                            'limit'             => ['type' => 'integer', 'description' => 'Máximo de filas (1-15)', 'default' => 5],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_property_detail',
                    'description' => 'Obtiene detalle de una propiedad aprobada por su id_properties.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id_properties' => ['type' => 'integer', 'description' => 'ID interno id_properties'],
                        ],
                        'required' => ['id_properties'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function searchProperties(array $args): string
    {
        $limit = isset($args['limit']) ? (int) $args['limit'] : 5;
        $limit = max(1, min(15, $limit));

        $model = new Properties();
        $q = $model
            ->select(
                'properties.id_properties, properties.price, properties.price_additional, properties.address, '
                . 'properties.bedrooms, properties.bathrooms, properties.meters_construction, properties.environments, '
                . 'municipality.name AS municipality_name, state.name AS state_name, '
                . 'housingtype.name AS housingtype_name, businessmodel.name AS business_model'
            )
            ->join('housingtype', 'housingtype.id = properties.housing_type')
            ->join('municipality', 'municipality.id = properties.municipality')
            ->join('state', 'state.id = properties.state')
            ->join('businessmodel', 'businessmodel.id = properties.business_model')
            ->join('status', 'status.id = properties.status')
            ->where('status.name', 'Aprobado');

        if (isset($args['min_price']) && is_numeric($args['min_price'])) {
            $q->where('properties.price >=', (float) $args['min_price']);
        }
        if (isset($args['max_price']) && is_numeric($args['max_price'])) {
            $q->where('properties.price <=', (float) $args['max_price']);
        }
        if (isset($args['min_bedrooms']) && ctype_digit((string) $args['min_bedrooms'])) {
            $q->where('properties.bedrooms >=', (int) $args['min_bedrooms']);
        }
        if (! empty($args['location_keyword']) && is_string($args['location_keyword'])) {
            $kw = '%' . $args['location_keyword'] . '%';
            $q->groupStart()
                ->like('municipality.name', $kw)
                ->orLike('state.name', $kw)
                ->orLike('properties.address', $kw)
                ->groupEnd();
        }

        $rows = $q->orderBy('properties.id_properties', 'DESC')->limit($limit)->findAll();

        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Listado para auditoría CLI (no exponer al LLM límites altos).
     * Mismos JOIN/columnas que search_properties, sin filtros opcionales.
     *
     * @return list<array<string, mixed>>
     */
    public static function listApprovedCatalogSnapshot(int $maxRows = 500): array
    {
        $maxRows = max(1, min(2000, $maxRows));

        $model = new Properties();

        return $model
            ->select(
                'properties.id_properties, properties.price, properties.price_additional, properties.address, '
                . 'properties.bedrooms, properties.bathrooms, properties.meters_construction, properties.environments, '
                . 'municipality.name AS municipality_name, state.name AS state_name, '
                . 'housingtype.name AS housingtype_name, businessmodel.name AS business_model'
            )
            ->join('housingtype', 'housingtype.id = properties.housing_type')
            ->join('municipality', 'municipality.id = properties.municipality')
            ->join('state', 'state.id = properties.state')
            ->join('businessmodel', 'businessmodel.id = properties.business_model')
            ->join('status', 'status.id = properties.status')
            ->where('status.name', 'Aprobado')
            ->orderBy('properties.id_properties', 'DESC')
            ->limit($maxRows)
            ->findAll();
    }

    public static function getPropertyDetail(int $idProperties): string
    {
        if ($idProperties < 1) {
            return json_encode(['error' => 'invalid_id_properties'], JSON_UNESCAPED_UNICODE);
        }
        $model = new Properties();
        $row = $model
            ->select(
                'properties.id_properties, properties.price, properties.price_additional, properties.address, '
                . 'properties.bedrooms, properties.bathrooms, properties.meters_construction, properties.meters_land, '
                . 'properties.environments, properties.amenities, properties.exterior, properties.adjacencies, '
                . 'municipality.name AS municipality_name, state.name AS state_name, '
                . 'housingtype.name AS housingtype_name, businessmodel.name AS business_model, markettype.name AS market_type'
            )
            ->join('housingtype', 'housingtype.id = properties.housing_type')
            ->join('municipality', 'municipality.id = properties.municipality')
            ->join('state', 'state.id = properties.state')
            ->join('businessmodel', 'businessmodel.id = properties.business_model')
            ->join('markettype', 'markettype.id = properties.market_type')
            ->join('status', 'status.id = properties.status')
            ->where('status.name', 'Aprobado')
            ->where('properties.id_properties', $idProperties)
            ->first();

        return json_encode(
            $row ?? ['error' => 'not_found_or_not_approved', 'id_properties' => $idProperties],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    public static function execute(string $name, string $argumentsJson): string
    {
        $args = json_decode($argumentsJson, true);

        return match ($name) {
            'search_properties' => self::searchProperties(is_array($args) ? $args : []),
            'get_property_detail' => self::getPropertyDetail(
                is_array($args) ? (int) ($args['id_properties'] ?? 0) : 0
            ),
            default => json_encode(['error' => 'unknown_tool', 'name' => $name], JSON_UNESCAPED_UNICODE),
        };
    }
}
