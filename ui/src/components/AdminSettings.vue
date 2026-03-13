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
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Non Player Companies</h5>
                        <button @click="addNpc" class="btn btn-sm btn-light text-danger fw-bold">+ Toevoegen</button>
                    </div>
                    <div class="card-body">

                        <div v-if="form.npcs.length === 0" class="alert alert-secondary py-2 text-center fst-italic">
                            Geen NPC's ingesteld. De AI is momenteel uitgeschakeld.
                        </div>

                        <div v-for="(npc, index) in form.npcs" :key="'npc'+index" class="d-flex mb-2 gap-2">
                            <input type="color" v-model="npc.color" class="form-control form-control-color flex-shrink-0" style="width: 50px;">
                            <input type="text" v-model="npc.name" class="form-control border-danger" placeholder="Naam van NPC (bijv. De Staf)" required>
                            <button @click="removeNpc(index)" class="btn btn-outline-danger flex-shrink-0 fw-bold">✖ Wis</button>
                        </div>

                        <div v-if="form.npcs.length > 0" class="mt-4 pt-3 border-top">
                            <label class="form-label fw-bold text-danger">Moeilijkheidsgraad ({{ form.ai_difficulty }})</label>
                            <input type="range" class="form-range" min="1" max="3" v-model="form.ai_difficulty">

                            <div class="alert mt-2 p-2 small" :class="difficultyStyle">
                                <strong><i class="bi bi-info-circle-fill me-1"></i> Wat doet de AI?</strong><br>
                                {{ difficultyDescription }}
                            </div>

                            <label class="form-label mt-3 fw-bold">Faillissement Bescherming (ƒ)</label>
                            <input type="number" v-model="form.npc_bankruptcy_safeguard" class="form-control border-danger">
                            <div class="form-text text-muted">De NPC zal geen onverwachte kosten meer maken als hun kas onder dit bedrag zakt.</div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Patrouilles (Bedrijven)</h5>
                        <button @click="addCompany" class="btn btn-sm btn-light text-primary fw-bold">+ Toevoegen</button>
                    </div>
                    <div class="card-body">
                        <div v-for="(c, index) in form.companies" :key="'comp'+index" class="d-flex mb-2 gap-2">
                            <input type="color" v-model="c.color" class="form-control form-control-color flex-shrink-0" style="width: 50px;">
                            <input type="text" v-model="c.name" class="form-control border-primary" placeholder="Patrouille Naam" required>
                            <button @click="removeCompany(index)" class="btn btn-outline-danger flex-shrink-0 fw-bold">
                                ✖ Wis
                            </button>
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
import { ref, onMounted, computed } from 'vue';
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
    ai_difficulty: 2,
    npc_bankruptcy_safeguard: 1000,
    npcs: [
        { name: 'De Staf', color: '#E53935' }
    ],
    companies: [
        { name: 'Haviken', color: '#ff69b4' },
        { name: 'Spechten', color: '#198754' },
        { name: 'Sperwers', color: '#ffc107' },
        { name: 'Zwaluwen', color: '#0d6efd' },
        { name: 'Valken', color: '#fd7e14' }
    ]
});

const difficultyDescription = computed(() => {
    switch(Number(form.value.ai_difficulty)) {
        case 1: return "Makkelijk: De NPC is relatief passief. Deelt voornamelijk subsidies uit en krijgt zelden onverwachte kosten. Neemt geen bedrijven over.";
        case 2: return "Normaal: Actief gebalanceerd. Regelmatig subsidies en kosten. Activeert het 'Aasgier Protocol' (Vulture Mode): koopt zwakke bedrijven op met 30% korting als ze failliet dreigen te gaan.";
        case 3: return "Agressief: Meedogenloos! Vaak torenhoge onverwachte kosten en zelden subsidies. Maakt agressief gebruik van het 'Aasgier Protocol' om de markt te domineren.";
        default: return "";
    }
});

const difficultyStyle = computed(() => {
    switch(Number(form.value.ai_difficulty)) {
        case 1: return "alert-success";
        case 2: return "alert-warning";
        case 3: return "alert-danger border-danger";
        default: return "alert-secondary";
    }
});

onMounted(async () => {
    try {
        const settings = await apiCall('/api/game/settings');
        if (settings) {
            form.value.starting_cash = settings.starting_cash;
            form.value.total_shares_per_company = settings.total_shares_per_company;
            form.value.starting_shares_own = settings.starting_shares_own;
            form.value.starting_shares_cross = settings.starting_shares_cross;
            form.value.ai_difficulty = settings.ai_difficulty;
            form.value.npc_bankruptcy_safeguard = settings.npc_bankruptcy_safeguard;

            if (settings.ai_enabled === 0 || settings.ai_enabled === false) {
                form.value.npcs = [];
            }
        }
    } catch (e) {
        console.error("Kon settings niet laden", e);
    }
});

const addCompany = () => form.value.companies.push({ name: '', color: '#000000' });
const removeCompany = (index) => form.value.companies.splice(index, 1);

// DYNAMIC DEFAULT NPC LOGIC
const addNpc = () => {
    if (form.value.npcs.length === 0) {
        form.value.npcs.push({ name: 'De Staf', color: '#E53935' });
    } else {
        form.value.npcs.push({ name: 'Nieuwe NPC', color: '#343a40' });
    }
};

const removeNpc = (index) => form.value.npcs.splice(index, 1);

const startGame = async () => {
    loading.value = true;
    error.value = '';
    try {
        const payload = {
            ...form.value,
            ai_enabled: form.value.npcs.length > 0 ? 1 : 0
        };

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
