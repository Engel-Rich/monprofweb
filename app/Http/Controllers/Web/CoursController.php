<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoursValidateRequest;
use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Matieres;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoursController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $cours = Cours::with('classe', 'user', "matiere",'categorie')->paginate(20);
        // dd($matieres);
        $result = $cours->getCollection()->transform(function ($value) {
            // dd($value->video_url);
            $fileManager = new FileManager('Videos/'.$value->matiere->libelle);
            $value->video_url = $fileManager->get($value->video_url);
            // dd($value->video_url);
            return $value;
        });
        $cours->setCollection($result);
        return view('screen.cours.index', ['cours' => $cours],);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $matieres = Matieres::all();
        $categories = Categorie::all();
        $classes = Classe::all();

        return view('screen.cours.create', [
            'matieres' => $matieres,
            'categories' => $categories,
            'classes' => $classes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {                  
            // dd($request->all());

            $validatortable=  [
                "libelle" => 'string|required',
                "description" => 'string|required',
                'video' => 'required|file',
                'classe_id'=>'required|exists:classes,id',
                'matieres_id'=>'required|exists:matieres,id',
                'categorie_id'=>'required|exists:categories,id'     ,    
                'open'=> "integer|required"                      
            ];                  
            $validation = $request->all();
            // if ($request->video=) {
            $validation['open']= $request->open;
            dd($validation);
                Log::info($request->all());
                $request->validate($validatortable);
                $titre = $request->libelle;
                $classe  = Classe::find($request->classe_id)->libelle;
                $matiere  = Matieres::find($request->matieres_id)->libelle;
                $categorie  = Categorie::find($request->categorie_id)->libelle;
                $video = $request->file('video');
                $extention = $video->extension();
                $user = $request->user()->id;
                // dd($classe->libelle,$matiere->libelle,$categorie->libelle);
                
                $filemanager = new FileManager('Videos/'.$matiere);
                $videoUrl = $filemanager->store($video);  //$video->store("videos/$categorie/$classe/$matiere/$titre.$extention", 'public');
                Log::info($videoUrl);
                $validation['video_url'] = $videoUrl;//asset("storage/$videoUrl");
                $validation['user_id'] = $user;            
                Log::info($validation);
                unset($validation['video']);
                Cours::create($validation);
                Log::info($validation);
                return redirect()->route('cours.index');
            // }
            // dd($validation);
        } catch (\Throwable $th) {
            Log::info("Erreur d'ajour du cours");
            Log::error($th);
            return to_route('cours.create')->withErrors(['error' => $th->getMessage()])->onlyInput('libelle', 'description');
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
