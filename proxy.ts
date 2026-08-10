const PHP_PORT = 8001;
const PORT = 8000;

console.log(`🚀 Keysha Bun Proxy listening on http://0.0.0.0:${PORT} -> forwarding to PHP on http://127.0.0.1:${PHP_PORT}`);

Bun.serve({
    port: PORT,
    hostname: '0.0.0.0',
    async fetch(req) {
        const url = new URL(req.url);
        const targetUrl = `http://127.0.0.1:${PHP_PORT}${url.pathname}${url.search}`;

        const headers = new Headers(req.headers);

        try {
            const body = (req.method !== 'GET' && req.method !== 'HEAD') ? await req.arrayBuffer() : undefined;

            const response = await fetch(targetUrl, {
                method: req.method,
                headers: headers,
                body: body,
                redirect: 'manual',
            });

            const resHeaders = new Headers(response.headers);

            return new Response(response.body, {
                status: response.status,
                statusText: response.statusText,
                headers: resHeaders,
            });
        } catch (err: any) {
            console.error('Proxy Fetch Error:', err.message);
            return new Response(`Keysha Server Proxy Error: ${err.message}`, { status: 502 });
        }
    },
});
