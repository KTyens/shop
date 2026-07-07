const DEFAULT_API_PROXY_BASE_URL = 'https://api.crtlu.me/api';

function buildTargetUrl(request, params, env) {
  const requestUrl = new URL(request.url);
  const pathParam = params.path;
  const path = Array.isArray(pathParam) ? pathParam.join('/') : String(pathParam || '');
  const proxyBase = String(env.CRTLU_API_PROXY_BASE_URL || DEFAULT_API_PROXY_BASE_URL).replace(/\/+$/, '');
  const targetUrl = new URL(`${proxyBase}/${path.replace(/^\/+/, '')}`);
  targetUrl.search = requestUrl.search;

  if (targetUrl.hostname === requestUrl.hostname) {
    throw new Error('API proxy target cannot be the same host as the Cloudflare Pages site.');
  }

  return targetUrl;
}

function proxyHeaders(request) {
  const headers = new Headers(request.headers);
  headers.delete('host');
  headers.delete('cf-connecting-ip');
  headers.delete('cf-ipcountry');
  headers.delete('cf-ray');
  headers.delete('cf-visitor');
  headers.delete('x-forwarded-proto');
  headers.delete('x-real-ip');
  return headers;
}

async function normalizeJsonResponse(response) {
  const contentType = response.headers.get('content-type') || '';
  if (!contentType.toLowerCase().includes('application/json')) {
    return response;
  }

  const body = await response.text();
  const cleanedBody = body
    .replace(/^```php\s*/i, '')
    .replace(/\s*```\s*$/i, '');
  const headers = new Headers(response.headers);
  headers.delete('content-length');

  if (cleanedBody === body) {
    return new Response(body, {
      status: response.status,
      statusText: response.statusText,
      headers,
    });
  }

  return new Response(cleanedBody, {
    status: response.status,
    statusText: response.statusText,
    headers,
  });
}

export async function onRequest({ request, params, env }) {
  try {
    const targetUrl = buildTargetUrl(request, params, env);
    const method = request.method.toUpperCase();
    const init = {
      method,
      headers: proxyHeaders(request),
      redirect: 'manual',
    };

    if (!['GET', 'HEAD'].includes(method)) {
      init.body = request.body;
      init.duplex = 'half';
    }

    const response = await fetch(targetUrl.toString(), init);
    return normalizeJsonResponse(response);
  } catch (error) {
    return Response.json(
      { error: error instanceof Error ? error.message : 'API proxy failed.' },
      { status: 502 }
    );
  }
}
