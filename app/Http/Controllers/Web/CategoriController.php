<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieValidateRequest;
use App\Models\Categorie;
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
        return view('screen.categorie.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategorieValidateRequest $request)
    {
        try{
            Categorie::create($request->all());            
         return   redirect()->route('categorie.index');
        }catch(Exception $th){
            dd($th);
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
