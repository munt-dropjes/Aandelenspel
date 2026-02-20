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
import { useAuth } from '../composables/useAuth'; // Import Auth

const username = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();

const reloadCompanies = inject('reloadCompanies');
const { login } = useAuth(); // Destructure login function

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

            // 2. Fetch Data immediately (updates cash in header)
            if (reloadCompanies) {
                await reloadCompanies();
            }

            // 3. Redirect based on Role
            if (response.user.role === 'admin') {
                router.push('/'); // Admin goes to Rules/Home
            } else {
                router.push('/transacties'); // Companies go to their history
            }
        } else {
            error.value = "Login failed: No token received";
        }
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};
</script>
