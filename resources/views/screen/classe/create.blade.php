@extends('nav')

@section('content')
    <h1 class="display-5"> Ajouter une nouvelle classe</h1>

    <div class="container py-5 px-5">
        <div class="row">
            <div class="col-lg col-md">
                <form method="POST" action="{{route('classe.store')}}">
                    @csrf
                    <div class="mb-3">
                        <label for="libelle" class="form-label fw-bold">Nom de la classe</label>
                        <input type="text" class="form-control" id="libelle" aria-describedby="classe_name" name="libelle" required>
                        <div id="libelle" class="form-text">Entrer le nom complet de la classe.</div>
                    </div>
                    <div class="mb-3">
                        <label for="short_name" class="form-label fw-bold">Abréviation</label>
                        <input type="text" class="form-control" id="short_name" name="short_name" required>
                    </div>
                    <div class="mb-3">                        
                        <label class="form-label fw-bold" for="description">Description de la classe</label>
                        <input type="text" class="form-control" id="description" name="description">
                    </div>
                    <button type="submit" class="btn btn-outline-primary px-5">Submit</button>
                </form>

            </div>
            <div class="col-lg col-md">

            </div>
        </div>
    </div>
@endsection
