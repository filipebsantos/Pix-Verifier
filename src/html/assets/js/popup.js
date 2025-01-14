/**
 * Show a popup message
 * 
 * @param {string} title 
 * @param {string} type success, alert or error 
 * @param {string} message 
 */
export function popupMessage(title, type, message) {
    const popupModal = new bootstrap.Modal(document.getElementById('popupMsg'));

    popupModal._element.querySelector('#modalTitle').textContent = title;
    popupModal._element.querySelector('.modal-body p').textContent = message;

    const icon = popupModal._element.querySelector('.popup-icon');
    switch (type) {
        case 'error':
            icon.src = '/assets/imgs/error-icon.png';
            break;

        case 'alert':
            icon.src = '/assets/imgs/alert-icon.png';
            break;

        case 'success':
            icon.src = '/assets/imgs/success-icon.png';
            break;
    }

    popupModal.show();
}

/**
 * Shows a popup dialog box to user confirm an action
 * 
 * @param {string} title 
 * @param {string} message 
 * @returns 
 */
export function popupConfirmation(title, message) {
    return new Promise((resolve) => {
        const confirmationDialog = new bootstrap.Modal(document.getElementById('confirmationDialog'));

        confirmationDialog._element.querySelector('#modalTitle').textContent = title;
        confirmationDialog._element.querySelector('.modal-body p').textContent = message;

        const btnYes = confirmationDialog._element.querySelector('#confirmationDialog-yes');
        const btnNo = confirmationDialog._element.querySelector('#confirmationDialog-no');

        confirmationDialog.show();

        btnYes.addEventListener('click', () => {
            resolve(true);
            confirmationDialog.hide();
        });

        btnNo.addEventListener('click', () => {
            resolve(false);
            confirmationDialog.hide();
        });
    })
}