<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use Illuminate\Http\Request;

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
            $request->
            validate([
                'classe_id' => "integer|required|exists:classes,id",
                'eleve_id' => "integer|required|exists:eleves,id",
                'matiere_id' => "integer|required|exists:matieres,id",
                'categorie_id' => "integer|required|:categories,id",
            ]);

            // TODO vérifier si l'élève possède un abonement et ouvrir le cours

            $cours  = Cours::where('matieres_id', $request['matiere_id'])
                ->where('classe_id', $request['classe_id'])
                ->where('categorie_id', $request->categorie_id)
                ->paginate(20);

            // Ouvrire les cours si l'élève possède un abonnemnet

            return response()->json([
                'status' => true,
                'data' => $cours,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error'=>$th->getMessage(),
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
