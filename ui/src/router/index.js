import { createRouter, createWebHistory } from 'vue-router';
import Rules from '../components/Rules.vue';
import Tasks from '../components/Tasks.vue';
import Stocks from '../components/StockDivider.vue';
import Graph from '../components/Graph.vue';
import Login from '../components/Login.vue';
import Transactions from '../components/Transactions.vue';
import TradeOffers from '../components/TradeOffers.vue';
import AdminSettings from '../components/AdminSettings.vue';

const routes = [
    { path: '/', component: Rules },
    { path: '/opdrachten', component: Tasks },
    { path: '/aandelen', component: Stocks },
    { path: '/grafiek', component: Graph },
    { path: '/login', component: Login },
    { path: '/transacties', component: Transactions },
    { path: '/handelsverzoeken', component: TradeOffers },
    { path: '/setup', component: AdminSettings }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const publicPages = ['/login'];
    const authRequired = !publicPages.includes(to.path);
    const loggedIn = localStorage.getItem('authToken');

    if (authRequired && !loggedIn) {
        return next('/login');
    }
    next();
});

export default router;
