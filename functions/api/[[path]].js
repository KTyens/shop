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
  headers.delete('x-real-ip');
  // Tell PHP this is the shop → CF → Serv00 path so session cookies use SameSite=Lax.
  headers.set('X-CRTLU-Proxy', '1');
  headers.set('X-Forwarded-Proto', 'https');
  try {
    const shopHost = new URL(request.url).hostname;
    headers.set('X-Forwarded-Host', shopHost);
  } catch {
    // ignore
  }
  return headers;
}

function collectSetCookies(responseHeaders) {
  if (typeof responseHeaders.getSetCookie === 'function') {
    const list = responseHeaders.getSetCookie();
    if (Array.isArray(list) && list.length) return list;
  }
  // Fallback: some runtimes only expose a single combined header
  const single = responseHeaders.get('set-cookie');
  if (!single) return [];
  // Prefer not to split on commas (expires dates contain commas); treat as one cookie.
  return [single];
}

function rewriteUpstreamCookies(responseHeaders, requestUrl) {
  // Rebuild headers so Set-Cookie is never dropped by Headers() copy quirks.
  const headers = new Headers();
  for (const [key, value] of responseHeaders.entries()) {
    if (key.toLowerCase() === 'set-cookie') continue;
    headers.append(key, value);
  }

  const cookies = collectSetCookies(responseHeaders);
  for (const raw of cookies) {
    let next = String(raw)
      .replace(/;\s*Domain=[^;]*/gi, '')
      .replace(/;\s*SameSite=[^;]*/gi, '');
    // Same-origin proxy: Lax + Secure on shop host
    if (!/;\s*Secure/i.test(next) && requestUrl.startsWith('https:')) {
      next += '; Secure';
    }
    next += '; SameSite=Lax';
    if (!/;\s*Path=/i.test(next)) {
      next += '; Path=/';
    }
    headers.append('set-cookie', next);
  }

  // Avoid leaking upstream CORS that confuses same-origin clients
  headers.delete('access-control-allow-origin');
  headers.delete('access-control-allow-credentials');
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
