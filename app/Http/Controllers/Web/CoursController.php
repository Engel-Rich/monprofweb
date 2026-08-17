<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\AddCourseNotificationJob;
use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Matieres;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
        //dd($cours);
        $result = $cours->getCollection()->transform(function ($value) {
            // dd($value->video_url);
            $matiere = $value->matiere->libelle;
            $categorie = $value->categorie->libelle;
            $classe = $value->classe->libelle;
            $fileManager = new FileManager("Videos/$categorie/$classe/$matiere");
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
            $validation = $request->validate([
                'libelle' => 'string|required|max:255',
                'description' => 'string|required',
                'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:512000',
                'classe_id' => 'required|exists:classes,id',
                'matieres_id' => 'required|exists:matieres,id',
                'categorie_id' => 'required|exists:categories,id',
                'open' => 'required|boolean',
            ]);

            $classe = Classe::findOrFail($request->classe_id)->libelle;
            $matiere = Matieres::findOrFail($request->matieres_id)->libelle;
            $categorie = Categorie::findOrFail($request->categorie_id)->libelle;
            $video = $request->file('video');
            $filemanager = new FileManager("Videos/$categorie/$classe/$matiere");
            $videoUrl = $filemanager->store($video);

            if (! $videoUrl) {
                throw new RuntimeException('Le stockage de la vidéo a échoué.');
            }

            $validation['video_url'] = $videoUrl;
            $validation['user_id'] = $request->user()->id;
            unset($validation['video']);

            try {
                $cour = Cours::create($validation);
                AddCourseNotificationJob::dispatch($cour->id);
            } catch (\Throwable $th) {
                $filemanager->delete($videoUrl);
                throw $th;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Le cours a été publié avec succès.',
                    'redirect' => route('cours.index'),
                ], 201);
            }

            return redirect()->route('cours.index')->with('success', 'Cours publié avec succès.');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error($th);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Impossible de publier le cours : '.$th->getMessage()], 500);
            }

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
        $cour = Cours::find($id);
        $matieres = Matieres::all();
        $categories = Categorie::all();
        $classes = Classe::all();

        return view('screen.cours.create', [
            'matieres' => $matieres,
            'categories' => $categories,
            'classes' => $classes,
            'cour' => $cour
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $cour = Cours::findOrFail($id);

            $data = $request->validate([
                'libelle' => 'string|required|max:255',
                'description' => 'string|required',
                'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:512000',
                'classe_id' => 'required|exists:classes,id',
                'matieres_id' => 'required|exists:matieres,id',
                'categorie_id' => 'required|exists:categories,id',
                'open' => 'required|boolean',
            ]);

            $classe = Classe::findOrFail($request->classe_id)->libelle;
            $matiere = Matieres::findOrFail($request->matieres_id)->libelle;
            $categorie = Categorie::findOrFail($request->categorie_id)->libelle;

            unset($data['video']);
            $data['user_id'] = $request->user()->id;

            if ($request->hasFile('video')) {
                $filemanager = new FileManager("Videos/$categorie/$classe/$matiere");
                $videoUrl = $filemanager->store($request->file('video'));
                if (! $videoUrl) {
                    throw new RuntimeException('Le stockage de la nouvelle vidéo a échoué.');
                }
                $data['video_url'] = $videoUrl;
            }

            try {
                $cour->update($data);
            } catch (\Throwable $th) {
                if (isset($filemanager, $videoUrl)) {
                    $filemanager->delete($videoUrl);
                }
                throw $th;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Le cours a été mis à jour avec succès.',
                    'redirect' => route('cours.index'),
                ]);
            }

            return redirect()->route('cours.index')->with('success', 'Cours mis à jour avec succès!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error($th);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Impossible de mettre à jour le cours : '.$th->getMessage()], 500);
            }

            return back()->withErrors(['error' => $th->getMessage()])->onlyInput('libelle', 'description');
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $cour = Cours::with('classe', "matiere", 'categorie')->find($id);
            // dd($cour);
            $categorie = $cour->categorie->libelle;
            $classe = $cour->classe->libelle;
            $matiere = $cour->matiere->libelle;
            $filemanager = new FileManager("Videos/$categorie/$classe/$matiere");
            $filemanager->delete($cour->video_url);
            $cour->delete();

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Cours supprimé avec succès.']);
            }

            return redirect()->route('cours.index');
        } catch (\Throwable $th) {
            Log::error($th);

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Impossible de supprimer le cours : '.$th->getMessage()], 500);
            }

            return back()->withErrors(['error' => $th->getMessage()])->onlyInput('libelle', 'description');
        }
    }
}
