<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use APP\Models\Questions;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the r   esource.
     */
    public function index()
    {
        $questions = \App\Models\Questions::with('classe','matiere',"eleve", 'categorie','reponse')->paginate(20);
        return view('screen.question.index_question',['questions'=>$questions]);
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
        $question = \App\Models\Questions::with('reponse','matiere','classe')->find($id);
        return view('screen.question.show_question', ['question'=>$question]);
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
