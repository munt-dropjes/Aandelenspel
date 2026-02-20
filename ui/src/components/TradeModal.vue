<template>
    <div>
        <div v-if="isOpen" class="modal-backdrop show"></div>
        <div v-if="isOpen" class="modal d-block" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">Aandelen Handelen</h5>
                        <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="executeTrade">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Koper (Wie betaalt?)</label>
                                <select v-model="form.buyer_id" class="form-select" required>
                                    <option disabled value="">Selecteer Koper...</option>
                                    <option v-for="c in companies" :key="c.id" :value="c.id">
                                        {{ c.name }} (Kas: ƒ {{ c.cash.toLocaleString() }})
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Verkoper (Wie ontvangt geld?)</label>
                                <select v-model="form.seller_id" class="form-select" required>
                                    <option :value="null">🏛️ De Bank (Nieuwe uitgifte)</option>
                                    <option disabled>──────────</option>
                                    <option v-for="c in availableSellers" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                                <div class="form-text text-danger" v-if="form.buyer_id && form.buyer_id === form.seller_id">
                                    Een bedrijf kan niet van zichzelf kopen.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Welk Aandeel?</label>
                                <select v-model="form.stock_company_id" class="form-select" required>
                                    <option disabled value="">Selecteer Aandeel...</option>
                                    <option v-for="c in companies" :key="c.id" :value="c.id">
                                        {{ c.name }} (Koers: ƒ {{ c.stock_price.toLocaleString() }})
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Aantal aandelen</label>
                                <div class="input-group">
                                    <input type="number" v-model="form.amount" class="form-control" min="1" required>
                                    <span class="input-group-text">stuks</span>
                                </div>
                                <div class="form-text mt-2" v-if="estimatedCost > 0">
                                    Totaal: <strong>ƒ {{ estimatedCost.toLocaleString() }}</strong>
                                </div>
                            </div>

                            <div v-if="error" class="alert alert-danger">{{ error }}</div>
                            <div v-if="success" class="alert alert-success">{{ success }}</div>

                            <button type="submit" class="btn btn-primary w-100 py-2" :disabled="loading || isInvalid">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                {{ loading ? 'Bezig...' : 'Transactie Uitvoeren' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, inject } from 'vue';
import { apiCall } from '../services/api';

const props = defineProps(['isOpen']);
const emit = defineEmits(['close']);
const companies = inject('companies');
const reloadCompanies = inject('reloadCompanies');

const loading = ref(false);
const error = ref('');
const success = ref('');

const form = reactive({
    buyer_id: '',
    seller_id: null, // Default to Null (Bank)
    stock_company_id: '',
    amount: 1
});

// Computed: Filter potential sellers (optional logic)
const availableSellers = computed(() => {
    return companies;
});

// Computed: Check if form is valid
const isInvalid = computed(() => {
    // Basic checks
    if (!form.buyer_id || !form.stock_company_id || form.amount < 1) return true;
    // Logical check: Buyer cannot be Seller
    return form.buyer_id === form.seller_id;

});

// Computed: Calculate Cost
const estimatedCost = computed(() => {
    if (!form.stock_company_id || !form.amount) return 0;
    const target = companies.find(c => c.id === form.stock_company_id);
    return target ? target.stock_price * form.amount : 0;
});

const executeTrade = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        // API Payload matches your requirement
        const payload = {
            buyer_id: form.buyer_id,
            seller_id: form.seller_id, // Can be null (Bank) or ID (Company)
            stock_company_id: form.stock_company_id,
            amount: form.amount
        };

        await apiCall('/api/stocks/trade', 'POST', payload);

        success.value = 'Transactie geslaagd!';
        if (reloadCompanies) await reloadCompanies();

        setTimeout(() => {
            success.value = '';
            // Reset form partly
            form.amount = 1;
            emit('close');
        }, 1500);

    } catch (e) {
        error.value = e.message || 'Transactie mislukt';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.modal-backdrop { opacity: 0.5; background: black; }
</style>
