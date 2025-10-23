<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PointSetupRequest;
use App\Http\Resources\PointSetupResource;
use App\Services\PointSetupService;
use Exception;

class PointSetupController extends AdminController
{
    public PointSetupService $pointSetupService;

    public function __construct(PointSetupService $pointSetupService)
    {
        parent::__construct();
        $this->pointSetupService = $pointSetupService;
        $this->middleware(['permission:settings'])->only('update');
    }

    public function index(
    ) : \Illuminate\Http\Response | PointSetupResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new PointSetupResource($this->pointSetupService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(PointSetupRequest $request
    ) : \Illuminate\Http\Response | pointSetupResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory {
        try {
            return new pointSetupResource($this->pointSetupService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
