<?php

namespace App\Http\Controllers;

use App\Http\Requests\API\ListPaymentServicesRequest;
use App\Http\Requests\API\StorePayementServiceRequest;
use App\Http\Requests\API\UpdatePayementServiceRequest;
use App\Models\PayementServices;
use App\Services\FileManager;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PayementServicesController extends Controller
{
    public function index(ListPaymentServicesRequest $request): JsonResponse
    {
        try {
            $isManagementRoute = $request->routeIs('payment-services.*');
            $query = PayementServices::query();

            if (! $isManagementRoute) {
                $query->where('is_active', true)
                    ->where('sens', 'IN')
                    ->whereHas('provider', fn ($query) => $query->where('is_active', true))
                    ->orderBy('id');
            } else {
                $query
                    ->with('provider')
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $search = '%'.$request->string('search')->trim().'%';
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'like', $search)
                                ->orWhere('subtitle', 'like', $search)
                                ->orWhere('description', 'like', $search);
                        });
                    })
                    ->when($request->filled('provider_id'), fn ($query) => $query->where('payment_provider_id', $request->integer('provider_id')))
                    ->when($request->filled('sens'), fn ($query) => $query->where('sens', $request->string('sens')->upper()))
                    ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
                    ->latest();
            }

            $services = $query->get();

            if (! $isManagementRoute) {
                $services->each->makeHidden(['payment_provider_id', 'provider']);
            }

            return response()->json([
                'status' => true,
                'data' => $services,
                'error' => null,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function store(StorePayementServiceRequest $request): JsonResponse
    {
        $uploadedFilename = null;

        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $uploadedFilename = $this->files()->store($request->file('image'))
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['img'] = $uploadedFilename;
            }

            unset($data['image']);
            $service = PayementServices::create($data)->load('provider');

            return response()->json([
                'status' => true,
                'data' => $service,
                'message' => 'Service de paiement créé avec succès.',
            ], 201);
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function show(PayementServices $payment_service): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $payment_service->load('provider'),
        ]);
    }

    public function update(UpdatePayementServiceRequest $request, PayementServices $payment_service): JsonResponse
    {
        $uploadedFilename = null;
        $previousImage = $payment_service->img;

        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $uploadedFilename = $this->files()->store($request->file('image'))
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['img'] = $uploadedFilename;
            }

            unset($data['image']);
            $payment_service->update($data);

            if ($uploadedFilename && $this->isManagedImage($previousImage)) {
                $this->files()->delete($previousImage);
            }

            return response()->json([
                'status' => true,
                'data' => $payment_service->fresh('provider'),
                'message' => 'Service de paiement mis à jour avec succès.',
            ]);
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            return response()->json([
                'status' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function destroy(PayementServices $payment_service): JsonResponse
    {
        $payment_service->update(['is_active' => false]);

        return response()->json([
            'status' => true,
            'message' => 'Service de paiement désactivé avec succès.',
        ]);
    }

    private function files(): FileManager
    {
        return app(FileManager::class, ['filefolder' => 'payment/services']);
    }

    private function isManagedImage(?string $image): bool
    {
        return filled($image)
            && ! str_starts_with($image, 'http://')
            && ! str_starts_with($image, 'https://')
            && ! str_starts_with($image, '/')
            && ! str_starts_with($image, 'images/')
            && ! str_starts_with($image, 'storage/');
    }
}
