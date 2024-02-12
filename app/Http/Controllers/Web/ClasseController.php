<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClasseValidateRequest;
use App\Models\Classe;
use Exception;
// use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClasseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index():View
    {
        $classes = Classe::paginate(20);
        return view('screen.classe.index_classe', ['classes' => $classes],);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {         
        return view('screen.classe.create', ['classe'=> new Classe(),]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClasseValidateRequest $request)
        
    {
        // dd($request->all());
        try{
            Classe::create($request->all());
            // route('classe.index')
         return   redirect()->route('classe.index');
        }catch(Exception $th){
            dd($th);
        }
    }

    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     $classe =  Classe::find($id);

    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $classe  = Classe::find($id);
        return view('screen.classe.create', ['classe'=>$classe]);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClasseValidateRequest $request, string $id)
    {
        $classe = Classe::find($id);
        $classe->fill($request->all())        ;
        $classe->save();
        return   redirect()->route('classe.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
