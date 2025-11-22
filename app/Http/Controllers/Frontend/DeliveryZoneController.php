<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryZoneResource;
use App\Models\Branch;
use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;
use Exception;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function __construct(public DeliveryZoneService $deliveryZoneService)
    {
    }

    /**
     * Get all active delivery zones (optionally filtered by branch)
     */
    public function index(Request $request)
    {
        $query = DeliveryZone::where('status', Status::ACTIVE);

        // Filter by branch_id if provided
        if ($request->has('branch_id') && $request->get('branch_id') !== '') {
            $query->where('branch_id', (int)$request->get('branch_id'));
        }

        $zones = $query->orderBy('sort_order', 'asc')
                      ->orderBy('max_distance_km', 'asc')
                      ->get();
        
        return DeliveryZoneResource::collection($zones);
    }

    /**
     * Get zones for a specific branch
     */
    public function getByBranch(Branch $branch)
    {
        try {
            $zones = $this->deliveryZoneService->getZonesByBranch($branch);
            return DeliveryZoneResource::collection($zones);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Detect zone and get delivery price for customer location
     */
    public function detectZone(Request $request, Branch $branch)
    {
        try {
            $request->validate([
                'latitude' => ['required', 'numeric'],
                'longitude' => ['required', 'numeric'],
            ]);

            $result = $this->deliveryZoneService->getDeliveryPrice(
                $branch,
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            return response([
                'status' => true,
                'data' => [
                    'zone' => new DeliveryZoneResource($result['zone']),
                    'delivery_price' => $result['delivery_price'],
                    'distance_km' => round($result['distance_km'], 2),
                ]
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}


