<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\StorePayementServiceRequest;
use App\Http\Requests\API\UpdatePayementServiceRequest;
use App\Models\PayementServices;
use Illuminate\Http\Request;

class PayementServicesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $services = PayementServices::where('is_active', 1)->get();
            return response()->json(['status' => true, 'data' => $services, 'error' => null]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => 'null', 'error' => $th->getMessage(),], 500);
        }
    }

     public function store(StorePayementServiceRequest $request)
    {
        try {
            $service = PayementServices::create($request->validated());

            return response()->json([
                'status' => true,
                'data' => $service,
                'message' => 'Service created successfully'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show one service
     */
    public function show($id)
    {
        try {
            $service = PayementServices::findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $service
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'Service not found'
            ], 404);
        }
    }

    /**
     * Update service
     */
    public function update(UpdatePayementServiceRequest $request, $id)
    {
        try {
            $service = PayementServices::findOrFail($id);

            $service->update($request->validated());

            return response()->json([
                'status' => true,
                'data' => $service,
                'message' => 'Service updated successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Delete service (soft logique avec is_active recommandé)
     */
    public function destroy($id)
    {
        try {
            $service = PayementServices::findOrFail($id);
            $service->update(['is_active' => 0]);

            return response()->json([
                'status' => true,
                'message' => 'Service deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
