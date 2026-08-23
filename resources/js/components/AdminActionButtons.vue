<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    AlertTriangle,
    BadgeCheck,
    RefreshCw,
    Send,
    X,
} from '@lucide/vue';

const props = defineProps({
    actions: { type: Array, default: () => [] },
    compact: { type: Boolean, default: false },
});

const confirmation = ref(null);
const busy = ref(false);
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const icons = { refresh: RefreshCw, send: Send, activate: BadgeCheck };
const confirmTone = computed(() => confirmation.value?.confirm?.tone ?? confirmation.value?.style ?? 'primary');

function requestAction(action) {
    if (action.disabled || busy.value) return;

    if (action.confirm) {
        confirmation.value = action;
        return;
    }

    submitAction(action);
}

function closeConfirmation() {
    if (!busy.value) confirmation.value = null;
}

function submitAction(action = confirmation.value) {
    if (!action || action.disabled) return;

    const method = String(action.method ?? 'GET').toUpperCase();
    busy.value = true;

    if (method === 'GET') {
        window.location.assign(action.url);
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action.url;
    form.hidden = true;

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = csrf;
    form.appendChild(token);

    if (method !== 'POST') {
        const override = document.createElement('input');
        override.type = 'hidden';
        override.name = '_method';
        override.value = method;
        form.appendChild(override);
    }

    document.body.appendChild(form);
    form.submit();
}

function onKeydown(event) {
    if (event.key === 'Escape') closeConfirmation();
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="admin-action-list" :class="{ compact }">
        <button
            v-for="action in actions"
            :key="`${action.method ?? 'GET'}-${action.url}-${action.label}`"
            type="button"
            class="admin-button"
            :class="action.style ?? 'secondary'"
            :disabled="action.disabled || busy"
            :title="action.disabled ? 'Action indisponible pour ce paiement' : action.label"
            @click="requestAction(action)"
        >
            <component :is="icons[action.icon] ?? BadgeCheck" :size="16" />
            {{ action.label }}
        </button>
    </div>

    <Teleport to="body">
        <Transition name="confirm-modal">
            <div v-if="confirmation" class="admin-confirm-layer" role="dialog" aria-modal="true" :aria-label="confirmation.confirm.title">
                <button class="admin-confirm-backdrop" type="button" aria-label="Fermer" @click="closeConfirmation" />
                <article class="admin-confirm-modal" :class="confirmTone">
                    <header>
                        <span><AlertTriangle :size="21" /></span>
                        <button type="button" class="admin-icon-button" aria-label="Fermer" @click="closeConfirmation"><X :size="20" /></button>
                    </header>
                    <h2>{{ confirmation.confirm.title }}</h2>
                    <p>{{ confirmation.confirm.description }}</p>
                    <div v-if="confirmation.confirm.warning" class="admin-confirm-warning">
                        <AlertTriangle :size="16" />
                        <span>{{ confirmation.confirm.warning }}</span>
                    </div>
                    <footer>
                        <button class="admin-button secondary" type="button" :disabled="busy" @click="closeConfirmation">Annuler</button>
                        <button class="admin-button" :class="confirmTone" type="button" :disabled="busy" @click="submitAction()">
                            <RefreshCw v-if="busy" class="spin" :size="16" />
                            {{ busy ? 'Traitement…' : confirmation.confirm.confirmLabel }}
                        </button>
                    </footer>
                </article>
            </div>
        </Transition>
    </Teleport>
</template>
