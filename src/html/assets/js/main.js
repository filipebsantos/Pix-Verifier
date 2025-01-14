import { fetchTransactions, fetchTransactionsByPayer, fetchLastTransactions, popupMessage, getServiceStatus } from './transaction-functions.js'
import { listAccounts } from './accounts.js';

const accountSelector = document.getElementById('inputAccountSelector');
const filterByDate = document.getElementById('btnFilterByDate');
const findByPayer = document.getElementById('btnFindByPayer');
const payerDocField = document.getElementById('payerDoc');
const currentDate = new Date();
const formattedCurrentDate = `${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().padStart(2, '0')}-${currentDate.getDate().toString().padStart(2, '0')}`

let maskedPayerDoc = IMask(payerDocField, {
    mask: [
        { mask: '000.000.000-00', maxLength: 11 },
        { mask: '00.000.000/0000-00' }
    ],
    dispatch: function(appended, dynamicMasked) {
        const number = (dynamicMasked.value + appended).replace(/\D/g, '');

        return number.length > 11 ? dynamicMasked.compiledMasks[1] : dynamicMasked.compiledMasks[0]
    }
});

async function autoUpdate() {
    const transactionsTable = document.getElementById("tableBody");

    if (formattedCurrentDate === localStorage.getItem('tlStartDate') && formattedCurrentDate === localStorage.getItem('tlEndDate') && localStorage.getItem('tlAction') === 'fetchTransactions' && localStorage.getItem('tlCurrentPage') == 1){
        
        if ((transactionsTable.rows.length >= 1) && (transactionsTable.rows[0].textContent === 'Nenhuma transação encontrada.')){
            await fetchTransactions('fetchTransactions', accountSelector.options[accountSelector.selectedIndex].value, formattedCurrentDate, formattedCurrentDate, '1');
        } else {
            const lastTransactionTimestamp = transactionsTable.querySelector('tr td:nth-child(5)').textContent;
            const [datePart, timePart] = lastTransactionTimestamp.split(" ");
            const [day, month, year] = datePart.split("/");
            const timestamp = `${year}-${month}-${day} ${timePart}`;

            await fetchLastTransactions('1', timestamp);
        }
    }

    await getServiceStatus();
}

async function checkForNewVersion() {
    const localVersion = document.getElementById('appVersion').innerText.trim(); // Pega a versão local do HTML
    const apiUrl = "https://api.github.com/repos/filipebsantos/Pix-Verifier/releases/latest";
    const versionIndicator = document.getElementById('newVersionIndicator'); // Seleciona o elemento do indicador

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error("Erro ao buscar a versão do GitHub.");

        const release = await response.json();
        const latestVersion = release.tag_name; // Nome da tag da versão mais recente

        // Verifica se a versão mais recente é diferente da versão local
        if (latestVersion !== localVersion) {
            // Exibe o indicador e atualiza a tooltip com a versão mais recente
            versionIndicator.style.display = 'inline-block';
            versionIndicator.setAttribute('data-bs-title', `Versão ${latestVersion} disponível`);
            
            // Ativa a tooltip do Bootstrap
            new bootstrap.Tooltip(versionIndicator);
        }
    } catch (error) {
        console.error("Erro ao verificar a versão:", error);
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    await getServiceStatus();
    await listAccounts();
    await fetchTransactions('fetchTransactions', accountSelector.options[accountSelector.selectedIndex].value, formattedCurrentDate, formattedCurrentDate, '1');
    await checkForNewVersion();

    setInterval(autoUpdate, 5000);

    accountSelector.addEventListener("change", async () => {
        await fetchTransactions('fetchTransactions', accountSelector.options[accountSelector.selectedIndex].value, formattedCurrentDate, formattedCurrentDate, '1');
    });

    findByPayer.addEventListener('click', async (event) => {
        const payerDoc = document.getElementById('payerDoc');
        
        if (payerDoc.value !== '') {
            await fetchTransactionsByPayer(maskedPayerDoc.unmaskedValue);
        } else {
            popupMessage('Buscar por emissor', 'alert', 'Informe o CPF ou CNPJ sem pontos ou hífen.')
        }
    });
    
    payerDocField.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            findByPayer.click();
        }
    });

    filterByDate.addEventListener('click', async (event) => {
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
    
        if (startDate.value !== '' && endDate.value !== '') {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            const current = new Date();
            
            if (start > current) {
                popupMessage('Filtrar por data', 'alert', 'A data inicial não pode ser maior que a data atual.');
            } else if (end > current) {
                popupMessage('Filtrar por data', 'alert', 'A data final não pode ser maior que a data atual.');
            } else if (start > end) {
                popupMessage('Filtrar por data', 'alert', 'A data inical não pode ser maior que a data final.');
            } else {
                const tlAction = localStorage.getItem('tlAction');
                const tlAccountId = localStorage.getItem('tlAccountId');
    
                await fetchTransactions(tlAction, tlAccountId, startDate.value, endDate.value, '1');
            }
            
        } else {
            popupMessage('Filtrar por data', 'alert', 'Informe a data de início e data final.')
        }
    });
});