<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { Check, Copy } from '@lucide/vue';

const props = defineProps({
    value: { type: String, required: true },
    label: { type: String, default: 'Copier' },
    copiedLabel: { type: String, default: 'Copié' },
    variant: { type: String, default: 'secondary' },
});

const copied = ref(false);
const failed = ref(false);
let timer = null;

// navigator.clipboard exige un contexte sécurisé (HTTPS) : repli sur
// execCommand pour une console servie en HTTP.
function fallbackCopy(text) {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(area);

    if (!ok) throw new Error('copy failed');
}

async function copy() {
    failed.value = false;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(props.value);
        } else {
            fallbackCopy(props.value);
        }
        copied.value = true;
    } catch {
        failed.value = true;
    }

    clearTimeout(timer);
    timer = setTimeout(() => {
        copied.value = false;
        failed.value = false;
    }, 2000);
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <button class="admin-button" :class="variant" type="button" :aria-label="`${label} : ${value}`" @click="copy">
        <component :is="copied ? Check : Copy" :size="15" aria-hidden="true" />
        <span aria-live="polite">{{ failed ? 'Copie impossible' : (copied ? copiedLabel : label) }}</span>
    </button>
</template>
