<template>
    <div class="opdrachten-container">
        <div class="row">
            <div class="col-md-4">

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">1. Kies Niveau</h5>
                    </div>
                    <div class="card-body">
                        <select v-model="selectedCategory" class="form-select" :disabled="loading">
                            <option value="" disabled>Selecteer Categorie...</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="card shadow-sm mb-3" v-if="selectedCategory">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">2. Kies Opdracht</h5>
                    </div>
                    <div class="card-body">
                        <select v-model="selectedTaskId" class="form-select">
                            <option value="" disabled>Selecteer Opdracht...</option>
                            <option v-for="task in filteredTasks" :key="task.id" :value="task.id">
                                {{ task.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="card border-info shadow-sm" v-if="currentTask">
                    <div class="card-body">
                        <h6 class="text-info text-uppercase small fw-bold">Beloningen: {{ currentTask.name }}</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li><i class="bi bi-trophy-fill text-warning"></i> 1e: ƒ {{ currentTask.reward_p1.toLocaleString() }}</li>
                            <li><i class="bi bi-award text-secondary"></i> 2e: ƒ {{ currentTask.reward_p2.toLocaleString() }}</li>
                            <li><i class="bi bi-award-fill text-danger"></i> 3e: ƒ {{ currentTask.reward_p3.toLocaleString() }}</li>
                            <li><i class="bi bi-person text-muted"></i> 4e: ƒ {{ currentTask.reward_p4.toLocaleString() }}</li>
                            <li><i class="bi bi-person text-muted"></i> 5e: ƒ {{ currentTask.reward_p5.toLocaleString() }}</li>

                            <li class="border-top pt-2 mt-2 text-danger">
                                <i class="bi bi-x-circle-fill"></i> <strong>Straf bij falen:</strong> ƒ {{ (currentTask.penalty || 0).toLocaleString() }}
                            </li>
                            <li class="text-muted fst-italic mt-1">
                                <i class="bi bi-exclamation-triangle"></i> Let op: Slechts 1 poging toegestaan!
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-primary h-100">

                    <div class="card-body text-center py-5 d-flex flex-column justify-content-center" v-if="!currentTask">
                        <i class="bi bi-list-check display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">Selecteer een opdracht</h4>
                        <p class="text-muted">Kies links een categorie en opdracht om resultaten in te voeren.</p>
                    </div>

                    <div class="card-body" v-else>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="h4 mb-0 text-primary">{{ currentTask.name }}</h3>
                            <span class="badge bg-secondary">{{ currentTask.category }}</span>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3">Resultaten</h6>

                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-striped align-middle">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 50px">Rank</th>
                                    <th>Bedrijf</th>
                                    <th>Tijdstip</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="entry in allResults" :key="entry.company_id">
                                    <td>
                                        <span v-if="entry.success && entry.rank === 1" class="badge bg-warning text-dark">1e</span>
                                        <span v-else-if="entry.success && entry.rank === 2" class="badge bg-secondary">2e</span>
                                        <span v-else-if="entry.success && entry.rank === 3" class="badge bg-danger">3e</span>
                                        <span v-else-if="entry.success" class="badge bg-light text-dark border">{{ entry.rank }}e</span>

                                        <span v-else class="badge bg-dark">Gefaald</span>
                                    </td>
                                    <td class="fw-bold" :class="{'text-decoration-line-through text-muted': !entry.success}">
                                        {{ entry.company_name }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ new Date(entry.completed_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                    </td>
                                </tr>
                                <tr v-if="allResults.length === 0">
                                    <td colspan="3" class="text-center text-muted fst-italic py-3">
                                        Nog niemand heeft deze opdracht geprobeerd.
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3">Nieuwe Poging Registreren</h6>
                        <div class="row g-3">
                            <div v-for="c in remainingCompanies" :key="c.id" class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm" :style="{ borderLeft: `5px solid ${c.color} !important` }">
                                    <div class="card-body p-0">
                                        <div class="p-2 fw-bold text-truncate border-bottom">{{ c.name }}</div>

                                        <div class="d-flex w-100">
                                            <button
                                                @click="submitCompletion(c.id, true, c.name)"
                                                class="btn btn-success w-50 rounded-0 rounded-bottom-start"
                                                :disabled="processing"
                                                title="Gelukt"
                                            >
                                                <i class="bi bi-check-lg fs-5"></i>
                                            </button>

                                            <button
                                                @click="submitCompletion(c.id, false, c.name)"
                                                class="btn btn-danger w-50 rounded-0 rounded-bottom-end"
                                                :disabled="processing"
                                                title="Mislukt"
                                            >
                                                <i class="bi bi-x-lg fs-5"></i>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="remainingCompanies.length === 0" class="alert alert-secondary mt-3 text-center">
                            <i class="bi bi-check-circle-fill me-2"></i> Iedereen heeft een poging gewaagd!
                        </div>

                        <div v-if="feedback" :class="['alert mt-3', feedbackType === 'error' ? 'alert-danger' : feedbackType === 'penalty' ? 'alert-danger border-danger' : 'alert-success']">
                            <i v-if="feedbackType === 'success'" class="bi bi-trophy-fill me-2"></i>
                            <i v-if="feedbackType === 'penalty'" class="bi bi-exclamation-octagon-fill me-2"></i>
                            {{ feedback }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject, onMounted, computed } from 'vue';
import { apiCall } from '../services/api';

const companies = inject('companies');
const reloadCompanies = inject('reloadCompanies');

const tasks = ref([]);
const loading = ref(false);
const processing = ref(false);
const selectedCategory = ref('');
const selectedTaskId = ref('');
const feedback = ref('');
const feedbackType = ref(''); // 'success', 'error', 'penalty'

const loadTasks = async () => {
    loading.value = true;
    try {
        const data = await apiCall('/api/tasks');
        if (data) tasks.value = data;
    } catch (e) {
        console.error("Failed to load tasks", e);
    } finally {
        loading.value = false;
    }
};

const categories = computed(() => {
    const unique = new Set(tasks.value.map(t => t.category));
    return Array.from(unique).sort();
});

const filteredTasks = computed(() => {
    if (!selectedCategory.value) return [];
    return tasks.value.filter(t => t.category === selectedCategory.value);
});

const currentTask = computed(() => {
    if (!selectedTaskId.value) return null;
    return tasks.value.find(t => t.id === selectedTaskId.value);
});

// MERGE LOGIC: Combine finished_by (Success) and failed_by (Fail)
const allResults = computed(() => {
    if (!currentTask.value) return [];

    const finished = (currentTask.value.finished_by || []).map(entry => ({
        ...entry,
        success: true // Explicitly mark as success for UI
    }));

    const failed = (currentTask.value.failed_by || []).map(entry => ({
        ...entry,
        success: false, // Explicitly mark as failed
        rank: null // Failures have no rank
    }));

    // Merge and sort by completed_at
    const combined = [...finished, ...failed];
    return combined.sort((a, b) => new Date(a.completed_at) - new Date(b.completed_at));
});

// FILTER LOGIC: Remove companies that are in EITHER list
const remainingCompanies = computed(() => {
    if (!currentTask.value) return [];

    const finishedIds = (currentTask.value.finished_by || []).map(f => f.company_id);
    const failedIds = (currentTask.value.failed_by || []).map(f => f.company_id);

    const allAttemptedIds = [...finishedIds, ...failedIds];

    return companies.filter(c => !allAttemptedIds.includes(c.id));
});

/**
 * Submit the result
 */
const submitCompletion = async (companyId, isSuccess, companyName) => {
    const action = isSuccess ? "SUCCES" : "FALEN";
    const msg = isSuccess
        ? `Weet je zeker dat ${companyName} de opdracht heeft voltooid?`
        : `Weet je zeker dat ${companyName} heeft GEFAALD? Dit kost hen de boete.`;

    if (!confirm(`${action}: ${msg}`)) return;

    processing.value = true;
    feedback.value = '';

    try {
        const result = await apiCall('/api/tasks/complete', 'POST', {
            company_id: companyId,
            task_id: selectedTaskId.value,
            success: isSuccess
        });

        if (isSuccess) {
            feedbackType.value = 'success';
            // Result likely contains: reward, success=true
            feedback.value = `${result.company_name} geslaagd! Beloning: ƒ ${result.reward.toLocaleString()}.`;
        } else {
            feedbackType.value = 'penalty';
            // Check if result has penalty field, or just imply it from success=false
            feedback.value = `${result.company_name} gefaald.`;
        }

        // Refresh data to update tables and lists
        await loadTasks();
        await reloadCompanies();

    } catch (e) {
        feedbackType.value = 'error';
        feedback.value = e.message || "Er ging iets mis.";
    } finally {
        processing.value = false;
        setTimeout(() => { feedback.value = ''; }, 5000);
    }
};

onMounted(() => {
    loadTasks();
});
</script>

<style scoped>
.opdrachten-container { min-height: 80vh; }

/* Custom button styling for the 50/50 split */
.rounded-bottom-start {
    border-bottom-left-radius: 0.375rem !important; /* Bootstrap default radius */
}
.rounded-bottom-end {
    border-bottom-right-radius: 0.375rem !important;
}
</style>
