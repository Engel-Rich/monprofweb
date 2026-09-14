@props([
    'eyebrow' => 'Gestion',
    'title',
    'description' => '',
    'columns' => [],
    'items' => [],
    'createUrl' => '',
    'createLabel' => 'Ajouter',
    'paginator' => null,
    'searchPlaceholder' => 'Rechercher…',
    'filters' => [],
    // Filtrage côté serveur : recherche, filtres et taille de page passent par
    // l'URL et sont traités par le contrôleur, sur toute la table.
    'serverSide' => false,
    'filterValues' => [],
    'searchKey' => 'search',
    'perPage' => null,
    'perPageOptions' => \App\Support\TableFilters::PER_PAGE_OPTIONS,
])

@php
    $pagination = $paginator ? [
        'from' => $paginator->firstItem(),
        'to' => $paginator->lastItem(),
        'total' => $paginator->total(),
        'currentPage' => $paginator->currentPage(),
        'lastPage' => $paginator->lastPage(),
        'prevUrl' => $paginator->previousPageUrl(),
        'nextUrl' => $paginator->nextPageUrl(),
    ] : [];
@endphp

<admin-data-table
    eyebrow="{{ $eyebrow }}"
    title="{{ $title }}"
    description="{{ $description }}"
    :columns="{{ Js::from($columns) }}"
    :items="{{ Js::from($items) }}"
    create-url="{{ $createUrl }}"
    create-label="{{ $createLabel }}"
    :pagination="{{ Js::from($pagination) }}"
    search-placeholder="{{ $searchPlaceholder }}"
    :filters="{{ Js::from($filters) }}"
    :server-side="{{ Js::from((bool) $serverSide) }}"
    :filter-values="{{ Js::from($filterValues) }}"
    search-key="{{ $searchKey }}"
    per-page="{{ $perPage }}"
    :per-page-options="{{ Js::from($perPageOptions) }}"
></admin-data-table>
