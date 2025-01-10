<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ManageMatiereToClasse extends Component
{

    public  $classe;
    public  $matieres;
    public  $matieresclasse;


    public function mount($classe)
    {
        $this->classe = $classe;
        $this->loadMatieres();
    }
    public function render()
    {
        return view('livewire.manage-matiere-to-classe');
    }

    protected function loadMatieres()
    {
        $this->matieres = \App\Models\Matieres::whereDoesntHave('classe', function ($query) {
            return $query->where('classes.id', $this->classe->id);
        })->get();
        $this->matieresclasse = \App\Models\Matieres::whereHas('classe', function ($query) {
            return $query->where('classes.id', $this->classe->id);
        })->get();
    }

    public function addMatiereToClasse($matiereId)
    {
        // dd($matiereId, $this->classe);
        // Http::post(route('classe.add_matiere'), ['classe_id'=>$this->classeId, 'matiere_id'=>$matiereId]);
        // $classe = \App\Models\Classe::findOrFail($this->classeId);
        $this->classe->matieres()->attach($matiereId);
        $this->loadMatieres();
    }
    public function deleteMatiereToClasse($matiereId)
    {
        // Http::delete('/classe/delete_matiere', ['classe_id' => $this->classeId, 'matiere_id' => $matiereId]);
        $this->classe->matieres()->detach($matiereId);
        $this->loadMatieres();
    }
}
