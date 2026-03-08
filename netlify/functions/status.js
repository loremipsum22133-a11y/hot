const CLIENT_ID = '05835457-fc65-481f-bbb1-eeb2bf5ce2a1';
const CLIENT_SECRET = '9a137442-1dc5-4a81-b88a-c883379c3483';
const API_BASE = 'https://api.syncpayments.com.br';

let cachedToken = null;
let tokenExpiresAt = 0;

async function getToken() {
    if (cachedToken && Date.now() < tokenExpiresAt) {
        return cachedToken;
    }

    try {
        const response = await fetch(`${API_BASE}/api/partner/v1/auth-token`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                client_id: CLIENT_ID,
                client_secret: CLIENT_SECRET
            })
        });

        const data = await response.json();
        if (response.ok && data.access_token) {
            cachedToken = data.access_token;
            // Token expira em 3600s, subtraímos 60s de margem
            tokenExpiresAt = Date.now() + (data.expires_in - 60) * 1000;
            return cachedToken;
        }
        throw new Error(data.message || 'Erro ao autenticar');
    } catch (e) {
        throw new Error('Falha de conexão com a API de Auth: ' + e.message);
    }
}

exports.handler = async function (event, context) {
    // Apenas permite POST (ou GET se quiser validar via query params, mas HTML tá enviando via POST JSON)
    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            body: JSON.stringify({ error: 'Method Not Allowed' })
        };
    }

    try {
        const input = JSON.parse(event.body || '{}');
        const { identifier } = input;

        if (!identifier) {
            return {
                statusCode: 400,
                body: JSON.stringify({ error: 'Parâmetros incompletos.' })
            };
        }

        const token = await getToken();

        const response = await fetch(`${API_BASE}/api/partner/v1/transaction/${encodeURIComponent(identifier)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        const data = await response.json();

        if (response.ok && data.data) {
            return {
                statusCode: 200,
                body: JSON.stringify(data)
            };
        } else {
            return {
                statusCode: 200,
                body: JSON.stringify({ status: 'PENDING', raw: data })
            };
        }
    } catch (error) {
        return {
            statusCode: 500,
            body: JSON.stringify({ error: error.message })
        };
    }
};
