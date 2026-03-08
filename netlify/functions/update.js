exports.handler = async function (event, context) {
    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            body: JSON.stringify({ error: 'Method Not Allowed' })
        };
    }
    return {
        statusCode: 200,
        body: JSON.stringify({ success: true, message: 'Update handled silently' })
    };
};
