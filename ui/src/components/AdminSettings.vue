<template>
    <div class="container" v-if="isAdmin">
        <h2 class="mb-4"><i class="bi bi-gear-fill me-2"></i> Game Configuratie</h2>

        <div v-if="credentials.length > 0" class="alert alert-success shadow-lg border-success p-4">
            <h4 class="alert-heading fw-bold">🚀 De Game is Gestart!</h4>
            <p>Deel de onderstaande inloggegevens uit aan de patrouille leiders. <strong>Deze worden nergens opgeslagen, schrijf ze direct op!</strong></p>
            <hr>
            <div class="row">
                <div class="col-md-4 mb-3" v-for="cred in credentials" :key="cred.gebruiker">
                    <div class="card">
                        <div class="card-body bg-light text-dark">
                            <h5 class="card-title text-primary fw-bold">{{ cred.bedrijf }}</h5>
                            <div><strong>Gebruiker:</strong> {{ cred.gebruiker }}</div>
                            <div><strong>Wachtwoord (PIN):</strong> <span class="fs-4 fw-bold text-danger">{{ cred.wachtwoord }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <button @click="refreshPage" class="btn btn-success mt-3 w-100 fw-bold">Naar Live Dashboard</button>
        </div>

        <div v-else class="row">
            <div class="col-lg-6 mb-4">

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">Economie & Aandelen</h5></div>
                    <div class="card-body">
                        <label class="form-label fw-bold">Startkapitaal (ƒ)</label>
                        <input type="number" v-model="form.starting_cash" class="form-control mb-3">

                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="form-label fw-bold">Totaal (100%)</label>
                                <input type="number" v-model="form.total_shares_per_company" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-bold">Eigen Bezit</label>
                                <input type="number" v-model="form.starting_shares_own" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-bold">Kruisling Bezit</label>
                                <input type="number" v-model="form.starting_shares_cross" class="form-control">
                            </div>
                        </div>
                        <div class="form-text text-muted">De bank krijgt automatisch alle overgebleven aandelen.</div>
                    </div>
                </div>

                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white"><h5 class="mb-0">De Staf (AI / NPC)</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="form.ai_enabled" id="aiToggle">
                            <label class="form-check-label fw-bold" for="aiToggle">De Staf doet mee aan het spel</label>
                        </div>

                        <label class="form-label mt-2">Moeilijkheidsgraad (1 = Makkelijk, 3 = Agressief)</label>
                        <input type="range" class="form-range" min="1" max="3" v-model="form.ai_difficulty" :disabled="!form.ai_enabled">

                        <label class="form-label mt-3 fw-bold">Faillissement Bescherming (ƒ)</label>
                        <input type="number" v-model="form.npc_bankruptcy_safeguard" class="form-control" :disabled="!form.ai_enabled">
                        <div class="form-text text-muted">De Staf zal geen onverwachte kosten meer maken als hun kas onder dit bedrag zakt.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between">
                        <h5 class="mb-0">Patrouilles (Bedrijven)</h5>
                        <button @click="addCompany" class="btn btn-sm btn-light text-primary fw-bold">+ Toevoegen</button>
                    </div>
                    <div class="card-body">
                        <div v-for="(c, index) in form.companies" :key="index" class="d-flex mb-2 gap-2">
                            <input type="color" v-model="c.color" class="form-control form-control-color flex-shrink-0" style="width: 50px;">
                            <input type="text" v-model="c.name" class="form-control" placeholder="Patrouille Naam" required>
                            <button @click="removeCompany(index)" class="btn btn-outline-danger flex-shrink-0"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <button @click="startGame" class="btn btn-success btn-lg w-100 fw-bold shadow" :disabled="loading || form.companies.length < 2">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    {{ loading ? 'Bezig met genereren...' : 'GAME STARTEN & WACHTWOORDEN GENEREREN' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { apiCall } from '../services/api';
import { useAuth } from '../composables/useAuth';

const { isAdmin } = useAuth();
const loading = ref(false);
const error = ref('');
const credentials = ref([]);

const form = ref({
    starting_cash: 100000,
    total_shares_per_company: 100,
    starting_shares_own: 25,
    starting_shares_cross: 5,
    ai_enabled: true,
    ai_difficulty: 2,
    npc_bankruptcy_safeguard: 1000,
    companies: [
        { name: 'Haviken', color: '#ff69b4' },
        { name: 'Spechten', color: '#198754' },
        { name: 'Sperwers', color: '#ffc107' },
        { name: 'Zwaluwen', color: '#0d6efd' },
        { name: 'Valken', color: '#fd7e14' }
    ]
});

// Load the default layout saved in the DB so it remembers previous setups
onMounted(async () => {
    try {
        const settings = await apiCall('/api/game/settings');
        if (settings) {
            form.value.starting_cash = settings.starting_cash;
            form.value.total_shares_per_company = settings.total_shares_per_company;
            form.value.starting_shares_own = settings.starting_shares_own;
            form.value.starting_shares_cross = settings.starting_shares_cross;
            form.value.ai_enabled = settings.ai_enabled === 1;
            form.value.ai_difficulty = settings.ai_difficulty;
            form.value.npc_bankruptcy_safeguard = settings.npc_bankruptcy_safeguard;
        }
    } catch (e) {
        console.error("Kon settings niet laden", e);
    }
});

const addCompany = () => form.value.companies.push({ name: '', color: '#000000' });
const removeCompany = (index) => form.value.companies.splice(index, 1);

const startGame = async () => {
    loading.value = true;
    error.value = '';
    try {
        // Ensure ai_enabled is sent as 1 or 0
        const payload = { ...form.value, ai_enabled: form.value.ai_enabled ? 1 : 0 };

        const response = await apiCall('/api/game/start', 'POST', payload);
        credentials.value = response.credentials;

    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};

const refreshPage = () => {
    window.location.href = '/';
};
</script>
