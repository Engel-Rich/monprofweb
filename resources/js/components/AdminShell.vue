<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    BarChart3,
    Bell,
    BookOpen,
    Boxes,
    ChevronRight,
    CircleDollarSign,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    LogOut,
    Menu,
    MessageSquareText,
    Search,
    Settings,
    Shapes,
    TicketCheck,
    UserRoundCog,
    UsersRound,
    X,
} from '@lucide/vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    user: { type: Object, required: true },
    current: { type: String, default: '' },
    logoutUrl: { type: String, required: true },
});

const sidebarOpen = ref(false);
const profileOpen = ref(false);
const searchOpen = ref(false);
const searchQuery = ref('');
const searchInput = ref(null);

const icons = {
    dashboard: LayoutDashboard,
    classes: GraduationCap,
    subjects: BookOpen,
    categories: Shapes,
    students: UsersRound,
    teachers: UserRoundCog,
    courses: Boxes,
    questions: ClipboardList,
    messages: MessageSquareText,
    suggestions: Bell,
    payments: CircleDollarSign,
    codes: TicketCheck,
    statistics: BarChart3,
    settings: Settings,
};

const initials = computed(() => `${props.user.name?.[0] ?? ''}${props.user.lastName?.[0] ?? ''}`.toUpperCase());
const currentItem = computed(() => props.items.find((item) => item.active) ?? props.items[0]);
const filteredNavigation = computed(() => {
    const needle = searchQuery.value.trim().toLocaleLowerCase('fr');
    return needle
        ? props.items.filter((item) => item.label.toLocaleLowerCase('fr').includes(needle))
        : props.items;
});

watch(sidebarOpen, (value) => {
    document.body.classList.toggle('admin-no-scroll', value);
});

function closeOnEscape(event) {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchOpen.value = true;
        nextTick(() => searchInput.value?.focus());
        return;
    }
    if (event.key === 'Escape') {
        sidebarOpen.value = false;
        profileOpen.value = false;
        searchOpen.value = false;
    }
}

function openSearch() {
    searchOpen.value = true;
    nextTick(() => searchInput.value?.focus());
}

function closeSearch() {
    searchOpen.value = false;
    searchQuery.value = '';
}

onMounted(() => window.addEventListener('keydown', closeOnEscape));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', closeOnEscape);
    document.body.classList.remove('admin-no-scroll');
});
</script>

<template>
    <div class="admin-shell">
        <div v-if="sidebarOpen" class="admin-sidebar-overlay" @click="sidebarOpen = false" />

        <aside class="admin-sidebar" :class="{ 'is-open': sidebarOpen }">
            <div class="admin-brand">
                <img class="admin-brand-mark" :src="'/images/logo.png'" alt="" width="42" height="42">
                <div class="admin-brand-identity">
                    <img class="admin-brand-wordmark" :src="'/images/mp2.png'" alt="MonProf" width="92" height="32">
                    <span>Console administrateur</span>
                </div>
                <button class="admin-icon-button admin-sidebar-close" type="button" aria-label="Fermer le menu" @click="sidebarOpen = false">
                    <X :size="20" />
                </button>
            </div>

            <nav class="admin-nav" aria-label="Navigation de l’administration">
                <template v-for="section in ['principal', 'contenu', 'support']" :key="section">
                    <p class="admin-nav-label">{{ section === 'principal' ? 'Pilotage' : section === 'contenu' ? 'Gestion' : 'Communication' }}</p>
                    <a
                        v-for="item in items.filter((entry) => entry.section === section)"
                        :key="item.label"
                        :href="item.url"
                        class="admin-nav-item"
                        :class="{ active: item.active }"
                        @click="sidebarOpen = false"
                    >
                        <component :is="icons[item.icon] ?? LayoutDashboard" :size="19" :stroke-width="1.8" />
                        <span>{{ item.label }}</span>
                        <span v-if="item.badge" class="admin-nav-badge">{{ item.badge }}</span>
                        <ChevronRight v-else-if="item.active" class="admin-nav-chevron" :size="16" />
                    </a>
                </template>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-user-avatar">{{ initials }}</div>
                <div class="admin-sidebar-user">
                    <strong>{{ user.name }} {{ user.lastName }}</strong>
                    <span>Administrateur</span>
                </div>
                <a :href="logoutUrl" class="admin-icon-button" aria-label="Se déconnecter"><LogOut :size="18" /></a>
            </div>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button class="admin-icon-button admin-menu-button" type="button" aria-label="Ouvrir le menu" @click="sidebarOpen = true">
                        <Menu :size="21" />
                    </button>
                    <div class="admin-breadcrumb">
                        <span>Admin</span>
                        <ChevronRight :size="15" />
                        <strong>{{ currentItem?.label ?? current }}</strong>
                    </div>
                </div>

                <div class="admin-topbar-actions">
                    <button class="admin-search-trigger" type="button" aria-label="Rechercher dans l’administration" @click="openSearch">
                        <Search :size="18" />
                        <span>Rechercher…</span>
                        <kbd>⌘ K</kbd>
                    </button>
                    <button class="admin-icon-button admin-notification-button" type="button" aria-label="Notifications">
                        <Bell :size="19" />
                        <span class="admin-notification-dot" />
                    </button>
                    <div class="admin-profile-wrap">
                        <button class="admin-profile-button" type="button" @click="profileOpen = !profileOpen">
                            <span class="admin-user-avatar small">{{ initials }}</span>
                            <span class="admin-profile-copy"><strong>{{ user.name }}</strong><small>Admin</small></span>
                        </button>
                        <div v-if="profileOpen" class="admin-profile-menu">
                            <div><strong>{{ user.name }} {{ user.lastName }}</strong><span>{{ user.email }}</span></div>
                            <a :href="logoutUrl"><LogOut :size="17" /> Se déconnecter</a>
                        </div>
                    </div>
                </div>
            </header>

            <main class="admin-main">
                <slot />
            </main>
        </div>

        <Teleport to="body">
            <Transition name="search-modal">
                <div v-if="searchOpen" class="admin-search-layer" role="dialog" aria-modal="true" aria-label="Rechercher une page">
                    <button class="admin-search-backdrop" type="button" aria-label="Fermer" @click="closeSearch" />
                    <div class="admin-command-menu">
                        <label>
                            <Search :size="20" />
                            <input ref="searchInput" v-model="searchQuery" type="search" placeholder="Rechercher une rubrique…">
                            <kbd>ESC</kbd>
                        </label>
                        <p>Navigation rapide</p>
                        <div>
                            <a v-for="item in filteredNavigation" :key="item.label" :href="item.url">
                                <component :is="icons[item.icon] ?? LayoutDashboard" :size="18" />
                                <span>{{ item.label }}</span>
                                <ChevronRight :size="16" />
                            </a>
                            <span v-if="!filteredNavigation.length" class="admin-command-empty">Aucune rubrique trouvée.</span>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
