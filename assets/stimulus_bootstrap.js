import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// Les journaux de debug de Stimulus ralentissent la page (un log par contrôleur connecté).
app.debug = false;
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
