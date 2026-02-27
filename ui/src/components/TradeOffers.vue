<template>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary">
                <i class="bi bi-briefcase-fill me-2"></i> Handelsverzoeken
            </h2>
            <button @click="refreshData" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Verversen
            </button>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Nieuw Bod Plaatsen</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitOffer">

                            <div class="mb-3" v-if="isAdmin">
                                <label class="form-label fw-bold text-danger">Koper (Namens wie? - Admin)</label>
                                <select v-model="form.buyer_id" class="form-select border-danger" required :disabled="loading">
                                    <option value="" disabled>Selecteer kopende partij...</option>
                                    <option v-for="c in companies" :key="'buy'+c.id" :value="c.id">
                                        {{ c.name }} (Kas: ƒ {{ (c.cash || 0).toLocaleString() }})
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Aan wie wil je een bod doen?</label>
                                <select v-model="form.seller_id" class="form-select" required :disabled="loading">
                                    <option value="" disabled>Selecteer verkopende partij...</option>
                                    <option v-for="c in availableSellers" :key="'sell'+c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Welke aandelen wil je kopen?</label>
                                <select v-model="form.target_company_id" class="form-select" required :disabled="loading">
                                    <option value="" disabled>Selecteer aandeel...</option>
                                    <option v-for="c in companies" :key="'stock'+c.id" :value="c.id">
                                        Aandelen: {{ c.name }} (Koers: ƒ {{ c.stock_price.toLocaleString() }})
                                    </option>
                                </select>

                                <div v-if="form.seller_id && form.target_company_id" class="form-text text-primary mt-1">
                                    <i class="bi bi-info-circle"></i> {{ getCompanyName(form.seller_id) }} bezit momenteel <strong>{{ maxAvailableShares }}</strong> van deze aandelen.
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold">Aantal</label>
                                    <input
                                        type="number"
                                        v-model="form.amount"
                                        class="form-control"
                                        :class="{'is-invalid': form.amount > maxAvailableShares && form.amount > 0}"
                                        min="1"
                                        required
                                        :disabled="loading"
                                    >
                                    <div class="invalid-feedback" v-if="form.amount > maxAvailableShares">
                                        Ze bezitten er maar {{ maxAvailableShares }}!
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold">Totaal Bod (ƒ)</label>
                                    <input type="number" v-model="form.total_price" class="form-control" min="1" required :disabled="loading">
                                </div>
                            </div>

                            <div class="form-text text-muted mb-3" v-if="form.amount && form.target_company_id && form.total_price">
                                Bod is <strong>ƒ {{ form.total_price.toLocaleString() }}</strong> voor {{ form.amount }}x {{ getCompanyName(form.target_company_id) }}.
                                <br>
                                <em>(Huidige Marktwaarde: ƒ {{ (getCompany(form.target_company_id)?.stock_price * form.amount).toLocaleString() }})</em>
                            </div>

                            <div v-if="feedback" :class="['alert py-2', feedbackType === 'error' ? 'alert-danger' : 'alert-success']">
                                {{ feedback }}
                            </div>

                            <button type="submit" class="btn btn-primary w-100" :disabled="loading || isInvalid">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Bod Versturen
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">

                <h5 class="fw-bold mb-3">
                    <i class="bi bi-arrow-left-right text-primary"></i>
                    {{ isAdmin ? 'Alle Openstaande Verzoeken' : 'Mijn Handelsverzoeken' }}
                </h5>

                <div class="card shadow-sm mb-4 border-primary">
                    <div class="list-group list-group-flush" v-if="pendingOffers.length > 0">

                        <div v-for="offer in pendingOffers" :key="offer.id" class="list-group-item p-3"
                             :class="{ 'bg-light': !isAdmin && Number(offer.buyer_id) === Number(myCompanyId) }">
                            <div class="d-flex justify-content-between align-items-center">

                                <div>
                                    <h6 class="mb-1">
                                        {{ offer.amount }}x Aandeel {{ getCompanyName(offer.target_company_id) }}
                                    </h6>
                                    <p class="mb-1 text-muted small">
                                        Bod: <strong class="text-success">ƒ {{ offer.total_price.toLocaleString() }}</strong>
                                    </p>

                                    <div v-if="isAdmin">
                                        <span class="badge bg-info text-dark me-1">Koper: {{ getCompanyName(offer.buyer_id) }}</span>
                                        <span class="badge bg-secondary">Verkoper: {{ getCompanyName(offer.seller_id) }}</span>
                                    </div>
                                    <div v-else>
                                        <span v-if="Number(offer.buyer_id) === Number(myCompanyId)" class="badge border border-warning text-warning">
                                          <i class="bi bi-send"></i> Uitgaand bod aan {{ getCompanyName(offer.seller_id) }}
                                        </span>
                                        <span v-if="Number(offer.seller_id) === Number(myCompanyId)" class="badge bg-primary">
                                          <i class="bi bi-inbox"></i> Inkomend van {{ getCompanyName(offer.buyer_id) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 align-items-center">

                                    <template v-if="isAdmin || Number(offer.seller_id) === Number(myCompanyId)">
                                        <button @click="respondToOffer(offer.id, 'accept')" class="btn btn-success btn-sm" :disabled="loading">
                                            <i class="bi bi-check-lg"></i> Accepteren
                                        </button>
                                        <button @click="respondToOffer(offer.id, 'decline')" class="btn btn-danger btn-sm" :disabled="loading">
                                            <i class="bi bi-x-lg"></i> Afwijzen
                                        </button>
                                    </template>

                                    <template v-else-if="Number(offer.buyer_id) === Number(myCompanyId)">
                                        <span class="text-muted small fst-italic">
                                          <i class="bi bi-hourglass-split"></i> Wachten op reactie...
                                        </span>
                                    </template>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-body text-center text-muted fst-italic py-4" v-else>
                        Geen handelsverzoeken gevonden.
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue';
import { apiCall } from '../services/api';
import { useAuth } from '../composables/useAuth';

const companies = inject('companies');
const reloadCompanies = inject('reloadCompanies');
const checkPendingOffers = inject('checkPendingOffers');
const { myCompanyId, isAdmin } = useAuth();

const pendingOffers = ref([]);
const allShares = ref([]);
const loading = ref(false);
const feedback = ref('');
const feedbackType = ref('');

// Form State
const form = reactive({
    buyer_id: '',
    seller_id: '',
    target_company_id: '',
    amount: 1,
    total_price: 1000
});

// UI Helpers
const getCompany = (id) => companies.find(c => c.id === id);
const getCompanyName = (id) => getCompany(id)?.name || 'Onbekend';

const availableSellers = computed(() => {
    const currentBuyerId = isAdmin.value ? form.buyer_id : myCompanyId.value;
    return companies.filter(c => Number(c.id) !== Number(currentBuyerId));
});

// Calculate how many shares the seller actually has based on the real /api/stocks data
const maxAvailableShares = computed(() => {
    if (!form.seller_id || !form.target_company_id || allShares.value.length === 0) return 0;

    const stockRecord = allShares.value.find(s =>
        Number(s.owner_id) === Number(form.seller_id) &&
        Number(s.company_id) === Number(form.target_company_id)
    );

    return stockRecord ? stockRecord.amount : 0;
});

// Form validation
const isInvalid = computed(() => {
    if (isAdmin.value && !form.buyer_id) return true;
    if (!form.seller_id || !form.target_company_id || form.amount < 1 || form.total_price < 1) return true;
    if (form.amount > maxAvailableShares.value) return true; // Block submission if too high
    return false;
});

const loadOffers = async () => {
    try {
        const data = await apiCall('/api/offers/pending');
        if (data) {
            pendingOffers.value = data.sort((a, b) => b.id - a.id);
        }
    } catch (e) {
        console.error("Failed to load offers", e);
    }
};

// Fetch all active stocks so we can validate maxAvailableShares
const loadShares = async () => {
    try {
        const data = await apiCall('/api/stocks');
        if (data) {
            allShares.value = data;
        }
    } catch (e) {
        console.error("Failed to load stocks", e);
    }
};

// Helper to refresh everything
const refreshData = async () => {
    loading.value = true;
    await loadOffers();
    await loadShares();
    loading.value = false;
};

const submitOffer = async () => {
    loading.value = true;
    feedback.value = '';
    try {
        const payload = {
            seller_id: form.seller_id,
            target_company_id: form.target_company_id,
            amount: form.amount,
            total_price: form.total_price
        };

        if (isAdmin.value) {
            payload.buyer_id = form.buyer_id;
        }

        await apiCall('/api/offers', 'POST', payload);

        feedbackType.value = 'success';
        feedback.value = 'Bod succesvol verstuurd!';

        // Reset form
        form.amount = 1;
        form.total_price = 1000;
        if (!isAdmin.value) form.seller_id = '';

        await refreshData();
    } catch (e) {
        feedbackType.value = 'error';
        feedback.value = e.message || 'Mislukt om bod te sturen.';
    } finally {
        loading.value = false;
        setTimeout(() => { feedback.value = ''; }, 4000);
    }
};

const respondToOffer = async (offerId, action) => {
    const isAccepting = action === 'accept';
    if (!confirm(`Weet je zeker dat je dit bod wil ${isAccepting ? 'accepteren' : 'afwijzen'}?`)) return;

    loading.value = true;
    try {
        await apiCall(`/api/offers/${offerId}/${action}`, 'POST');

        if (isAccepting && reloadCompanies) {
            await reloadCompanies();
        }

        await refreshData();

        // Instantly update the red badge in the navbar
        if (checkPendingOffers) {
            await checkPendingOffers();
        }

    } catch (e) {
        alert(`Fout: ${e.message}`);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    refreshData();
});
</script>
