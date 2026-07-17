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

function rewriteUpstreamCookies(responseHeaders, requestUrl) {
  const headers = new Headers(responseHeaders);
  const host = new URL(requestUrl).hostname;
  // Re-emit Set-Cookie so the browser stores the session on shop.crtlu.me (proxy host), not api host.
  const cookies = typeof responseHeaders.getSetCookie === 'function'
    ? responseHeaders.getSetCookie()
    : [];
  if (cookies.length) {
    headers.delete('set-cookie');
    for (const raw of cookies) {
      let next = raw
        .replace(/;\s*Domain=[^;]*/gi, '')
        .replace(/;\s*SameSite=[^;]*/gi, '');
      // Same-origin proxy: Lax is fine on shop host
      if (!/;\s*Secure/i.test(next) && requestUrl.startsWith('https:')) {
        next += '; Secure';
      }
      next += '; SameSite=Lax';
      // Ensure path
      if (!/;\s*Path=/i.test(next)) {
        next += '; Path=/';
      }
      headers.append('set-cookie', next);
    }
  }
  // Avoid leaking upstream CORS that confuses same-origin clients
  headers.delete('access-control-allow-origin');
  headers.delete('access-control-allow-credentials');
  void host;
  return headers;
}

async function normalizeJsonResponse(response, requestUrl) {
  const contentType = response.headers.get('content-type') || '';
  const headers = rewriteUpstreamCookies(response.headers, requestUrl);
  headers.delete('content-length');

  if (!contentType.toLowerCase().includes('application/json')) {
    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers,
    });
  }

  const body = await response.text();
  const cleanedBody = body
    .replace(/^```php\s*/i, '')
    .replace(/\s*```\s*$/i, '');

  return new Response(cleanedBody, {
    status: response.status,
    statusText: response.statusText,
    headers,
  });
}

export async function onRequest({ request, params, env }) {
  try {
    // Same-origin preflight (rare) — answer locally
    if (request.method.toUpperCase() === 'OPTIONS') {
      return new Response(null, {
        status: 204,
        headers: {
          'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
          'Access-Control-Allow-Headers': 'Content-Type, Authorization',
          'Access-Control-Max-Age': '86400',
        },
      });
    }

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
    return normalizeJsonResponse(response, request.url);
  } catch (error) {
    return Response.json(
      { error: error instanceof Error ? error.message : 'API proxy failed.' },
      { status: 502 }
    );
  }
}
