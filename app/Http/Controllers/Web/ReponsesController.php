<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Reponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReponsesController extends Controller
{
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
                'titre' => 'string|nullable',
                'description' => 'string|required',
                'questions_id' => 'required|integer|exists:questions,id',
                'image' => 'file|nullable|mimetypes:image/*'
            ]);
            
            $validation = $request->all();
            // dd($validation);
            // unset($validation['_token']);
            unset($validation['image']);
            if ($request->image != null) {
                $video = $request->file('image');
                $extention = $video->extension();
                $question = \App\Models\Questions::with('matiere', 'classe')->find($request->questions_id);
                $videoUrl = $video->store("questions/images/" . $question->classe->libelle . "/" . $question->matiere->libelle . "/" . '.' . $extention, 'public');
                $validation['image_url'] = asset("storage/$videoUrl");
            }
            $validation['user_id'] = Auth::user()->id;
            // dd($validation);
            $response = \App\Models\Reponses::create($validation);
            return redirect()->route('question.index');
        } catch (\Throwable $th) {
            dd($th);
            return to_route('question.show',$request->questions_id)
                ->withErrors(['error' => $th->getMessage()])
                ->onlyInput('titre', 'description', 'image');
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

        try {
            $reponse = \App\Models\Reponses::find($id);
            $request->validate([
                'titre' => 'string|nullable',
                'description' => 'string|required',
                'questions_id' => 'required|integer|exists:questions,id',
                'image' => 'file|nullable|mimetypes:image/*'
            ]);
            $validation = $request->all();
            unset($validation['image']);
            if ($request->image != null) {
                $video = $request->file('image');
                $extention = $video->extension();
                $question = \App\Models\Questions::with('matiere', 'classe')->find($request->questions_id);
                $videoUrl = $video->store("questions/images/" . $question->classe->libelle . "/" . $question->matiere->libelle . "/" . '.' . $extention, 'public');
                $validation['image_url'] = asset("storage/$videoUrl");
            }
            $response = $reponse->update($validation);
            return redirect()->route('question.index');
        } catch (\Throwable $th) {
            dd($th);
            return to_route('question.show')
                ->withErrors(['error' => $th->getMessage()])
                ->onlyInput('titre', 'description', 'image');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
