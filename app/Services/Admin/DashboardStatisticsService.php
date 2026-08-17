<?php

namespace App\Services\Admin;

use App\Models\Categorie;
use App\Models\Codes;
use App\Models\Cours;
use App\Models\Eleve;
use App\Models\Paiements;
use App\Models\Questions;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardStatisticsService
{
    public function build(): array
    {
        $now = now();
        $currentPeriodStart = $now->copy()->subDays(30)->startOfDay();
        $previousPeriodStart = $now->copy()->subDays(60)->startOfDay();

        $studentsCurrent = Eleve::whereBetween('created_at', [$currentPeriodStart, $now])->count();
        $studentsPrevious = Eleve::whereBetween('created_at', [$previousPeriodStart, $currentPeriodStart])->count();
        $coursesCurrent = Cours::whereBetween('created_at', [$currentPeriodStart, $now])->count();
        $coursesPrevious = Cours::whereBetween('created_at', [$previousPeriodStart, $currentPeriodStart])->count();

        $validatedPayments = Paiements::query()->whereNotNull('paiement_date');
        $currentRevenue = (float) (clone $validatedPayments)
            ->whereBetween('paiement_date', [$currentPeriodStart, $now])
            ->sum('montant');
        $previousRevenue = (float) (clone $validatedPayments)
            ->whereBetween('paiement_date', [$previousPeriodStart, $currentPeriodStart])
            ->sum('montant');

        $questionCount = Questions::count();
        $pendingQuestions = Questions::whereDoesntHave('reponse')->count();
        $answeredQuestions = max(0, $questionCount - $pendingQuestions);

        return [
            'generatedAt' => $now->toIso8601String(),
            'cards' => [
                [
                    'key' => 'students',
                    'label' => 'Élèves inscrits',
                    'value' => Eleve::count(),
                    'change' => $this->percentageChange($studentsCurrent, $studentsPrevious),
                    'helper' => $studentsCurrent.' nouveaux sur 30 jours',
                    'tone' => 'blue',
                ],
                [
                    'key' => 'revenue',
                    'label' => 'Revenus validés',
                    'value' => (float) (clone $validatedPayments)->sum('montant'),
                    'format' => 'currency',
                    'change' => $this->percentageChange($currentRevenue, $previousRevenue),
                    'helper' => 'Paiements avec date de validation',
                    'tone' => 'green',
                ],
                [
                    'key' => 'courses',
                    'label' => 'Cours disponibles',
                    'value' => Cours::count(),
                    'change' => $this->percentageChange($coursesCurrent, $coursesPrevious),
                    'helper' => Cours::where('open', 1)->count().' cours gratuits',
                    'tone' => 'violet',
                ],
                [
                    'key' => 'questions',
                    'label' => 'Questions en attente',
                    'value' => $pendingQuestions,
                    'change' => null,
                    'helper' => $answeredQuestions.' réponses sur '.$questionCount.' questions',
                    'tone' => 'orange',
                ],
            ],
            'monthly' => $this->monthlySeries(),
            'categories' => $this->topCategories(),
            'operations' => [
                'activeCodes' => Codes::where('actif', 1)->count(),
                'unusedCodes' => Codes::where('actif', 0)->count(),
                'pendingPayments' => Paiements::whereNull('paiement_date')->count(),
                'successfulTransactions' => Transaction::where('status', 'SUCCESS')->count(),
                'failedTransactions' => Transaction::where('status', 'FAILED')->count(),
            ],
            'recentPayments' => $this->recentPayments(),
        ];
    }

    private function monthlySeries(): array
    {
        $months = collect(range(5, 0))->map(function (int $offset) {
            $date = now()->startOfMonth()->subMonths($offset);

            return [
                'key' => $date->format('Y-m'),
                'label' => ucfirst($date->locale('fr')->translatedFormat('M')),
                'start' => $date->copy(),
                'end' => $date->copy()->endOfMonth(),
            ];
        });

        $payments = Paiements::whereNotNull('paiement_date')
            ->whereBetween('paiement_date', [$months->first()['start'], $months->last()['end']])
            ->get(['montant', 'paiement_date']);

        $students = Eleve::whereBetween('created_at', [$months->first()['start'], $months->last()['end']])
            ->get(['created_at']);

        return [
            'labels' => $months->pluck('label')->all(),
            'revenue' => $months->map(fn (array $month) => (float) $payments
                ->filter(fn (Paiements $payment) => Carbon::parse($payment->paiement_date)->format('Y-m') === $month['key'])
                ->sum('montant'))->all(),
            'students' => $months->map(fn (array $month) => $students
                ->filter(fn (Eleve $student) => $student->created_at->format('Y-m') === $month['key'])
                ->count())->all(),
        ];
    }

    private function topCategories(): array
    {
        return Categorie::query()
            ->withSum(['paiements as validated_revenue' => fn ($query) => $query->whereNotNull('paiement_date')], 'montant')
            ->withCount(['paiements as validated_payments_count' => fn ($query) => $query->whereNotNull('paiement_date')])
            ->orderByDesc('validated_revenue')
            ->limit(5)
            ->get()
            ->map(fn (Categorie $category) => [
                'id' => $category->id,
                'label' => $category->libelle,
                'revenue' => (float) ($category->validated_revenue ?? 0),
                'payments' => (int) $category->validated_payments_count,
                'active' => (bool) $category->status,
            ])
            ->all();
    }

    private function recentPayments(): array
    {
        return Paiements::with(['user:id,name,last_name,email', 'categorie:id,libelle'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Paiements $payment) => [
                'id' => $payment->id,
                'customer' => trim(($payment->user?->name ?? '').' '.($payment->user?->last_name ?? '')) ?: 'Utilisateur inconnu',
                'email' => $payment->user?->email,
                'category' => $payment->categorie?->libelle ?? 'Sans catégorie',
                'amount' => (float) $payment->montant,
                'status' => $payment->paiement_date ? 'Validé' : 'En attente',
                'date' => $payment->created_at?->locale('fr')->translatedFormat('d M Y, H:i'),
                'url' => route('paiement.active', $payment->id),
            ])
            ->all();
    }

    private function percentageChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0 : 100;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
