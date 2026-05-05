import './bootstrap';

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';

import profileBuilder from './profile-builder';
import discovery from './discovery';

import dashboardApp from './dashboard';

Alpine.plugin(intersect);
window.Alpine = Alpine;

// Make components globally accessible for Blade views
window.profileBuilder = profileBuilder;
window.dashboardApp = dashboardApp;

Alpine.data('profileBuilder', profileBuilder);
Alpine.data('dashboardApp', dashboardApp);

// Initialize Stores
discovery();

Alpine.start();
