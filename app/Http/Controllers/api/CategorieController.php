<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Eleve;
use App\Models\Codes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategorieController extends Controller
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
            $classe = Categorie::all();
            return response()->json(['status' => true, 'data' => $classe,], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * get studens status
     */
    public function status()
    {
        try {
            $categorie = Categorie::all();

            $user = Auth::user();
            /**
             * 
             *@var Illuminate\Database\Eloquent\Concerns\HasAttributes::$encrypter
             */
            $eleve = Eleve::where('user_id', $user->id)->get()[0];

            $result = array();

            foreach ($categorie as $value) {
                $exist = Codes::with('paiement')->whereHas(
                    'paiement',
                    function ($query) use ($value) {                                                
                        $query->where('categorie_id', $value->id);
                    }
                )->where('actif', 1)->where('eleve_id', $eleve->id)->exists();
                // dd($exist);
                array_push($result, ['categorie' => $value, 'status' => $exist]);
            }

            return response()->json(['status' => true, 'data' => $result], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()]);
        }
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
