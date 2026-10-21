<?php

namespace App\DTO;

use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;

/**
 * Résultat d'une finalisation de paiement, transmis à l'envoi des
 * notifications une fois les verrous libérés.
 */
final class PaymentFinalization
{
    /** @param array<int, string> $codes */
    public function __construct(
        public readonly Paiements $paiement,
        public readonly User $user,
        public readonly array $codes,
        public readonly ?Codes $activatedCode = null,
    ) {}
}
