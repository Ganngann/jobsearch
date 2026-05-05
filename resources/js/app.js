import './bootstrap';

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';

import profileBuilder from './profile-builder';
import discovery from './discovery';

Alpine.plugin(intersect);
window.Alpine = Alpine;
Alpine.data('profileBuilder', profileBuilder);

// Initialize Stores
discovery();

Alpine.start();
