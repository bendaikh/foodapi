<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryZoneRequest;
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

    public function index(Request $request)
    {
        $query = DeliveryZone::with('branch');

        // Filter by branch_id if provided
        if ($request->has('branch_id') && $request->get('branch_id') !== '') {
            $query->where('branch_id', (int)$request->get('branch_id'));
        }

        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('status', (int)$request->get('status'));
        }
        
        if ($request->has('search') && $request->get('search') !== '') {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('max_distance_km', 'like', '%' . $search . '%')
                  ->orWhereHas('branch', function($branchQuery) use ($search) {
                      $branchQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $method = $request->get('paginate', 1) == 1 ? 'paginate' : 'get';
        $perPage = $request->get('per_page', 10);
        $zones = $query->orderBy('sort_order', 'asc')
                      ->orderBy('max_distance_km', 'asc')
                      ->$method($perPage);

        return DeliveryZoneResource::collection($zones);
    }

    public function store(DeliveryZoneRequest $request)
    {
        $zone = DeliveryZone::create($request->validated());
        $zone->load('branch');
        return new DeliveryZoneResource($zone);
    }

    public function show(DeliveryZone $deliveryZone)
    {
        $deliveryZone->load('branch');
        return new DeliveryZoneResource($deliveryZone);
    }

    public function update(DeliveryZoneRequest $request, DeliveryZone $deliveryZone)
    {
        $deliveryZone->update($request->validated());
        $deliveryZone->load('branch');
        return new DeliveryZoneResource($deliveryZone);
    }

    public function destroy(DeliveryZone $deliveryZone)
    {
        try {
            $deliveryZone->delete();
            return response()->json(['message' => 'Deleted'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get zones by branch
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
     * Detect zone by customer location (latitude/longitude)
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


