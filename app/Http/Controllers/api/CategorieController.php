<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Codes;
use App\Models\Eleve;
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
            $classe = Categorie::where('status', true)->get();

            return response()->json(['status' => true, 'data' => $classe], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * get studens status
     */
    public function status()
    {
        try {
            $categorie = Categorie::where('status', true)->get();

            $user = Auth::user();
            /**
             *@var Illuminate\Database\Eloquent\Concerns\HasAttributes::$encrypter
             */
            $eleve = Eleve::where('user_id', $user->id)->get()[0];

            $result = [];

            foreach ($categorie as $value) {
                $exist = Codes::with('paiement')->whereHas(
                    'paiement',
                    function ($query) use ($value) {
                        $query->where('categorie_id', $value->id)
                            ->where('status', true)
                            ->whereNull('revoked_at');
                    }
                )->where('actif', 1)
                    ->whereNull('revoked_at')
                    ->where('eleve_id', $eleve->id)
                    ->exists();
                // dd($exist);
                array_push($result, ['categorie' => $value, 'status' => $exist]);
            }

            return response()->json(['status' => true, 'data' => $result], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }

    public function statusCodesParent()
    {
        try {
            $categorie = Categorie::all();

            $user = Auth::user();

            $result = [];

            $filter_activeCode = function ($code) {
                // dd($code['actif']);
                return $code['actif'] == 1 && empty($code['revoked_at']);
            };
            $filter_unactiveCode = function ($code) {
                return $code['actif'] == 0 && empty($code['revoked_at']);
            };
            $filter_revokedCode = function ($code) {
                return ! empty($code['revoked_at']);
            };

            foreach ($categorie as $value) {
                $codes = Codes::with('paiement')->whereHas(
                    'paiement',
                    function ($query) use ($value, $user) {
                        $query->where('categorie_id', $value->id)->where('user_id', $user->id);
                    }
                )->get();
                $activeCode = array_filter($codes->toArray(), $filter_activeCode);
                $unactiveCode = array_filter($codes->toArray(), $filter_unactiveCode);
                $revokedCode = array_filter($codes->toArray(), $filter_revokedCode);
                $details = [
                    'total' => count($codes),
                    'actifs' => count($activeCode),
                    'unactifs' => count($unactiveCode),
                    'revoques' => count($revokedCode),
                ];
                array_push($result, ['categorie' => $value, 'details' => $details]);
            }

            return response()->json(['status' => true, 'data' => $result], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()], 400);
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
