@extends('nav')


@section('content')
    <div class="row">
        <div class="col">
            <h1 class="display-5"> Classes </h1>
        </div>
        <div class="col">
            <a href="{{ route('classe.create') }}">
                <button class="btn btn-outline-primary "> Ajouter une nouvelle classe</button>
            </a>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Nom de la classe</th>
                <th scope="col" class="display-7 fw-bold">Abréviation</th>
                <th scope="col" class="display-7 fw-bold">Description</th>
                <th scope="col" class="display-7 fw-bold">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classes as $classe)
            <tr>
                <th scope="row">{{$classe->libelle }}</th>
                {{-- <td>{{$classe->libelle  }}</td> --}}
                <td>{{ $classe->short_name }}</td>
                <td>{{ $classe->description }}</td>
                <td><a href="{{route('classe.create')}}">modifier</a></td>
            </tr>
            @endforeach                            
        </tbody>
    </table>
@endsection
