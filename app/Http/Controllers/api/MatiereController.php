<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Matieres;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatiereController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:api');
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {   
            $user = Auth::user();
            $eleve = Eleve::where('user_id', $user->id)->limit(1)->get()->first();
             $userClasse = Classe::with('matieres')-> findOrFail($eleve?->id);   
            //  dd($userClasse)           ;
            // $matieres= $userClasse?->matieres();
            return response()->json(['status' => true,'data'=>$userClasse?->matieres], 200);
            } catch (\Throwable $th) {
                return response()->json(['status' => false, 'data'=>null, 'error'=> $th->getMessage()]);
            }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
