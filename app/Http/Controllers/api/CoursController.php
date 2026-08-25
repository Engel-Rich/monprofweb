<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Cours;
use App\Models\Eleve;
use App\Services\CourseAccessService;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoursController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CourseAccessService $courseAccess)
    {

        try {
            // dd($request->all());
            $request->validate([
                'matiere_id' => 'integer|required|exists:matieres,id',
                'categorie_id' => 'integer|required|exists:categories,id',
            ]);

            // Ouvrire les cours si l'élève possède un abonnemnet

            $user = Auth::user();
            $eleve = Eleve::where('user_id', $user->id)->with('classe')->firstOrFail();
            $cours = Cours::where('matieres_id', $request['matiere_id'])
                ->where('classe_id', $eleve->classe_id)
                ->where('categorie_id', $request->categorie_id)
                ->with('matiere')
                ->paginate(20);
            $categorie = Categorie::find($request->categorie_id);
            //CHECK IF USER HAVE ACTIVE CODE
            $exist = $courseAccess->studentHasCategoryAccess($eleve, (int) $request->categorie_id);
            if ($exist) {
                foreach ($cours as $cour) {
                    $cour->open = true;
                }
            }

            $categorie = $categorie->libelle;
            $classe = $eleve->classe->libelle;

            $result = $cours->getCollection()->transform(function ($value) use ($classe, $categorie) {
                // dd($value->video_url);
                $matiere = $value->matiere->libelle;
                $fileManager = new FileManager("Videos/$categorie/$classe/$matiere");

                // $fileManager = new FileManager('Videos/'.$value->matiere->libelle);
                $value->video_url = $fileManager->get($value->video_url);
                $value->video_delivery = [
                    'supports_range' => config('file-storage.driver') === 'minio',
                    'resume_strategy' => 'http-range',
                ];

                // dd($value->video_url);
                return $value;
            });
            $cours->setCollection($result);

            return response()->json([
                'status' => true,
                'error' => null,
                'data' => $cours,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ], 400);
        }
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
