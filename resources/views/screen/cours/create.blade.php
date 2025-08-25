@extends('nav')

@section('content')
<h1 class="display-5">
    {{ isset($cour) ? 'Modifier le cours' : 'Ajouter un nouveau cours' }}
</h1>

<div class="container py-5 px-5">
    <form id="cours-save-form"
        method="POST"
        action="{{ isset($cour) ? route('cours.update', $cour->id) : route('cours.store') }}"
        enctype="multipart/form-data">
        @csrf
        @if(isset($cour))
        @method('PUT')
        @endif

        @error('error')
        <div class="m-3 text-danger">
            {{ $message }}
        </div>
        @enderror

        <div class="row">
            <div class="col-lg col-md">
                {{-- Matières --}}
                <div class="mb-3">
                    <label for="matieres_id" class="form-label fw-bold">Choisir la matière</label>
                    <select required class="form-select form-select-md fw-normal" name="matieres_id">
                        @foreach ($matieres as $item)
                        <option value="{{ $item->id }}"
                            {{ (isset($cour) && $cour->matieres_id == $item->id) ? 'selected' : '' }}>
                            {{ $item->libelle }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Classe --}}
                <div class="mb-3">
                    <label for="classe_id" class="form-label fw-bold">Choisir la classe</label>
                    <select required class="form-select form-select-md fw-normal" name="classe_id">
                        @foreach ($classes as $item)
                        <option value="{{ $item->id }}"
                            {{ (isset($cour) && $cour->classe_id == $item->id) ? 'selected' : '' }}>
                            {{ $item->libelle }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Catégorie --}}
                <div class="mb-3">
                    <label for="categorie_id" class="form-label fw-bold">Choisir la catégorie</label>
                    <select required class="form-select form-select-md fw-normal" name="categorie_id">
                        @foreach ($categories as $item)
                        <option value="{{ $item->id }}"
                            {{ (isset($cour) && $cour->categorie_id == $item->id) ? 'selected' : '' }}>
                            {{ $item->libelle }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Description --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" for="description">Description du cours</label>
                    <textarea rows="5" class="form-control" id="description" name="description" required>
                    {{ old('description', isset($cour) ? $cour->description : '') }}
                    </textarea>
                </div>
            </div>

            <div class="col-lg col-md">
                {{-- Titre --}}
                <div class="mb-3">
                    <label for="libelle" class="form-label fw-bold">Titre du cours</label>
                    <input type="text" class="form-control" id="libelle"
                        name="libelle"
                        required
                        value="{{ old('libelle', isset($cour) ? $cour->libelle : '') }}">
                    <div id="libelle" class="form-text">Entrer le titre du cours</div>
                </div>

                {{-- Gratuit / Payant --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Cours gratuit</label>
                    <div class="row">
                        <div class="col">
                            <input class="form-check-input" type="radio" name="open" value="1"
                                {{ old('open', isset($cour) ? $cour->open : 0) == 1 ? 'checked' : '' }}>
                            <label class="form-check-label">Gratuit</label>
                        </div>
                        <div class="col">
                            <input class="form-check-input" type="radio" name="open" value="0"
                                {{ old('open', isset($cour) ? $cour->open : 0) == 0 ? 'checked' : '' }}>
                            <label class="form-check-label">Payant</label>
                        </div>
                    </div>
                </div>

                {{-- Vidéo --}}
                <div class="mb-3">
                    <label class="form-label fw-bold" for="video">Chargez une Vidéo</label>
                    <input type="file" class="form-control" id="video" name="video" accept="video/*">
                    @if(isset($cour) && $cour->video)
                    <small class="text-muted">Vidéo actuelle: {{ $cour->video }}</small>
                    @endif
                </div>

                {{-- Bouton --}}
                <button type="submit" class="btn btn-outline-primary px-5">
                    {{ isset($cour) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>


                @if(isset($cour))
                <form action="{{ route('cours.destroy', $cour) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger px-5"
                        onclick="return confirm('Voulez-vous vraiment supprimer ce cours ?')">
                        Supprimer
                    </button>
                </form>
                @endif

                {{-- Progress bar --}}
                <div class="progress mt-3">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" id="progressBar">0%</div>
                </div>
                <div id="uploadStatus"></div>
            </div>
        </div>
    </form>
</div>
@endsection