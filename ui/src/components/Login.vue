<template>
    <div class="container mt-5">
        <div class="card mx-auto shadow-sm" style="max-width: 400px;">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Login</h3>
                <form @submit.prevent="handleLogin">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input v-model="username" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input v-model="password" type="password" class="form-control" required />
                    </div>
                    <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                        {{ loading ? 'Bezig...' : 'Login' }}
                    </button>
                    <p v-if="error" class="text-danger mt-3 text-center small">{{ error }}</p>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import { apiCall } from '../services/api';
import { useAuth } from '../composables/useAuth';

const username = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();

const reloadCompanies = inject('reloadCompanies');
const checkPendingOffers = inject('checkPendingOffers');
const refreshGameState = inject('refreshGameState');
const gameState = inject('gameState');
const { login } = useAuth();

const handleLogin = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await apiCall('/login', 'POST', {
            username: username.value,
            password: password.value
        });

        if (response && response.token) {
            // 1. Save Token & User Object via Composable
            login(response.user, response.token);

            // 2. Fetch Global State to know where to route them
            if (refreshGameState) {
                await refreshGameState();
            }

            // 3. Routing Logic
            if (response.user.role === 'admin') {
                if (gameState.value === 'SETUP') {
                    router.push('/setup'); // Admin needs to setup the game
                } else {
                    router.push('/');      // Game is active
                }
            } else {
                // Regular users go to history
                router.push('/transacties');
            }

            // Fetch Data immediately if game is active
            if (gameState.value === 'ACTIVE') {
                if (reloadCompanies) await reloadCompanies();
                if (checkPendingOffers) await checkPendingOffers();
            }

        } else {
            error.value = "Login mislukt: Geen token ontvangen";
        }
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};
</script>
