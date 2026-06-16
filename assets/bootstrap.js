import { startStimulusApp } from '@symfony/stimulus-bundle';
import AccessCodeController from './controllers/access_code_controller.js';
import StayController from './controllers/stay_controller.js';
import PwaController from './controllers/pwa_controller.js';
import DiscoveriesController from './controllers/discoveries_controller.js';
import AdminNavController from './controllers/admin_nav_controller.js';
import PropertyTabsController from './controllers/property_tabs_controller.js';

const app = startStimulusApp();
app.register('access-code', AccessCodeController);
app.register('stay', StayController);
app.register('pwa', PwaController);
app.register('discoveries', DiscoveriesController);
app.register('admin-nav', AdminNavController);
app.register('property-tabs', PropertyTabsController);
