@extends('nav')

@section('title', 'Statistiques')

@section('content')
    <admin-statistics
        :data="{{ Js::from($statistics) }}"
        action="{{ route('statistiques') }}"
    ></admin-statistics>
@endsection
