<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    /**
     * L'administration ne doit pas proposer de créer un compte depuis l'écran
     * de connexion : les comptes y sont créés par un administrateur.
     */
    public function test_the_login_page_does_not_offer_self_registration(): void
    {
        $response = $this->get(route('auth.login'));

        $response->assertOk()
            ->assertDontSee(route('auth.register'))
            ->assertDontSee('Créer un compte');
    }
}
