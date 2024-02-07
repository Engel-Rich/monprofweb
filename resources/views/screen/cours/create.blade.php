@extends('nav')

@section('content')
    <h1 class="display-5"> Ajouter Un nouveau cours</h1>

    <div class="container py-5 px-5">
        <form method="POST" action="{{ route('cours.store') }}" enctype="multipart/form-data">
            @error('error')
                <div class="text-danger">{{ $message }}</div>
            @enderror
            <div class="row">
                <div class="col-lg col-md">

                    @csrf

                    {{-- Matieress --}}

                    <div class="mb-3">
                        <label for="matieres_id" class="form-label fw-bold">Choisir la matière</label>
                        <select required class="form-select form-select-md fw-normal" aria-label="Sélectionner une matière"
                            name="matieres_id">
                            @foreach ($matieres as $item)
                                <option value="{{ $item->id }}">{{ $item->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Classe --}}

                    <div class="mb-3">
                        <label for="classe_id" class="form-label fw-bold">Choisir la classe</label>
                        <select required class="form-select form-select-md fw-normal" aria-label="Sélectionner une classe"
                            name="classe_id">
                            @foreach ($classes as $item)
                                <option value="{{ $item->id }}">{{ $item->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Catégories --}}

                    <div class="mb-3">
                        <label for="categorie_id" class="form-label fw-bold">Choisir la catégorie</label>
                        <select required class="form-select form-select-md fw-normal"
                            aria-label="Sélectionner une catégorie" name="categorie_id">
                            @foreach ($categories as $item)
                                <option value="{{ $item->id }}">{{ $item->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Description  --}}

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="description">Description du cours</label>
                        <textarea  rows="5" type="text" class="form-control" id="description" name="description" required>{{old('description')}}</textarea>
                    </div>
                </div>
                <div class="col-lg col-md">

                    {{-- Titre de la vidéo  --}}

                    <div class="mb-3">
                        <label for="libelle" class="form-label fw-bold">Titre du cours</label>
                        <input type="text" class="form-control" id="libelle" aria-describedby="classe_name"
                            name="libelle" required value="{{old('libelle')}}">
                        <div id="libelle" class="form-text">Entrer ltitre du nouveau cours</div>
                    </div>

                    {{-- Fichier  --}}

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="video">Chargez une Vidéo</label>
                        <input type="file" class="form-control" id="video" name="video"
                            accept="video/mp4, video/webm, video/ogg" required></textarea>
                    </div>

                    {{-- Bouton de validation  --}}

                    <button type="submit" class="btn btn-outline-primary px-5">Submit</button>

                </div>
            </div>
        </form>
    </div>
@endsection
