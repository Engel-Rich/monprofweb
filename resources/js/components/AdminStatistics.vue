<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import {
    ArrowRight,
    BadgeCheck,
    Banknote,
    Filter,
    Layers3,
    ReceiptText,
    RotateCcw,
    Sparkles,
} from '@lucide/vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps({
    data: { type: Object, required: true },
    action: { type: String, required: true },
});

const money = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XAF',
    maximumFractionDigits: 0,
});
const number = new Intl.NumberFormat('fr-FR');

const cards = computed(() => [
    {
        label: 'Revenus validés',
        value: money.format(props.data.summary.revenue),
        helper: 'Montant réellement encaissé',
        icon: Banknote,
        tone: 'blue',
    },
    {
        label: 'Paiements validés',
        value: number.format(props.data.summary.payments),
        helper: 'Transactions avec une date de paiement',
        icon: BadgeCheck,
        tone: 'green',
    },
    {
        label: 'Panier moyen',
        value: money.format(props.data.summary.average),
        helper: 'Revenu moyen par paiement',
        icon: ReceiptText,
        tone: 'violet',
    },
    {
        label: 'Catégories performantes',
        value: number.format(props.data.summary.performingCategories),
        helper: 'Catégories ayant généré un revenu',
        icon: Layers3,
        tone: 'orange',
    },
]);

const chartRows = computed(() => props.data.rows.filter((row) => row.revenue > 0).slice(0, 8));
const chartData = computed(() => ({
    labels: chartRows.value.map((row) => row.label),
    datasets: [{
        label: 'Revenus validés',
        data: chartRows.value.map((row) => row.revenue),
        backgroundColor: '#3569ed',
        hoverBackgroundColor: '#1f52d4',
        borderRadius: 7,
        borderSkipped: false,
        barThickness: 18,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: { label: (context) => money.format(context.raw) },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: '#edf0f5' },
            ticks: {
                color: '#7d8796',
                callback: (value) => money.format(value),
            },
        },
        y: {
            border: { display: false },
            grid: { display: false },
            ticks: { color: '#4f5969', font: { weight: 600 } },
        },
    },
};
</script>

<template>
    <section class="admin-page statistics-page">
        <div class="admin-page-heading statistics-heading">
            <div>
                <p class="admin-eyebrow">Analyse commerciale</p>
                <h1>Statistiques des abonnements</h1>
                <p>Comprenez les revenus générés par chaque offre MonProf.</p>
            </div>
            <span class="statistics-scope"><Filter :size="15" /> {{ data.filters.classLabel }}</span>
        </div>

        <form :action="action" method="get" class="statistics-filter admin-panel">
            <div>
                <label for="statistics-class">Classe des élèves</label>
                <select id="statistics-class" name="classe" :value="data.filters.classId ?? ''">
                    <option value="">Toutes les classes</option>
                    <option v-for="schoolClass in data.filters.classes" :key="schoolClass.id" :value="schoolClass.id">
                        {{ schoolClass.label }}
                    </option>
                </select>
            </div>
            <button class="admin-button primary" type="submit"><Filter :size="17" /> Appliquer le filtre</button>
            <a v-if="data.filters.classId" :href="action" class="admin-button secondary"><RotateCcw :size="16" /> Réinitialiser</a>
            <p><BadgeCheck :size="15" /> Seuls les paiements validés, avec une date de paiement, sont comptabilisés.</p>
        </form>

        <div class="statistics-cards">
            <article v-for="card in cards" :key="card.label" class="statistics-card" :class="`tone-${card.tone}`">
                <span><component :is="card.icon" :size="21" /></span>
                <div><p>{{ card.label }}</p><strong>{{ card.value }}</strong><small>{{ card.helper }}</small></div>
            </article>
        </div>

        <div class="statistics-grid">
            <article class="admin-panel statistics-chart-panel">
                <div class="admin-panel-head">
                    <div><p class="admin-eyebrow">Comparaison</p><h2>Revenus par catégorie</h2></div>
                    <span class="admin-period-chip">Top 8</span>
                </div>
                <div v-if="chartRows.length" class="statistics-chart"><Bar :data="chartData" :options="chartOptions" /></div>
                <div v-else class="admin-empty-state statistics-empty">Aucun paiement validé pour cette sélection.</div>
            </article>

            <aside class="statistics-highlight">
                <span><Sparkles :size="20" /></span>
                <p>Catégorie la plus performante</p>
                <template v-if="data.leader">
                    <h2>{{ data.leader.label }}</h2>
                    <strong>{{ money.format(data.leader.revenue) }}</strong>
                    <small>{{ data.leader.payments }} paiement(s) · {{ data.leader.share }} % du revenu</small>
                </template>
                <template v-else>
                    <h2>Aucune donnée</h2>
                    <small>Les performances apparaîtront après le premier paiement validé.</small>
                </template>
            </aside>
        </div>

        <article class="admin-panel statistics-table-panel">
            <div class="admin-panel-head">
                <div><p class="admin-eyebrow">Détail</p><h2>Performance de toutes les catégories</h2></div>
                <span class="statistics-result-count">{{ data.rows.length }} catégorie(s)</span>
            </div>
            <div class="statistics-table-wrap">
                <table class="statistics-table">
                    <thead><tr><th>Catégorie</th><th>État</th><th>Paiements validés</th><th>Panier moyen</th><th>Part du revenu</th><th>Revenus</th></tr></thead>
                    <tbody>
                        <tr v-for="(row, index) in data.rows" :key="row.id">
                            <td data-label="Catégorie"><div class="statistics-category"><span>{{ String(index + 1).padStart(2, '0') }}</span><div><strong>{{ row.label }}</strong><small>{{ row.description || 'Aucune description' }}</small></div></div></td>
                            <td data-label="État"><span class="status-pill" :class="row.active ? 'success' : 'muted'">{{ row.active ? 'Active' : 'Inactive' }}</span></td>
                            <td data-label="Paiements"><strong>{{ number.format(row.payments) }}</strong></td>
                            <td data-label="Panier moyen">{{ money.format(row.average) }}</td>
                            <td data-label="Part du revenu"><div class="statistics-share"><div><span :style="{ width: `${row.share}%` }" /></div><strong>{{ row.share }} %</strong></div></td>
                            <td data-label="Revenus" class="statistics-revenue">{{ money.format(row.revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!data.rows.length" class="admin-empty-state statistics-empty">Aucune catégorie disponible.</div>
            </div>
            <footer class="statistics-table-footer">
                <span>Total pour {{ data.filters.classLabel.toLowerCase() }}</span>
                <strong>{{ money.format(data.summary.revenue) }}</strong>
                <ArrowRight :size="17" />
            </footer>
        </article>
    </section>
</template>
