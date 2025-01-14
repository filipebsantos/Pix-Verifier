import { popupMessage , popupConfirmation } from './popup.js'

const accountUrlApi = '/controller/accounts.php';
let accountDetailModal;

/**
 * Sent a resquest to backend API
 * 
 * @param {Object} payload 
 * @param {string} urlRequest 
 * @returns 
 */
async function apiRequest(payload, urlRequest) {
    const responseApi = await fetch(urlRequest, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (!responseApi.ok) {
        const errorResponse = await responseApi.json().catch(() => ({}));
        throw {
            status: responseApi.status,
            code: errorResponse.code || 'unknown',
            message: errorResponse.message || 'Unknown error'
        };
    }

    return await responseApi.json();
}

/**
 * Populate the account selector with options.
 * @param {HTMLElement} selector - The select element to populate.
 * @param {Object} accounts - The accounts data from the API.
 */
function populateAccountSelector(selector, accounts) {
    selector.innerHTML = ''; 
    
    if (accounts.totalaccounts > 0) {
        Object.keys(accounts).forEach(account => {
            if (account !== 'totalaccounts') {
                const option = document.createElement('option');
                option.value = account;
                option.textContent = `${accounts[account].name} - ${accounts[account].bankName}`;
                option.dataset.bank = accounts[account].bank;
                option.dataset.accname = accounts[account].name;
                selector.appendChild(option);
            }
        });
    } else {
        const option = document.createElement('option');
        option.value = null;
        option.textContent = 'Não há contas';
        selector.appendChild(option);
    }
}

/**
 * Preenche a tabela de contas no painel de administração.
 * @param {HTMLElement} table
 * @param {Object} accounts
 */
function populateAccountTable(table, accounts) {
    
    table.innerHTML = '';

    if (accounts.totalaccounts > 0) {
        Object.keys(accounts).forEach(accountKey => {
            if (accountKey !== 'totalaccounts') {
                const account = accounts[accountKey];
                const tr = document.createElement('tr');

                const tdBank = document.createElement('td');
                tdBank.innerText = account.bankName;
                tr.appendChild(tdBank);

                const tdAccountName = document.createElement('td');
                tdAccountName.innerText = account.name;
                tr.appendChild(tdAccountName);

                const tdBranchNumber = document.createElement('td');
                tdBranchNumber.innerText = account.branchNumber;
                tr.appendChild(tdBranchNumber);

                const tdAccountNumber = document.createElement('td');
                tdAccountNumber.innerText = account.accountNumber;
                tr.appendChild(tdAccountNumber);

                const tdActionButtons = document.createElement('td');

                const btnAccountDetail = document.createElement('button');
                btnAccountDetail.classList.add('btn', 'btn-outline-primary', 'edit-account', 'me-1');
                btnAccountDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>`;
                btnAccountDetail.onclick = () => openAccountDetail(accountKey);

                const btnDeleteAccount = document.createElement('button');
                btnDeleteAccount.classList.add('btn', 'btn-outline-danger', 'delete-account');
                btnDeleteAccount.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>`;
                btnDeleteAccount.onclick = () => deleteAccount(accountKey);

                tdActionButtons.appendChild(btnAccountDetail);
                tdActionButtons.appendChild(btnDeleteAccount);

                tr.appendChild(tdActionButtons);
                
                table.appendChild(tr);
            }
        });
    } else {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.innerText = 'Nenhuma conta encontrada.';
        tr.appendChild(td);
        table.appendChild(tr);
    }
}

/**
 * Main function to list accounts and update the selector.
 */
export async function listAccounts() {
    const accountSelector = document.querySelector('#inputAccountSelector');

    const payload = {
        action: 'fetchAvailableAccounts'
    };

    try {
        const accounts = await apiRequest(payload, accountUrlApi);
        populateAccountSelector(accountSelector, accounts);
    } catch (error) {
        console.error(error.message);

        const option = document.createElement('option');
        option.value = null;
        option.textContent = 'Erro ao carregar contas';
        accountSelector.appendChild(option);
        accountSelector.disabled = true;
    }
}

/**
 * Load account table in admin page
 */
export async function loadAccountTable() {
    const accountTable = document.getElementById('tableBody');

    const payload = {
        action: 'fetchAvailableAccounts'
    };

    try{
        const accounts = await apiRequest(payload, accountUrlApi);
        populateAccountTable(accountTable, accounts);
    } catch (error) {
        console.error(error.message);

        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.innerText = 'Erro ao carregar contas';
        tr.appendChild(td);
        accountTable.appendChild(tr);
    }
}

/**
 * Create a new account
 * 
 * @param {int} bankId 
 * @param {string} branchNumber 
 * @param {string} accountNumber 
 * @param {string} accountName 
 */
export async function saveAccount(bankId, branchNumber, accountNumber, accountName) {
    
    const payload = {
        action: 'createNewAccount',
        bankId: bankId,
        branchNumber: branchNumber,
        accountNumber: accountNumber,
        accountName: accountName
    }

    try {
        const newAcc = await apiRequest(payload, accountUrlApi);
    } catch (error) {
        if (error.status === 400) {
            switch (error.code) {
                case 'AC10':
                    popupMessage('Cadastrar nova conta', 'alert', 'Formato inválido na sua requisição');
                    break;
                case 'AC10.1':
                    popupMessage('Cadastrar nova conta', 'alert', 'Token de autorização não enviado, tente fazer o login novamente.');
                    break;
                case 'AC10.2':
                    popupMessage('Cadastrar nova conta', 'alert', 'Token de autorização inválido');
                    break;
                case 'AC10.3':
                    popupMessage('Cadastrar nova conta', 'alert', 'Conta já existente');
                    break;
            }
        } else if (error.status === 500) {
            popupMessage('Cadastrar nova conta', 'error', 'Erro no servidor. Tente mais tarde');
        } else {
            popupMessage('Cadastrar nova conta', 'error', `Erro inesperado: ${error.message}`);
        }

        console.error('[saveAccount] Error: ', error);
    }

    await loadAccountTable();
}

/**
 * Delete an specific account
 * 
 * @param {int} accountId 
 */
async function deleteAccount(accountId) {
    
    const userConfirmation = await popupConfirmation('Deletar conta', 'Tem certeza que deseja excluir a conta? Todos os pix recebidos também serão apagados!');

    if (userConfirmation) {
        
        const payload = {
            action: 'deleteAccount',
            accountId: accountId
        };

        try {
            await apiRequest(payload, accountUrlApi);
        } catch (error) {
            if (error.status === 400) {
                switch (error.code) {
                    case 'AC20':
                        popupMessage('Deletar conta', 'alert', 'Formato inválido na sua requisição');
                        break;
                    case 'AC20.1':
                        popupMessage('Deletar conta', 'alert', 'Token de autorização não enviado, tente fazer o login novamente.');
                        break;
                    case 'AC20.2':
                        popupMessage('Deletar conta', 'alert', 'Token de autorização inválido');
                        break;
                }
            } else if (error.status === 500) {
                popupMessage('Deletar conta', 'error', 'Erro no servidor. Tente mais tarde');
            } else {
                popupMessage('Deletar conta', 'error', `Erro inesperado: ${error.message}`);
            }
    
            console.error('[deleteAccount] Error: ', error);
        }
    
        await loadAccountTable();
    }

}

/**
 * Open the account detail modal
 * 
 * @param {int} accountId 
 */
async function openAccountDetail(accountId) {
    accountDetailModal = new bootstrap.Modal(document.getElementById('accountDetail'));

    const accountNameModal = accountDetailModal._element.querySelector('#modalTitle');
    const accountClientId = accountDetailModal._element.querySelector('#clientID');
    const accountClientSecret = accountDetailModal._element.querySelector('#clientSecret');
    const accountIgnoredList = accountDetailModal._element.querySelector('#ignoredList');
    const accountUpdateButtom = accountDetailModal._element.querySelector('.save-account-detail');
    const accountCertFileInfo = accountDetailModal._element.querySelector('#certFileInfo');
    const accountKeyFileInfo = accountDetailModal._element.querySelector('#keyFileInfo');

    const payload = {
        action: 'fetchAccountDetail',
        accountId: accountId
    };

    try {
        const accountDetailData = await apiRequest(payload, accountUrlApi);

        accountNameModal.textContent = accountDetailData['accountName'];
        accountClientId.value = accountDetailData['clientId'];
        accountClientSecret.value = accountDetailData['clientSecret'];
        accountIgnoredList.value = accountDetailData['ignoredSenders'];
        accountCertFileInfo.textContent = accountDetailData['certFile'];
        accountKeyFileInfo.textContent = accountDetailData['certKeyFile'];

        accountUpdateButtom.onclick = () => updateAccountData(accountId);

        accountDetailModal.show();

    } catch (error) {
        if (error.status === 400) {
            switch (error.code) {
                case 'AC30':
                    popupMessage('Detalhe da conta', 'alert', 'Formato inválido na sua requisição');
                    break;
                case 'AC30.1':
                    popupMessage('Detalhe da conta', 'alert', 'Token de autorização não enviado, tente fazer o login novamente.');
                    break;
                case 'AC30.2':
                    popupMessage('Detalhe da conta', 'alert', 'Token de autorização inválido');
                    break;
            }
        } else if (error.status === 500) {
            popupMessage('Detalhe da conta', 'error', 'Erro no servidor. Tente mais tarde');
        } else {
            popupMessage('Detalhe da conta', 'error', `Erro inesperado: ${error.message}`);
        }

        console.error('[openAccountDetail] Error: ', error);
    }
}

/**
 * Update the account detail in modal form on database
 * 
 * @param {int} accountId 
 */
async function updateAccountData(accountId) {
    const clientId = document.getElementById('clientID');
    const clientSecret = document.getElementById('clientSecret');
    const certFile = document.getElementById('certFile');
    const certKeyFile = document.getElementById('certKeyFile');
    const ignoredSenders = document.getElementById('ignoredList');
    
    let certFileBase64 = null;
    let certKeyFileBase64 = null;

    const readFileAsBase64 = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result.split(',')[1]);
            reader.onerror = (error) => reject(error);
            reader.readAsDataURL(file);
        });
    };

    if (certFile.files.length > 0) {
        certFileBase64 = await readFileAsBase64(certFile.files[0]);
    }

    if (certKeyFile.files.length > 0) {
        certKeyFileBase64 = await readFileAsBase64(certKeyFile.files[0]);
    }

    const payload = {
        action: 'updateAccountDetail',
        accountId: accountId,
        clientId: clientId.value.trim(),
        clientSecret: clientSecret.value.trim(),
        ignoredSenders: ignoredSenders.value.trim(),
        certFile: certFileBase64,
        certKeyFile: certKeyFileBase64
    }

    try {
        await apiRequest(payload, accountUrlApi);

        certFile.value = '';
        certKeyFile.value = '';
        accountDetailModal.hide();

        popupMessage('Detalhes da conta', 'success', 'Dados gravados com sucesso!');

    } catch (error) {
        if (error.status === 400) {
            switch (error.code) {
                case 'AC40':
                    popupMessage('Detalhe da conta', 'alert', 'Formato inválido na sua requisição');
                    break;
                case 'AC40.1':
                    popupMessage('Detalhe da conta', 'alert', 'Token de autorização não enviado, tente fazer o login novamente.');
                    break;
                case 'AC40.2':
                    popupMessage('Detalhe da conta', 'alert', 'Token de autorização inválido');
                    break;
                case 'AC40.3':
                    popupMessage('Detalhe da conta', 'alert', 'Não foi possível gravar o arquivo do certificado no servidor. Tente fazer o upload novamente.');
                    break;
                case 'AC40.4':
                    popupMessage('Detalhe da conta', 'alert', 'Não foi possível gravar o arquivo da chave do certificado no servidor. Tente fazer o upload novamente.');
                    break;
                case 'AC40.5':
                    popupMessage('Detalhe da conta', 'alert', 'Lista de ignorados inválida. Digite apenas os números do CPF/CNPJ sem pontuação, para mais de um ignorado separe com ponto e vírgula (;)');
                    break;
            }
        } else if (error.status === 500) {
            popupMessage('Detalhe da conta', 'error', 'Erro no servidor. Tente mais tarde');
        } else {
            popupMessage('Detalhe da conta', 'error', `Erro inesperado: ${error.message}`);
        }

        console.error('[updateAccountData] Error: ', error);
    }
}