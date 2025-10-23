<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Resources\UserPointResource;
use Exception;
use App\Services\PointService;
use App\Http\Controllers\Controller;

class PointController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private PointService $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function userPointsChecking() 
    {
        try {
            return new UserPointResource($this->pointService->userPointsChecking());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
