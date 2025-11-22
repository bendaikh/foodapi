<?php

namespace App\Services;

use Exception;
use App\Enums\Status;
use App\Models\Branch;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Libraries\QueryExceptionLibrary;

class DeliveryZoneService
{
    /**
     * Calculate distance between two coordinates in kilometers using Haversine formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lng1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lng2 Longitude of second point
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Detect which delivery zone a customer location falls into based on distance from branch
     *
     * @param Branch $branch The branch to check zones for
     * @param float $customerLatitude Customer's latitude
     * @param float $customerLongitude Customer's longitude
     * @return DeliveryZone|null The matching delivery zone or null if out of service area
     * @throws Exception
     */
    public function detectZoneByDistance(Branch $branch, float $customerLatitude, float $customerLongitude): ?DeliveryZone
    {
        try {
            // Check if branch has latitude and longitude
            if (empty($branch->latitude) || empty($branch->longitude)) {
                throw new Exception('Branch location (latitude/longitude) is not set', 422);
            }

            $branchLat = (float) $branch->latitude;
            $branchLng = (float) $branch->longitude;

            // Calculate distance from branch to customer location
            $distance = $this->calculateDistance($branchLat, $branchLng, $customerLatitude, $customerLongitude);

            // Get all active delivery zones for this branch, ordered by sort_order (ascending)
            // This ensures we check zones in order (e.g., 0-5km, then 5-10km, then 10-15km)
            $zones = DeliveryZone::where('branch_id', $branch->id)
                ->where('status', Status::ACTIVE)
                ->orderBy('sort_order', 'asc')
                ->orderBy('max_distance_km', 'asc')
                ->get();

            // Find the first zone where the distance is within the max_distance_km
            foreach ($zones as $zone) {
                if ($distance <= (float) $zone->max_distance_km) {
                    return $zone;
                }
            }

            // If no zone matches, customer is out of service area
            return null;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Get delivery price for a customer location based on delivery zones
     *
     * @param Branch $branch The branch
     * @param float $customerLatitude Customer's latitude
     * @param float $customerLongitude Customer's longitude
     * @return array Returns array with 'zone' (DeliveryZone object) and 'delivery_price' (float)
     * @throws Exception
     */
    public function getDeliveryPrice(Branch $branch, float $customerLatitude, float $customerLongitude): array
    {
        $zone = $this->detectZoneByDistance($branch, $customerLatitude, $customerLongitude);

        if ($zone === null) {
            throw new Exception(trans('all.message.out_of_service_area'), 422);
        }

        return [
            'zone' => $zone,
            'delivery_price' => (float) $zone->delivery_price,
            'distance_km' => $this->calculateDistance(
                (float) $branch->latitude,
                (float) $branch->longitude,
                $customerLatitude,
                $customerLongitude
            )
        ];
    }

    /**
     * Get all delivery zones for a branch
     *
     * @param Branch $branch
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getZonesByBranch(Branch $branch)
    {
        return DeliveryZone::where('branch_id', $branch->id)
            ->where('status', Status::ACTIVE)
            ->orderBy('sort_order', 'asc')
            ->orderBy('max_distance_km', 'asc')
            ->get();
    }
}

