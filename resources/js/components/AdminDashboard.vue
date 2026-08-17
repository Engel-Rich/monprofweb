<script setup>
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import {
    ArrowDownRight,
    ArrowRight,
    ArrowUpRight,
    BookOpenCheck,
    CircleHelp,
    Coins,
    MoreHorizontal,
    Plus,
    RefreshCw,
    UsersRound,
} from '@lucide/vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler);

const props = defineProps({
    data: { type: Object, required: true },
    links: { type: Object, required: true },
});

const cardIcons = { students: UsersRound, revenue: Coins, courses: BookOpenCheck, questions: CircleHelp };
const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 });
const number = new Intl.NumberFormat('fr-FR');

const revenueChart = computed(() => ({
    labels: props.data.monthly.labels,
    datasets: [{
        label: 'Revenus',
        data: props.data.monthly.revenue,
        borderColor: '#1f5eff',
        backgroundColor: 'rgba(31, 94, 255, 0.09)',
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#1f5eff',
        pointBorderWidth: 3,
        pointRadius: 4,
        tension: 0.38,
        fill: true,
    }],
}));

const studentChart = computed(() => ({
    labels: props.data.monthly.labels,
    datasets: [{
        label: 'Nouveaux élèves',
        data: props.data.monthly.students,
        backgroundColor: '#dce7ff',
        hoverBackgroundColor: '#1f5eff',
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#77808f' } },
        y: { beginAtZero: true, border: { display: false }, grid: { color: '#eef1f5' }, ticks: { color: '#77808f', callback: (value) => `${Math.round(value / 1000)}k` } },
    },
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#77808f' } },
        y: { beginAtZero: true, border: { display: false }, grid: { color: '#eef1f5' }, ticks: { precision: 0, color: '#77808f' } },
    },
};

function formatCard(card) {
    return card.format === 'currency' ? money.format(card.value) : number.format(card.value);
}

function refresh() {
    window.location.reload();
}
</script>

<template>
    <section class="admin-page dashboard-page">
        <div class="admin-page-heading dashboard-heading">
            <div>
                <p class="admin-eyebrow">Vue d’ensemble</p>
                <h1>Bonjour, {{ data.userName ?? 'Admin' }} 👋</h1>
                <p>Voici les indicateurs clés de la plateforme MonProf.</p>
            </div>
            <div class="admin-heading-actions">
                <button class="admin-button secondary" type="button" @click="refresh"><RefreshCw :size="17" /> Actualiser</button>
                <a :href="links.createCourse" class="admin-button primary"><Plus :size="18" /> Nouveau cours</a>
            </div>
        </div>

        <div class="dashboard-cards">
            <article v-for="card in data.cards" :key="card.key" class="metric-card" :class="`tone-${card.tone}`">
                <div class="metric-card-top">
                    <span class="metric-icon"><component :is="cardIcons[card.key]" :size="21" /></span>
                    <button class="admin-icon-button subtle" type="button" aria-label="Options"><MoreHorizontal :size="19" /></button>
                </div>
                <p>{{ card.label }}</p>
                <strong>{{ formatCard(card) }}</strong>
                <div class="metric-foot">
                    <span v-if="card.change !== null" :class="card.change >= 0 ? 'positive' : 'negative'">
                        <ArrowUpRight v-if="card.change >= 0" :size="15" />
                        <ArrowDownRight v-else :size="15" />
                        {{ Math.abs(card.change) }}%
                    </span>
                    <small>{{ card.helper }}</small>
                </div>
            </article>
        </div>

        <div class="dashboard-grid-main">
            <article class="admin-panel chart-panel revenue-panel">
                <div class="admin-panel-head">
                    <div><p class="admin-eyebrow">Performance</p><h2>Évolution des revenus</h2></div>
                    <span class="admin-period-chip">6 derniers mois</span>
                </div>
                <div class="dashboard-chart"><Line :data="revenueChart" :options="lineOptions" /></div>
            </article>

            <article class="admin-panel chart-panel">
                <div class="admin-panel-head">
                    <div><p class="admin-eyebrow">Acquisition</p><h2>Nouveaux élèves</h2></div>
                </div>
                <div class="dashboard-chart compact"><Bar :data="studentChart" :options="barOptions" /></div>
            </article>
        </div>

        <div class="dashboard-grid-bottom">
            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div><p class="admin-eyebrow">Offres</p><h2>Catégories performantes</h2></div>
                    <a :href="links.statistics">Voir les statistiques <ArrowRight :size="16" /></a>
                </div>
                <div class="category-performance">
                    <div v-for="(category, index) in data.categories" :key="category.id" class="category-row">
                        <span class="category-rank">{{ String(index + 1).padStart(2, '0') }}</span>
                        <div><strong>{{ category.label }}</strong><small>{{ category.payments }} paiement(s) validé(s)</small></div>
                        <span class="status-pill" :class="category.active ? 'success' : 'muted'">{{ category.active ? 'Active' : 'Inactive' }}</span>
                        <strong>{{ money.format(category.revenue) }}</strong>
                    </div>
                    <div v-if="!data.categories.length" class="admin-empty-state">Aucune donnée de paiement disponible.</div>
                </div>
            </article>

            <article class="admin-panel operations-panel">
                <div class="admin-panel-head"><div><p class="admin-eyebrow">Opérations</p><h2>À surveiller</h2></div></div>
                <div class="operations-list">
                    <a :href="links.payments"><span class="operation-dot orange" /><div><strong>{{ number.format(data.operations.pendingPayments) }}</strong><small>Paiements en attente</small></div><ArrowRight :size="17" /></a>
                    <a :href="links.questions"><span class="operation-dot violet" /><div><strong>{{ number.format(data.cards.find((card) => card.key === 'questions')?.value ?? 0) }}</strong><small>Questions sans réponse</small></div><ArrowRight :size="17" /></a>
                    <a :href="links.codes"><span class="operation-dot green" /><div><strong>{{ number.format(data.operations.activeCodes) }}</strong><small>Codes activés</small></div><ArrowRight :size="17" /></a>
                    <div class="operation-summary"><span><b>{{ data.operations.successfulTransactions }}</b> transactions réussies</span><span><b>{{ data.operations.failedTransactions }}</b> échouées</span></div>
                </div>
            </article>
        </div>

        <article class="admin-panel recent-panel">
            <div class="admin-panel-head">
                <div><p class="admin-eyebrow">Activité récente</p><h2>Derniers paiements</h2></div>
                <a :href="links.payments">Tous les paiements <ArrowRight :size="16" /></a>
            </div>
            <div class="recent-payment-list">
                <a v-for="payment in data.recentPayments" :key="payment.id" :href="payment.url" class="recent-payment-row">
                    <span class="admin-user-avatar light">{{ payment.customer.slice(0, 2).toUpperCase() }}</span>
                    <div><strong>{{ payment.customer }}</strong><small>{{ payment.category }} · {{ payment.date }}</small></div>
                    <span class="status-pill" :class="payment.status === 'Validé' ? 'success' : 'warning'">{{ payment.status }}</span>
                    <strong>{{ money.format(payment.amount) }}</strong>
                    <ArrowRight :size="17" />
                </a>
                <div v-if="!data.recentPayments.length" class="admin-empty-state">Aucun paiement récent.</div>
            </div>
        </article>
    </section>
</template>
