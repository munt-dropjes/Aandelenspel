import router from '../router'; // Import the router instance directly

const API_URL = import.meta.env.VITE_API_URL;

export async function apiCall(endpoint, method = 'GET', body = null) {
    const token = localStorage.getItem('authToken');

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const options = {
        method,
        headers,
    };

    if (body) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(`${API_URL}${endpoint}`, options);

    // Handle 401 Unauthorized (Token expired)
    if (response.status === 401) {
        localStorage.removeItem('authToken');
        await router.push('/login');
        return null;
    }

    if (!response.ok) {
        let errorMsg = `Error: ${response.status} ${response.statusText}`;

        try {
            const errorData = await response.json();
            if (errorData.errorMessage) {
                errorMsg = errorData.errorMessage;
            } else if (errorData.message) {
                errorMsg = errorData.message;
            }
        } catch (parseError) {
            console.warn("Failed to parse error response as JSON:", parseError);
        }

        throw new Error(errorMsg);
    }

    // Return null for 204 No Content, otherwise JSON
    return response.status === 204 ? null : await response.json();
}
