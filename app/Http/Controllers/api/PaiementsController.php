<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Paiements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaiementsController extends Controller
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
        //
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
        try {
            $request->validate([
                // 'user_id' => 'integer|required|exists:users,id',
                'categorie_id' => 'integer|required|exists:categories,id',
                'nombre_de_code' => 'integer|required',
                'numero_payeur' => 'required',
                'numero_client' => 'required',
            ]);
            $user = Auth::user();
            $categorie =  Categorie::find($request->categorie_id);
            $data = $request->all();
            $data['user_id'] = $user->id;
            $data['montant'] = $categorie->prix * $request->nombre_de_code;
            $paiment = Paiements::create($data);
            return response()->json(['status' => true, 'data' => $paiment], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, "error" => $th->getMessage()],500);
        }
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
