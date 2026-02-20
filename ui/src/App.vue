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
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><router-link to="/" class="nav-link" active-class="active">Regels</router-link></li>
                        <li class="nav-item"><router-link to="/opdrachten" class="nav-link" active-class="active">Opdrachten</router-link></li>
                        <li class="nav-item"><router-link to="/aandelen" class="nav-link" active-class="active">Aandelenverdeling</router-link></li>
                        <li class="nav-item"><router-link to="/grafiek" class="nav-link" active-class="active">Grafiek</router-link></li>

                        <li class="nav-item">
                            <router-link to="/transacties" class="nav-link" active-class="active">
                                {{ isAdmin ? 'Admin Overzicht' : 'Mijn Rekening' }}
                            </router-link>
                        </li>

                        <li class="nav-item">
                            <router-link to="/handelsverzoeken" class="nav-link" active-class="active">
                                <i class="bi bi-briefcase-fill me-1"></i> Handelsverzoeken
                            </router-link>
                        </li>
                    </ul>

                    <ul class="navbar-nav ms-auto" v-if="username">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5 me-2"></i>
                                <span class="fw-bold">{{ username }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow" aria-labelledby="navbarDropdown">
                                <li><h6 class="dropdown-header">Ingelogd als {{ isAdmin ? 'Admin' : 'Bedrijf' }}</h6></li>
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
            <div class="row mb-4">
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

            <router-view></router-view>
        </main>
    </div>
</template>

<script setup>
import { provide, onMounted, onUnmounted } from 'vue';
import { useGameEngine } from './composables/useGameEngine';
import { useAuth } from './composables/useAuth';

// Initialize Engine
const {
    companies,
    history,
    graphTrigger,
    loadCompanies,
    startEngine,
    stopEngine
} = useGameEngine();

// Initialize Auth
const { initAuth, isAdmin, logout, username } = useAuth();

onMounted(() => {
    initAuth();
    startEngine();
});

onUnmounted(() => {
    stopEngine();
});

// Provide state to all child components
provide('companies', companies);
provide('reloadCompanies', loadCompanies);
provide('history', history);
provide('graphTrigger', graphTrigger);
</script>

<style>
/* Highlight active links */
.navbar-nav .nav-link.active { color: #fff !important; font-weight: bold; border-bottom: 2px solid #0d6efd; }

/* Optional: Make the dropdown nicer */
.dropdown-menu-dark { background-color: #343a40; border-color: #495057; }
.dropdown-item:hover { background-color: #495057; }
</style>
