const apiUrl = '/controller/service.php';

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

export async function getProcessStatus() {
    
    const serviceStatus = document.getElementById('service-status');
    const statusDesciption = document.getElementById('status-description');
    const startButton = document.getElementById('start-service');
    const stopButton = document.getElementById('stop-service');

    const payload = {
        request: 'getProcess'
    };

    try {
        const data = await apiRequest(payload, apiUrl);

        serviceStatus.textContent = data['status'];
        statusDesciption.textContent = data['description'];

        if (data['status'] === 'Em execução') {
            startButton.disabled = true;
            stopButton.disabled = false;
        } else if (data['status'] === 'Parado' || data['status'] === 'Erro fatal') {
            startButton.disabled = false;
            stopButton.disabled = true;
        } else {
            startButton.disabled = true;
            stopButton.disabled = true;
        }
    } catch (error) {
        console.error(error);
    }
}

export async function startService() {
    const payload = {
        request: 'startService'
    }

    const data = await apiRequest(payload, apiUrl);
    console.log(data);
}

export async function stopService() {
    const payload = {
        request: 'stopService'
    }

    const data = await apiRequest(payload, apiUrl);
    console.log(data);
}