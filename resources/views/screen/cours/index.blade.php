@extends('nav')


@section('content')
<form action="" class="form">
    <div class="row">

        <div class="col-md-3 d-flex align-content-center">
            <h1 class="display-5">Cours </h1>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control", placeholder="Recherche">
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-primary" type="submit">Rechercher</button>
        </div>
        <div class="col-md-3">
            <a href="{{route('cours.create')}}"  class="btn btn-outline-primary" >Ajouter un cours </a>
        </div>
    </div>
</form>


    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Titre </th>
                <th scope="col" class="display-7 fw-bold">Description</th>
                <th scope="col" class="display-7 fw-bold">Matière</th>
                <th scope="col" class="display-7 fw-bold">Classe</th>
                <th scope="col" class="display-7 fw-bold">Catégorie</th>
                <th scope="col" class="display-7 fw-bold">Utilisateur</th>
                <th scope="col" class="display-7 fw-bold">Vidéo</th>                
                <th scope="col" class="display-7 fw-bold">Action</th>
                {{-- <th scope="col" class="display-7 fw-bold">Action</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($cours as $cour)
                <tr>
                    <th scope="row">{{ $cour->libelle }}</th>
                    {{-- <td>{{$matiere->libelle  }}</td> --}}
                    <td>{{  $cour->description }}</td>
                    <td>{{  $cour->matiere->libelle }}</td>
                    <td>{{ $cour->classe->libelle }}</td>
                    <td>{{ $cour->categorie->libelle }}</td>
                    <td>{{ $cour->user->name }}</td>
                    <td> <video contextmenu="nocontextmenu" style="height:4.5rem;width:4.5rem border:solide,2px,blue" controls src="{{url($cour->video_url)}}"></video></td>
                    {{-- <td>{{ $eleve->sexe }}</td> --}}
                    <td><a href="{{ route('cours.create') }}">modifier</a></td>
                    {{-- <td><a href="{{route('matiere.create')}}">Ajouter à une classe</a></td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
