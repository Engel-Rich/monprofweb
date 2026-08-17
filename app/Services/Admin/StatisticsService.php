<?php

namespace App\Services\Admin;

use App\Models\Categorie;
use App\Models\Classe;

class StatisticsService
{
    public function build(?int $classId = null): array
    {
        $classes = Classe::query()->orderBy('libelle')->get(['id', 'libelle']);
        $selectedClass = $classId ? $classes->firstWhere('id', $classId) : null;
        $classId = $selectedClass?->id;

        $validatedPayments = function ($query) use ($classId) {
            $query->whereNotNull('paiement_date');

            if ($classId) {
                $query->whereHas('user.eleve', fn ($student) => $student->where('classe_id', $classId));
            }
        };

        $categories = Categorie::query()
            ->withCount(['paiements as validated_payments_count' => $validatedPayments])
            ->withSum(['paiements as validated_revenue' => $validatedPayments], 'montant')
            ->orderByDesc('validated_revenue')
            ->get();

        $totalRevenue = (float) $categories->sum(fn ($category) => (float) ($category->validated_revenue ?? 0));
        $totalPayments = (int) $categories->sum('validated_payments_count');

        $rows = $categories->map(function ($category) use ($totalRevenue) {
            $revenue = (float) ($category->validated_revenue ?? 0);
            $payments = (int) $category->validated_payments_count;

            return [
                'id' => $category->id,
                'label' => $category->libelle,
                'description' => $category->description,
                'price' => (float) ($category->prix ?? 0),
                'active' => (bool) $category->status,
                'payments' => $payments,
                'revenue' => $revenue,
                'average' => $payments > 0 ? round($revenue / $payments, 2) : 0,
                'share' => $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 1) : 0,
            ];
        })->values();

        $leader = $rows->first(fn ($row) => $row['revenue'] > 0);

        return [
            'filters' => [
                'classId' => $classId,
                'classLabel' => $selectedClass?->libelle ?? 'Toutes les classes',
                'classes' => $classes->map(fn ($class) => [
                    'id' => $class->id,
                    'label' => $class->libelle,
                ])->values(),
            ],
            'summary' => [
                'revenue' => $totalRevenue,
                'payments' => $totalPayments,
                'average' => $totalPayments > 0 ? round($totalRevenue / $totalPayments, 2) : 0,
                'performingCategories' => $rows->where('revenue', '>', 0)->count(),
            ],
            'leader' => $leader,
            'rows' => $rows,
        ];
    }
}
