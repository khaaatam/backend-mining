<?php

namespace App\Services;

use Softonic\GraphQL\ClientBuilder;
use Softonic\GraphQL\Client;
use Illuminate\Support\Facades\Log;

class VeridaptService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::build(
            config('services.veridapt.url'),
            [
                'headers' => [
                    'Authorization' => 'Token token=' . config('services.veridapt.api_key'),
                    'Content-Type'  => 'application/json',
                ]
            ]
        );
    }

    /**
     * Get GPS coordinates for a fixed fuel station (Tank)
     * Returns static coordinates — call once or sync periodically
     */
    public function getFuelStationCoordinates(string $siteId): array
    {
        $query = <<<'GRAPHQL'
            query GetFuelStations($siteId: ID!) {
                site(id: $siteId) {
                    tanks {
                        edges {
                            node {
                                id
                                code
                                name
                                gpsCoordinates
                                enabled
                                location {
                                    code
                                    description
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        try {
            $response = $this->client->query($query, [
                'siteId' => $siteId
            ]);

            if ($response->hasErrors()) {
                Log::error('Veridapt getFuelStationCoordinates error', [
                    'errors' => $response->getErrors()
                ]);
                return [];
            }

            $tanks = data_get($response->getData(), 'site.tanks.edges', []);

            return collect($tanks)
                ->map(fn($edge) => $edge['node'])
                ->filter(fn($tank) => !empty($tank['gpsCoordinates']) && $tank['enabled'])
                ->map(function ($tank) {
                    // gpsCoordinates from Veridapt is a string "lat,lng"
                    // format may vary, confirm with client
                    [$lat, $lng] = explode(',', $tank['gpsCoordinates']);
                    return [
                        'asset_id'      => $tank['id'],
                        'code'          => $tank['code'],
                        'name'          => $tank['name'],
                        'latitude'      => (float) trim($lat),
                        'longitude'     => (float) trim($lng),
                        'location_code' => data_get($tank, 'location.code'),
                        'type'          => 'fuel_station',
                        'is_static'     => true,
                    ];
                })
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Veridapt getFuelStationCoordinates exception', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get last known GPS coordinates for fuel trucks
     * Based on their latest transaction (Dispense or Transfer)
     */
    public function getFuelTruckLastPosition(string $siteId): array
    {
        $query = <<<'GRAPHQL'
            query GetFuelTruckPositions($siteId: ID!) {
                site(id: $siteId) {
                    serviceTrucks {
                        edges {
                            node {
                                id
                                equipmentId
                                description
                                gpsCoordinates
                                status
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        try {
            $response = $this->client->query($query, [
                'siteId' => $siteId
            ]);

            if ($response->hasErrors()) {
                Log::error('Veridapt getFuelTruckLastPosition error', [
                    'errors' => $response->getErrors()
                ]);
                return [];
            }

            $trucks = data_get($response->getData(), 'site.serviceTrucks.edges', []);

            return collect($trucks)
                ->map(fn($edge) => $edge['node'])
                ->filter(fn($truck) => !empty($truck['gpsCoordinates']))
                ->map(function ($truck) {
                    [$lat, $lng] = explode(',', $truck['gpsCoordinates']);
                    return [
                        'asset_id'    => $truck['id'],
                        'equipment_id' => $truck['equipmentId'],
                        'description' => $truck['description'],
                        'latitude'    => (float) trim($lat),
                        'longitude'   => (float) trim($lng),
                        'status'      => $truck['status'],
                        'type'        => 'fuel_truck',
                        'is_static'   => false,
                    ];
                })
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Veridapt getFuelTruckLastPosition exception', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get latest transaction-based position for fuel trucks
     * More accurate than static gpsCoordinates on ServiceTruck
     * Use this to update last known position when new transaction occurs
     */
    public function getLatestFuelTruckTransactions(string $siteId, string $updatedFrom): array
    {
        $query = <<<'GRAPHQL'
            query GetLatestTransactions($siteId: ID!, $filter: MovementQuery) {
                site(id: $siteId) {
                    dispenses(filter: $filter) {
                        edges {
                            node {
                                id
                                gpsCoordinates
                                recordCollectedAt
                                source {
                                    code
                                    name
                                }
                                target {
                                    equipmentId
                                    description
                                }
                            }
                        }
                    }
                    transfers(filter: $filter) {
                        edges {
                            node {
                                id
                                gpsCoordinates
                                recordCollectedAt
                                source {
                                    code
                                    name
                                }
                                target {
                                    code
                                    name
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        try {
            $response = $this->client->query($query, [
                'siteId' => $siteId,
                'filter' => [
                    'updatedFrom' => $updatedFrom // ISO8601 e.g. "2024-01-01T00:00:00Z"
                ]
            ]);

            if ($response->hasErrors()) {
                Log::error('Veridapt getLatestFuelTruckTransactions error', [
                    'errors' => $response->getErrors()
                ]);
                return [];
            }

            $data      = $response->getData();
            $dispenses = data_get($data, 'site.dispenses.edges', []);
            $transfers = data_get($data, 'site.transfers.edges', []);

            // Combine and normalize both transaction types
            $transactions = collect([...$dispenses, ...$transfers])
                ->map(fn($edge) => $edge['node'])
                ->filter(fn($tx) => !empty($tx['gpsCoordinates']))
                ->map(function ($tx) {
                    [$lat, $lng] = explode(',', $tx['gpsCoordinates']);
                    return [
                        'transaction_id'   => $tx['id'],
                        'collected_at'     => $tx['recordCollectedAt'],
                        'latitude'         => (float) trim($lat),
                        'longitude'        => (float) trim($lng),
                        'source'           => data_get($tx, 'source.code'),
                        'target_equipment' => data_get($tx, 'target.equipmentId'),
                    ];
                })
                ->sortByDesc('collected_at')
                ->values()
                ->toArray();

            return $transactions;
        } catch (\Exception $e) {
            Log::error('Veridapt getLatestFuelTruckTransactions exception', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }
}
