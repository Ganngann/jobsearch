import './bootstrap';
console.log('DEBUG: app.js started');

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';

import profileBuilder from './profile-builder';
import discovery from './discovery';

import dashboardApp from './dashboard';
import mobilityApp from './mobility';
import skillApp from './skills';
import skillsManager from './skills-manager';
import manageFacts from './manage-facts';
import aiWizard from './ai-wizard';
import feedbackSystem from './feedback';
import jobDetails from './job-details';
import { mobilityForm, languagesForm, permitsForm } from './profile-edit';

Alpine.plugin(intersect);
window.Alpine = Alpine;

// Make components globally accessible for Blade views
window.profileBuilder = profileBuilder;
window.dashboardApp = dashboardApp;
window.mobilityApp = mobilityApp;
window.skillApp = skillApp;
window.skillsManager = skillsManager;
window.manageFacts = manageFacts;
window.aiWizard = aiWizard;
window.feedbackSystem = feedbackSystem;
window.jobDetails = jobDetails;
window.mobilityForm = mobilityForm;
window.languagesForm = languagesForm;
window.permitsForm = permitsForm;

Alpine.data('profileBuilder', profileBuilder);
Alpine.data('dashboardApp', dashboardApp);
Alpine.data('mobilityApp', mobilityApp);
Alpine.data('skillApp', skillApp);
Alpine.data('skillsManager', skillsManager);
Alpine.data('manageFacts', manageFacts);
Alpine.data('aiWizard', aiWizard);
Alpine.data('feedbackSystem', feedbackSystem);
Alpine.data('jobDetails', jobDetails);
Alpine.data('mobilityForm', mobilityForm);
Alpine.data('languagesForm', languagesForm);
Alpine.data('permitsForm', permitsForm);

// Initialize Stores
discovery();

console.log('DEBUG: Alpine started');
Alpine.start();
