@extends('nav')

@section('title', 'Tableau de bord')

@section('content')
    @php
        $dashboard['userName'] = auth()->user()->name;
        $dashboardLinks = [
            'createCourse' => route('cours.create'),
            'courses' => route('cours.index'),
            'students' => route('eleve.index'),
            'payments' => route('paiement.index'),
            'codes' => route('codes.index', 'all'),
            'questions' => route('question.index'),
            'statistics' => route('statistiques'),
        ];
    @endphp

    <admin-dashboard
        :data="{{ Js::from($dashboard) }}"
        :links="{{ Js::from($dashboardLinks) }}"
    ></admin-dashboard>
@endsection
