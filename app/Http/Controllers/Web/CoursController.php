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
    public function index(Request $request)
    {

        $classe = $request->classe;
        $categorie = $request->categorie;
        $matiere = $request->matiere;

        // dd($classe, $categorie, $matiere, $request->all());

        if ($classe != null && $matiere != null && $categorie != null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('classe_id', $classe)
                ->where('matieres_id', $matiere)
                ->where('categorie_id', $categorie)
                ->paginate(10);
        } elseif ($classe != null && $matiere != null && $categorie == null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('classe_id', $classe)
                ->where('matieres_id', $matiere)
                ->paginate(10);
        } elseif ($classe != null && $matiere == null && $categorie != null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('classe_id', $classe)
                ->where('categorie_id', $categorie)
                ->paginate(10);
        } elseif ($classe == null && $matiere != null && $categorie != null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('matieres_id', $matiere)
                ->where('categorie_id', $categorie)
                ->paginate(10);
        } elseif ($classe != null && $matiere == null && $categorie == null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('classe_id', $classe)
                ->paginate(10);
        } elseif ($classe == null && $matiere != null && $categorie == null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('matieres_id', $matiere)
                ->paginate(10);
        } elseif ($classe == null && $matiere == null && $categorie != null) {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')
                ->where('categorie_id', $categorie)
                ->paginate(10);
        } else {
            $cours = Cours::with('classe', 'user', "matiere", 'categorie')->paginate(10);
        }

        // $cours = Cours::with('classe', 'user', "matiere", 'categorie')->paginate(10);
        $result = $cours->getCollection()->transform(function ($value) {
            // dd($value->video_url);
            $matiere = $value->matiere->libelle;
            $categorie = $value->categorie->libelle;
            $classe = $value->classe->libelle;
            $fileManager = new FileManager("Videos/$categorie/$classe/$matiere" . $value->video);
            $value->video_url = $fileManager->get($value->video_url);
            // dd($value->video_url);
            return $value;
        });
        $matieres = Matieres::all();
        $classes = Classe::all();
        $categories = Categorie::all();
        // dd($result);
        $cours->setCollection($result);
        return view(
            'screen.cours.index',
            [
                'cours' => $cours,
                'matieres' => $matieres,
                "classes" => $classes,
                "categories" => $categories,
                "classe" => Classe::find($classe),
                "categorie" => Categorie::find($categorie),
                "matiere" => Matieres::find($matiere),
            ],
        );
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

            $validatortable = [
                "libelle" => 'string|required',
                "description" => 'string|required',
                'video' => 'required|file',
                'classe_id' => 'required|exists:classes,id',
                'matieres_id' => 'required|exists:matieres,id',
                'categorie_id' => 'required|exists:categories,id',
                'open' => "integer|required"
            ];
            $validation = $request->all();
            // if ($request->video=) {
            $validation['open'] = $request->open;
            Log::info($request->all());
            $request->validate($validatortable);
            // $titre = $request->libelle;
            $classe  = Classe::find($request->classe_id)->libelle;
            $matiere = Matieres::find($request->matieres_id)->libelle;
            $categorie  = Categorie::find($request->categorie_id)->libelle;
            $video = $request->file('video');
            // $extention = $video->extension();
            $user = $request->user()->id;
            $filemanager = new FileManager("Videos/$categorie/$classe/$matiere");
            $videoUrl = $filemanager->store($video); // $video->store("Videos/$categorie/$classe/$matiere", 'public');
            // dd($videoUrl,asset("storage/$videoUrl"), url("storage/$videoUrl"), url($videoUrl));
            $validation['video_url'] = $videoUrl; //asset("storage/$videoUrl");
            $validation['user_id'] = $user;
            try {
                Log::info($validation);
                unset($validation['video']);
                Cours::create($validation);
                Log::info($validation);
            } catch (\Throwable $th) {
                $filemanager->delete($videoUrl);
                return to_route('cours.create')->withErrors(['error' => $th->getMessage()])->onlyInput('libelle', 'description');
            }
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
