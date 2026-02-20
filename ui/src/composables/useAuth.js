import { ref, computed } from 'vue';

// Global state (persists across components)
const user = ref(null);
const token = ref(localStorage.getItem('authToken'));

export function useAuth() {

    // 1. Initialize User from LocalStorage on page refresh
    const initAuth = () => {
        const storedToken = localStorage.getItem('authToken');
        const storedUser = localStorage.getItem('user');

        if (storedToken && storedUser) {
            token.value = storedToken;
            try {
                user.value = JSON.parse(storedUser);
            } catch (e) {
                console.error("Auth: Failed to parse user data", e);
                logout();
            }
        }
    };

    // 2. Login Action
    const login = (userData, apiToken) => {
        user.value = userData;
        token.value = apiToken;
        localStorage.setItem('authToken', apiToken);
        localStorage.setItem('user', JSON.stringify(userData));
    };

    // 3. Logout Action
    const logout = () => {
        user.value = null;
        token.value = null;
        localStorage.removeItem('authToken');
        localStorage.removeItem('user');
        window.location.href = '/login';
    };

    // --- COMPUTED HELPERS ---
    const isAdmin = computed(() => user.value?.role === 'admin');

    // Direct access to the ID provided by the backend (e.g., user.company_id = 4)
    const myCompanyId = computed(() => user.value?.company_id || null);

    const username = computed(() => user.value?.username || '');

    return {
        user,
        token,
        isAdmin,
        myCompanyId,
        username,
        initAuth,
        login,
        logout
    };
}
