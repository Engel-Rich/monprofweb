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
></admin-data-table>
