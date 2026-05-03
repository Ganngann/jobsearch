import './bootstrap';

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';

import profileBuilder from './profile-builder';

Alpine.plugin(intersect);
window.Alpine = Alpine;
Alpine.data('profileBuilder', profileBuilder);

Alpine.start();
