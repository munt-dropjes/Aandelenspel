<template>
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Aandelenverdeling & Marktwaarde</h5>
            <button @click="showTradeModal = true" class="btn btn-warning fw-bold shadow-sm">
                <i class="bi bi-cart-plus-fill me-2"></i> Handelen
            </button>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                    <tr>
                        <th>Eigenaar</th>
                        <th v-for="c in companies" :key="c.id" class="text-center">
                            {{ c.name }}<br>
                            <small class="fw-normal text-white-50">ƒ {{ (c.stock_price).toLocaleString() }}</small>
                        </th>
                        <th class="text-center bg-success text-white">Totale Netto Waarde</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="owner in companies" :key="owner.id">
                        <td class="fw-bold" :style="{ color: owner.color }">{{ owner.name }}</td>

                        <td v-for="target in companies" :key="target.id" class="text-center">
                            <div class="small">
                                <span v-if="owner.id === target.id" class="badge bg-secondary">
                                    {{ getShareAmount(owner.id, target.id) }} (Eigen)
                                </span>
                                <span v-else-if="getShareAmount(owner.id, target.id) > 0" class="badge border text-dark">
                                    {{ getShareAmount(owner.id, target.id) }} stuks
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </div>
                            <div class="text-muted small" v-if="getShareAmount(owner.id, target.id) > 0">
                                ƒ {{ (getShareAmount(owner.id, target.id) * target.stock_price).toLocaleString() }}
                            </div>
                        </td>

                        <td class="text-center fw-bold text-success bg-light">
                            ƒ {{ owner.net_worth.toLocaleString() }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TradeModal :isOpen="showTradeModal" @close="handleModalClose" />
    </div>

    <div class="card shadow-sm mb-4 border-secondary">
        <div class="card-header bg-secondary text-white d-flex align-items-center">
            <i class="bi bi-bank2 me-2"></i>
            <span class="fw-bold">Bank Voorraad (Te Koop)</span>
        </div>
        <div class="card-body py-3">
            <div class="row text-center">
                <div v-for="c in companies" :key="c.id" class="col">
                    <div class="fw-bold mb-1" :style="{ color: c.color }">{{ c.name }}</div>
                    <div v-if="getBankAmount(c.id) > 0">
                            <span class="badge bg-dark rounded-pill border border-light">
                                {{ getBankAmount(c.id) }} stuks
                            </span>
                        <div class="small text-muted mt-1">
                            ƒ {{ (getBankAmount(c.id) * c.stock_price).toLocaleString() }}
                        </div>
                    </div>
                    <div v-else>
                        <span class="badge bg-danger rounded-pill">Uitverkocht</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, inject, onMounted, computed } from 'vue';
import { apiCall } from '../services/api';
import TradeModal from './TradeModal.vue';

const companies = inject('companies');
const showTradeModal = ref(false);
const allShares = ref([]);
const bankShares = ref([]); // NEW: Store bank portfolio

const loadShares = async () => {
    try {
        const stocksData = await apiCall('/api/stocks');
        if (stocksData) {
            allShares.value = stocksData;
        }

        const bankData = await apiCall('/api/stocks/bank');
        if (bankData) {
            bankShares.value = bankData;
        }
    } catch (e) {
        console.error("Failed to load stocks", e);
    }
};

const shareMap = computed(() => {
    const map = {};
    allShares.value.forEach(share => {
        const key = `${share.owner_id}-${share.company_id}`;
        map[key] = share.amount;
    });
    return map;
});

const getShareAmount = (ownerId, targetCompanyId) => {
    const key = `${ownerId}-${targetCompanyId}`;
    return shareMap.value[key] || 0;
};

const getBankAmount = (companyId) => {
    const record = bankShares.value.find(s => s.company_id === companyId);
    return record ? record.amount : 0;
};

const handleModalClose = () => {
    showTradeModal.value = false;
    loadShares();
};

onMounted(() => {
    loadShares();
});
</script>
