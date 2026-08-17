<?php

namespace App\Http\Controllers;

use App\Models\PaymentProvider;
use App\Services\FileManager;
use App\Services\Payments\PaymentFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $providers = PaymentProvider::query()
            ->withCount('services')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('code', 'like', $search));
            })
            ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'data' => $providers, 'error' => null]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $uploadedFilename = null;

        try {
            if ($request->hasFile('image_file')) {
                $uploadedFilename = $this->files()->store($request->file('image_file'))
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['image'] = $uploadedFilename;
            }

            unset($data['image_file']);
            $provider = DB::transaction(function () use ($data) {
                if ($data['is_active'] ?? false) {
                    PaymentProvider::query()->update(['is_active' => false]);
                }

                return PaymentProvider::create($data);
            });

            return response()->json(['status' => true, 'data' => $provider->loadCount('services')], 201);
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            throw $exception;
        }
    }

    public function show(PaymentProvider $payment_provider): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $payment_provider->loadCount('services')->load('services'),
        ]);
    }

    public function update(Request $request, PaymentProvider $payment_provider): JsonResponse
    {
        $data = $this->validated($request, $payment_provider);
        $uploadedFilename = null;
        $previousImage = $payment_provider->image;

        if (array_key_exists('is_active', $data) && ! $data['is_active'] && $payment_provider->is_active) {
            throw ValidationException::withMessages([
                'is_active' => 'Activez d’abord un autre fournisseur afin de conserver un moyen de paiement opérationnel.',
            ]);
        }

        try {
            if ($request->hasFile('image_file')) {
                $uploadedFilename = $this->files()->store($request->file('image_file'))
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['image'] = $uploadedFilename;
            }

            unset($data['image_file']);
            DB::transaction(function () use ($data, $payment_provider) {
                if ($data['is_active'] ?? false) {
                    PaymentProvider::query()->whereKeyNot($payment_provider->id)->update(['is_active' => false]);
                }
                $payment_provider->update($data);
            });

            if ($uploadedFilename && $this->isManagedImage($previousImage)) {
                $this->files()->delete($previousImage);
            }

            return response()->json([
                'status' => true,
                'data' => $payment_provider->fresh()->loadCount('services'),
            ]);
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            throw $exception;
        }
    }

    public function destroy(PaymentProvider $payment_provider): JsonResponse
    {
        if ($payment_provider->is_active) {
            throw ValidationException::withMessages([
                'provider' => 'Le fournisseur actif ne peut pas être supprimé. Activez-en un autre auparavant.',
            ]);
        }

        $image = $payment_provider->image;
        $payment_provider->delete();

        if ($this->isManagedImage($image)) {
            $this->files()->delete($image);
        }

        return response()->json(['status' => true, 'message' => 'Fournisseur supprimé avec succès.']);
    }

    private function validated(Request $request, ?PaymentProvider $provider = null): array
    {
        if ($request->has('code')) {
            $request->merge(['code' => Str::upper((string) $request->input('code'))]);
        }
        $required = $provider ? 'sometimes' : 'required';
        $data = $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'code' => [$required, 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:payment_providers,code'.($provider ? ','.$provider->id : '')],
            'image' => ['nullable', 'string', 'max:2048'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $code = $data['code'] ?? $provider?->code;
        $willBeActive = $data['is_active'] ?? $provider?->is_active ?? false;
        if ($willBeActive && ! PaymentFactory::supports($code)) {
            throw ValidationException::withMessages([
                'code' => 'Aucune stratégie technique n’est configurée pour ce code fournisseur.',
            ]);
        }

        return $data;
    }

    private function files(): FileManager
    {
        return app(FileManager::class, ['filefolder' => 'payment/providers']);
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
