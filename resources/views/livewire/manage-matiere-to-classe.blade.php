<div>
    @if ($matieresclasse == null || $matieresclasse->isEmpty())
        <div class="container p-5">
            <h5 class="display-8">Aucune matière disponible dans la classe</h5>
        </div>
    @else
        <div class="container p-3">
            <table class="table" id="table_classe_matiere">
                @foreach ($matieresclasse as $matiere)
                    <tr class="mb-3">
                        <td> {{ $matiere->libelle }} </td>
                        <td>
                            <button class="btn btn-outline-danger px-5 py-0"
                                wire:click='deleteMatiereToClasse({{ $matiere->id }})'>
                                retier
                            </button>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
    <hr class="divider py-1 bg-secondary" />
    <div class="container p-3">
        <table class="table" id="table_matiere">
            @foreach ($matieres as $matiere)
                <tr class="mb-3">
                    <td> {{ $matiere->libelle }} </td>
                    <td>
                        <button class="btn btn-outline-primary px-5 py-0"
                            wire:click="addMatiereToClasse('{{ $matiere->id }}')">
                            Ajouter
                        </button>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
