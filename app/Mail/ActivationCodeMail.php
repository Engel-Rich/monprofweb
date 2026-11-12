<?php

namespace App\Mail;

use App\Models\Codes;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Envoyé de façon synchrone : aucun worker de file ne tourne en production.
 */
class ActivationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Codes $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre code d’activation MonProf');
    }

    public function content(): Content
    {
        $user = $this->code->paiement?->user;

        return new Content(
            markdown: 'emails.activation-code',
            with: [
                'name' => trim(($user?->name ?? '').' '.($user?->last_name ?? '')),
                'activationCode' => $this->code->code,
                'category' => $this->code->paiement?->categorie?->libelle,
            ],
        );
    }
}
