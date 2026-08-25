<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->decimal('base_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('service_fee', 12, 2)->default(0)->after('base_amount');
            $table->decimal('provider_fee_percentage', 8, 4)->default(2.5)->after('service_fee');
            $table->decimal('user_fee_percentage', 5, 2)->default(100)->after('provider_fee_percentage');
            $table->string('conclusion_method', 20)->nullable()->after('raison_reject');
        });

        DB::table('transactions')
            ->select(['id', 'amount'])
            ->orderBy('id')
            ->chunkById(500, function ($transactions): void {
                $baseAmounts = DB::table('paiements')
                    ->whereIn('transaction_id', $transactions->pluck('id'))
                    ->pluck('montant', 'transaction_id');

                foreach ($transactions as $transaction) {
                    if (! $baseAmounts->has($transaction->id)) {
                        continue;
                    }

                    $baseAmount = (float) $baseAmounts->get($transaction->id);
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'base_amount' => $baseAmount,
                            'service_fee' => max(0, (float) $transaction->amount - $baseAmount),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn([
                'base_amount',
                'service_fee',
                'provider_fee_percentage',
                'user_fee_percentage',
                'conclusion_method',
            ]);
        });
    }
};
