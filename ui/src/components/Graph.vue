<template>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Live Koersverloop</h5>
            <span class="badge rounded-pill bg-danger animate-pulse">LIVE</span>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 450px; width: 100%;">
                <canvas ref="chartCanvas"></canvas>
            </div>

            <div v-if="!history.labels.length" class="position-absolute top-50 start-50 translate-middle text-muted">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Laden...
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, inject, ref, watch, shallowRef, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const history = inject('history');
const graphTrigger = inject('graphTrigger');
const chartCanvas = ref(null);
const chartInstance = shallowRef(null);

const createChart = () => {
    if (!chartCanvas.value) return;

    if (chartInstance.value) {
        chartInstance.value.destroy();
    }

    const ctx = chartCanvas.value.getContext('2d');

    chartInstance.value = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [...history.labels],
            datasets: history.datasets.map(ds => ({ ...ds, data: [...ds.data] }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            elements: {
                line: { borderWidth: 3, showLine: true, tension: 0.3 },
                point: { radius: 3, hitRadius: 10 }
            },
            scales: {
                y: {
                    ticks: { callback: (val) => 'ƒ ' + val.toLocaleString() }
                }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });
};

watch(graphTrigger, () => {
    // If no chart, or if the number of datasets changed (e.g. init vs loaded), recreate.
    if (!chartInstance.value || chartInstance.value.data.datasets.length !== history.datasets.length) {
        createChart();
        return;
    }

    const chart = chartInstance.value;
    chart.data.labels = [...history.labels];

    chart.data.datasets.forEach((dataset, i) => {
        if (history.datasets[i]) {
            dataset.data = [...history.datasets[i].data];
        }
    });

    // 'none' mode prevents animation = instant update
    chart.update('none');
});

onMounted(async () => {
    await nextTick();

    if (history.labels.length > 0) {
        createChart();
    }
});

onUnmounted(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
});
</script>

<style scoped>
.animate-pulse { animation: pulse 2s infinite; }
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
}
</style>
