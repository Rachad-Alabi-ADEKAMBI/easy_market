import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import DashboardApp from './admin/DashboardApp.vue';

const root = document.getElementById('app');

if (root) {
    createApp({}).mount(root);
}

const dashboardRoot = document.getElementById('dashboard-app');

if (dashboardRoot) {
    createApp(DashboardApp, {
        businessId: dashboardRoot.dataset.businessId,
        csrfToken: dashboardRoot.dataset.csrfToken,
        initialSection: dashboardRoot.dataset.section,
    }).mount(dashboardRoot);
}
