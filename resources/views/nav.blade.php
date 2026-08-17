@extends('base')

@php
    $isAdmin = (int) auth()->user()->rule_id === 1;
    $navigation = collect([
        ['label' => 'Tableau de bord', 'icon' => 'dashboard', 'section' => 'principal', 'url' => route('index'), 'active' => request()->routeIs('index')],
        ['label' => 'Statistiques', 'icon' => 'statistics', 'section' => 'principal', 'url' => route('statistiques'), 'active' => request()->routeIs('statistiques')],
        ['label' => 'Paiements', 'icon' => 'payments', 'section' => 'principal', 'url' => route('paiement.index'), 'active' => request()->routeIs('paiement.*'), 'admin' => true],
        ['label' => 'Services de paiement', 'icon' => 'payments', 'section' => 'principal', 'url' => route('admin.payment-services.index'), 'active' => request()->routeIs('admin.payment-services.*'), 'admin' => true],
        ['label' => 'Fournisseurs', 'icon' => 'payments', 'section' => 'principal', 'url' => route('admin.payment-providers.index'), 'active' => request()->routeIs('admin.payment-providers.*'), 'admin' => true],
        ['label' => 'Codes', 'icon' => 'codes', 'section' => 'principal', 'url' => route('codes.index', 'all'), 'active' => request()->routeIs('codes.*'), 'admin' => true],
        ['label' => 'Classes', 'icon' => 'classes', 'section' => 'contenu', 'url' => route('classe.index'), 'active' => request()->routeIs('classe.*'), 'admin' => true],
        ['label' => 'Matières', 'icon' => 'subjects', 'section' => 'contenu', 'url' => route('matiere.index'), 'active' => request()->routeIs('matiere.*'), 'admin' => true],
        ['label' => 'Catégories', 'icon' => 'categories', 'section' => 'contenu', 'url' => route('categorie.index'), 'active' => request()->routeIs('categorie.*'), 'admin' => true],
        ['label' => 'Cours', 'icon' => 'courses', 'section' => 'contenu', 'url' => route('cours.index'), 'active' => request()->routeIs('cours.*'), 'admin' => true],
        ['label' => 'Élèves', 'icon' => 'students', 'section' => 'contenu', 'url' => route('eleve.index'), 'active' => request()->routeIs('eleve.*'), 'admin' => true],
        ['label' => 'Enseignants', 'icon' => 'teachers', 'section' => 'contenu', 'url' => route('professeur.index'), 'active' => request()->routeIs('professeur.*'), 'admin' => true],
        ['label' => 'Partenaires', 'icon' => 'teachers', 'section' => 'contenu', 'url' => route('partner.index'), 'active' => request()->routeIs('partner.*'), 'admin' => true],
        ['label' => 'Questions', 'icon' => 'questions', 'section' => 'support', 'url' => route('question.index'), 'active' => request()->routeIs('question.*'), 'admin' => true],
        ['label' => 'Messages', 'icon' => 'messages', 'section' => 'support', 'url' => route('messages.index'), 'active' => request()->routeIs('messages.*'), 'admin' => true],
        ['label' => 'Suggestions', 'icon' => 'suggestions', 'section' => 'support', 'url' => route('index.suggestion'), 'active' => request()->routeIs('index.suggestion'), 'admin' => true],
    ])->filter(fn ($item) => !($item['admin'] ?? false) || $isAdmin)->values()->all();

    $adminUser = [
        'name' => auth()->user()->name,
        'lastName' => auth()->user()->last_name,
        'email' => auth()->user()->email,
    ];
@endphp

@section('nav')
    <div id="admin-app">
        <admin-shell
            :items="{{ Js::from($navigation) }}"
            :user="{{ Js::from($adminUser) }}"
            logout-url="{{ route('auth.logout') }}"
        >
            @yield('content')
        </admin-shell>
    </div>
@endsection
