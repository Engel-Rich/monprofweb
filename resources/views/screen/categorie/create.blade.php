@extends('nav')

@section('content')
    <h1 class="display-5">{{$categorie->id==null? "Ajouter une nouvelle Catégorie" : "Modifier la catégorie ".$categorie->libelle }}</h1>

    <div class="container py-5 px-5">
        <div class="row">
            <div class="col-lg col-md">
                <form method="POST" action="{{$categorie->id==null? route('categorie.store'):route('categorie.update',$categorie->id) }}">
                    @csrf
                    @if ($categorie->id==null)
                        @method('POST')
                    @else
                        @method('PUT')
                    @endif
                    <div class="mb-3">
                        <label for="libelle" class="form-label fw-bold">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="libelle" aria-describedby="classe_name" name="libelle" value="{{old('libelle',$categorie->libelle)}}" required>
                        <div id="libelle" class="form-text">Entrer le nom de la catégorie.</div>
                    </div>   
                    <div class="mb-3">                        
                        <label class="form-label fw-bold" for="prix">Prix de la catégorie</label>
                        <input type="number" class="form-control" id="prix" name="prix" value="{{old('prix',$categorie->prix)}}" required>
                    </div>                 
                    <div class="mb-3">                        
                        <label class="form-label fw-bold" for="description">Description de la catégorie</label>
                        <input type="text" class="form-control" id="description" name="description" value="{{old('description',$categorie->description)}}">
                    </div>
                    
                    <button type="submit" class="btn btn-outline-primary px-5">Submit</button>
                </form>

            </div>
            <div class="col-lg col-md">

            </div>
        </div>
    </div>
@endsection
