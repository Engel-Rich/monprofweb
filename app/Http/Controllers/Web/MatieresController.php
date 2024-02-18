<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatiereValidateRequest;
use App\Models\Matieres;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatieresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index():View
    {
        $matieres = Matieres::paginate(20);
        return view('screen.matiere.index_matiere', ['matieres' =>$matieres],);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('screen.matiere.create',['matiere'=>null]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MatiereValidateRequest $request)
    {
        // dd($request->all());
        try{
            Matieres::create($request->all());            
           return  redirect()->route('matiere.index');
        }catch(Exception $th){
            
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
        $matiere = Matieres::find($id); 
        return view('screen.matiere.create',['matiere'=>$matiere]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            
            $matiere = Matieres::find($id);            
            $matiere->fill($request->all());
            $matiere->save();
           return  redirect()->route('matiere.index');
        }catch(Exception $th){}
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
