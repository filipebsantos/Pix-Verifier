function formatCpfCnpj(value) {
    value = value.replace(/\D/g, '');

    if (value.length === 11) {
        return value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    } else if (value.length === 14) {
        return value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
    } else {
        return value;
    }
}

export function popupMessage(title, type, message) {
    const popupModal = new bootstrap.Modal(document.getElementById('popupMsg'));

    popupModal._element.querySelector('#modalTitle').textContent = title;
    popupModal._element.querySelector('.modal-body p').textContent = message;

    const icon = popupModal._element.querySelector('.popup-icon');
    switch (type) {
        case 'error':
            icon.src = './assets/imgs/error-icon.png';
            break;

        case 'alert':
            icon.src = './assets/imgs/alert-icon.png';
            break;

        case 'success':
            icon.src = './assets/imgs/success-icon.png';
            break;
    }

    popupModal.show()
}

/**
 * Create the pagination to transaction list results
 * 
 * @param {*} totalPages 
 * @param {*} currentPage 
 */
function pagination(totalPages, currentPage) {
    const resultPagination = document.querySelector('.pagination');
    const paginationBox = document.getElementById('pagination-box');
    const maxPagesView = 10;
    const tlAction = localStorage.getItem('tlAction');
    const tlAccountId = localStorage.getItem('tlAccountId');
    const tlStartDate = localStorage.getItem('tlStartDate');
    const tlEndDate = localStorage.getItem('tlEndDate');

    resultPagination.innerHTML = '';

    const prevButton = document.createElement('li');
    prevButton.classList.add('page-item');
    if (currentPage <= 1) { 
        prevButton.classList.add('disabled');
    } else {
        prevButton.onclick = () => pagination(totalPages, Math.max(currentPage - 10, 1));
    }
    prevButton.innerHTML = `<span class="page-link" aria-label="Previous" aria-hidden="true">&laquo;</span>`;
    resultPagination.appendChild(prevButton);

    let startPage = Math.max(1, currentPage - Math.floor(maxPagesView / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesView - 1);
    
    if (endPage - startPage < maxPagesView - 1) {
        startPage = Math.max(1, endPage - maxPagesView + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('li');
        pageButton.classList.add('page-item');
        if (currentPage === i) { 
            pageButton.classList.add('active'); 
        }
        pageButton.innerHTML = `<span class="page-link">${i}</span>`;
        pageButton.onclick = () => fetchTransactions(tlAction, tlAccountId, tlStartDate, tlEndDate, i);
        resultPagination.appendChild(pageButton);
    }

    const nextButton = document.createElement('li');
    nextButton.classList.add('page-item');
    if (currentPage >= totalPages) { 
        nextButton.classList.add('disabled');
    } else {
        nextButton.onclick = () => pagination(totalPages, Math.min(currentPage + 10, totalPages));
    }
    nextButton.innerHTML = `<span class="page-link" aria-label="Next" aria-hidden="true">&raquo;</span>`;
    resultPagination.appendChild(nextButton);

    paginationBox.style.display = 'inline';
}


/**
 * Receive the payload from API and populate the results in table
 * 
 * @param {*} payload 
 */
function populateTransactionList(payload) {
    const transactionsTable = document.querySelector('.table-group-divider');
    const resultPagination = document.querySelector('.pagination');

    transactionsTable.innerHTML = '';

    if (payload.transactions.length > 0) {

        payload.transactions.forEach(transaction => {
            const tr = document.createElement('tr');
    
            const tdBank = document.createElement('td');
            const bankId = inputAccountSelector.options[inputAccountSelector.selectedIndex].dataset.bank;
            tdBank.innerHTML = `<img class="bank-logo-${bankId}" src="./assets/imgs/bank-logo-${bankId}.png" alt="">`;
            tr.appendChild(tdBank);
    
            const tdAccount = document.createElement('td');
            tdAccount.innerText = inputAccountSelector.options[inputAccountSelector.selectedIndex].dataset.accname;
            tr.appendChild(tdAccount);
    
            const tdValue = document.createElement('td');
            tdValue.innerText = `R$ ${parseFloat(transaction.value).toFixed(2).replace('.', ',')}`;
            tr.appendChild(tdValue);
    
            const tdPayer = document.createElement('td');
            tdPayer.innerText = transaction.payer;
            tr.appendChild(tdPayer);
    
            const tdDate = document.createElement('td');
            const originalDate = new Date(transaction.date);
            const formattedDate = `${originalDate.getDate().toString().padStart(2, '0')}/${
                (originalDate.getMonth() + 1).toString().padStart(2, '0')}/${
                originalDate.getFullYear()} ${
                originalDate.getHours().toString().padStart(2, '0')}:${
                originalDate.getMinutes().toString().padStart(2, '0')}:${
                originalDate.getSeconds().toString().padStart(2, '0')}`;
    
            tdDate.innerText = formattedDate;
            tr.appendChild(tdDate);
    
            const tdDetails = document.createElement('td');
            const buttonDetails = document.createElement('button');
            buttonDetails.classList.add('btn', 'btn-outline-secondary');
            buttonDetails.innerHTML = `<img src="./assets/imgs/dots-vertical.svg" alt="Detalhes">`;
            buttonDetails.onclick = () => fetchTransactionData(transaction.e2eid);
            tdDetails.appendChild(buttonDetails);
            tr.appendChild(tdDetails);
    
            transactionsTable.appendChild(tr);
        });

        if (payload.totalPages > 1) {
            pagination(payload.totalPages, payload.currentPage);
        } else { 
            resultPagination.innerHTML = '';
        }
    } else {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.innerText = 'Nenhuma transação encontrada.';
        tr.appendChild(td);
        transactionsTable.appendChild(tr);
        resultPagination.innerHTML = '';
    }
}

function updateTransactionTable(payload) {
    const notificationSound = document.getElementById("notificationSound");
    const transactionsTable = document.getElementById("tableBody");

    if (payload.qty >= 1) {

        if ((transactionsTable.rows.length == 1) && (transactionsTable.rows[0].textContent === 'Nenhuma transação encontrada.')) {
            transactionsTable.innerHTML = '';     
        }

        for (let i = 0; i < payload.transactions.length; i++) {
            const transaction = payload.transactions[i];
    
            const newLine = transactionsTable.insertRow(i);

            const bankImg = newLine.insertCell(0);
            bankImg.innerHTML = `<img class="bank-logo-${inputAccountSelector.options[inputAccountSelector.selectedIndex].dataset.bank}" src="./assets/imgs/bank-logo-${inputAccountSelector.options[inputAccountSelector.selectedIndex].dataset.bank}.png" alt="">`;
            bankImg.classList.add("new-transaction");

            const accountName = newLine.insertCell(1);
            accountName.innerHTML = inputAccountSelector.options[inputAccountSelector.selectedIndex].dataset.accname;
            accountName.classList.add("new-transaction");
    
            const transactionValue = newLine.insertCell(2);
            transactionValue.innerHTML = `R$ ${parseFloat(transaction.value).toFixed(2).replace('.', ',')}`;
            transactionValue.classList.add("new-transaction");
    
            const payerName = newLine.insertCell(3);
            payerName.innerHTML = transaction.payer;
            payerName.classList.add("new-transaction");
    
            const transactionDate = newLine.insertCell(4);
            const originalDate = new Date(transaction.date);
            const formattedDate = `${originalDate.getDate().toString().padStart(2, '0')}/${
                (originalDate.getMonth() + 1).toString().padStart(2, '0')}/${
                originalDate.getFullYear()} ${
                originalDate.getHours().toString().padStart(2, '0')}:${
                originalDate.getMinutes().toString().padStart(2, '0')}:${
                originalDate.getSeconds().toString().padStart(2, '0')}`;
            transactionDate.innerHTML = formattedDate;
            transactionDate.classList.add("new-transaction");
    
            const detailTransaction = newLine.insertCell(5);
            detailTransaction.innerHTML = '<button class="btn btn-outline-secondary"><img src="./assets/imgs/dots-vertical.svg" alt="Detalhes"></button>';
            detailTransaction.onclick = () => fetchTransactionData(transaction.e2eid);
            detailTransaction.classList.add("new-transaction");

            setTimeout(() => {
                bankImg.classList.remove("new-transaction");
                accountName.classList.remove("new-transaction");
                transactionValue.classList.remove("new-transaction");
                payerName.classList.remove("new-transaction");
                transactionDate.classList.remove("new-transaction");
                detailTransaction.classList.remove("new-transaction");
            }, 6000);
        }
    
        if (payload.totalPages > 1){
            while (transactionsTable.rows.length > 10) {
                transactionsTable.deleteRow(transactionsTable.rows.length - 1);
            }
    
            pagination(payload.totalPages, 1);
        }
        const notificationSwitch = document.getElementById('notificationSwitch');

        if (notificationSwitch.checked) {
            notificationSound.play();
        }   
    }
}

/**
 * Fetch the transactions
 * 
 * @param {*} action Witch type of transactions shoud be fetched.
 * @param {*} accountId The unique account id.
 * @param {*} startDate Initial date
 * @param {*} endDate Until witch date transactions should be fetched.
 * @param {*} page FOR PAGINATION - Witch page should be fetched.
 * @returns 
 */
export async function fetchTransactions(action, accountId, startDate, endDate, page) {
    
    // Save action to use in pagination if needed.
    if (window.localStorage) {
        localStorage.setItem('tlAction', action);
        localStorage.setItem('tlAccountId', accountId);
        localStorage.setItem('tlStartDate', startDate);
        localStorage.setItem('tlEndDate', endDate);
        localStorage.setItem('tlCurrentPage', page);
    }
       
    const payload = {
        action: action,
        accountId: accountId,
        startDate: startDate,
        endDate: endDate,
        page: page
    }

    const responseApi = await fetch('./controller/transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const data = await responseApi.json();

    switch (responseApi.status) {
        case 200:
            populateTransactionList(data);
            break;

        case 400:
            switch (data.code) {
                case 'TR10.1':
                    popupMessage('Alerta', 'alert', 'A data inicial não pode ser maior que a data atual.');
                break;

                case 'TR10.2':
                    popupMessage('Alerta', 'alert', 'A data final não pode ser maior que a data atual.');
                break;

                case 'TR10.3':
                    popupMessage('Alerta', 'alert', 'A data inicial não pode ser maior que a data final.');
                break;

                default:
                    popupMessage('Alerta', 'alert', 'Houve um problema com a sua requisição.');
                    console.error(`[${action}][${data.code}] ${data.message}`);
                break;
            }
            break;
        
        default:
            popupMessage('Falha Crítica', 'error', 'Não foi possível processar sua requisição.');
            console.error(`[${action}] ${data.message}`);
        break;
    }
}

export async function fetchTransactionData(e2eid) {
    const payload = {
        action: 'fetchTransactionData',
        e2eid: e2eid
    };

    const responseApi = await fetch('./controller/transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });
    const data = await responseApi.json();
    switch (responseApi.status) {
        case 200:
            const tDetailPayer = document.getElementById('tDetail-payer');
            const tDetailValue = document.getElementById('tDetail-value');
            const tDetailDescription = document.getElementById('tDetail-description');
            const tDetailPayerBank = document.getElementById('tDetail-payerBank');
            const tDetailE2eid = document.getElementById('tDetail-e2eid');
            const tDetailPayerDoc = document.getElementById('tDetail-payerDoc');

            tDetailPayer.innerText = data.payer;
            tDetailValue.innerText = `R$ ${parseFloat(data.value).toFixed(2).replace('.', ',')}`;
            tDetailPayerDoc.innerText = formatCpfCnpj(data.payerdoc);
            tDetailPayerBank.innerText = data.payerbank;
            tDetailE2eid.innerText = data.e2eid;
            tDetailDescription.innerText = data.description;
            
            const transactionDetailModal = new bootstrap.Modal(document.getElementById('transactionDetail'));
            transactionDetailModal.show();
        break;

        case 400:
            popupMessage('Alerta', 'alert', 'O identificador E2EID não foi enviado.');
        break;

        default:
            popupMessage('Falha Crítica', 'error', 'Não foi possível processar sua requisição.');
            console.error(`[${action}] ${data.message}`);
        break;
    }
}

export async function fetchTransactionsByPayer(payerdoc) {
    const tlAccountId = localStorage.getItem('tlAccountId');

    const payload = {
        action: 'filterBySender',
        accountId: tlAccountId,
        payerdoc: payerdoc
    };

    const responseApi = await fetch('./controller/transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    const data = await responseApi.json();

    switch (responseApi.status) {
        case 200:        
            populateTransactionList(data);
        break;

        case 400:
            popupMessage('Alerta', 'alert', 'Faltam dados na sua requisição.');
            console.error(`[${action}][${data.code}] ${data.message}`);
        break;

        default:
            popupMessage('Falha Crítica', 'error', 'Não foi possível processar sua requisição.');
            console.error(`[${action}] ${data.message}`);
        break;
    }

}

export async function fetchLastTransactions(accountId, timestamp) {

    const payload = {
        action: 'fetchLastTransactions',
        accountId: accountId,
        timestamp: timestamp
    }

    const responseApi = await fetch('./controller/transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })

    const data = await responseApi.json();

    switch (responseApi.status) {
        case 200:        
            updateTransactionTable(data);
        break;

        case 400:
            console.error(`[${payload.action}][${data.code}] ${data.message}`);
        break;

        default:
            console.error(`[${payload.action}] ${data.message}`);
        break;
    }

}

export async function getServiceStatus() {
    const serviceStatus = document.getElementById('serviceStatus');

    const payload = {
        request: 'getStatus'
    }

    try {
        const responseApi = await fetch('./controller/service.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await responseApi.json();

        switch (responseApi.status) {
            case 200:
                serviceStatus.innerHTML = '';

                switch (data.status) {
                    case 'running':
                        serviceStatus.innerHTML = 'Status do serviço: 🟢';
                    break;

                    case 'stopped':
                        serviceStatus.innerHTML = 'Status do serviço: 🔴';
                    break;

                    case 'error':
                        serviceStatus.innerHTML = 'Status do serviço: 🟡';
                    break;
                }
            break;
        }
    } catch (error) {
        console.error("Network Error: ", error);
    }

}
