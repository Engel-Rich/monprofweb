@extends('nav')


@section('content')
    <form action="" class="form">
        <div class="row">

            <div class="col-md-3 d-flex align-content-center">
                <h1 class="display-5">Enseignants </h1>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control", placeholder="Recherche">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary" type="submit">Rechercher</button>
            </div>
            <div class="col-md-3">
                <a href="{{route('professeur.create')}}"  class="btn btn-outline-primary" >Ajouter un Prof </a>
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
                <th scope="col" class="display-7 fw-bold">Matieres de base</th>
                <th scope="col" class="display-7 fw-bold">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($profs as $prof)
                <tr>
                    <th scope="row">{{ $prof->user->name }}</th>
                    <td>{{ $prof->user->last_name }}</td>
                    <td>{{ $prof->user->phone }}</td>
                    <td>{{ $prof->user->email }}</td>
                    <td>{{ $prof->matiere->libelle }}</td>
                    <td><a href="{{ route('matiere.create') }}">modifier</a></td>
                    {{-- <td><a href="{{route('matiere.create')}}">Ajouter à une classe</a></td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
