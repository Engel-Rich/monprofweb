@extends('nav')

@section('content')
    <h1 class="display-5">{{$matiere==null? "Ajouter une nouvelle Matiere": "Modifier la matière ".$matiere->libelle}}</h1>

    <div class="container py-5 px-5">
        <div class="row">
            <div class="col-lg col-md">
                <form method="POST" action="{{$matiere==null? route('matiere.store'):route('matiere.update',$matiere->id)}}">
                    @csrf
                    @if ($matiere==null)
                        @method('POST')
                    @else
                        @method('PUT')
                    @endif
                    <div class="mb-3">
                        <label for="libelle" class="form-label fw-bold">Nom de la matière</label>
                        <input type="text" class="form-control" id="libelle" aria-describedby="classe_name" name="libelle" value="{{old('libelle', $matiere?->libelle)}}" required>
                        
                    </div>   
                    <div class="mb-3">                        
                    <label class="form-label fw-bold" for="app_name">Nom reservé</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" value="{{old('app_name', $matiere?->app_name)}}" required>
                        <div id="libelle" class="form-text">association du nom de la matière à celui d'une classe</div>
                    </div>

                    <div class="mb-3">                        
                        <label class="form-label fw-bold" for="description">Description de la classe</label>
                        <input type="text" class="form-control" id="description" name="description" value="{{old('description', $matiere?->description)}}">
                    </div>
                    <button type="submit" class="btn btn-outline-primary px-5">Submit</button>
                </form>
            </div>
            <div class="col-lg col-md">

            </div>
        </div>
    </div>
@endsection
