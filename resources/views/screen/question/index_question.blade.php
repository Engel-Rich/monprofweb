@extends('nav')


@section('content')
        <div class="row display-flex align-items-center">
            <div class="col-md-3 d-flex align-content-center">
                <h1 class="display-5">Questions </h1>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control", placeholder="Recherche">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary" type="submit">Rechercher</button>
            </div>
        </div>
  

    <table class="table">
        <thead>
            <tr class="display-flex align-items-center ">
                <th scope="col" class="display-7 fw-bold border border-solid">Titre</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Question</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Classe</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Matieres</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Categorie</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Elève</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Status</th>
                <th scope="col" class="display-7 fw-bold border border-solid">Date</th>                                
                <th scope="col" class="display-7 fw-bold border border-solid">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($questions as $qestion)
                <tr>
                    <th scope="row">{{$qestion->titre }}</th>
                    <td>{{ $qestion->description}}</td>
                    <td>{{ $qestion->classe->libelle }} </td>
                    <td>{{ $qestion->matiere->libelle }}</td>
                    <td>{{ $qestion->categorie->libelle }}</td>                    
                    <td>{{ $qestion->eleve->user->name }}</td>                    
                    <td>{{ $qestion->reponse?->id==null?'En attente':"Répondue"}}</td>
                    <td>{{ $qestion->created_at->format('D d M Y H:m') }}</td>                    
                    <td><a href="{{route('question.show',$qestion->id)}}">Voir</a></td>
                    {{-- <td><a href="{{route('matiere.create')}}">Ajouter à une classe</a></td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
