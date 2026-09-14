<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\AddCourseNotificationJob;
use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Matieres;
use App\Services\FileManager;
use App\Support\TableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CoursController extends Controller
{
    /** Clés de filtre reconnues sur le catalogue de cours. */
    private const FILTER_KEYS = [
        'search', 'title', 'description', 'matiere', 'classe', 'categorie', 'access', 'author', 'from', 'to',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = TableFilters::values($request, self::FILTER_KEYS);
        $perPage = TableFilters::perPage($request, 15);

        $cours = $this->filteredQuery($filters)
            ->with('classe', 'user', 'matiere', 'categorie')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $cours->setCollection($cours->getCollection()->transform(function (Cours $value) {
            $matiere = $value->matiere?->libelle ?? 'inconnue';
            $categorie = $value->categorie?->libelle ?? 'inconnue';
            $classe = $value->classe?->libelle ?? 'inconnue';
            $fileManager = new FileManager("Videos/$categorie/$classe/$matiere");
            $value->video_url = $fileManager->get($value->video_url);

            return $value;
        }));

        return view('screen.cours.index', [
            'cours' => $cours,
            'filters' => $filters,
            'perPage' => $perPage,
            'filterDefinitions' => $this->filterDefinitions(),
        ]);
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return Cours::query()
            ->when($filters['search'], fn (Builder $query, string $term) => $query
                ->where(fn (Builder $query) => $query
                    ->whereLike('libelle', $term)
                    ->orWhereLike('description', $term)
                    ->orWhereHas('matiere', fn (Builder $matiere) => $matiere->whereLike('libelle', $term))
                    ->orWhereHas('classe', fn (Builder $classe) => $classe->whereLike('libelle', $term))
                    ->orWhereHas('categorie', fn (Builder $categorie) => $categorie->whereLike('libelle', $term))
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->whereLike('name', $term)
                        ->orWhereLike('last_name', $term))))
            ->when($filters['title'], fn (Builder $query, string $title) => $query->whereLike('libelle', $title))
            ->when($filters['description'], fn (Builder $query, string $description) => $query
                ->whereLike('description', $description))
            ->when($filters['matiere'], fn (Builder $query, string $matiere) => $query
                ->where('matieres_id', $matiere))
            ->when($filters['classe'], fn (Builder $query, string $classe) => $query
                ->where('classe_id', $classe))
            ->when($filters['categorie'], fn (Builder $query, string $categorie) => $query
                ->where('categorie_id', $categorie))
            ->when($filters['access'], fn (Builder $query, string $access) => $query
                ->where('open', $access === 'free'))
            ->when($filters['author'], function (Builder $query, string $author): void {
                $words = preg_split('/\s+/', trim($author), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                $query->whereHas('user', function (Builder $user) use ($words): void {
                    foreach ($words as $word) {
                        $user->where(fn (Builder $inner) => $inner
                            ->whereLike('name', $word)
                            ->orWhereLike('last_name', $word));
                    }
                });
            })
            ->when($filters['from'], fn (Builder $query, string $from) => $query
                ->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query
                ->whereDate('created_at', '<=', $to));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filterDefinitions(): array
    {
        return [
            [
                'key' => 'access',
                'label' => 'Accès',
                'display' => 'chips',
                'allLabel' => 'Tous',
                'options' => [
                    ['value' => 'free', 'label' => 'Gratuit'],
                    ['value' => 'subscription', 'label' => 'Abonnement'],
                ],
            ],
            [
                'key' => 'matiere',
                'label' => 'Matière',
                'type' => 'select',
                'options' => TableFilters::options(Matieres::orderBy('libelle')->get()),
            ],
            [
                'key' => 'classe',
                'label' => 'Classe',
                'type' => 'select',
                'options' => TableFilters::options(Classe::orderBy('libelle')->get()),
            ],
            [
                'key' => 'categorie',
                'label' => 'Catégorie',
                'type' => 'select',
                'options' => TableFilters::options(Categorie::orderBy('libelle')->get()),
            ],
            ['key' => 'title', 'label' => 'Titre', 'type' => 'text', 'placeholder' => 'Titre contient…'],
            ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'placeholder' => 'Description contient…'],
            ['key' => 'author', 'label' => 'Publié par', 'type' => 'text', 'placeholder' => 'Auteur contient…'],
            ['key' => 'from', 'label' => 'Ajouté depuis le', 'type' => 'date'],
            ['key' => 'to', 'label' => 'Ajouté jusqu’au', 'type' => 'date'],
        ];
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
            'cour' => $cour,
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
            $cour = Cours::with('classe', 'matiere', 'categorie')->find($id);
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
