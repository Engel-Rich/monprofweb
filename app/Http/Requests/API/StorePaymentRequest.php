<?php

namespace App\Http\Requests\API;

use App\Models\PayementServices;
use App\Rules\CameroonMobileNumber;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    private ?PayementServices $resolvedPaymentService = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $serviceId = $this->input('payment_service_id', $this->input('service_id'));

        // Compatibilité avec les applications déjà publiées : elles envoient
        // encore le subscription_id fournisseur au lieu de l'ID local.
        if (blank($serviceId) && filled($this->input('subscription_id'))) {
            $serviceId = PayementServices::query()
                ->where('subscription_id', $this->integer('subscription_id'))
                ->where('sens', 'IN')
                ->where('is_active', true)
                ->whereHas('provider', fn ($query) => $query->where('is_active', true))
                ->value('id');
        }

        $this->merge([
            'payment_service_id' => $serviceId,
            'numero_payeur' => PhoneNumber::local(
                is_scalar($this->input('numero_payeur'))
                    ? (string) $this->input('numero_payeur')
                    : null,
            ),
            'numero_client' => PhoneNumber::local(
                is_scalar($this->input('numero_client'))
                    ? (string) $this->input('numero_client')
                    : null,
            ),
            'sens' => 'IN',
        ]);
    }

    public function rules(): array
    {
        return [
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'nombre_de_code' => ['required', 'integer', 'min:1', 'max:100'],
            'numero_payeur' => ['required', 'string', new CameroonMobileNumber],
            'numero_client' => ['required', 'string', new CameroonMobileNumber],
            'payment_service_id' => ['required', 'integer', 'exists:payment_services,id'],
            'sens' => ['required', 'in:IN'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('payment_service_id')) {
                return;
            }

            $this->resolvedPaymentService = PayementServices::query()
                ->with('provider')
                ->whereKey($this->integer('payment_service_id'))
                ->where('sens', 'IN')
                ->where('is_active', true)
                ->whereHas('provider', fn ($query) => $query->where('is_active', true))
                ->first();

            if (! $this->resolvedPaymentService) {
                $validator->errors()->add(
                    'payment_service_id',
                    'Ce service de paiement entrant est indisponible.',
                );
            }
        });
    }

    public function paymentService(): PayementServices
    {
        return $this->resolvedPaymentService
            ?? PayementServices::query()
                ->with('provider')
                ->whereKey($this->integer('payment_service_id'))
                ->where('sens', 'IN')
                ->where('is_active', true)
                ->whereHas('provider', fn ($query) => $query->where('is_active', true))
                ->firstOrFail();
    }
}
