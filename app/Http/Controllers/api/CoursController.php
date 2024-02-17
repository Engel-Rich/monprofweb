<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Codes;
use App\Models\Cours;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoursController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:api');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            // dd($request->all());
            $request->validate([
                'matiere_id' => "integer|required|exists:matieres,id",
                'categorie_id' => "integer|required|:categories,id",
            ]);

            // Ouvrire les cours si l'élève possède un abonnemnet

            $user = Auth::user();
            $eleve = Eleve::where('user_id', $user->id)->get()[0];
            $cours  = Cours::where('matieres_id', $request['matiere_id'])
                ->where('classe_id', $eleve->classe_id)
                ->where('categorie_id', $request->categorie_id)
                ->paginate(20);

            //CHECK IF USER HAVE ACTIVE CODE
            $exist = Codes::with('paiement')->whereHas(
                'paiement',
                function ($query) use($request) {
                    $query->where('categorie_id', $request->categorie_id);
                }
            )->where('actif', 1)->where('eleve_id', $eleve->id)->exists();
            if ($exist) {
                foreach ($cours as $cour) {
                    $cour->open = true;
                }
            }
            return response()->json([
                'status' => true,
                'error' => null,
                'data' => $cours,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),500,
            ]);
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
