require('./bootstrap');

import { createApp } from 'vue';
import RegionCostForm from './components/RegionCostForm.vue';
import TariffTemplateForm from './components/TariffTemplateForm.vue';
import UserManagementForm from './components/UserManagementForm.vue';
import UserAccountSetupForm from './components/UserAccountSetupForm.vue';
import UserAccountManagerForm from './components/UserAccountManagerForm.vue';

// Mount Vue app for legacy region-cost-app element
const regionCostApp = document.getElementById('region-cost-app');
if (regionCostApp) {
    const app = createApp(RegionCostForm, {
        ...JSON.parse(regionCostApp.dataset.props || '{}')
    });
    app.mount(regionCostApp);
}

// Mount Vue app for new tariff-template-app element
const tariffTemplateApp = document.getElementById('tariff-template-app');
if (tariffTemplateApp) {
    const app = createApp(TariffTemplateForm, {
        ...JSON.parse(tariffTemplateApp.dataset.props || '{}')
    });
    app.mount(tariffTemplateApp);
}

// Mount Vue app for user management
const userManagementApp = document.getElementById('user-management-app');
if (userManagementApp) {
    const app = createApp(UserManagementForm, {
        ...JSON.parse(userManagementApp.dataset.props || '{}')
    });
    app.mount(userManagementApp);
}

// Mount Vue app for user account setup wizard
const userAccountSetupApp = document.getElementById('user-account-setup-app');
if (userAccountSetupApp) {
    const app = createApp(UserAccountSetupForm, {
        ...JSON.parse(userAccountSetupApp.dataset.props || '{}')
    });
    app.mount(userAccountSetupApp);
}

// Mount Vue app for user account manager dashboard
const userAccountManagerApp = document.getElementById('user-account-manager-app');
if (userAccountManagerApp) {
    const app = createApp(UserAccountManagerForm, {
        ...JSON.parse(userAccountManagerApp.dataset.props || '{}')
    });
    app.mount(userAccountManagerApp);
}
