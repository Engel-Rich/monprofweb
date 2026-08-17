<div class="subject-manager">
    <div class="subject-manager-column">
        <div class="subject-manager-head"><div><strong>Matières associées</strong><small>Actuellement disponibles dans la classe</small></div><span>{{ $matieresclasse?->count() ?? 0 }}</span></div>
        <div class="subject-manager-list">
            @forelse ($matieresclasse ?? [] as $matiere)
                <div class="subject-manager-item"><span>{{ strtoupper(substr($matiere->libelle, 0, 2)) }}</span><strong>{{ $matiere->libelle }}</strong><button type="button" wire:click="deleteMatiereToClasse({{ $matiere->id }})" wire:loading.attr="disabled">Retirer</button></div>
            @empty
                <div class="subject-manager-empty">Aucune matière n’est encore associée.</div>
            @endforelse
        </div>
    </div>
    <div class="subject-manager-column available">
        <div class="subject-manager-head"><div><strong>Matières disponibles</strong><small>Ajoutez une matière à cette classe</small></div><span>{{ $matieres?->count() ?? 0 }}</span></div>
        <div class="subject-manager-list">
            @forelse ($matieres ?? [] as $matiere)
                <div class="subject-manager-item"><span>{{ strtoupper(substr($matiere->libelle, 0, 2)) }}</span><strong>{{ $matiere->libelle }}</strong><button type="button" wire:click="addMatiereToClasse('{{ $matiere->id }}')" wire:loading.attr="disabled">Ajouter</button></div>
            @empty
                <div class="subject-manager-empty">Toutes les matières sont déjà associées.</div>
            @endforelse
        </div>
    </div>
</div>
