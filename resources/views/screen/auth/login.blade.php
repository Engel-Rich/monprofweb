@extends('base')

@section('login')
<main class="auth-page">
    <section class="auth-shell" aria-labelledby="login-title">
        <aside class="auth-brand-panel">
            <div>
                <div class="auth-brand-logo">
                    <img src="{{ asset('images/mp2.png') }}" alt="MonProf" width="270" height="94">
                </div>
                <span class="auth-brand-badge">Console administrateur</span>
                <h1>Gérez MonProf avec clarté et efficacité.</h1>
                <p>Retrouvez dans un espace unique les contenus pédagogiques, les utilisateurs et le suivi de l’activité.</p>

                <ul class="auth-benefits" aria-label="Fonctionnalités de la console">
                    <li><span aria-hidden="true">✓</span> Pilotage centralisé de la plateforme</li>
                    <li><span aria-hidden="true">✓</span> Suivi des cours et des paiements</li>
                    <li><span aria-hidden="true">✓</span> Accès réservé aux administrateurs</li>
                </ul>
            </div>

            <div class="auth-brand-footer">
                <img src="{{ asset('images/logo.png') }}" alt="" width="28" height="28">
                <span>MonProf · L’éducation à portée de tous</span>
            </div>
        </aside>

        <div class="auth-form-panel">
            <div class="auth-mobile-brand">
                <img src="{{ asset('images/mp2.png') }}" alt="MonProf" width="180" height="63">
            </div>

            <div class="auth-form-header">
                <span>Espace sécurisé</span>
                <h2 id="login-title">Bienvenue</h2>
                <p>Connectez-vous pour accéder à la console d’administration MonProf.</p>
            </div>

            @if ($errors->any())
                <div class="auth-alert" role="alert">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/>
                    </svg>
                    <div>
                        <strong>Connexion impossible</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <form class="auth-form" method="post" action="{{ route('auth.signin') }}">
                @csrf

                <label class="auth-field" for="email">
                    <span>Adresse e-mail</span>
                    <div>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>
                        </svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="admin@monprof.cm" autocomplete="email" required autofocus>
                    </div>
                    @error('email')<small>{{ $message }}</small>@enderror
                </label>

                <label class="auth-field" for="password">
                    <span>Mot de passe</span>
                    <div>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <input id="password" type="password" name="password" placeholder="Votre mot de passe" autocomplete="current-password" required>
                    </div>
                    @error('password')<small>{{ $message }}</small>@enderror
                </label>

                <button class="auth-submit" type="submit">
                    <span>Se connecter</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>
            </form>

            <p class="auth-register">Vous n’avez pas encore de compte ? <a href="{{ route('auth.register') }}">Créer un compte</a></p>
            <p class="auth-security">Vos identifiants sont transmis via une connexion sécurisée.</p>
        </div>
    </section>
</main>
@endsection

@section('login-style')
<style>
    .auth-page {
        display: grid;
        min-height: 100vh;
        place-items: center;
        padding: 32px;
        background: #eef3f8;
    }

    .auth-shell {
        display: grid;
        width: min(1040px, 100%);
        min-height: 650px;
        grid-template-columns: minmax(0, 1.05fr) minmax(400px, .95fr);
        overflow: hidden;
        background: #fff;
        border: 1px solid #dfe6ee;
        border-radius: 24px;
        box-shadow: 0 24px 70px rgba(25, 48, 77, .13);
    }

    .auth-brand-panel {
        position: relative;
        display: flex;
        justify-content: space-between;
        flex-direction: column;
        overflow: hidden;
        padding: 58px 56px 40px;
        color: #fff;
        background: #103b67;
    }

    .auth-brand-panel::before,
    .auth-brand-panel::after {
        position: absolute;
        content: '';
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 50%;
        pointer-events: none;
    }

    .auth-brand-panel::before { right: -150px; bottom: -150px; width: 390px; height: 390px; }
    .auth-brand-panel::after { right: -65px; bottom: -65px; width: 220px; height: 220px; }
    .auth-brand-panel > * { position: relative; z-index: 1; }

    .auth-brand-logo {
        display: flex;
        width: fit-content;
        min-height: 92px;
        align-items: center;
        padding: 10px 18px;
        background: #fff;
        border-radius: 16px;
    }

    .auth-brand-logo img { display: block; width: 240px; height: auto; }
    .auth-brand-badge { display: block; margin-top: 38px; color: #90d8f4; font-size: 11px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; }
    .auth-brand-panel h1 { max-width: 430px; margin: 13px 0 15px; font: 800 clamp(29px, 3.1vw, 42px)/1.15 'Manrope', sans-serif; letter-spacing: -.035em; }
    .auth-brand-panel > div > p { max-width: 440px; margin: 0; color: #c9d8e8; font-size: 14px; line-height: 1.65; }
    .auth-benefits { display: grid; gap: 13px; margin: 34px 0 0; padding: 0; list-style: none; }
    .auth-benefits li { display: flex; align-items: center; gap: 10px; color: #e2ebf4; font-size: 13px; }
    .auth-benefits li span { display: grid; width: 23px; height: 23px; flex: 0 0 auto; place-items: center; color: #0f5786; font-size: 11px; font-weight: 800; background: #9edff4; border-radius: 50%; }
    .auth-brand-footer { display: flex; align-items: center; gap: 10px; margin-top: 36px; color: #9fb4c9; font-size: 11px; }
    .auth-brand-footer img { width: 28px; height: 28px; object-fit: contain; }

    .auth-form-panel { display: flex; justify-content: center; flex-direction: column; padding: 58px 62px; }
    .auth-mobile-brand { display: none; }
    .auth-form-header > span { color: #0f78b8; font-size: 10px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .auth-form-header h2 { margin: 9px 0 8px; color: #15273b; font: 800 32px/1.15 'Manrope', sans-serif; letter-spacing: -.03em; }
    .auth-form-header p { margin: 0; color: #748092; font-size: 13px; line-height: 1.55; }

    .auth-alert { display: flex; align-items: flex-start; gap: 11px; margin-top: 23px; padding: 12px 14px; color: #a82f3b; background: #fff1f2; border: 1px solid #f4c5ca; border-radius: 10px; }
    .auth-alert svg { width: 19px; height: 19px; flex: 0 0 auto; }
    .auth-alert strong, .auth-alert span { display: block; }
    .auth-alert strong { font-size: 11px; }
    .auth-alert span { margin-top: 3px; font-size: 10px; }

    .auth-form { display: grid; gap: 19px; margin-top: 30px; }
    .auth-field { display: grid; gap: 8px; }
    .auth-field > span { color: #344256; font-size: 11px; font-weight: 700; }
    .auth-field > div { display: flex; height: 48px; align-items: center; gap: 10px; padding: 0 13px; background: #fff; border: 1px solid #d9e0e8; border-radius: 10px; transition: border-color .18s, box-shadow .18s; }
    .auth-field > div:focus-within { border-color: #0f78b8; box-shadow: 0 0 0 3px rgba(15, 120, 184, .1); }
    .auth-field svg { width: 19px; height: 19px; flex: 0 0 auto; color: #8895a5; }
    .auth-field input { width: 100%; min-width: 0; color: #1d2d40; font-size: 13px; background: transparent; border: 0; outline: 0; }
    .auth-field input::placeholder { color: #a3acb8; }
    .auth-field small { color: #b3313e; font-size: 10px; }

    .auth-submit { display: flex; height: 48px; align-items: center; justify-content: center; gap: 10px; margin-top: 4px; color: #fff; font-size: 13px; font-weight: 800; background: #0f78b8; border: 0; border-radius: 10px; cursor: pointer; box-shadow: 0 10px 22px rgba(15, 120, 184, .2); transition: background .18s, transform .18s; }
    .auth-submit:hover { background: #0b659c; transform: translateY(-1px); }
    .auth-submit:focus-visible { outline: 3px solid rgba(15, 120, 184, .24); outline-offset: 3px; }
    .auth-submit svg { width: 18px; height: 18px; }
    .auth-register { margin: 27px 0 0; color: #7b8795; text-align: center; font-size: 11px; }
    .auth-register a { color: #0f78b8; font-weight: 800; }
    .auth-register a:hover { text-decoration: underline; }
    .auth-security { margin: 28px 0 0; color: #a0a8b3; text-align: center; font-size: 9px; }

    @media (max-width: 860px) {
        .auth-page { padding: 20px; }
        .auth-shell { min-height: auto; grid-template-columns: 1fr; }
        .auth-brand-panel { display: none; }
        .auth-form-panel { min-height: 620px; padding: 48px; }
        .auth-mobile-brand { display: flex; width: fit-content; margin-bottom: 46px; padding: 8px 12px; background: #f3f6f9; border-radius: 11px; }
        .auth-mobile-brand img { width: 180px; height: auto; }
    }

    @media (max-width: 520px) {
        .auth-page { display: block; min-height: 100vh; padding: 0; background: #fff; }
        .auth-shell { min-height: 100vh; border: 0; border-radius: 0; box-shadow: none; }
        .auth-form-panel { min-height: 100vh; padding: 34px 24px; }
        .auth-mobile-brand { margin-bottom: 38px; }
        .auth-form-header h2 { font-size: 28px; }
    }
</style>
@endsection
