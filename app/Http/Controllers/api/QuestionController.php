<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Eleve;
// use App\Models\Matieres;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class QuestionController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'categorie_id' => 'integer|required|exists:categories,id',
                'matiere_id' => 'integer|required|exists:matieres,id',               
            ]);
            $user = Auth::user();
            $eleve = Eleve::where('user_id', $user->id)->get()[0];
            $questions = \App\Models\Questions::with('reponse')
                ->where('matieres_id', $request->matiere_id)
                ->where('categorie_id', '=', $request->categorie_id)
                ->paginate(20);
            return response()->json(['status' => true, 'data' => $questions, 'error' => null]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => 'null', 'error' => $th->getMessage(),], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'categorie_id' => 'integer|required|exists:categories,id',
                'matiere_id' => 'integer|required|exists:matieres,id',
                'titre' => 'nullable|string',
                'description' => 'required|string',
                'image' => 'file|nullable|mimetypes:image/*',
                'question_id' => 'nullable|intenger',
            ]);
            $data = $request->all();
            $user = Auth::user();
            $eleve = Eleve::where('user_id', $user->id)->get()[0];
            $data['eleve_id'] = $eleve->id;
            $data['classe_id'] = $eleve->classe_id;
            $data['matieres_id'] = $request->matiere_id;
            $data['questions_id'] = $request->question_id;
            $matiere = \App\Models\Matieres::find($request->matiere_id);
            if ($data['titre'] == null) {               
                $data['titre'] = $matiere->libelle;
            }
            $timestampMilliseconds = strval(Carbon::now()->valueOf());
            if ($request->image != null) {
                $image = $request->file('image');
                $extention = $image->extension();                
                $imageUrl = $image->store("questions/eleves/" . $request-> $matiere->libelle .$timestampMilliseconds. '.' . $extention, 'public');
                Log::info($imageUrl);
                $data['image_url'] = asset("storage/$imageUrl");
                Log::info($data);               
            }
            $questions = \App\Models\Questions::create($data);
            return response()->json([
                'status' => true,
                'data' => $questions,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => 'null',
                'error' => $th->getMessage()
            ], 500);
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
