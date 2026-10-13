import './stimulus_bootstrap.js';
import * as Turbo from '@hotwired/turbo';
import './styles/fonts.css';
import './styles/tokens.css';
import './styles/layout.css';
import './styles/components.css';
import './styles/motion.css';

/*
 * Confirmation des suppressions (data-turbo-confirm) : dialogue maison à la place de window.confirm().
 */
function confirmWithDialog(message) {
    const { confirmOk = 'OK', confirmCancel = 'Annuler' } = document.body.dataset;
    const dialog = document.createElement('dialog');
    dialog.className = 'confirm';
    dialog.setAttribute('aria-label', message);

    const form = document.createElement('form');
    form.method = 'dialog';
    const text = document.createElement('p');
    text.textContent = message;
    const actions = document.createElement('div');
    actions.className = 'confirm__actions';

    const cancel = document.createElement('button');
    cancel.className = 'btn btn--ghost btn--sm';
    cancel.value = 'cancel';
    cancel.textContent = confirmCancel;
    const ok = document.createElement('button');
    ok.className = 'btn btn--primary btn--sm';
    ok.value = 'ok';
    ok.textContent = confirmOk;
    ok.autofocus = true;

    actions.append(cancel, ok);
    form.append(text, actions);
    dialog.append(form);
    document.body.append(dialog);

    return new Promise((resolve) => {
        dialog.addEventListener('close', () => {
            resolve(dialog.returnValue === 'ok');
            dialog.remove();
        });
        dialog.showModal();
    });
}

if (Turbo.config?.forms) {
    Turbo.config.forms.confirm = confirmWithDialog;
} else {
    Turbo.setConfirmMethod(confirmWithDialog);
}

/*
 * Réactivité perçue : barre de progression immédiate, bouton « occupé » dès l'envoi d'un formulaire,
 * et pas de rejeu des animations d'entrée quand la page revient après une action.
 */
if (typeof Turbo.setProgressBarDelay === 'function') {
    Turbo.setProgressBarDelay(0);
}

document.addEventListener('turbo:submit-start', (event) => {
    event.target.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => button.classList.add('is-busy'));
});

document.addEventListener('turbo:submit-end', (event) => {
    event.target.querySelectorAll('.is-busy').forEach((button) => button.classList.remove('is-busy'));
    if (event.detail.success) {
        document.documentElement.dataset.quick = '1';
        setTimeout(() => delete document.documentElement.dataset.quick, 1800);
    }
});
