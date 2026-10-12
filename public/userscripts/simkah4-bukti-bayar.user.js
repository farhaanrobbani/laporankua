// ==UserScript==
// @name         SIMKAH4 — Unduh Massal Bukti Bayar & Surat Tugas
// @namespace    laporan-kua
// @version      1.3.0
// @description  Mengunduh bukti bayar (no_billing) dan surat tugas manten (no_daftar) PDF secara massal dari SIMKAH4 memakai sesi login aktif. Daftar disalin dari aplikasi Laporan (tab Data → Bukti Bayar / Surat Tugas).
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

    var MODES = {
        bukti: {
            param: 'no_billing',
            uuidKey: 'sb-uuid-bukti',
            legacyKey: 'sb-uuid',
            listLabel: 'Daftar No Billing (satu per baris)',
            placeholder: '820260908847263 - Ahmad Fauzi\n820260908847264 - Siti Aminah',
            uuidPlaceholder: 'tempel URL bukti bayar / UUID',
            fileName: function (item) { return item.key + (item.nama ? ' - ' + item.nama : '') + '.pdf'; },
            countNoun: 'bukti bayar'
        },
        surat: {
            param: 'no_daftar',
            uuidKey: 'sb-uuid-surat',
            legacyKey: '',
            listLabel: 'Daftar Nomor Daftar (satu per baris)',
            placeholder: 'ND01003507061092026 - Budi dan Siti\nND01003507061092027 - Ahmad dan Aminah',
            uuidPlaceholder: 'tempel URL surat tugas / UUID',
            fileName: function (item) {
                var label = item.nama ? 'STM - ' + item.nama : 'STM - ' + item.key;
                return label + '.pdf';
            },
            countNoun: 'surat tugas'
        }
    };

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

            var dashIdx = line.indexOf(' - ');
            if (dashIdx !== -1) {
                var key = line.slice(0, dashIdx).trim();
                if (key && !seen[key]) {
                    seen[key] = true;
                    out.push({ key: key, nama: sanitizeNama(line.slice(dashIdx + 3)) });
                }
                return;
            }

            if (/^\d{8,}$/.test(line)) {
                if (!seen[line]) {
                    seen[line] = true;
                    out.push({ key: line, nama: '' });
                }
                return;
            }

            line.split(/[\s,;]+/).forEach(function (token) {
                if (/^\d{8,}$/.test(token) && !seen[token]) {
                    seen[token] = true;
                    out.push({ key: token, nama: '' });
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

    function modeOf() {
        var sel = document.getElementById('sb-mode');
        return MODES[sel && sel.value === 'surat' ? 'surat' : 'bukti'];
    }

    function uuidStorage(mode) {
        if (mode.legacyKey && !GM_getValue(mode.uuidKey, '') && GM_getValue(mode.legacyKey, '')) {
            GM_setValue(mode.uuidKey, GM_getValue(mode.legacyKey, ''));
        }
        return GM_getValue(mode.uuidKey, '');
    }

    function refreshPlaceholders() {
        var mode = modeOf();
        var uuidEl = document.getElementById('sb-uuid');
        var listEl = document.getElementById('sb-list');
        var listLabel = document.getElementById('sb-list-label');
        var title = document.getElementById('sb-title');
        if (title) title.textContent = 'Unduh Massal ' + (mode === MODES.surat ? 'Surat Tugas Manten' : 'Bukti Bayar');
        if (uuidEl) uuidEl.placeholder = mode.uuidPlaceholder;
        if (listEl) listEl.placeholder = mode.placeholder;
        if (listLabel) listLabel.textContent = mode.listLabel;
        if (uuidEl) uuidEl.value = uuidStorage(mode);
    }

    function panelHtml() {
        return ''
            + '<div id="sb-toggle" style="position:fixed;right:16px;bottom:16px;z-index:999999;background:#4f46e5;color:#fff;border:none;border-radius:9999px;padding:8px 14px;font-size:12px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.3);font-family:sans-serif;">Unduh Massal</div>'
            + '<div id="sb-panel" style="position:fixed;right:16px;bottom:56px;z-index:999999;width:360px;max-width:calc(100vw - 32px);background:#fff;color:#111;border:1px solid #bbb;border-radius:8px;padding:10px;box-shadow:0 4px 16px rgba(0,0,0,.3);font-family:sans-serif;display:none;">'
            +   '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
            +     '<strong id="sb-title" style="font-size:13px;">Unduh Massal Bukti Bayar</strong>'
            +     '<button id="sb-hide" title="Tutup panel" style="border:none;background:none;cursor:pointer;font-size:14px;line-height:1;">✕</button>'
            +   '</div>'
            +   '<label for="sb-mode" style="font-size:11px;display:block;margin-bottom:2px;">Jenis dokumen</label>'
            +   '<select id="sb-mode" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;">'
            +     '<option value="bukti">Bukti Bayar</option>'
            +     '<option value="surat">Surat Tugas Manten</option>'
            +   '</select>'
            +   '<label for="sb-uuid" style="font-size:11px;display:block;margin-bottom:2px;">UUID SIMKAH (sekali saja, tersimpan per jenis)</label>'
            +   '<input id="sb-uuid" type="text" placeholder="tempel URL bukti bayar / UUID" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" />'
            +   '<label id="sb-list-label" for="sb-list" style="font-size:11px;display:block;margin-bottom:2px;">Daftar No Billing (satu per baris)</label>'
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
        var mode = modeOf();
        var uuid = extractUuid(document.getElementById('sb-uuid').value) || uuidStorage(mode);
        var list = parseList(document.getElementById('sb-list').value);

        if (!uuid) {
            progress('UUID belum diisi — tempel URL dokumen dulu.');
            return;
        }
        if (!list.length) {
            progress('Daftar kosong — tempel daftar dulu.');
            return;
        }

        GM_setValue(mode.uuidKey, uuid);

        running = true;
        stopRequested = false;
        document.getElementById('sb-start').disabled = true;
        document.getElementById('sb-stop').disabled = false;
        log('Mulai unduh ' + list.length + ' ' + mode.countNoun + ' (UUID ' + uuid + ')', '=== ' + new Date().toLocaleTimeString() + ' ===');

        var ok = 0;
        var gagal = 0;

        for (var i = 0; i < list.length; i++) {
            if (stopRequested) {
                log('Dihentikan pada ' + (i + 1) + '/' + list.length + '. Sisa bisa ditempel ulang.', '');
                break;
            }

            var item = list[i];
            var key = item.key;
            var fileName = mode.fileName(item);
            progress('[' + (i + 1) + '/' + list.length + '] ' + key);
            var url = BASE + '/' + uuid + '?' + mode.param + '=' + encodeURIComponent(key);

            try {
                var res = await fetch(url, { credentials: 'include', redirect: 'follow' });
                if (!res.ok) {
                    gagal++;
                    log('Gagal ' + key + ' — HTTP ' + res.status, '');
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
                log('Gagal ' + key + ' — ' + err, '');
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
        style.textContent = '#sb-panel input,#sb-panel textarea,#sb-panel select,#sb-panel button{font-family:inherit;}#sb-panel button{border:1px solid #ccc;border-radius:4px;background:#f8f8f8;}#sb-panel button:disabled{opacity:.5;cursor:default;}#sb-start{background:#4f46e5 !important;color:#fff !important;border-color:#4f46e5 !important;}';
        document.head.appendChild(style);

        var holder = document.createElement('div');
        holder.innerHTML = panelHtml();
        while (holder.firstChild) document.body.appendChild(holder.firstChild);

        refreshPlaceholders();

        document.getElementById('sb-mode').addEventListener('change', refreshPlaceholders);
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

        if (!uuidStorage(MODES.bukti) && !uuidStorage(MODES.surat)) {
            document.getElementById('sb-panel').style.display = 'block';
        }
    }

    mount();
})();
