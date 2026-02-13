<template>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary">
                <i class="bi bi-list-columns-reverse me-2"></i>
                {{ isAdmin ? 'Transactie Overzicht (Admin)' : 'Mijn Transactie Geschiedenis' }}
            </h2>

            <div class="d-flex gap-2">
                <button @click="openTransferModal" class="btn btn-success btn-sm">
                    <i class="bi bi-send-fill me-1"></i> Geld Overmaken
                </button>

                <button @click="loadTransactions" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Verversen
                </button>
            </div>
        </div>

        <div v-if="isAdmin" class="card shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto fw-bold">Filter op bedrijf:</div>
                    <div class="col-auto">
                        <select v-model="filterCompanyId" class="form-select form-select-sm">
                            <option :value="null">Alle Bedrijven</option>
                            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Tijdstip</th>
                        <th>Bedrijf</th>
                        <th>Omschrijving</th>
                        <th class="text-end">Bedrag</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="tx in filteredTransactions" :key="tx.id">
                        <td class="small text-muted">
                            {{ new Date(tx.created_at).toLocaleString() }}
                        </td>
                        <td>
                 <span class="badge" :style="getCompanyStyle(tx.company_id)">
                    {{ getCompanyName(tx.company_id) }}
                 </span>
                        </td>
                        <td>{{ tx.description }}</td>
                        <td class="text-end fw-bold" :class="tx.amount >= 0 ? 'text-success' : 'text-danger'">
                            {{ tx.amount >= 0 ? '+' : '' }} ƒ {{ tx.amount.toLocaleString() }}
                        </td>
                    </tr>
                    <tr v-if="filteredTransactions.length === 0">
                        <td colspan="4" class="text-center py-4 text-muted">
                            Geen transacties gevonden.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="isModalOpen">
            <div class="modal-backdrop show" style="opacity: 0.5; background: black;"></div>
            <div class="modal d-block" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-send-fill me-2"></i>Geld Overmaken</h5>
                            <button type="button" class="btn-close btn-close-white" @click="closeTransferModal"></button>
                        </div>

                        <form @submit.prevent="executeTransfer">
                            <div class="modal-body">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Van (Zender)</label>
                                    <select v-model="transferForm.sender_id" class="form-select" required :disabled="!isAdmin">
                                        <option v-if="isAdmin" value="" disabled>Selecteer zender...</option>
                                        <option v-for="c in companies" :key="c.id" :value="c.id">
                                            {{ c.name }} <span v-if="isAdmin || c.id === myCompanyId">(Kas: ƒ {{ c.cash.toLocaleString() }})</span>
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Naar (Ontvanger)</label>
                                    <select v-model="transferForm.receiver_id" class="form-select" required>
                                        <option value="" disabled>Selecteer ontvanger...</option>
                                        <option v-for="c in availableReceivers" :key="c.id" :value="c.id">
                                            {{ c.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Bedrag</label>
                                    <div class="input-group">
                                        <span class="input-group-text">ƒ</span>
                                        <input type="number" v-model="transferForm.amount" class="form-control" min="1" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Omschrijving</label>
                                    <input type="text" v-model="transferForm.description" class="form-control" placeholder="Bijv. Lening afbetalen, Deal X..." required maxlength="255">
                                </div>

                                <div v-if="transferError" class="alert alert-danger py-2">{{ transferError }}</div>
                                <div v-if="transferSuccess" class="alert alert-success py-2">{{ transferSuccess }}</div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" @click="closeTransferModal" :disabled="isTransferring">Annuleren</button>
                                <button type="submit" class="btn btn-success" :disabled="isTransferring || !isTransferValid">
                                    <span v-if="isTransferring" class="spinner-border spinner-border-sm me-1"></span>
                                    Overmaken
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, inject, computed } from 'vue';
import { apiCall } from '../services/api';
import { useAuth } from '../composables/useAuth';

const companies = inject('companies');
const reloadCompanies = inject('reloadCompanies');
const { isAdmin, myCompanyId } = useAuth();

const transactions = ref([]);
const filterCompanyId = ref(null);

// ==========================================
// TABLE LOGIC
// ==========================================
const getCompany = (id) => companies.find(c => c.id === id);
const getCompanyName = (id) => getCompany(id)?.name || 'Onbekend';
const getCompanyStyle = (id) => {
    const c = getCompany(id);
    return c ? { backgroundColor: c.color, color: '#fff' } : { backgroundColor: '#6c757d', color: '#fff' };
};

const loadTransactions = async () => {
    try {
        const data = await apiCall('/api/transactions');
        if (data) {
            transactions.value = data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }
    } catch (e) {
        console.error("Failed to load transactions", e);
    }
};

const filteredTransactions = computed(() => {
    let list = transactions.value;
    if (isAdmin.value) {
        if (filterCompanyId.value) {
            list = list.filter(t => t.company_id === filterCompanyId.value);
        }
    } else {
        if (myCompanyId.value) {
            list = list.filter(t => t.company_id === myCompanyId.value);
        } else {
            list = [];
        }
    }
    return list;
});

const isModalOpen = ref(false);
const isTransferring = ref(false);
const transferError = ref('');
const transferSuccess = ref('');

const transferForm = reactive({
    sender_id: '',
    receiver_id: '',
    amount: 1000,
    description: ''
});

const openTransferModal = () => {
    transferError.value = '';
    transferSuccess.value = '';
    transferForm.receiver_id = '';
    transferForm.amount = 1000;
    transferForm.description = '';

    // Lock sender if user, leave open if admin
    transferForm.sender_id = isAdmin.value ? '' : myCompanyId.value;
    isModalOpen.value = true;
};

const closeTransferModal = () => {
    if (!isTransferring.value) isModalOpen.value = false;
};

// Filter receivers: Everyone EXCEPT the sender
const availableReceivers = computed(() => {
    if (!transferForm.sender_id) return companies;
    return companies.filter(c => c.id !== transferForm.sender_id);
});

// Validation
const isTransferValid = computed(() => {
    return transferForm.sender_id &&
        transferForm.receiver_id &&
        transferForm.amount > 0 &&
        transferForm.description.trim().length > 0;
});

const executeTransfer = async () => {
    isTransferring.value = true;
    transferError.value = '';
    transferSuccess.value = '';

    try {
        // Assume API endpoint: POST /api/transactions/transfer
        await apiCall('/api/transactions/transfer', 'POST', {
            sender_id: transferForm.sender_id,
            receiver_id: transferForm.receiver_id,
            amount: transferForm.amount,
            description: transferForm.description
        });

        transferSuccess.value = "Geld succesvol overgemaakt!";

        // Refresh tables and header cash
        await loadTransactions();
        if (reloadCompanies) await reloadCompanies();

        // Close after brief delay
        setTimeout(() => {
            closeTransferModal();
        }, 1500);

    } catch (e) {
        transferError.value = e.message || "Overboeking mislukt.";
    } finally {
        isTransferring.value = false;
    }
};

onMounted(() => {
    loadTransactions();
});
</script>
