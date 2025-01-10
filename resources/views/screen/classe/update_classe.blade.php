@extends('nav')

@section('content')
    <h1 class="display-5">{{ 'Modifier la classe ' . $classe->libelle }}</h1>

    <div class="container py-5 px-5">
        <div class="row">
            <div class="col-lg col-md">
                <form method="POST" action="{{ route('classe.update', $classe->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="libelle" class="form-label fw-bold">Nom de la classe</label>
                        <input type="text" class="form-control" id="libelle" aria-describedby="classe_name"
                            name="libelle" required, value="{{ old('libelle', $classe->libelle) }}">
                        <div id="libelle" class="form-text">Entrer le nom complet de la classe.</div>
                    </div>
                    <div class="mb-3">
                        <label for="short_name" class="form-label fw-bold">Abréviation</label>
                        <input type="text" class="form-control" id="short_name" name="short_name"
                            value="{{ old('short_name', $classe->short_name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="description">Description de la classe</label>
                        <input type="text" class="form-control" id="description" name="description"
                            value="{{ old('description', $classe->description) }}">
                    </div>
                    <button type="submit" class="btn btn-outline-primary px-5">Submit</button>
                </form>

            </div>
            <div class="col-lg col-md">
                <h3 class="display-6">modifier les matières</h3>

                @livewire('manage-matiere-to-classe', ['classe' => $classe])                
            </div>
        </div>
    </div>
@endsection




{{-- 
@section('update_classe_scripte')
    <script>
        async function ajouterMatiere(classe_id, matiere_id) {
            try {
                const response = await fetch('/api/classe/add_matiere', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        classe_id: classe_id,
                        matiere_id: matiere_id
                    })
                });
                console.log(body);
                const data = await response.json();
                console.log(data);
                if (data.status == true) {
                    alert('Opération réuissie')
                } else {
                    alert('Opération Echoué')
                }
                // actualiserListeMatieres();
            } catch (error) {
                console.error(error);
            }
        }

        async function supprimerMatiere(classe_id, matiere_id) {
            try {
                const response = await fetch('/api/supprimer-matiere', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        classe_id: classe_id,
                        matiere_id: matiere_id
                    })
                });
                const data = await response.json();
                console.log(data);
                if (data.status == true) {
                    alert('Opération réuissie')
                } else {
                    alert('Opération Echoué')
                }
                // actualiserListeMatieres();
            } catch (error) {
                console.error(error);
            }
        }

        const add_matiere_to_class_button = document.getElementById('add_matiere_to_class_button');
        const delete_matiere_to_class_button = document.getElementById('delete_matiere_to_class_button');

        add_matiere_to_class_button.addEventListener('click', function() {
            idClasse = document.getElementById('add_matiere_to_class_button').getAttribute('data-classe_id');
            idMatiere = document.getElementById('add_matiere_to_class_button').getAttribute(' data-matiere_id');
            ajouterMatiere(idClasse, idMatiere);
        })

        // delete_matiere_to_class_button.addEventListener('click', function () {
        //     idClasse = document.getElementById('add_matiere_to_class_button').dataset.classe_id;
        //     idMatiere = document.getElementById('add_matiere_to_class_button').dataset.matiere_id;
        //     supprimerMatiere(idClasse, idMatiere);
        // })
    </script>
@endsection --}}
