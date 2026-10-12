// ==UserScript==
// @name         SIMKAH4 — Unduh Massal Bukti Bayar
// @namespace    laporan-kua
// @version      1.2.0
// @description  Mengunduh bukti bayar (PDF) secara massal dari SIMKAH4 memakai sesi login aktif. Daftar no billing disalin dari aplikasi Laporan (tab Data → Bukti Bayar).
// @match        https://simkah4.kemenag.go.id/*
// @grant        GM_setValue
// @grant        GM_getValue
// @run-at       document-idle
// ==/UserScript==

(function () {
    'use strict';

    var BASE = 'https://simkah4.kemenag.go.id';
    var DELAY_MS = 1200;
    var running = false;
    var stopRequested = false;

    var UUID_RE = /[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i;

    function extractUuid(value) {
        value = (value || '').trim();
        if (!value) return '';
        var m = value.match(UUID_RE);
        return m ? m[0] : '';
    }

    function sanitizeNama(nama) {
        return (nama || '')
            .replace(/[\\/:*?"<>|]/g, ' ')
            // eslint-disable-next-line no-control-regex
            .replace(/[\x00-\x1f\x7f]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, 80);
    }

    function parseList(raw) {
        var out = [];
        var seen = {};
        (raw || '').split(/\r?\n/).forEach(function (line) {
            line = line.trim();
            if (!line) return;

            var m = line.match(/^(\d{8,})\s*(?:-\s*(.*))?$/);
            if (m) {
                var nb = m[1];
                if (seen[nb]) return;
                seen[nb] = true;
                out.push({ no_billing: nb, nama: sanitizeNama(m[2] || '') });
                return;
            }

            line.split(/[\s,;]+/).forEach(function (token) {
                if (/^\d{8,}$/.test(token) && !seen[token]) {
                    seen[token] = true;
                    out.push({ no_billing: token, nama: '' });
                }
            });
        });
        return out;
    }

    function log(msg, line) {
        var el = document.getElementById('sb-log');
        if (!el) return;
        el.textContent += line + '\n';
        el.scrollTop = el.scrollHeight;
    }

    function progress(msg) {
        var el = document.getElementById('sb-progress');
        if (el) el.textContent = msg;
    }

    function downloadBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
    }

    function panelHtml() {
        return ''
            + '<div id="sb-toggle" style="position:fixed;right:16px;bottom:16px;z-index:999999;background:#4f46e5;color:#fff;border:none;border-radius:9999px;padding:8px 14px;font-size:12px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.3);font-family:sans-serif;">Unduh Massal</div>'
            + '<div id="sb-panel" style="position:fixed;right:16px;bottom:56px;z-index:999999;width:360px;max-width:calc(100vw - 32px);background:#fff;color:#111;border:1px solid #bbb;border-radius:8px;padding:10px;box-shadow:0 4px 16px rgba(0,0,0,.3);font-family:sans-serif;display:none;">'
            +   '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
            +     '<strong style="font-size:13px;">Unduh Massal Bukti Bayar</strong>'
            +     '<button id="sb-hide" title="Tutup panel" style="border:none;background:none;cursor:pointer;font-size:14px;line-height:1;">✕</button>'
            +   '</div>'
            +   '<label for="sb-uuid" style="font-size:11px;display:block;margin-bottom:2px;">UUID SIMKAH (sekali saja)</label>'
            +   '<input id="sb-uuid" type="text" placeholder="tempel URL bukti bayar / UUID" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" />'
            +   '<label for="sb-list" style="font-size:11px;display:block;margin-bottom:2px;">Daftar No Billing (satu per baris)</label>'
            +   '<textarea id="sb-list" rows="6" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" placeholder="820260908847263 - Ahmad Fauzi\n820260908847264 - Siti Aminah"></textarea>'
            +   '<div style="display:flex;gap:6px;margin-bottom:6px;">'
            +     '<button id="sb-start" style="flex:1;padding:5px 8px;font-size:12px;cursor:pointer;">Mulai</button>'
            +     '<button id="sb-stop" disabled style="flex:1;padding:5px 8px;font-size:12px;cursor:pointer;">Berhenti</button>'
            +   '</div>'
            +   '<div id="sb-progress" style="font-size:11px;margin-bottom:4px;"></div>'
            +   '<pre id="sb-log" style="max-height:120px;overflow:auto;font-size:10px;margin:0;white-space:pre-wrap;"></pre>'
            + '</div>';
    }

    async function run() {
        var uuid = extractUuid(document.getElementById('sb-uuid').value) || GM_getValue('sb-uuid', '');
        var list = parseList(document.getElementById('sb-list').value);

        if (!uuid) {
            progress('UUID belum diisi — tempel URL bukti bayar dulu.');
            return;
        }
        if (!list.length) {
            progress('Daftar kosong — tempel no billing dulu.');
            return;
        }

        GM_setValue('sb-uuid', uuid);

        running = true;
        stopRequested = false;
        document.getElementById('sb-start').disabled = true;
        document.getElementById('sb-stop').disabled = false;
        log('Mulai unduh ' + list.length + ' bukti bayar (UUID ' + uuid + ')', '=== ' + new Date().toLocaleTimeString() + ' ===');

        var ok = 0;
        var gagal = 0;

        for (var i = 0; i < list.length; i++) {
            if (stopRequested) {
                log('Dihentikan pada ' + (i + 1) + '/' + list.length + '. Sisa bisa ditempel ulang.', '');
                break;
            }

            var item = list[i];
            var nb = item.no_billing;
            var fileName = nb + (item.nama ? ' - ' + item.nama : '') + '.pdf';
            progress('[' + (i + 1) + '/' + list.length + '] ' + nb);
            var url = BASE + '/' + uuid + '?no_billing=' + encodeURIComponent(nb);

            try {
                var res = await fetch(url, { credentials: 'include', redirect: 'follow' });
                if (!res.ok) {
                    gagal++;
                    log('Gagal ' + nb + ' — HTTP ' + res.status, '');
                    continue;
                }

                var ct = (res.headers.get('content-type') || '').toLowerCase();
                if (ct.indexOf('html') !== -1 || ct.indexOf('json') !== -1) {
                    log('Berhenti: respons bukan PDF (' + ct + '). Sesi login berakhir? Login ulang SIMKAH4 lalu jalankan lagi.', '');
                    running = false;
                    document.getElementById('sb-start').disabled = false;
                    document.getElementById('sb-stop').disabled = true;
                    progress('Berhenti — sesi login bermasalah.');
                    return;
                }

                var blob = await res.blob();
                downloadBlob(blob, fileName);
                ok++;
                log('OK ' + fileName, '');
            } catch (err) {
                gagal++;
                log('Gagal ' + nb + ' — ' + err, '');
            }

            await new Promise(function (r) { setTimeout(r, DELAY_MS); });
        }

        progress('Selesai: ' + ok + ' berhasil, ' + gagal + ' gagal.');
        log('Selesai: ' + ok + ' berhasil, ' + gagal + ' gagal.', '');
        running = false;
        document.getElementById('sb-start').disabled = false;
        document.getElementById('sb-stop').disabled = true;
    }

    function mount() {
        if (document.getElementById('sb-panel')) return;

        var style = document.createElement('style');
        style.textContent = '#sb-panel input,#sb-panel textarea,#sb-panel button{font-family:inherit;}#sb-panel button{border:1px solid #ccc;border-radius:4px;background:#f8f8f8;}#sb-panel button:disabled{opacity:.5;cursor:default;}#sb-start{background:#4f46e5 !important;color:#fff !important;border-color:#4f46e5 !important;}';
        document.head.appendChild(style);

        var holder = document.createElement('div');
        holder.innerHTML = panelHtml();
        while (holder.firstChild) document.body.appendChild(holder.firstChild);

        document.getElementById('sb-uuid').value = GM_getValue('sb-uuid', '');
        document.getElementById('sb-toggle').addEventListener('click', function () {
            var p = document.getElementById('sb-panel');
            p.style.display = p.style.display === 'block' ? 'none' : 'block';
        });
        document.getElementById('sb-hide').addEventListener('click', function () {
            document.getElementById('sb-panel').style.display = 'none';
        });
        document.getElementById('sb-start').addEventListener('click', function () {
            if (!running) run();
        });
        document.getElementById('sb-stop').addEventListener('click', function () {
            stopRequested = true;
        });

        if (!GM_getValue('sb-uuid', '')) {
            document.getElementById('sb-panel').style.display = 'block';
        }
    }

    mount();
})();
