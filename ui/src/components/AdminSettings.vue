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
            
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-excel-fill me-2"></i>Opdrachten (CSV / Excel Import)</h5>
                        <button @click="downloadTemplate" class="btn btn-sm btn-light text-success fw-bold">
                            <i class="bi bi-download me-1"></i> Download Template
                        </button>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4 border-bottom pb-3">
                            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-server me-2"></i>Huidige Database Status</h6>
                            
                            <div v-if="currentCategories.length > 0" class="table-responsive">
                                <table class="table table-sm table-bordered align-middle text-center mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Categorie</th>
                                            <th># Taken</th>
                                            <th>1e Prijs</th>
                                            <th class="text-danger">Boete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(cat, i) in currentCategories" :key="'curr'+i">
                                            <tr @click="cat.expanded = !cat.expanded" style="cursor: pointer;" class="table-hover">
                                                <td class="text-start fw-bold text-primary user-select-none">
                                                    <i :class="cat.expanded ? 'bi bi-chevron-down' : 'bi bi-chevron-right'"></i> {{ cat.name }}
                                                </td>
                                                <td><span class="badge bg-secondary">{{ cat.tasks.length }}</span></td>
                                                <td class="text-success">ƒ {{ Number(cat.reward_p1).toLocaleString() }}</td>
                                                <td class="text-danger fw-bold">ƒ {{ Number(cat.penalty).toLocaleString() }}</td>
                                            </tr>
                                            <tr v-if="cat.expanded" class="bg-light">
                                                <td colspan="4" class="text-start small p-3">
                                                    <ul class="mb-0 text-muted" style="column-count: 2; column-gap: 20px;">
                                                        <li v-for="(taskName, idx) in cat.tasks" :key="idx">{{ taskName }}</li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="alert alert-secondary py-2 text-center fst-italic mb-0">
                                Er staan momenteel geen opdrachten in de database. Upload een CSV bestand om te beginnen.
                            </div>
                        </div>

                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Upload een ingevuld CSV bestand (.csv)</label>
                                <input type="file" class="form-control border-success" accept=".csv" @change="handleFileUpload" :disabled="loading">
                            </div>
                            
                            <div class="col-md-4 text-end mt-4 mt-md-0" v-if="currentCategories.length > 0">
                                <button @click="clearTasks" class="btn btn-outline-danger fw-bold" :disabled="loading">
                                    <i class="bi bi-trash-fill me-1"></i> Wis Huidige Opdrachten
                                </button>
                            </div>
                        </div>

                        <div v-if="parsedCategories.length > 0" class="alert alert-light border shadow-sm mt-4">
                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-eye-fill me-2"></i>Preview: Klik op een categorie om de opdrachten te zien</h6>
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle text-center">
                                    <thead class="table-success">
                                        <tr>
                                            <th class="text-start">Categorie</th>
                                            <th># Taken</th>
                                            <th>1e Prijs</th>
                                            <th class="text-danger">Boete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(cat, i) in parsedCategories" :key="'prev'+i">
                                            <tr @click="cat.expanded = !cat.expanded" style="cursor: pointer;" class="table-hover">
                                                <td class="text-start fw-bold text-primary user-select-none">
                                                    <i :class="cat.expanded ? 'bi bi-chevron-down' : 'bi bi-chevron-right'"></i> {{ cat.name }}
                                                </td>
                                                <td><span class="badge bg-secondary">{{ cat.tasks.length }}</span></td>
                                                <td class="text-success">ƒ {{ Number(cat.reward_p1).toLocaleString() }}</td>
                                                <td class="text-danger fw-bold">ƒ {{ Number(cat.penalty).toLocaleString() }}</td>
                                            </tr>
                                            <tr v-if="cat.expanded" class="bg-light">
                                                <td colspan="4" class="text-start small p-3">
                                                    <ul class="mb-0 text-muted" style="column-count: 2; column-gap: 20px;">
                                                        <li v-for="(taskName, idx) in cat.tasks" :key="idx">{{ taskName }}</li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <button @click="importTasks" class="btn btn-success w-100 fw-bold shadow-sm mt-2" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Importeren & Opslaan (Oude taken worden overschreven)
                            </button>
                        </div>
                        
                        <div v-if="taskFeedback" :class="['alert mt-3 mb-0', taskFeedbackType === 'error' ? 'alert-danger' : 'alert-success']">
                            {{ taskFeedback }}
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
const currentCategories = ref([]);
const parsedCategories = ref([]);
const taskFeedback = ref('');
const taskFeedbackType = ref('');

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

// --- TASK FETCHING LOGIC ---
const loadCurrentTasks = async () => {
    try {
        const data = await apiCall('/api/tasks');
        if (data && data.length > 0) {
            const categoryMap = {};
            data.forEach(t => {
                if (!categoryMap[t.category]) {
                    categoryMap[t.category] = {
                        name: t.category,
                        reward_p1: t.reward_p1,
                        penalty: t.penalty,
                        tasks: [],
                        expanded: false // UI Helper
                    };
                }
                categoryMap[t.category].tasks.push(t.name);
            });
            currentCategories.value = Object.values(categoryMap);
        } else {
            currentCategories.value = [];
        }
    } catch (e) {
        console.error("Kon huidige taken niet laden", e);
    }
};

// --- CSV IMPORTER LOGIC ---

const downloadTemplate = () => {
    // We gebruiken nu de overzichtelijke, gegroepeerde layout!
    const csvData = 
`sep=;
Categorie;Opdracht;Prijs_1e;Prijs_2e;Prijs_3e;Prijs_4e;Prijs_5e;Boete
3e Klasse;;25000;12500;5000;-12500;-25000;-25000
;Kruissjorring;;;;;;
;8-vormige sjorring;;;;;;
;Blokkenstel inscheren;;;;;;
2e Klasse;;50000;25000;10000;-25000;-50000;-50000
;Dubbele werpanker;;;;;;
;Diagonaalssjorring;;;;;;
;Vorkssjorring;;;;;;
1e Klasse;;100000;50000;20000;-50000;-100000;-100000
;Oogsplits;;;;;;
;Eindsplits;;;;;;
;Tussensplits;;;;;;
Algemeen/Overige;;50000;25000;10000;-25000;-50000;-50000
;Kaartenhuis 6 verdiepingen;;;;;;
;Kruiwagen hout halen;;;;;;
;Koffie aan de staf;;;;;;
Vragen;;5000;2500;1000;-2500;-5000;-5000
;Naam Groep 3 (1911);;;;;;
;Hopman Grijze Driehoek;;;;;;
;Oprichting Camerons;;;;;;`;

    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + encodeURIComponent(csvData);
    const link = document.createElement("a");
    link.setAttribute("href", csvContent);
    link.setAttribute("download", "Aandelenspel_Opdrachten_Template.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const handleFileUpload = (event) => {
    taskFeedback.value = '';
    const file = event.target.files[0];
    if (!file) return;

    if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
        taskFeedbackType.value = 'error';
        taskFeedback.value = "Oeps! Je hebt een Excel bestand (.xlsx) geselecteerd. Sla het bestand in Excel eerst op als CSV (Opslaan als > CSV) en upload die versie.";
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const text = e.target.result;
            // Filter lege regels EN de 'sep=;' regel eruit
            const lines = text.split(/\r\n|\n/).filter(line => line.trim().length > 0 && !line.startsWith('sep='));
            
            const headerLine = lines[0];
            let delimiter = ';';
            if (headerLine.includes(',') && !headerLine.includes(';')) {
                delimiter = ',';
            }
            
            lines.shift();
            
            const categoryMap = {};
            let currentCategoryName = '';

            lines.forEach((line) => {
                const cols = line.split(delimiter);
                
                const colCat = cols[0] ? cols[0].trim() : '';
                const colTask = cols[1] ? cols[1].trim() : '';

                if (!colCat && !colTask) return;

                if (colCat !== '') {
                    currentCategoryName = colCat;

                    const hasPrices = !isNaN(parseInt(cols[2]));

                    if (!categoryMap[currentCategoryName]) {
                        categoryMap[currentCategoryName] = {
                            name: currentCategoryName,
                            reward_p1: parseInt(cols[2]) || 0,
                            reward_p2: parseInt(cols[3]) || 0,
                            reward_p3: parseInt(cols[4]) || 0,
                            reward_p4: parseInt(cols[5]) || 0,
                            reward_p5: parseInt(cols[6]) || 0,
                            penalty: parseInt(cols[7]) || 0,
                            tasks: [],
                            expanded: false
                        };
                    } else if (hasPrices) {
                        categoryMap[currentCategoryName].reward_p1 = parseInt(cols[2]) || 0;
                        categoryMap[currentCategoryName].reward_p2 = parseInt(cols[3]) || 0;
                        categoryMap[currentCategoryName].reward_p3 = parseInt(cols[4]) || 0;
                        categoryMap[currentCategoryName].reward_p4 = parseInt(cols[5]) || 0;
                        categoryMap[currentCategoryName].reward_p5 = parseInt(cols[6]) || 0;
                        categoryMap[currentCategoryName].penalty = parseInt(cols[7]) || 0;
                    }
                }

                if (colTask !== '') {
                    // Fallback voor als iemand vergeet bovenaan een Categorie aan te maken
                    if (!currentCategoryName) {
                        currentCategoryName = 'Overig';
                        if (!categoryMap[currentCategoryName]) {
                            categoryMap[currentCategoryName] = { name: 'Overig', reward_p1: 0, reward_p2: 0, reward_p3: 0, reward_p4: 0, reward_p5: 0, penalty: 0, tasks: [], expanded: false };
                        }
                    }
                    categoryMap[currentCategoryName].tasks.push(colTask);
                }
            });

            parsedCategories.value = Object.values(categoryMap);
            
            if(parsedCategories.value.length === 0) {
                 taskFeedbackType.value = 'error';
                 taskFeedback.value = "Geen geldige rijen gevonden. Controleer of het bestand correct is opgeslagen.";
            }
        } catch (err) {
            taskFeedbackType.value = 'error';
            taskFeedback.value = "Fout bij het uitlezen van het bestand.";
        }
    };
    reader.readAsText(file);
};

const importTasks = async () => {
    if (parsedCategories.value.length === 0) return;
    loading.value = true;
    taskFeedback.value = '';

    try {
        await apiCall('/api/tasks/import', 'POST', { categories: parsedCategories.value });
        taskFeedbackType.value = 'success';
        taskFeedback.value = "Opdrachten succesvol geïmporteerd in de database!";
        parsedCategories.value = []; 
        document.querySelector('input[type="file"]').value = '';
        
        await loadCurrentTasks();
    } catch (e) {
        taskFeedbackType.value = 'error';
        taskFeedback.value = e.message;
    } finally {
        loading.value = false;
    }
};

const clearTasks = async () => {
    if (!confirm("Weet je zeker dat je ALLE bestaande opdrachten uit het systeem wilt wissen? Dit kan niet ongedaan worden gemaakt!")) return;
    loading.value = true;
    taskFeedback.value = '';
    try {
        await apiCall('/api/tasks/all', 'DELETE');
        taskFeedbackType.value = 'success';
        taskFeedback.value = "Alle opdrachten zijn permanent gewist uit de database.";
        
        await loadCurrentTasks();
    } catch (e) {
        taskFeedbackType.value = 'error';
        taskFeedback.value = e.message;
    } finally {
        loading.value = false;
    }
};

// --- GAME LOGIC & HELPERS ---

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
    
    await loadCurrentTasks();
});

const addCompany = () => {
    if (form.value.companies.length === 0) form.value.companies.push({ name: 'Haviken', color: '#ff69b4' });
    else if (form.value.companies.length === 1) form.value.companies.push({ name: 'Spechten', color: '#198754' });
    else if (form.value.companies.length === 2) form.value.companies.push({ name: 'Sperwers', color: '#ffc107' });
    else if (form.value.companies.length === 3) form.value.companies.push({ name: 'Zwaluwen', color: '#0d6efd' });
    else if (form.value.companies.length === 4) form.value.companies.push({ name: 'Valken', color: '#fd7e14' });
    else form.value.companies.push({ name: 'Patrouille naam', color: '#000000' });
};
const removeCompany = (index) => form.value.companies.splice(index, 1);

const addNpc = () => {
    if (form.value.npcs.length === 0) form.value.npcs.push({ name: 'De Staf', color: '#E53935' });
    else form.value.npcs.push({ name: 'Nieuwe NPC', color: '#343a40' });
};
const removeNpc = (index) => form.value.npcs.splice(index, 1);

const startGame = async () => {
    loading.value = true;
    error.value = '';
    try {
        const payload = { ...form.value, ai_enabled: form.value.npcs.length > 0 ? 1 : 0 };
        const response = await apiCall('/api/game/start', 'POST', payload);
        credentials.value = response.credentials;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};

const refreshPage = () => { window.location.href = '/'; };
</script>

<style scoped>
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.025);
}
</style>