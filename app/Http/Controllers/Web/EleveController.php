<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Support\TableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EleveController extends Controller
{
    /** Clés de filtre reconnues sur la liste des élèves. */
    private const FILTER_KEYS = [
        'search', 'name', 'email', 'phone', 'classe', 'etablissement', 'sexe', 'from', 'to',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = TableFilters::values($request, self::FILTER_KEYS);
        $perPage = TableFilters::perPage($request);

        $eleves = $this->filteredQuery($filters)
            ->with('classe', 'user')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('screen.eleve.index_eleve', [
            'eleves' => $eleves,
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
        return Eleve::query()
            ->when($filters['search'], fn (Builder $query, string $term) => $query
                ->where(fn (Builder $query) => $query
                    ->whereLike('etablissement', $term)
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->whereLike('name', $term)
                        ->orWhereLike('last_name', $term)
                        ->orWhereLike('email', $term)
                        ->orWhereLike('phone', $term))
                    ->orWhereHas('classe', fn (Builder $classe) => $classe->whereLike('libelle', $term))))
            // Le nom peut être saisi en entier (« Jean Dupont ») alors qu'il est
            // stocké en deux colonnes : chaque mot doit correspondre à l'une ou
            // l'autre. Concaténer en SQL aurait lié la requête à un SGBD précis.
            ->when($filters['name'], function (Builder $query, string $name): void {
                $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                $query->whereHas('user', function (Builder $user) use ($words): void {
                    foreach ($words as $word) {
                        $user->where(fn (Builder $inner) => $inner
                            ->whereLike('name', $word)
                            ->orWhereLike('last_name', $word));
                    }
                });
            })
            ->when($filters['email'], fn (Builder $query, string $email) => $query
                ->whereHas('user', fn (Builder $user) => $user->whereLike('email', $email)))
            ->when($filters['phone'], fn (Builder $query, string $phone) => $query
                ->whereHas('user', fn (Builder $user) => $user->whereLike('phone', $phone)))
            ->when($filters['classe'], fn (Builder $query, string $classe) => $query
                ->whereHas('classe', fn (Builder $builder) => $builder->whereLike('libelle', $classe)))
            ->when($filters['etablissement'], fn (Builder $query, string $school) => $query
                ->whereLike('etablissement', $school))
            ->when($filters['sexe'], fn (Builder $query, string $sexe) => $query->where('sexe', $sexe))
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
            ['key' => 'name', 'label' => 'Nom', 'type' => 'text', 'placeholder' => 'Nom contient…'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'placeholder' => 'Email contient…'],
            ['key' => 'phone', 'label' => 'Téléphone', 'type' => 'text', 'placeholder' => 'Numéro contient…'],
            ['key' => 'classe', 'label' => 'Classe', 'type' => 'text', 'placeholder' => 'Classe contient…'],
            ['key' => 'etablissement', 'label' => 'Établissement', 'type' => 'text', 'placeholder' => 'Établissement contient…'],
            [
                'key' => 'sexe',
                'label' => 'Sexe',
                'type' => 'select',
                'options' => [
                    ['value' => 'HOMME', 'label' => 'Homme'],
                    ['value' => 'FEMME', 'label' => 'Femme'],
                ],
            ],
            ['key' => 'from', 'label' => 'Inscrit depuis le', 'type' => 'date'],
            ['key' => 'to', 'label' => 'Inscrit jusqu’au', 'type' => 'date'],
        ];
    }
}
