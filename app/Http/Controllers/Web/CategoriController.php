<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieValidateRequest;
use App\Models\Categorie;
use App\Models\Classe;
use Exception;
use Illuminate\Http\Request;

class CategoriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Categorie::paginate(20);
        return view('screen.categorie.index', ['categories' => $categories],);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('screen.categorie.create', ['categorie' => new Categorie()]);
    }

    public function statistiques(Request $request)
    {
        // filter get status=1 paiement  (total paiement amount and total paiment count)
        $classes  = Classe::all();
        if ($request->classe != null) {
            // paiement has user_id and eleve has classe_id and user_id
            $categories = Categorie::with(['paiements' => function ($query) use ($request) {
                $query->where('paiement_date', '!=', null)
                    ->whereHas('user.eleve', function ($query) use ($request) {
                        $query->where('classe_id', $request->classe);
                    })
                    ->selectRaw('categorie_id, SUM(montant) as total_montant, COUNT(*) as total_paiements')
                    ->groupBy('categorie_id');
            }])->get();
        } else {
            $categories = Categorie::with(['paiements' => function ($query) {
                $query->selectRaw('categorie_id, SUM(montant) as total_montant, COUNT(*) as total_paiements')
                    ->where('paiement_date', '!=', null)
                    ->groupBy('categorie_id');
            }])->get();
        }

        $categories->transform(function ($item) {
            $item['total_montant'] = $item->paiements->sum('total_montant');
            $item['total_paiements'] = $item->paiements->sum('total_paiements');
            return $item;
        });
        // dd($categories->toArray());

        return view('screen.partner.home', ['categories' => $categories, 'classes' => $classes]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CategorieValidateRequest $request)
    {
        try {
            Categorie::create($request->all());
            return   redirect()->route('categorie.index');
        } catch (Exception $th) {
            return redirect()->back()->withInput()->withErrors($th->getMessage(), 'errors');
            dd($th);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $categorie = Categorie::find($id);
        return view('screen.categorie.create', ['categorie' => $categorie]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategorieValidateRequest $request, string $id)
    {
        try {
            $classe = Categorie::find($id);
            $classe->fill($request->all());
            $classe->save();
            return   redirect()->route('categorie.index');
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $classe = Categorie::find($id);
        $classe->delete();
        return redirect()->route('categorie.index');
    }
}
