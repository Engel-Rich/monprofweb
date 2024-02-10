@extends('nav')


@section('content')
    <form action="" class="form">
        <div class="row">

            <div class="col-md-3">
                <h1 class="display-5">Elèves </h1>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control", placeholder="Recherche">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary" type="submit">Rechercher</button>
            </div>

        </div>
    </form>


    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Nom </th>
                <th scope="col" class="display-7 fw-bold">Prénom</th>
                <th scope="col" class="display-7 fw-bold">Téléphone</th>
                <th scope="col" class="display-7 fw-bold">Email</th>
                <th scope="col" class="display-7 fw-bold">Etablissement</th>
                <th scope="col" class="display-7 fw-bold">Classe</th>
                <th scope="col" class="display-7 fw-bold">Sexe</th>
                <th scope="col" class="display-7 fw-bold">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($eleves as $eleve)
                <tr>
                    <th scope="row">{{ $eleve->user->name }}</th>
                    {{-- <td>{{$matiere->libelle  }}</td> --}}
                    <td>{{ $eleve->user->last_name }}</td>
                    <td>{{ $eleve->user->phone }}</td>
                    <td>{{ $eleve->user->email }}</td>
                    <td>{{ $eleve->etablissement }}</td>
                    <td>{{ $eleve->classe->libelle }}</td>
                    <td>{{ $eleve->sexe }}</td>
                    <td><a href="{{ route('matiere.create') }}">voire</a></td>
                    {{-- <td><a href="{{route('matiere.create')}}">Ajouter à une classe</a></td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
