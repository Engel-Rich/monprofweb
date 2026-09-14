<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Outils communs aux tables d'administration filtrées côté serveur.
 *
 * Le filtrage se faisait auparavant dans le navigateur, sur les seules lignes
 * de la page courante : chercher un élève au-delà des 25 premiers résultats
 * était impossible. Ces helpers donnent une base commune aux contrôleurs pour
 * traiter recherche, filtres et pagination sur l'ensemble de la table.
 */
class TableFilters
{
    public const PER_PAGE_OPTIONS = [15, 25, 50, 100, 200];

    public const DEFAULT_PER_PAGE = 25;

    /**
     * Taille de page demandée, bornée aux valeurs proposées : une valeur libre
     * dans l'URL permettrait de réclamer la table entière en une requête.
     */
    public static function perPage(Request $request, int $default = self::DEFAULT_PER_PAGE): int
    {
        $requested = (int) $request->input('per_page', $default);

        return in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : $default;
    }

    /**
     * Valeurs courantes des filtres, renvoyées à la vue pour réafficher les
     * champs tels que l'utilisateur les a saisis.
     *
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    public static function values(Request $request, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $value = $request->input($key);
            $values[$key] = is_scalar($value) ? trim((string) $value) : '';
        }

        return $values;
    }

    /**
     * Caractère d'échappement des clauses LIKE.
     *
     * Volontairement différent de l'antislash : MySQL en fait un échappement
     * implicite, SQLite non, et « ESCAPE '\\' » ne veut pas dire la même chose
     * dans les deux. Un caractère banal se comporte identiquement partout.
     */
    public const LIKE_ESCAPE = '!';

    /**
     * Motif « contient » pour une clause LIKE.
     *
     * Les jokers saisis par l'utilisateur sont neutralisés : sans cela une
     * recherche sur « 100% » ramènerait toute la table, et le « _ » très courant
     * dans les adresses email s'y comporterait comme un caractère libre.
     * À utiliser avec les macros whereLike()/orWhereLike(), qui ajoutent la
     * clause ESCAPE correspondante.
     */
    public static function like(?string $term): string
    {
        $escaped = str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE.self::LIKE_ESCAPE, self::LIKE_ESCAPE.'%', self::LIKE_ESCAPE.'_'],
            trim((string) $term),
        );

        return '%'.$escaped.'%';
    }

    /**
     * Options d'une liste déroulante à partir d'une collection de modèles.
     *
     * @return array<int, array{value: mixed, label: string}>
     */
    public static function options(iterable $models, string $labelAttribute = 'libelle', string $valueAttribute = 'id'): array
    {
        $options = [];

        foreach ($models as $model) {
            $options[] = [
                'value' => $model->{$valueAttribute},
                'label' => (string) $model->{$labelAttribute},
            ];
        }

        return $options;
    }
}
