<?php

namespace App\Providers;

use App\Support\TableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerLikeMacros();
    }

    /**
     * Recherche « contient » sûre, pour les filtres d'administration.
     *
     * Les jokers tapés par l'utilisateur sont neutralisés par
     * TableFilters::like(), à condition que la requête déclare le caractère
     * d'échappement — ce que le constructeur de requêtes ne permet pas via
     * where(..., 'like', ...). D'où ces macros.
     */
    private function registerLikeMacros(): void
    {
        $sql = function (Builder $builder, string $column): string {
            return sprintf(
                '%s LIKE ? ESCAPE \'%s\'',
                $builder->getQuery()->getGrammar()->wrap($column),
                TableFilters::LIKE_ESCAPE,
            );
        };

        Builder::macro('whereLike', function (string $column, ?string $term) use ($sql): Builder {
            /** @var Builder $this */
            return $this->whereRaw($sql($this, $column), [TableFilters::like($term)]);
        });

        Builder::macro('orWhereLike', function (string $column, ?string $term) use ($sql): Builder {
            /** @var Builder $this */
            return $this->orWhereRaw($sql($this, $column), [TableFilters::like($term)]);
        });
    }
}
