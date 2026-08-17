<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayementServices;
use App\Models\PaymentProvider;
use App\Services\FileManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PaymentServicesController extends Controller
{
    public function index(): View
    {
        $this->ensureAdministrator();

        return view('screen.payment_services.index', [
            'services' => PayementServices::with('provider')->latest()->paginate(20),
            'providers' => PaymentProvider::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->ensureAdministrator();

        return view('screen.payment_services.form', [
            'service' => new PayementServices(['status' => 1, 'is_active' => true]),
            'providers' => PaymentProvider::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdministrator();
        $data = $this->validated($request);
        $uploadedFilename = null;

        try {
            if ($request->hasFile('image')) {
                $uploadedFilename = $this->files()->store($request->file('image'))
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['img'] = $uploadedFilename;
            }
            unset($data['image']);
            PayementServices::create($data);

            return to_route('admin.payment-services.index')->with('success', 'Service de paiement créé avec succès.');
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            return back()->withErrors(['error' => $exception->getMessage()])->withInput();
        }
    }

    public function edit(PayementServices $payment_service): View
    {
        $this->ensureAdministrator();

        return view('screen.payment_services.form', [
            'service' => $payment_service,
            'providers' => PaymentProvider::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PayementServices $payment_service): RedirectResponse
    {
        $this->ensureAdministrator();
        $data = $this->validated($request);
        $uploadedFilename = null;
        $previousImage = $payment_service->img;

        try {
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

            return to_route('admin.payment-services.index')->with('success', 'Service de paiement mis à jour avec succès.');
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            return back()->withErrors(['error' => $exception->getMessage()])->withInput();
        }
    }

    public function destroy(PayementServices $payment_service): RedirectResponse
    {
        $this->ensureAdministrator();
        $payment_service->update(['is_active' => false]);

        return to_route('admin.payment-services.index')->with('success', 'Service de paiement désactivé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'payment_provider_id' => ['required', 'integer', 'exists:payment_providers,id'],
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'integer'],
            'is_active' => ['required', 'boolean'],
            'reg_exp' => ['nullable', 'string', 'max:500'],
            'subscription_id' => ['nullable', 'integer'],
            'sens' => ['required', 'in:IN,OUT'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    private function files(): FileManager
    {
        return app(FileManager::class, ['filefolder' => 'payment/services']);
    }

    private function isManagedImage(?string $image): bool
    {
        return filled($image) && ! Str::startsWith($image, ['http://', 'https://', '/', 'images/', 'storage/']);
    }

    private function ensureAdministrator(): void
    {
        abort_unless((int) auth()->user()->rule_id === 1, 403);
    }
}
