import { popupMessage , popupConfirmation } from './popup.js'

const logUrlApi = '/controller/logs.php';

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
 * 
 * @param {HTMLElement} selector 
 */
export async function logList(selector) {
    const payload = {
        request: "loglist"
    };
    const list = await apiRequest(payload, logUrlApi);

    selector.textContent = '';

    for (let index = 0; index < list.length; index++) {
        const logOption = document.createElement('option');
        logOption.text = list[index];
        logOption.value = list[index];

        selector.add(logOption);
    }
}
 /**
  * 
  * @param {HTMLElement} textArea
  * @param {string} fileName
  */
export async function tailLog(textArea, fileName) {
    textArea.textContent = '';

    if (fileName === 'pix-service.log') {

        const payload = {
            request: "logtext",
            filename: fileName
        }

        const logContent = await apiRequest(payload, logUrlApi);
        textArea.textContent = logContent['logContent'];
    } else {
        fetchLogFile(fileName, textArea);
    }

    requestAnimationFrame(() => {
        textArea.scrollTop = textArea.scrollHeight;
    });
}

/**
 * 
 * @param {string} filename 
 */
async function fetchLogFile(filename, textArea) {
    const textarea = textArea;
 
    try {
        // Faz a requisição
        const response = await fetch(logUrlApi, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ filename, request: 'logfile' }),
        });

        if (!response.ok) {
            throw new Error(`Erro: ${response.statusText}`);
        }

        // Lê o arquivo ZIP como blob
        const blob = await response.blob();

        // Extrai o conteúdo do ZIP
        const zip = new JSZip();
        const zipContent = await zip.loadAsync(blob);
        const logFile = Object.keys(zipContent.files)[0];
        const logText = await zipContent.files[logFile].async('string');

        // Atualiza a textarea com o conteúdo do log
        textarea.textContent = logText;
    } catch (error) {
        textarea.textContent = `Erro ao baixar log: ${error.message}`;
    }
}

