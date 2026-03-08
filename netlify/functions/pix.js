const CLIENT_ID = '01c5933d-3ecd-4782-8a79-2b96a8d41e92';
const CLIENT_SECRET = 'eab4b472-730b-43b5-97af-0ee68fb1f195';
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
    // Apenas permite POST
    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            body: JSON.stringify({ error: 'Method Not Allowed' })
        };
    }

    try {
        const input = JSON.parse(event.body || '{}');
        const { amount, name, cpf, email, phone, plan } = input;

        if (!amount || !name || !cpf || !email || !phone) {
            return {
                statusCode: 400,
                body: JSON.stringify({ error: 'Parâmetros incompletos.' })
            };
        }

        const token = await getToken();

        // Determinar URL de callback se hospedado, baseado no host atual
        const webhook_url = event.headers.host
            ? `https://${event.headers.host}/.netlify/functions/webhook`
            : 'https://site.com/webhook';

        const payload = {
            amount: parseFloat(amount),
            description: "Compra do plano: " + (plan || 'Plano Generico'),
            webhook_url: webhook_url,
            client: {
                name: name,
                cpf: cpf.replace(/[^0-9]/g, ''),
                email: email,
                phone: String(phone).replace(/[^0-9]/g, '').padStart(11, '0')
            }
        };

        const response = await fetch(`${API_BASE}/api/partner/v1/cash-in`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.pix_code && data.identifier) {
            return {
                statusCode: 200,
                body: JSON.stringify({
                    pix_code: data.pix_code,
                    identifier: data.identifier
                })
            };
        } else {
            return {
                statusCode: 400,
                body: JSON.stringify({
                    error: data.message || 'Erro ao conectar à API da Syncpay',
                    raw: data
                })
            };
        }
    } catch (error) {
        return {
            statusCode: 500,
            body: JSON.stringify({ error: error.message })
        };
    }
};
