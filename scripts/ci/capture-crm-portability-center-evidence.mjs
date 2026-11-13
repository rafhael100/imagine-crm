#!/usr/bin/env node
// CRM IN-118 F3-201: Chrome screenshot evidence for a new static offline-only page.
// Only approved offline HTML fixtures. This is visual evidence, not a UX sign-off.
import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve, join } from 'node:path';
import { pathToFileURL } from 'node:url';

const [proposalArg, outputArg] = process.argv.slice(2);
if (!proposalArg || !outputArg) throw new Error(
  'Usage: node capture-crm-portability-center-evidence.mjs proposed.html output-dir'
);
const proposed = resolve(proposalArg);
const out = resolve(outputArg);
const pages = [['new-offline-center', proposed]];
for (const [, filename] of pages) {
  const source = readFileSync(filename, 'utf8');
  for (const policy of ["connect-src 'none'", "form-action 'none'", 'PROTÓTIPO — DADOS FICTÍCIOS']) {
    if (!source.includes(policy)) throw new Error('Offline boundary missing: ' + filename + ' ' + policy);
  }
  if (source.includes('http://') || source.includes('https://cdn.')) throw new Error('External fixture resource found');
}
mkdirSync(out, { recursive: true });
const profile = mkdtempSync(join(tmpdir(), 'imagine-cdp-'));
const chrome = spawn(process.env.CHROME_BINARY || 'google-chrome', [
  '--headless=new', '--disable-gpu', '--no-sandbox', '--no-first-run',
  '--disable-background-networking', '--disable-extensions', '--disable-dev-shm-usage',
  '--no-default-browser-check', '--remote-debugging-address=127.0.0.1',
  '--remote-debugging-port=0', '--remote-allow-origins=*',
  '--user-data-dir=' + profile, 'about:blank',
], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let socket;
let nextId = 1;
const pending = new Map();
const listeners = new Map();
const failPending = error => {
  for (const [, entry] of pending) entry.reject(error);
  pending.clear();
};
function waitEvent(method, sessionId, timeoutMs = 12000) {
  return new Promise((resolveEvent, rejectEvent) => {
    const key = method + ':' + sessionId;
    const timer = setTimeout(() => {
      listeners.delete(key);
      rejectEvent(new Error('Missing CDP event: ' + method));
    }, timeoutMs);
    listeners.set(key, () => {
      clearTimeout(timer);
      listeners.delete(key);
      resolveEvent();
    });
  });
}
async function command(method, params = {}, sessionId = undefined) {
  if (!socket || socket.readyState !== WebSocket.OPEN) throw new Error('CDP socket not open');
  const id = nextId++;
  const response = new Promise((resolveCommand, rejectCommand) => {
    const timer = setTimeout(() => {
      pending.delete(id);
      rejectCommand(new Error('CDP timeout: ' + method));
    }, 15000);
    pending.set(id, {
      resolve: x => { clearTimeout(timer); resolveCommand(x); },
      reject: e => { clearTimeout(timer); rejectCommand(e); },
    });
  });
  socket.send(JSON.stringify({ id, method, params, ...(sessionId ? {sessionId} : {}) }));
  return response;
}
try {
  for (let i = 0; i < 120 && !existsSync(join(profile, 'DevToolsActivePort')); i++) {
    if (chrome.exitCode !== null) throw new Error('Chromium exited early: ' + chrome.exitCode);
    await sleep(100);
  }
  if (!existsSync(join(profile, 'DevToolsActivePort'))) throw new Error('Chromium DevTools port unavailable');
  const [port, wsPath] = readFileSync(join(profile, 'DevToolsActivePort'), 'utf8').trim().split('\n');
  socket = new WebSocket('ws://127.0.0.1:' + port + wsPath);
  socket.addEventListener('message', event => {
    let message;
    try { message = JSON.parse(event.data); } catch { return; }
    if (message.id) {
      const task = pending.get(message.id);
      if (!task) return;
      pending.delete(message.id);
      if (message.error) task.reject(new Error(JSON.stringify(message.error)));
      else task.resolve(message.result || {});
    } else if (message.method) {
      const key = message.method + ':' + (message.sessionId || '');
      if (listeners.has(key)) listeners.get(key)();
    }
  });
  socket.addEventListener('close', () => failPending(new Error('Chromium disconnected')));
  await new Promise((ok, fail) => {
    const timer = setTimeout(() => fail(new Error('CDP socket open timeout')), 15000);
    socket.addEventListener('open', () => { clearTimeout(timer); ok(); }, { once: true });
    socket.addEventListener('error', () => { clearTimeout(timer); fail(new Error('CDP socket error')); }, { once: true });
  });
  const target = await command('Target.createTarget', { url: 'about:blank' });
  const attached = await command('Target.attachToTarget', { targetId: target.targetId, flatten: true });
  const session = attached.sessionId;
  await command('Page.enable', {}, session);
  await command('Runtime.enable', {}, session);
  await command('Network.enable', {}, session);
  await command('Network.setBlockedURLs', { urls: ['http://*', 'https://*'] }, session);
  const captures = [];
  for (const [version, filename] of pages) {
    const fileHash = createHash('sha256').update(readFileSync(filename)).digest('hex');
    for (const [label, width, height] of [
      ['desktop', 1440, 950], ['tablet', 768, 950], ['reflow-reference', 550, 950], ['mobile', 320, 800],
    ]) {
      await command('Emulation.setDeviceMetricsOverride', {
        width, height, deviceScaleFactor: 1, mobile: false, screenWidth: width, screenHeight: height,
      }, session);
      const load = waitEvent('Page.loadEventFired', session);
      const navigation = await command('Page.navigate', { url: pathToFileURL(filename).href }, session);
      if (navigation.errorText) throw new Error('Page navigate failed: ' + navigation.errorText);
      await load;
      const status = await command('Runtime.evaluate', {
        expression: 'JSON.stringify({ready:document.readyState, width:window.innerWidth, full:document.documentElement.scrollWidth, active:document.querySelectorAll("[data-operation][aria-pressed=true]").length})',
        returnByValue: true,
      }, session);
      const summary = JSON.parse(status.result.value);
      if (summary.ready !== 'complete' || summary.width !== width || summary.full > width || summary.active !== 1)
        throw new Error('Layout assertion failed at ' + version + ':' + label + ': ' + JSON.stringify(summary));
      const picture = await command('Page.captureScreenshot', {
        format: 'png', fromSurface: true, captureBeyondViewport: false,
      }, session);
      const bytes = Buffer.from(picture.data, 'base64');
      if (bytes.subarray(0,8).toString('hex') !== '89504e470d0a1a0a')
        throw new Error('Chromium did not return PNG image');
      const pngWidth = bytes.readUInt32BE(16), pngHeight = bytes.readUInt32BE(20);
      if (pngWidth !== width || pngHeight !== height)
        throw new Error('PNG dimensions differ from viewport: '+pngWidth+'x'+pngHeight);
      const outputFile = version+'-'+label+'-'+width+'px.png';
      writeFileSync(join(out, outputFile), bytes);
      captures.push({version, label, width, height, htmlSha256:fileHash, png:outputFile,
        pngSha256:createHash('sha256').update(bytes).digest('hex')});
      // Second screenshot: same fictional page, after selecting a restore
      // into an already-populated target. This performs NO file operation.
      const edited = await command('Runtime.evaluate', {
        expression: '(function(){document.querySelector("[data-operation=restore_complete]").click();document.querySelector("[data-target=populated]").click();return JSON.stringify({selected:document.querySelector("[data-operation=restore_complete]").getAttribute("aria-pressed"),count:document.getElementById("warning-count").textContent,blocked:document.getElementById("operation-status").textContent});})()',
        returnByValue: true,
      }, session);
      const post = JSON.parse(edited.result.value);
      if (post.selected !== 'true' || post.count !== '3'
          || !post.blocked.includes('nenhuma operação autorizada')) {
        throw new Error('Restoration warning state failed: '+JSON.stringify(post));
      }
      const warningShot = await command('Page.captureScreenshot', {
        format: 'png', fromSurface: true, captureBeyondViewport: false,
      }, session);
      const warningBytes = Buffer.from(warningShot.data, 'base64');
      if (warningBytes.subarray(0,8).toString('hex') !== '89504e470d0a1a0a'
          || warningBytes.readUInt32BE(16) !== width
          || warningBytes.readUInt32BE(20) !== height) {
        throw new Error('Restoration screenshot PNG mismatch');
      }
      const warningName = version+'-restore-conflict-'+label+'-'+width+'px.png';
      writeFileSync(join(out,warningName),warningBytes);
      captures.push({version, state:'restore-existing-records', label, width, height,
        htmlSha256:fileHash, png:warningName,
        pngSha256:createHash('sha256').update(warningBytes).digest('hex')});
    }
  }
  writeFileSync(join(out, 'manifest.json'), JSON.stringify({
    purpose: 'New Imagine CRM IN-118 offline UX: initial and restoration warning states, not before-after',
    captures, caveat: 'Automated browser evidence is not human UX review or production approval',
  }, null, 2) + '\n');
  console.log('IN-118 new offline screen screenshots captured in Chrome CDP: ' + captures.length + ' images');
} finally {
  if (socket) socket.close();
  chrome.kill('SIGTERM');
  await sleep(100);
  rmSync(profile, { recursive:true, force:true });
}
