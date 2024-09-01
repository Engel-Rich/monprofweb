<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\FileManager;
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
        $result = $questions->getCollection()->transform(function ($value){                            
            if($value->image_url!=null){
                $fileManager = new FileManager("questions/eleves/");
                $value->image_url = $fileManager->get($value->image_url);                
            }
            if($value->reponse!=null && $value->reponse->image_url!=null){
                $fileManager = new FileManager("Reponses/$value->id");
                $value->reponse->image_url = $fileManager->get($value->reponse->image_url);
            }
            return $value;
        });
        $questions->setCollection($result);
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
        if($question->image_url!=null){
            $fileManager = new FileManager("questions/eleves/");
            $question->image_url = $fileManager->get($question->image_url);
            if($question->reponse!=null && $question->reponse->image_url!=null){
                $fileManager = new FileManager("Reponses/$question->id");
                $question->reponse->image_url = $fileManager->get($question->reponse->image_url);
            }
        }
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
