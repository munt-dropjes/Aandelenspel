import { reactive, ref } from 'vue';
import { apiCall } from '../services/api';

export function useGameEngine() {
    const companies = reactive([]);
    const history = reactive({
        labels: [],
        datasets: []
    });

    const graphTrigger = ref(0);
    const lastRecordedTime = ref(null);
    let timer = null;

    const gameState = ref('SETUP');

    const loadGameState = async () => {
        try {
            const data = await apiCall('/api/game/settings');
            if (data) {
                gameState.value = data.state;
            }
        } catch (e) {
            console.error("Engine: Failed to fetch game state", e);
        }
    };

    const ensureGraphStructure = () => {
        if (history.datasets.length === 0 && companies.length > 0) {
            console.log("Engine: Initializing Graph Structure");
            history.datasets = companies.map(c => ({
                id: c.id,
                label: c.name,
                data: [],
                borderColor: c.color,
                backgroundColor: c.color,
                tension: 0.3,
                borderWidth: 3,
                pointRadius: 3,
                pointHoverRadius: 6,
                fill: false,
                spanGaps: true
            }));
            graphTrigger.value++;
        }
    };

    const loadCompanies = async () => {
        const token = localStorage.getItem('authToken');
        if (!token) return;

        try {
            const data = await apiCall('/api/companies');
            if (data) {
                companies.splice(0, companies.length, ...data);
                ensureGraphStructure();
            }
        } catch (e) {
            console.error("Engine: Failed to load companies", e);
        }
    };

    const loadHistory = async () => {
        const token = localStorage.getItem('authToken');
        if (!token || companies.length === 0) return;

        ensureGraphStructure();

        let sinceParam = '';

        if (lastRecordedTime.value) {
            // APPEND MODE: Use the last known time from the DB + 1 second
            const lastDate = new Date(lastRecordedTime.value.replace(' ', 'T') + 'Z');
            const nextTick = new Date(lastDate.getTime() + 1000);
            sinceParam = nextTick.toISOString().slice(0, 19).replace('T', ' ');
        } else {
            // INIT MODE: Last 65 minutes
            const d = new Date(Date.now() - 65 * 60000);
            sinceParam = d.toISOString().slice(0, 19).replace('T', ' ');
        }

        try {
            const rawData = await apiCall(`/api/history/${encodeURIComponent(sinceParam)}`);

            if (rawData && Array.isArray(rawData) && rawData.length > 0) {
                const groupedByTime = {};
                let maxTimeStr = lastRecordedTime.value;

                rawData.forEach(record => {
                    if (!maxTimeStr || record.recorded_at > maxTimeStr) {
                        maxTimeStr = record.recorded_at;
                    }
                    const timeLabel = record.recorded_at.substring(11, 16); // HH:MM

                    if (!groupedByTime[timeLabel]) {
                        groupedByTime[timeLabel] = {};
                    }
                    groupedByTime[timeLabel][record.company_id] = record.net_worth;
                });

                lastRecordedTime.value = maxTimeStr;

                const newLabels = Object.keys(groupedByTime).sort();

                newLabels.forEach(time => {
                    if (history.labels.length === 0 || history.labels[history.labels.length - 1] !== time) {
                        history.labels.push(time);
                        history.datasets.forEach(ds => {
                            const val = groupedByTime[time][ds.id] ?? null;
                            ds.data.push(val);
                        });
                    }
                });

                graphTrigger.value++;
            }
        } catch (e) {
            console.error("Engine: Failed to load graph history", e);
        }
    };

    const performHeartbeat = async () => {
        if (gameState.value === 'SETUP') return;

        const token = localStorage.getItem('authToken');
        if (!token) return;

        try {
            // 1. Snapshot
            await apiCall('/api/history/save', 'POST');
            // 2. Refresh Data
            await loadCompanies();
            await loadHistory();
        } catch (e) {
            console.error("Engine: Heartbeat failed", e);
        }
    };

    const startEngine = async () => {
        console.log("Engine: Starting...");

        // 1. Check State First
        await loadGameState();

        // 2. Abort engine if in SETUP
        if (gameState.value === 'SETUP') return;

        await loadCompanies();
        await loadHistory();

        if (!timer) {
            timer = setInterval(performHeartbeat, 60000);
        }
    };

    const stopEngine = () => {
        if (timer) {
            console.log("Engine: Stopping...");
            clearInterval(timer);
            timer = null;
        }
    };

    return {
        companies,
        history,
        graphTrigger,
        gameState,
        loadGameState,
        loadCompanies,
        startEngine,
        stopEngine
    };
}
