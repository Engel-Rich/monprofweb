@extends('nav')


@section('content')
    <div class="row">
        <div class="col">
            <h1 class="display-5"> Classes </h1>
        </div>
        <div class="col">
            <a href="{{route('categorie.create')}}">
                <button class="btn btn-outline-primary "> Ajouter une nouvelle Catégorie</button>
            </a>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="display-7 fw-bold">Nom de la catégorie</th>                
                <th scope="col" class="display-7 fw-bold">Description</th>
                <th scope="col" class="display-7 fw-bold">Prix</th>
                <th scope="col" class="display-7 fw-bold">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $cat)
            <tr>
                <th scope="row">{{$cat->libelle }}</th>                
                <td><strong> {{ $cat->prix }} XAF</strong> </td>
                <td>{{ $cat->description }}</td>                
                <td><a href="{{route('categorie.create')}}">Modifier</a></td>
            </tr>
            @endforeach                            
        </tbody>
    </table>
    {{$categories->links()}}
@endsection
