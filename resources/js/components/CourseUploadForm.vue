<script setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    CloudUpload,
    FileVideo,
    LoaderCircle,
    Save,
    Trash2,
    X,
} from '@lucide/vue';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    csrf: { type: String, required: true },
    course: { type: Object, default: () => ({}) },
    subjects: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    indexUrl: { type: String, required: true },
    deleteUrl: { type: String, default: '' },
});

const form = reactive({
    libelle: props.course.libelle ?? '',
    description: props.course.description ?? '',
    matieres_id: props.course.matieres_id ?? props.subjects[0]?.id ?? '',
    classe_id: props.course.classe_id ?? props.classes[0]?.id ?? '',
    categorie_id: props.course.categorie_id ?? props.categories[0]?.id ?? '',
    open: Number(props.course.open ?? 0),
});

const file = ref(null);
const dragActive = ref(false);
const progress = ref(0);
const state = ref('idle');
const errors = ref({});
const globalError = ref('');

const isEditing = computed(() => Boolean(props.course.id));
const selectedFileLabel = computed(() => file.value ? `${file.value.name} · ${formatBytes(file.value.size)}` : 'MP4, MOV ou WEBM — 500 Mo maximum');
const stateLabel = computed(() => {
    if (state.value === 'uploading') return `Envoi de la vidéo… ${progress.value}%`;
    if (state.value === 'processing') return 'Transfert terminé. Chiffrement et stockage en cours…';
    if (state.value === 'success') return 'Cours enregistré avec succès';
    return '';
});

function formatBytes(bytes) {
    if (!bytes) return '0 o';
    const units = ['o', 'Ko', 'Mo', 'Go'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** index)).toFixed(index > 1 ? 1 : 0)} ${units[index]}`;
}

function selectFile(candidate) {
    globalError.value = '';
    if (!candidate) return;
    if (!candidate.type.startsWith('video/')) {
        globalError.value = 'Le fichier sélectionné doit être une vidéo.';
        return;
    }
    if (candidate.size > 500 * 1024 * 1024) {
        globalError.value = 'La vidéo dépasse la limite de 500 Mo.';
        return;
    }
    file.value = candidate;
}

function onDrop(event) {
    dragActive.value = false;
    selectFile(event.dataTransfer.files?.[0]);
}

async function submit() {
    errors.value = {};
    globalError.value = '';

    if (!isEditing.value && !file.value) {
        errors.value.video = ['Ajoutez une vidéo avant de continuer.'];
        return;
    }

    const payload = new FormData();
    Object.entries(form).forEach(([key, value]) => payload.append(key, String(value)));
    payload.append('_token', props.csrf);
    if (props.method.toUpperCase() !== 'POST') payload.append('_method', props.method.toUpperCase());
    if (file.value) payload.append('video', file.value);

    state.value = 'uploading';
    progress.value = 0;

    try {
        const response = await axios.post(props.action, payload, {
            headers: { Accept: 'application/json' },
            onUploadProgress(event) {
                if (!event.total) return;
                progress.value = Math.min(100, Math.round((event.loaded * 100) / event.total));
                if (progress.value === 100) state.value = 'processing';
            },
        });
        state.value = 'success';
        window.location.assign(response.data.redirect ?? props.indexUrl);
    } catch (error) {
        state.value = 'idle';
        progress.value = 0;
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
            globalError.value = error.response.data.message ?? 'Certains champs doivent être corrigés.';
        } else {
            globalError.value = error.response?.data?.message ?? 'Impossible d’enregistrer le cours. Réessayez.';
        }
    }
}

async function removeCourse() {
    if (!props.deleteUrl || !window.confirm('Voulez-vous vraiment supprimer ce cours ?')) return;

    globalError.value = '';
    state.value = 'processing';
    try {
        await axios.delete(props.deleteUrl, {
            data: { _token: props.csrf },
            headers: { Accept: 'application/json' },
        });
        window.location.assign(props.indexUrl);
    } catch (error) {
        state.value = 'idle';
        globalError.value = error.response?.data?.message ?? 'Impossible de supprimer ce cours.';
    }
}
</script>

<template>
    <section class="admin-page course-form-page">
        <div class="admin-page-heading">
            <div>
                <a :href="indexUrl" class="admin-back-link"><ArrowLeft :size="16" /> Retour aux cours</a>
                <p class="admin-eyebrow">Bibliothèque pédagogique</p>
                <h1>{{ isEditing ? 'Modifier le cours' : 'Ajouter un nouveau cours' }}</h1>
                <p>Renseignez les informations pédagogiques puis importez la ressource vidéo.</p>
            </div>
        </div>

        <form class="course-form-grid" @submit.prevent="submit">
            <div class="admin-panel form-panel">
                <div class="form-section-heading"><span>01</span><div><h2>Informations générales</h2><p>Les informations visibles par les élèves.</p></div></div>

                <label class="admin-field">
                    <span>Titre du cours</span>
                    <input v-model="form.libelle" type="text" required placeholder="Ex. Les fonctions affines">
                    <small v-if="errors.libelle" class="field-error">{{ errors.libelle[0] }}</small>
                </label>

                <label class="admin-field">
                    <span>Description</span>
                    <textarea v-model="form.description" rows="7" required placeholder="Présentez les objectifs et le contenu du cours…" />
                    <small>{{ form.description.length }} caractères</small>
                    <small v-if="errors.description" class="field-error">{{ errors.description[0] }}</small>
                </label>

                <div class="form-field-grid three">
                    <label class="admin-field"><span>Matière</span><select v-model="form.matieres_id" required><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.libelle }}</option></select></label>
                    <label class="admin-field"><span>Classe</span><select v-model="form.classe_id" required><option v-for="level in classes" :key="level.id" :value="level.id">{{ level.libelle }}</option></select></label>
                    <label class="admin-field"><span>Catégorie</span><select v-model="form.categorie_id" required><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.libelle }}</option></select></label>
                </div>

                <div class="admin-field">
                    <span>Type d’accès</span>
                    <div class="access-choice-grid">
                        <label :class="{ active: form.open === 1 }"><input v-model="form.open" type="radio" :value="1"><span><Check :size="17" /> Gratuit</span><small>Accessible à tous les élèves</small></label>
                        <label :class="{ active: form.open === 0 }"><input v-model="form.open" type="radio" :value="0"><span><Check :size="17" /> Abonnement</span><small>Réservé aux comptes éligibles</small></label>
                    </div>
                </div>
            </div>

            <div class="course-form-side">
                <div class="admin-panel form-panel">
                    <div class="form-section-heading"><span>02</span><div><h2>Ressource vidéo</h2><p>Importez la vidéo associée au cours.</p></div></div>
                    <label
                        class="video-dropzone"
                        :class="{ active: dragActive, selected: file }"
                        @dragover.prevent="dragActive = true"
                        @dragleave.prevent="dragActive = false"
                        @drop.prevent="onDrop"
                    >
                        <input type="file" accept="video/*" @change="selectFile($event.target.files?.[0])">
                        <span class="video-drop-icon"><FileVideo v-if="file" :size="28" /><CloudUpload v-else :size="28" /></span>
                        <strong>{{ file ? file.name : isEditing ? 'Remplacer la vidéo' : 'Déposez votre vidéo ici' }}</strong>
                        <p>{{ selectedFileLabel }}</p>
                        <span class="admin-button secondary compact">Parcourir les fichiers</span>
                        <button v-if="file" class="remove-file" type="button" aria-label="Retirer la vidéo" @click.prevent="file = null"><X :size="17" /></button>
                    </label>
                    <small v-if="errors.video" class="field-error">{{ errors.video[0] }}</small>

                    <div v-if="state !== 'idle'" class="upload-progress-card" :class="state">
                        <div class="upload-progress-head">
                            <span><LoaderCircle v-if="state !== 'success'" class="spin" :size="18" /><CheckCircle2 v-else :size="18" />{{ stateLabel }}</span>
                            <strong v-if="state === 'uploading'">{{ progress }}%</strong>
                        </div>
                        <div class="upload-progress-track"><span :style="{ width: `${progress}%` }" /></div>
                        <small v-if="state === 'processing'">Cette étape peut prendre quelques instants pour une vidéo volumineuse.</small>
                    </div>
                </div>

                <div v-if="globalError" class="admin-alert error">{{ globalError }}</div>

                <div class="form-actions-card">
                    <button v-if="isEditing && deleteUrl" class="admin-button danger" type="button" :disabled="state !== 'idle'" @click="removeCourse"><Trash2 :size="17" /> Supprimer</button>
                    <a :href="indexUrl" class="admin-button secondary">Annuler</a>
                    <button class="admin-button primary" type="submit" :disabled="state !== 'idle'">
                        <LoaderCircle v-if="state !== 'idle'" class="spin" :size="18" /><Save v-else :size="18" />
                        {{ state === 'idle' ? (isEditing ? 'Enregistrer les modifications' : 'Publier le cours') : 'Traitement en cours…' }}
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>
