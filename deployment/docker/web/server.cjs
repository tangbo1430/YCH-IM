const http = require('http');
const fs = require('fs');
const path = require('path');

const port = Number(process.env.PORT || 8080);
const staticRoot = process.env.WEB_ROOT || '/app/public';
const apiBase = process.env.API_BASE || 'http://gateway:8080';
const mainAsset = process.env.MAIN_ASSET || '/assets/index-eab254cd.js';
const legacyAppId = process.env.LEGACY_IM_SDK_APP_ID || '1600088222';
const appId = process.env.IM_SDK_APP_ID || '';
const mime = {
  '.html': 'text/html; charset=utf-8', '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8', '.json': 'application/json; charset=utf-8',
  '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.svg': 'image/svg+xml',
  '.woff': 'font/woff', '.woff2': 'font/woff2', '.ttf': 'font/ttf', '.ico': 'image/x-icon',
};

function readBody(request) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    request.on('data', chunk => chunks.push(chunk));
    request.on('end', () => resolve(Buffer.concat(chunks)));
    request.on('error', reject);
  });
}

async function proxyApi(request, response, url) {
  const body = await readBody(request);
  const headers = { 'content-type': request.headers['content-type'] || 'application/json' };
  const upstream = await fetch(apiBase + url.pathname + url.search, {
    method: request.method,
    headers,
    body: ['GET', 'HEAD'].includes(request.method) ? undefined : body,
  });
  let raw = Buffer.from(await upstream.arrayBuffer());

  if (url.pathname === '/api/login/user_login' && upstream.ok) {
    const legacy = JSON.parse(raw.toString('utf8'));
    if (legacy.code === 200 && legacy.data && legacy.data.login_token) {
      const sessionResponse = await fetch(apiBase + '/imaccess/session', {
        method: 'POST',
        headers: { 'content-type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ login_token: legacy.data.login_token }),
      });
      const session = await sessionResponse.json();
      if (!sessionResponse.ok || session.code !== 200) {
        raw = Buffer.from(JSON.stringify({ code: 502, msg: session.msg || 'IM session signing failed', data: null }));
      } else {
        legacy.data.user_sig = session.data.user_sig;
        legacy.data.uid = session.data.im_user_id;
        legacy.data.sdk_app_id = session.data.sdk_app_id;
        raw = Buffer.from(JSON.stringify(legacy));
      }
    }
  }

  response.writeHead(upstream.status, {
    'content-type': upstream.headers.get('content-type') || 'application/json; charset=utf-8',
    'cache-control': 'no-store',
  });
  response.end(raw);
}

function serveStatic(response, pathname) {
  const relative = pathname === '/' ? '/index.html' : pathname;
  const decoded = decodeURIComponent(relative).replaceAll('/', path.sep);
  const file = path.resolve(staticRoot, '.' + decoded);
  if (!file.startsWith(path.resolve(staticRoot)) || !fs.existsSync(file) || !fs.statSync(file).isFile()) return false;
  let content = fs.readFileSync(file);
  if (pathname === mainAsset) {
    if (!appId) throw new Error('IM_SDK_APP_ID is required');
    content = Buffer.from(content.toString('utf8').replaceAll(legacyAppId, appId));
  }
  response.writeHead(200, {
    'content-type': mime[path.extname(file).toLowerCase()] || 'application/octet-stream',
    'cache-control': pathname === mainAsset ? 'no-store' : 'public, max-age=3600',
  });
  response.end(content);
  return true;
}

http.createServer(async (request, response) => {
  try {
    const url = new URL(request.url, `http://${request.headers.host}`);
    if (url.pathname.startsWith('/api/') || url.pathname === '/imaccess/session') {
      await proxyApi(request, response, url);
      return;
    }
    if (serveStatic(response, url.pathname)) return;
    response.writeHead(404, { 'content-type': 'text/plain; charset=utf-8' });
    response.end('Not Found');
  } catch (error) {
    response.writeHead(502, { 'content-type': 'application/json; charset=utf-8' });
    response.end(JSON.stringify({ code: 502, msg: 'Web gateway error', data: null }));
  }
}).listen(port, '0.0.0.0');
