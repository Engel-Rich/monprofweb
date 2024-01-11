@extends('nav')


@section('content')     
    <div class="row">
        <div class="col">
            <h1 class="display-5"> Matieres </h1>
        </div>
        <div class="col">
            <a href="{{ route('matiere.create') }}">
                <button class="btn btn-outline-primary ">Ajouter une matière</button>
            </a>
        </div>
    </div>    

    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Nom de la Matière</th>
                <th scope="col" class="display-7 fw-bold">Description</th>                
                <th scope="col" class="display-7 fw-bold">Action</th>
                <th scope="col" class="display-7 fw-bold">Classe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($matieres as $matiere)
            <tr>
                <th scope="row">{{$matiere->libelle  }}</th>
                {{-- <td>{{$matiere->libelle  }}</td>                 --}}
                <td>{{ $matiere->description }}</td>
                <td><a href="{{route('matiere.create')}}">modifier</a></td>
                <td><a href="{{route('matiere.create')}}">Ajouter à une classe</a></td>
            </tr>
            @endforeach                            
        </tbody>
    </table>

@endsection