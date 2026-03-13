<template>
    <div id="app">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-primary">
            <div class="container">
                <span class="navbar-brand fw-bold text-uppercase">
                  <i class="bi bi-graph-up-arrow me-2"></i>Aandelen Spel
                </span>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">

                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <router-link to="/" class="nav-link" active-class="active">Regels</router-link>
                        </li>

                        <template v-if="username && gameState === 'ACTIVE'">
                            <li class="nav-item"><router-link to="/opdrachten" class="nav-link" active-class="active">Opdrachten</router-link></li>
                            <li class="nav-item"><router-link to="/aandelen" class="nav-link" active-class="active">Aandelenverdeling</router-link></li>
                            <li class="nav-item"><router-link to="/grafiek" class="nav-link" active-class="active">Grafiek</router-link></li>

                            <li class="nav-item">
                                <router-link to="/transacties" class="nav-link" active-class="active">
                                    {{ isAdmin ? 'Admin Overzicht' : 'Mijn Rekening' }}
                                </router-link>
                            </li>

                            <li class="nav-item">
                                <router-link to="/handelsverzoeken" class="nav-link position-relative" active-class="active">
                                    <i class="bi bi-briefcase-fill me-1"></i> Handelsverzoeken
                                    <span v-if="incomingOffersCount > 0" class="position-absolute top-10 start-100 translate-middle badge rounded-pill bg-danger shadow-sm animate-pop">
                                        {{ incomingOffersCount }}
                                        <span class="visually-hidden">Nieuwe verzoeken</span>
                                    </span>
                                </router-link>
                            </li>
                        </template>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item" v-if="!username">
                            <router-link to="/login" class="nav-link fw-bold text-success">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Inloggen
                            </router-link>
                        </li>

                        <li class="nav-item dropdown" v-if="username">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5 me-2"></i>
                                <span class="fw-bold">{{ username }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow" aria-labelledby="navbarDropdown">
                                <li><h6 class="dropdown-header">Ingelogd als {{ isAdmin ? 'Admin' : 'Bedrijf' }}</h6></li>

                                <template v-if="isAdmin">
                                    <li><hr class="dropdown-divider"></li>
                                    <li v-if="gameState === 'SETUP'">
                                        <router-link to="/setup" class="dropdown-item text-warning">
                                            <i class="bi bi-gear-fill me-2"></i> Game Setup
                                        </router-link>
                                    </li>
                                    <li v-if="gameState === 'ACTIVE'">
                                        <button @click="triggerReset" class="dropdown-item text-danger fw-bold">
                                            <i class="bi bi-exclamation-octagon-fill me-2"></i> Stop & Reset Game
                                        </button>
                                    </li>
                                </template>

                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button @click="logout" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Uitloggen
                                    </button>
                                </li>
                            </ul>
                        </li>
                    </ul>

                </div>
            </div>
        </nav>

        <main class="container my-4">
            <div class="row mb-4" v-if="username && gameState === 'ACTIVE'">
                <div v-for="company in companies" :key="company.id" class="col-md-4 col-lg">
                    <div class="card text-white shadow-sm mb-2"
                         :style="{ backgroundColor: company.color, borderColor: company.color }">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div>
                                <h6 class="card-title mb-0 fw-bold">{{ company.name }}</h6>
                                <small class="opacity-75">Aandeel: ƒ {{ (company.stock_price || 0).toLocaleString() }}</small>
                            </div>

                            <h5 class="mb-0" v-if="typeof company.cash === 'number'">
                                ƒ {{ company.cash.toLocaleString() }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning text-center fw-bold shadow-sm" v-if="username && gameState === 'SETUP' && !isAdmin">
                De game wordt momenteel voorbereid door de spelleiding. Even geduld a.u.b...
            </div>

            <router-view></router-view>
        </main>
    </div>
</template>

<script setup>
import { provide, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useGameEngine } from './composables/useGameEngine';
import { useAuth } from './composables/useAuth';
import { apiCall } from './services/api';

// Initialize Engine
const {
    companies,
    history,
    graphTrigger,
    gameState,
    loadGameState,
    loadCompanies,
    startEngine,
    stopEngine
} = useGameEngine();

// Initialize Auth
const { initAuth, isAdmin, logout, username, myCompanyId } = useAuth();
const router = useRouter();

// Notifications
const incomingOffersCount = ref(0);
let offerPollingTimer = null;

const checkPendingOffers = async () => {
    // Only check if logged in and game is active
    if (!localStorage.getItem('authToken') || gameState.value === 'SETUP') return;

    try {
        const offers = await apiCall('/api/offers/pending');
        if (offers) {
            if (isAdmin.value) {
                incomingOffersCount.value = offers.length;
            } else {
                // For normal players, only count offers where they are the SELLER (meaning action is required)
                incomingOffersCount.value = offers.filter(o => Number(o.seller_id) === Number(myCompanyId.value)).length;
            }
        } else {
            incomingOffersCount.value = 0;
        }
    } catch (e) {
        console.error("Failed to poll offers", e);
    }
};

const triggerReset = async () => {
    if (!confirm("LET OP! Dit wist alle data, transacties, aandelen en spelers DEFINITIEF uit de database. Weet je het 100% zeker?")) return;
    try {
        await apiCall('/api/game/reset', 'POST');
        await loadGameState();
        stopEngine();
        await router.push('/setup');
    } catch (e) {
        alert("Reset failed: " + e.message);
    }
};

onMounted(async () => {
    initAuth();
    await loadGameState();
    await startEngine();

    await checkPendingOffers();
    offerPollingTimer = setInterval(checkPendingOffers, 10000);
});

onUnmounted(() => {
    stopEngine();
    if (offerPollingTimer) {
        clearInterval(offerPollingTimer);
    }
});

provide('companies', companies);
provide('reloadCompanies', loadCompanies);
provide('history', history);
provide('graphTrigger', graphTrigger);
provide('checkPendingOffers', checkPendingOffers);
provide('gameState', gameState);
provide('refreshGameState', loadGameState);
</script>

<style>
.navbar-nav .nav-link.active { color: #fff !important; font-weight: bold; border-bottom: 2px solid #0d6efd; }
.dropdown-menu-dark { background-color: #343a40; border-color: #495057; }
.dropdown-item:hover { background-color: #495057; }

.animate-pop {
    animation: pop 0.3s ease-out 1;
}
@keyframes pop {
    0% { transform: scale(0) translate(-50%, -50%); }
    80% { transform: scale(1.2) translate(-50%, -50%); }
    100% { transform: scale(1) translate(-50%, -50%); }
}
</style>
