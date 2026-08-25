<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    Eye,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    SlidersHorizontal,
    X,
} from '@lucide/vue';
import AdminActionButtons from './AdminActionButtons.vue';

const props = defineProps({
    eyebrow: { type: String, default: 'Gestion' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    columns: { type: Array, required: true },
    items: { type: Array, required: true },
    createUrl: { type: String, default: '' },
    createLabel: { type: String, default: 'Ajouter' },
    pagination: { type: Object, default: () => ({}) },
    searchPlaceholder: { type: String, default: 'Rechercher…' },
    filters: { type: Array, default: () => [] },
});

const query = ref('');
const statusFilter = ref('all');
const extraFilters = ref(Object.fromEntries(props.filters.map((filter) => [filter.key, 'all'])));
const showFilters = ref(false);
const selected = ref(null);
const drawer = ref(null);

const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 });
const number = new Intl.NumberFormat('fr-FR');

const statuses = computed(() => [...new Set(props.items.map((item) => item.status).filter(Boolean))]);
const searchableKeys = computed(() => props.columns.filter((column) => column.searchable !== false).map((column) => column.key));
const filteredItems = computed(() => {
    const needle = query.value.trim().toLocaleLowerCase('fr');

    return props.items.filter((item) => {
        const matchesStatus = statusFilter.value === 'all' || item.status === statusFilter.value;
        const matchesExtraFilters = props.filters.every((filter) => {
            const selectedValue = extraFilters.value[filter.key] ?? 'all';
            return selectedValue === 'all' || String(resolve(item, filter.key) ?? '') === String(selectedValue);
        });
        const searchableValues = [
            ...searchableKeys.value.map((key) => resolve(item, key)),
            item.searchText,
        ];
        const matchesSearch = !needle || searchableValues.some((value) => String(value ?? '').toLocaleLowerCase('fr').includes(needle));
        return matchesStatus && matchesExtraFilters && matchesSearch;
    });
});
const activeExtraFilterCount = computed(() => Object.values(extraFilters.value).filter((value) => value !== 'all').length);

function resolve(item, key) {
    return key.split('.').reduce((value, part) => value?.[part], item);
}

function format(value, type) {
    if (value === null || value === undefined || value === '') return '—';
    if (type === 'currency') return money.format(Number(value));
    if (type === 'number') return number.format(Number(value));
    if (type === 'boolean') return value ? 'Oui' : 'Non';
    return value;
}

function badgeClass(value) {
    const normalized = String(value ?? '').toLowerCase();
    if (['échoué', 'inactif', 'failed', 'annulé', 'révoqué'].some((status) => normalized.includes(status))) return 'danger';
    if (['attente', 'pending'].some((status) => normalized.includes(status))) return 'warning';
    if (['validé', 'activé', 'actif', 'active', 'répondue', 'gratuit', 'success'].some((status) => normalized.includes(status))) return 'success';
    return 'muted';
}

async function openDrawer(item) {
    selected.value = item;
    await nextTick();
    drawer.value?.focus();
}

function closeDrawer() {
    selected.value = null;
}

function onKeydown(event) {
    if (event.key === 'Escape') closeDrawer();
}

watch(selected, (value) => document.body.classList.toggle('admin-no-scroll', Boolean(value)));
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('admin-no-scroll');
});
</script>

<template>
    <section class="admin-page">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">{{ eyebrow }}</p>
                <h1>{{ title }}</h1>
                <p>{{ description }}</p>
            </div>
            <a v-if="createUrl" :href="createUrl" class="admin-button primary"><Plus :size="18" /> {{ createLabel }}</a>
        </div>

        <div class="list-summary-grid">
            <article><span>Total sur cette page</span><strong>{{ number.format(items.length) }}</strong></article>
            <article v-if="statuses.length"><span>{{ statuses[0] }}</span><strong>{{ number.format(items.filter((item) => item.status === statuses[0]).length) }}</strong></article>
            <article v-if="statuses.length > 1"><span>{{ statuses[1] }}</span><strong>{{ number.format(items.filter((item) => item.status === statuses[1]).length) }}</strong></article>
        </div>

        <article class="admin-panel data-table-panel">
            <div class="table-toolbar">
                <label class="admin-search-field">
                    <Search :size="18" />
                    <input v-model="query" type="search" :placeholder="searchPlaceholder">
                    <span v-if="query" role="button" tabindex="0" @click="query = ''"><X :size="15" /></span>
                </label>
                <div class="table-filter-chips" v-if="statuses.length">
                    <button type="button" :class="{ active: statusFilter === 'all' }" @click="statusFilter = 'all'">Tous</button>
                    <button v-for="status in statuses" :key="status" type="button" :class="{ active: statusFilter === status }" @click="statusFilter = status">{{ status }}</button>
                </div>
                <button v-if="filters.length" class="admin-button secondary compact" :class="{ active: activeExtraFilterCount }" type="button" @click="showFilters = !showFilters"><SlidersHorizontal :size="16" /> Filtres <span v-if="activeExtraFilterCount">{{ activeExtraFilterCount }}</span></button>
            </div>
            <div v-if="filters.length && showFilters" class="table-advanced-filters">
                <label v-for="filter in filters" :key="filter.key">
                    <span>{{ filter.label }}</span>
                    <select v-model="extraFilters[filter.key]">
                        <option value="all">{{ filter.allLabel ?? 'Tous' }}</option>
                        <option v-for="option in filter.options" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                    </select>
                </label>
                <button v-if="activeExtraFilterCount" type="button" @click="Object.keys(extraFilters).forEach((key) => extraFilters[key] = 'all')">Réinitialiser</button>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th v-for="column in columns" :key="column.key">{{ column.label }}</th><th aria-label="Actions" /></tr></thead>
                    <tbody>
                        <tr v-for="item in filteredItems" :key="item.id" tabindex="0" @click="openDrawer(item)" @keydown.enter="openDrawer(item)">
                            <td v-for="column in columns" :key="column.key" :data-label="column.label">
                                <span v-if="column.type === 'status'" class="status-pill" :class="badgeClass(resolve(item, column.key))">{{ format(resolve(item, column.key), column.type) }}</span>
                                <div v-else-if="column.type === 'identity'" class="table-identity">
                                    <span class="admin-user-avatar light">{{ String(resolve(item, column.key) ?? '?').slice(0, 2).toUpperCase() }}</span>
                                    <div><strong>{{ resolve(item, column.key) }}</strong><small v-if="column.secondaryKey">{{ resolve(item, column.secondaryKey) }}</small></div>
                                </div>
                                <div v-else-if="column.type === 'imageIdentity'" class="table-identity">
                                    <img v-if="resolve(item, column.imageKey ?? 'imageUrl')" class="table-identity-image" :src="resolve(item, column.imageKey ?? 'imageUrl')" alt="">
                                    <span v-else class="admin-user-avatar light">{{ String(resolve(item, column.key) ?? '?').slice(0, 2).toUpperCase() }}</span>
                                    <div><strong>{{ resolve(item, column.key) }}</strong><small v-if="column.secondaryKey">{{ resolve(item, column.secondaryKey) }}</small></div>
                                </div>
                                <strong v-else-if="column.emphasis">{{ format(resolve(item, column.key), column.type) }}</strong>
                                <span v-else class="table-cell-text" :class="{ truncate: column.truncate }">{{ format(resolve(item, column.key), column.type) }}</span>
                            </td>
                            <td class="table-actions"><button type="button" aria-label="Voir les détails" @click.stop="openDrawer(item)"><MoreHorizontal :size="19" /></button></td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!filteredItems.length" class="admin-empty-state large"><Search :size="28" /><strong>Aucun résultat</strong><span>Essayez une autre recherche ou retirez un filtre.</span></div>
            </div>

            <div v-if="pagination.total" class="table-pagination">
                <p>Affichage de <strong>{{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }}</strong> sur <strong>{{ pagination.total }}</strong></p>
                <div>
                    <a :href="pagination.prevUrl || '#'" :class="{ disabled: !pagination.prevUrl }" aria-label="Page précédente"><ChevronLeft :size="17" /></a>
                    <span>Page {{ pagination.currentPage }} sur {{ pagination.lastPage }}</span>
                    <a :href="pagination.nextUrl || '#'" :class="{ disabled: !pagination.nextUrl }" aria-label="Page suivante"><ChevronRight :size="17" /></a>
                </div>
            </div>
        </article>

        <Teleport to="body">
            <Transition name="drawer">
                <div v-if="selected" class="drawer-layer" role="dialog" aria-modal="true" :aria-label="`Détails de ${title}`">
                    <div class="drawer-backdrop" @click="closeDrawer" />
                    <aside ref="drawer" class="admin-drawer" tabindex="-1">
                        <header class="drawer-header">
                            <div><p class="admin-eyebrow">{{ eyebrow }}</p><h2>{{ selected.drawerTitle ?? selected.name ?? selected.label ?? `Détail #${selected.id}` }}</h2></div>
                            <button class="admin-icon-button" type="button" aria-label="Fermer" @click="closeDrawer"><X :size="22" /></button>
                        </header>

                        <div class="drawer-body">
                            <div v-if="selected.highlight" class="drawer-highlight" :class="badgeClass(selected.status)">
                                <div><span>{{ selected.highlight.label }}</span><strong>{{ format(selected.highlight.value, selected.highlight.type) }}</strong><small>{{ selected.highlight.helper }}</small></div>
                                <span v-if="selected.status" class="status-pill" :class="badgeClass(selected.status)">{{ selected.status }}</span>
                            </div>

                            <video v-if="selected.videoUrl" class="drawer-video" controls :src="selected.videoUrl" />
                            <img v-if="selected.imageUrl" class="drawer-image" :src="selected.imageUrl" :alt="selected.imageAlt ?? 'Illustration'">

                            <section v-for="group in selected.details ?? []" :key="group.title" class="drawer-section">
                                <div class="drawer-section-title"><h3>{{ group.title }}</h3><span v-if="group.note">{{ group.note }}</span></div>
                                <dl>
                                    <template v-for="field in group.fields" :key="field.label">
                                        <dt>{{ field.label }}</dt>
                                        <dd>
                                            <span v-if="field.type === 'status'" class="status-pill" :class="badgeClass(field.value)">{{ field.value }}</span>
                                            <a v-else-if="field.type === 'email'" :href="`mailto:${field.value}`">{{ field.value }}</a>
                                            <a v-else-if="field.type === 'phone'" :href="`tel:${field.value}`">{{ field.value }}</a>
                                            <div v-else-if="field.type === 'tags'" class="drawer-tags">
                                                <span v-for="tag in (field.value ?? [])" :key="tag">{{ tag }}</span>
                                                <em v-if="!(field.value ?? []).length">Aucun élément associé</em>
                                            </div>
                                            <code v-else-if="field.type === 'code'" class="drawer-code">{{ format(field.value, field.type) }}</code>
                                            <pre v-else-if="field.type === 'json'" class="drawer-json">{{ field.value ? JSON.stringify(field.value, null, 2) : '—' }}</pre>
                                            <span v-else>{{ format(field.value, field.type) }}</span>
                                        </dd>
                                    </template>
                                </dl>
                            </section>
                        </div>

                        <footer class="drawer-footer">
                            <button class="admin-button secondary" type="button" @click="closeDrawer"><ArrowLeft :size="17" /> Fermer</button>
                            <AdminActionButtons v-if="selected.actions?.length" :actions="selected.actions" compact />
                            <a v-if="selected.editUrl" :href="selected.editUrl" class="admin-button primary"><Pencil :size="17" /> Modifier</a>
                            <a v-else-if="selected.actionUrl" :href="selected.actionUrl" class="admin-button primary"><Eye :size="17" /> {{ selected.actionLabel ?? 'Ouvrir' }}</a>
                        </footer>
                    </aside>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>
