<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use App\Services\FileManager;
use App\Services\Payments\PaymentFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class PaymentProvidersController extends Controller
{
    public function index(): View
    {
        $this->ensureAdministrator();

        return view('screen.payment_providers.index', [
            'providers' => PaymentProvider::withCount('services')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->ensureAdministrator();

        return view('screen.payment_providers.form', ['provider' => new PaymentProvider]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdministrator();
        $data = $this->validated($request);
        $uploadedFilename = null;

        try {
            if ($request->hasFile('image_file')) {
                $uploadedFilename = $this->storeImage($request)
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['image'] = $uploadedFilename;
            }
            unset($data['image_file']);

            DB::transaction(function () use ($data) {
                if ($data['is_active']) {
                    PaymentProvider::query()->update(['is_active' => false]);
                }
                PaymentProvider::create($data);
            });

            return to_route('admin.payment-providers.index')->with('success', 'Fournisseur créé avec succès.');
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            return back()->withErrors(['error' => $exception->getMessage()])->withInput();
        }
    }

    public function edit(PaymentProvider $payment_provider): View
    {
        $this->ensureAdministrator();

        return view('screen.payment_providers.form', ['provider' => $payment_provider->loadCount('services')]);
    }

    public function update(Request $request, PaymentProvider $payment_provider): RedirectResponse
    {
        $this->ensureAdministrator();
        $data = $this->validated($request, $payment_provider);

        if (! $data['is_active'] && $payment_provider->is_active) {
            throw ValidationException::withMessages([
                'is_active' => 'Activez d’abord un autre fournisseur afin de conserver un moyen de paiement opérationnel.',
            ]);
        }

        $uploadedFilename = null;
        $previousImage = $payment_provider->image;

        try {
            if ($request->hasFile('image_file')) {
                $uploadedFilename = $this->storeImage($request)
                    ?? throw new RuntimeException('Le téléversement de l’image a échoué.');
                $data['image'] = $uploadedFilename;
            }
            unset($data['image_file']);

            DB::transaction(function () use ($data, $payment_provider) {
                if ($data['is_active']) {
                    PaymentProvider::query()->whereKeyNot($payment_provider->id)->update(['is_active' => false]);
                }
                $payment_provider->update($data);
            });

            if ($uploadedFilename && $this->isManagedImage($previousImage)) {
                $this->files()->delete($previousImage);
            }

            return to_route('admin.payment-providers.index')->with('success', 'Fournisseur mis à jour avec succès.');
        } catch (\Throwable $exception) {
            if ($uploadedFilename) {
                $this->files()->delete($uploadedFilename);
            }

            return back()->withErrors(['error' => $exception->getMessage()])->withInput();
        }
    }

    public function destroy(PaymentProvider $payment_provider): RedirectResponse
    {
        $this->ensureAdministrator();

        if ($payment_provider->is_active) {
            return back()->withErrors(['error' => 'Le fournisseur actif ne peut pas être supprimé. Activez-en un autre auparavant.']);
        }

        if ($payment_provider->transactions()->whereIn('status', ['PENDING', 'PROCESSING'])->exists()) {
            return back()->withErrors(['error' => 'Ce fournisseur possède encore des transactions en attente et ne peut pas être supprimé.']);
        }

        $image = $payment_provider->image;
        $payment_provider->delete();

        if ($this->isManagedImage($image)) {
            $this->files()->delete($image);
        }

        return to_route('admin.payment-providers.index')->with('success', 'Fournisseur supprimé avec succès.');
    }

    private function validated(Request $request, ?PaymentProvider $provider = null): array
    {
        $request->merge(['code' => Str::upper((string) $request->input('code'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:payment_providers,code'.($provider ? ','.$provider->id : '')],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['is_active'] && ! PaymentFactory::supports($data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Aucune stratégie technique n’est configurée pour ce code fournisseur.',
            ]);
        }

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        return $this->files()->store($request->file('image_file'));
    }

    private function files(): FileManager
    {
        return app(FileManager::class, ['filefolder' => 'payment/providers']);
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
