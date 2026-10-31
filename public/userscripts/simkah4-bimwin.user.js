// ==UserScript==
// @name         SIMKAH4 — Isi BIMWIN Otomatis
// @namespace    laporan-kua
// @version      1.1.1
// @description  Membantu mengecek dan mengisi BIMWIN di SIMKAH4 memakai sesi login aktif. Endpoint direkam per akun dari aplikasi Laporan.
// @match        https://simkah4.kemenag.go.id/*
// @grant        GM_getValue
// @grant        GM_setValue
// @grant        unsafeWindow
// @run-at       document-idle
// @noframes
// ==/UserScript==

(function () {
    'use strict';

    var pageWindow = typeof unsafeWindow !== 'undefined' ? unsafeWindow : window;
    var ENDPOINT_KEYS = ['cek', 'dialog', 'simpan', 'selesai'];
    var STORAGE_KEY = 'laporan-bimwin-config';

    function emptyEndpoints() {
        return { cek: '', dialog: '', simpan: '', selesai: '' };
    }

    function normalizeEndpoints(value) {
        var endpoints = emptyEndpoints();
        if (!value || typeof value !== 'object' || Array.isArray(value)) return endpoints;
        ENDPOINT_KEYS.forEach(function (key) {
            endpoints[key] = typeof value[key] === 'string' ? value[key] : '';
        });
        return endpoints;
    }

    function endpointsComplete(endpoints) {
        return ENDPOINT_KEYS.every(function (key) {
            return typeof endpoints[key] === 'string' && endpoints[key] !== '';
        });
    }

    var DEFAULTS = {
        appUrl: '',
        endpoints: emptyEndpoints(),
        provinsi: '',
        kabkota: '',
        kecamatan: '',
        fasilitatorSuami: '',
        fasilitatorIstri: '',
        maxCoba: 10,
        redamNotif: false
    };

    var running = false;
    var stopRequested = false;
    var recording = false;
    var recordedEndpoints = emptyEndpoints();
    var recordedAppUrl = '';
    var recordingPageWindow = null;
    var recordingFetch = null;
    var recordingXhrOpen = null;
    var recordingXhrSend = null;

    function sleep(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function norm(value) {
        return String(value || '').toUpperCase().replace(/\s+/g, ' ').trim();
    }

    function clampInt(value, min, max, fallback) {
        var number = parseInt(value, 10);
        if (isNaN(number)) return fallback;
        return Math.min(max, Math.max(min, number));
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function loadStoredConfig() {
        var stored = null;
        try {
            if (typeof GM_getValue === 'function') stored = GM_getValue(STORAGE_KEY, null);
        } catch (e) {
            stored = null;
        }
        if (!stored) {
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                stored = raw ? JSON.parse(raw) : null;
            } catch (e) {
                stored = null;
            }
        }
        if (typeof stored === 'string') {
            try {
                stored = JSON.parse(stored);
            } catch (e) {
                stored = null;
            }
        }
        var merged = Object.assign({}, DEFAULTS, stored || {});
        merged.endpoints = normalizeEndpoints(merged.endpoints);
        return merged;
    }

    function storeConfig(config) {
        try {
            if (typeof GM_setValue === 'function') GM_setValue(STORAGE_KEY, config);
        } catch (e) {}
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(config));
        } catch (e) {}
    }

    function panelElement(id) {
        return document.getElementById(id);
    }

    function log(message, type) {
        var line = '[' + new Date().toLocaleTimeString() + '] ' + message;
        console.log('[BIMWIN] ' + message);
        var el = panelElement('bw-log');
        if (!el) return;
        var lines = el.textContent ? el.textContent.split('\n') : [];
        lines.push(line);
        if (lines.length > 300) lines = lines.slice(-300);
        el.textContent = lines.join('\n');
        el.scrollTop = el.scrollHeight;
        if (type === 'err') console.error('[BIMWIN] ' + message);
        if (type === 'warn') console.warn('[BIMWIN] ' + message);
    }

    function progress(message) {
        var el = panelElement('bw-progress');
        if (el) el.textContent = message || '';
    }

    function readConfigFromPanel() {
        return {
            appUrl: recordedAppUrl,
            endpoints: normalizeEndpoints(recordedEndpoints),
            provinsi: (panelElement('bw-provinsi')?.value || '').trim(),
            kabkota: (panelElement('bw-kabkota')?.value || '').trim(),
            kecamatan: (panelElement('bw-kecamatan')?.value || '').trim(),
            fasilitatorSuami: (panelElement('bw-fas-suami')?.value || '').trim(),
            fasilitatorIstri: (panelElement('bw-fas-istri')?.value || '').trim(),
            maxCoba: clampInt(panelElement('bw-max-coba')?.value, 1, 30, 10),
            redamNotif: !!panelElement('bw-redam')?.checked
        };
    }

    function writeConfigToPanel(config) {
        var merged = Object.assign({}, DEFAULTS, config || {});
        merged.endpoints = normalizeEndpoints(merged.endpoints);
        recordedAppUrl = typeof merged.appUrl === 'string' ? merged.appUrl.replace(/\/+$/, '') : '';
        recordedEndpoints = merged.endpoints;
        renderEndpointStatus();
        if (panelElement('bw-provinsi')) panelElement('bw-provinsi').value = merged.provinsi || '';
        if (panelElement('bw-kabkota')) panelElement('bw-kabkota').value = merged.kabkota || '';
        if (panelElement('bw-kecamatan')) panelElement('bw-kecamatan').value = merged.kecamatan || '';
        if (panelElement('bw-fas-suami')) panelElement('bw-fas-suami').value = merged.fasilitatorSuami || '';
        if (panelElement('bw-fas-istri')) panelElement('bw-fas-istri').value = merged.fasilitatorIstri || '';
        if (panelElement('bw-max-coba')) panelElement('bw-max-coba').value = clampInt(merged.maxCoba, 1, 30, 10);
        if (panelElement('bw-redam')) panelElement('bw-redam').checked = !!merged.redamNotif;
    }

    function renderEndpointStatus() {
        var count = ENDPOINT_KEYS.filter(function (key) {
            return recordedEndpoints[key] !== '';
        }).length;
        var status = panelElement('bw-endpoint-status');
        if (status) {
            status.textContent = count + '/4';
            status.style.color = count === 4 ? '#047857' : '#b45309';
        }
        var list = panelElement('bw-endpoint-list');
        if (list) {
            list.textContent = ENDPOINT_KEYS.map(function (key) {
                return key + ': ' + (recordedEndpoints[key] ? 'OK' : '—');
            }).join(' · ');
        }
        var complete = endpointsComplete(recordedEndpoints);
        ['bw-send-endpoints', 'bw-copy-endpoints'].forEach(function (id) {
            var button = panelElement(id);
            if (button) button.disabled = !complete;
        });
        var recordButton = panelElement('bw-record');
        if (recordButton) recordButton.textContent = recording ? 'Stop rekam' : 'Rekam endpoint';
    }

    function cleanEndpoint(url) {
        try {
            var parsed = new URL(url, pageWindow.location.href);
            if (parsed.protocol !== 'https:' || parsed.hostname !== 'simkah4.kemenag.go.id') return '';
            if (parsed.pathname === '' || parsed.pathname === '/') return '';
            parsed.search = '';
            parsed.hash = '';
            return parsed.href;
        } catch (e) {
            return '';
        }
    }

    function bodyToText(body) {
        if (!body) return '';
        if (typeof body === 'string') return body;

        if (typeof body.forEach === 'function' && typeof body.append === 'function') {
            var pairs = [];
            body.forEach(function (value, key) {
                if (typeof value === 'string') {
                    pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            });
            return pairs.join('&');
        }

        if (typeof body.toString === 'function') {
            var text = body.toString();
            if (text && text !== '[object Object]' && text !== '[object FormData]' && text !== '[object URLSearchParams]') {
                return text;
            }
        }

        return '';
    }

    function classifyRequest(method, url, body) {
        var verb = String(method || 'GET').toUpperCase();
        var text = bodyToText(body);

        if (verb === 'POST') {
            if (text.includes('value=SS_ALL')) return 'selesai';
            if (text.includes('catin=') && text.includes('no_daftar=')) return 'cek';
            if (/id_(provinsi|kabkota|kecamatan|jadwal)_(suami|istri)=/.test(text)) return 'simpan';
            return null;
        }

        if (verb === 'GET') {
            try {
                var parsed = new URL(url, pageWindow.location.href);
                if (parsed.searchParams.has('no_daftar')) return 'dialog';
            } catch (e) {}
        }

        return null;
    }

    function recordRequest(method, url, body) {
        if (!recording) return;

        var key = classifyRequest(method, url, body);
        if (!key) return;

        var endpoint = cleanEndpoint(url);
        if (!endpoint || recordedEndpoints[key] === endpoint) return;

        recordedEndpoints[key] = endpoint;
        renderEndpointStatus();
        storeConfig(readConfigFromPanel());
        log('Endpoint ' + key + ' terekam.', 'ok');

        if (endpointsComplete(recordedEndpoints)) {
            stopRecording(true);
            log('4/4 endpoint lengkap. Klik Kirim ke Laporan.', 'ok');
        }
    }

    function installRecorder() {
        recordingPageWindow = pageWindow;
        var installed = false;

        try {
            if (typeof recordingPageWindow.fetch === 'function') {
                recordingFetch = recordingPageWindow.fetch;
                recordingPageWindow.fetch = function (input, init) {
                    var originalFetch = recordingFetch;
                    try {
                        var url = typeof input === 'string' ? input : (input && input.url);
                        var method = (init && init.method) || (input && input.method) || 'GET';
                        var body = init && init.body;
                        recordRequest(method, url, body);
                    } catch (e) {}
                    return originalFetch.apply(this, arguments);
                };
                installed = true;
            }
        } catch (e) {
            recordingFetch = null;
            log('Gagal memasang perekam fetch halaman: ' + e.message, 'err');
        }

        try {
            var Xhr = recordingPageWindow.XMLHttpRequest;
            if (Xhr && Xhr.prototype) {
                recordingXhrOpen = Xhr.prototype.open;
                recordingXhrSend = Xhr.prototype.send;
                Xhr.prototype.open = function (method, url) {
                    var originalOpen = recordingXhrOpen;
                    this.__bwRequest = { method: method, url: url };
                    return originalOpen.apply(this, arguments);
                };
                Xhr.prototype.send = function (body) {
                    var originalSend = recordingXhrSend;
                    try {
                        if (this.__bwRequest) {
                            recordRequest(this.__bwRequest.method, this.__bwRequest.url, body);
                        }
                    } catch (e) {}
                    return originalSend.apply(this, arguments);
                };
                installed = true;
            }
        } catch (e) {
            recordingXhrOpen = null;
            recordingXhrSend = null;
            log('Gagal memasang perekam XHR halaman: ' + e.message, 'err');
        }

        if (!installed) {
            recordingPageWindow = null;
        }

        return installed;
    }

    function stopRecording(silent) {
        if (!recording) {
            renderEndpointStatus();
            return;
        }

        recording = false;
        if (recordingPageWindow && recordingFetch) {
            try {
                recordingPageWindow.fetch = recordingFetch;
            } catch (e) {}
            recordingFetch = null;
        }
        if (recordingPageWindow && recordingXhrOpen) {
            try {
                recordingPageWindow.XMLHttpRequest.prototype.open = recordingXhrOpen;
            } catch (e) {}
            recordingXhrOpen = null;
        }
        if (recordingPageWindow && recordingXhrSend) {
            try {
                recordingPageWindow.XMLHttpRequest.prototype.send = recordingXhrSend;
            } catch (e) {}
            recordingXhrSend = null;
        }
        recordingPageWindow = null;
        renderEndpointStatus();
        if (!silent) log('Perekaman endpoint dihentikan.', 'info');
    }

    function startRecording() {
        if (recording) {
            stopRecording(false);
            return;
        }
        if (running) {
            log('Hentikan proses BIMWIN dulu sebelum merekam endpoint.', 'err');
            return;
        }

        var hasAny = ENDPOINT_KEYS.some(function (key) {
            return recordedEndpoints[key] !== '';
        });
        if (hasAny && !window.confirm('Kosongkan endpoint terekam dan mulai rekam ulang?')) {
            return;
        }

        if (typeof unsafeWindow === 'undefined') {
            log('unsafeWindow tidak tersedia; perekaman mungkin tidak menangkap request halaman.', 'warn');
        }

        recordedEndpoints = emptyEndpoints();
        recording = true;
        storeConfig(readConfigFromPanel());

        if (!installRecorder()) {
            recording = false;
            renderEndpointStatus();
            log('Perekam endpoint gagal dipasang. Isi endpoint manual di halaman Laporan.', 'err');
            return;
        }

        renderEndpointStatus();
        log('Perekam endpoint terpasang. Lakukan satu alur BIMWIN manual sampai 4/4.', 'info');
    }

    function copyText(text) {
        return new Promise(function (resolve) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function () {
                    resolve(true);
                }, function () {
                    resolve(false);
                });
                return;
            }

            var area = document.createElement('textarea');
            area.value = text;
            area.style.position = 'fixed';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {}
            document.body.removeChild(area);
            resolve(ok);
        });
    }

    async function sendEndpointsToLaporan() {
        if (!endpointsComplete(recordedEndpoints)) {
            log('Endpoint belum lengkap. Rekam sampai 4/4.', 'err');
            return;
        }
        if (!recordedAppUrl) {
            log('appUrl belum ada. Muat kode konfigurasi dari Laporan.', 'err');
            return;
        }

        var url = recordedAppUrl + '/bimwin#endpoints=' + encodeURIComponent(JSON.stringify(recordedEndpoints));
        var opened = null;
        try {
            opened = pageWindow.open(url, '_blank');
        } catch (e) {
            log('Gagal membuka halaman Laporan: ' + e.message, 'err');
            return;
        }
        if (!opened) {
            log('Popup diblokir browser. Izinkan popup lalu coba lagi.', 'warn');
            return;
        }
        try {
            opened.opener = null;
        } catch (e) {}
        log('Membuka halaman Laporan untuk menyimpan endpoint.', 'ok');
    }

    async function copyEndpoints() {
        if (!endpointsComplete(recordedEndpoints)) {
            log('Endpoint belum lengkap. Rekam sampai 4/4.', 'err');
            return;
        }
        var ok = await copyText(JSON.stringify(recordedEndpoints, null, 2));
        log(ok ? 'JSON endpoint tersalin.' : 'Gagal menyalin JSON endpoint.', ok ? 'ok' : 'err');
    }

    function panelHtml() {
        return ''
            + '<div id="bw-toggle" style="position:fixed;right:16px;bottom:16px;z-index:999999;background:#4f46e5;color:#fff;border:none;border-radius:9999px;padding:8px 14px;font-size:12px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.3);font-family:sans-serif;">BIMWIN</div>'
            + '<div id="bw-panel" style="display:none;position:fixed;right:16px;bottom:56px;z-index:999999;width:380px;max-width:calc(100vw - 32px);max-height:calc(100vh - 80px);overflow:auto;background:#fff;color:#111;border:1px solid #bbb;border-radius:8px;padding:10px;box-shadow:0 4px 16px rgba(0,0,0,.3);font-family:sans-serif;">'
            +   '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">'
            +     '<strong style="font-size:13px;">BIMWIN — SIMKAH4</strong>'
            +     '<button id="bw-hide" title="Tutup panel" style="border:none;background:none;cursor:pointer;font-size:14px;line-height:1;">✕</button>'
            +   '</div>'
            +   '<p style="font-size:10px;color:#555;margin:0 0 6px;">Memproses baris grid yang sedang tampil. Mode Cek saja tidak menulis data.</p>'
            +   '<label for="bw-mode" style="font-size:11px;display:block;margin-bottom:2px;">Mode</label>'
            +   '<select id="bw-mode" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;">'
            +     '<option value="cek">Cek saja (tidak menulis)</option>'
            +     '<option value="isi">Isi jadwal (tanpa tandai selesai)</option>'
            +     '<option value="lengkap">Isi + tandai selesai</option>'
            +   '</select>'
            +   '<label for="bw-provinsi" style="font-size:11px;display:block;margin-bottom:2px;">Provinsi</label>'
            +   '<input id="bw-provinsi" type="text" placeholder="JAWA TIMUR" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" />'
            +   '<label for="bw-kabkota" style="font-size:11px;display:block;margin-bottom:2px;">Kabupaten/Kota</label>'
            +   '<input id="bw-kabkota" type="text" placeholder="KAB. PASURUAN" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" />'
            +   '<label for="bw-kecamatan" style="font-size:11px;display:block;margin-bottom:2px;">Kecamatan</label>'
            +   '<input id="bw-kecamatan" type="text" placeholder="PASREPAN" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" />'
            +   '<div style="display:flex;gap:6px;">'
            +     '<label style="flex:1;font-size:11px;display:block;margin-bottom:2px;">Fasilitator suami<input id="bw-fas-suami" type="text" placeholder="opsional" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" /></label>'
            +     '<label style="flex:1;font-size:11px;display:block;margin-bottom:2px;">Fasilitator istri<input id="bw-fas-istri" type="text" placeholder="opsional" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" /></label>'
            +   '</div>'
            +   '<div style="display:flex;gap:6px;align-items:end;">'
            +     '<label style="flex:1;font-size:11px;display:block;margin-bottom:2px;">Max percobaan<input id="bw-max-coba" type="number" min="1" max="30" value="10" style="width:100%;box-sizing:border-box;margin-bottom:6px;font-size:12px;padding:4px;" /></label>'
            +     '<label style="display:flex;align-items:center;gap:4px;font-size:11px;margin-bottom:6px;"><input id="bw-redam" type="checkbox" /> Redam notifikasi</label>'
            +   '</div>'
            +   '<details style="margin:6px 0;">'
            +     '<summary style="font-size:11px;cursor:pointer;">Kode konfigurasi dari Laporan</summary>'
            +     '<textarea id="bw-config-code" rows="4" placeholder=\'{"provinsi":"JAWA TIMUR","kabkota":"KAB. PASURUAN","kecamatan":"PASREPAN"}\' style="width:100%;box-sizing:border-box;margin-top:4px;font-size:11px;padding:4px;"></textarea>'
            +     '<button id="bw-load-code" style="margin-top:4px;padding:4px 8px;font-size:11px;cursor:pointer;">Muat</button>'
            +   '</details>'
            +   '<div style="border:1px dashed #4f46e5;border-radius:4px;padding:6px;margin:6px 0;">'
            +     '<div style="display:flex;justify-content:space-between;align-items:center;">'
            +       '<strong style="font-size:11px;">Endpoint akun ini</strong>'
            +       '<span id="bw-endpoint-status" style="font-size:11px;font-weight:bold;color:#b45309;">0/4</span>'
            +     '</div>'
            +     '<div id="bw-endpoint-list" style="font-size:10px;color:#555;margin-top:2px;">cek: — · dialog: — · simpan: — · selesai: —</div>'
            +     '<div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px;">'
            +       '<button id="bw-record" style="padding:3px 6px;font-size:10px;cursor:pointer;">Rekam endpoint</button>'
            +       '<button id="bw-send-endpoints" disabled style="padding:3px 6px;font-size:10px;cursor:pointer;">Kirim ke Laporan</button>'
            +       '<button id="bw-copy-endpoints" disabled style="padding:3px 6px;font-size:10px;cursor:pointer;">Salin JSON</button>'
            +     '</div>'
            +     '<p style="font-size:10px;color:#555;margin:4px 0 0;">Rekam dengan satu alur BIMWIN manual. Simpan/selesai menulis ke SIMKAH4.</p>'
            +   '</div>'
            +   '<div style="display:flex;gap:6px;margin:6px 0;">'
            +     '<button id="bw-start" style="flex:1;padding:6px 8px;font-size:12px;cursor:pointer;background:#4f46e5;color:#fff;border:none;border-radius:4px;">Mulai</button>'
            +     '<button id="bw-stop" disabled style="flex:1;padding:6px 8px;font-size:12px;cursor:pointer;">Berhenti</button>'
            +     '<button id="bw-save" style="padding:6px 8px;font-size:12px;cursor:pointer;">Simpan</button>'
            +   '</div>'
            +   '<div id="bw-progress" style="font-size:11px;margin-bottom:4px;"></div>'
            +   '<pre id="bw-log" style="max-height:150px;overflow:auto;font-size:10px;margin:0;white-space:pre-wrap;background:#f7f7f7;padding:4px;border-radius:4px;"></pre>'
            +   '<button id="bw-clear-log" style="margin-top:4px;padding:3px 6px;font-size:10px;cursor:pointer;">Bersihkan log</button>'
            + '</div>';
    }

    function initPanel() {
        if (panelElement('bw-toggle')) return;
        var host = document.createElement('div');
        host.innerHTML = panelHtml();
        document.body.appendChild(host);

        panelElement('bw-toggle').addEventListener('click', function () {
            var panel = panelElement('bw-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });

        panelElement('bw-hide').addEventListener('click', function () {
            panelElement('bw-panel').style.display = 'none';
        });

        panelElement('bw-start').addEventListener('click', run);
        panelElement('bw-stop').addEventListener('click', function () {
            stopRequested = true;
            log('Permintaan berhenti diterima. Proses berjalan akan diselesaikan lebih dulu.', 'warn');
        });
        panelElement('bw-save').addEventListener('click', function () {
            storeConfig(readConfigFromPanel());
            log('Konfigurasi disimpan di browser.', 'ok');
        });
        panelElement('bw-load-code').addEventListener('click', function () {
            var raw = (panelElement('bw-config-code')?.value || '').trim();
            if (!raw) {
                log('Kode konfigurasi masih kosong.', 'warn');
                return;
            }
            try {
                var parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                    throw new Error('Format harus objek JSON.');
                }
                writeConfigToPanel(parsed);
                storeConfig(readConfigFromPanel());
                log('Konfigurasi berhasil dimuat.', 'ok');
            } catch (e) {
                log('Kode konfigurasi tidak valid: ' + e.message, 'err');
            }
        });
        panelElement('bw-clear-log').addEventListener('click', function () {
            panelElement('bw-log').textContent = '';
        });
        panelElement('bw-record').addEventListener('click', startRecording);
        panelElement('bw-send-endpoints').addEventListener('click', sendEndpointsToLaporan);
        panelElement('bw-copy-endpoints').addEventListener('click', copyEndpoints);

        writeConfigToPanel(loadStoredConfig());
    }

    function requestHeaders() {
        var csrf = csrfToken();
        if (!csrf) throw new Error('CSRF token SIMKAH4 tidak ditemukan. Muat ulang halaman.');
        return {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    async function fetchJson(url, body) {
        var response = await fetch(url, {
            method: 'POST',
            credentials: 'include',
            headers: requestHeaders(),
            body: body.toString()
        });

        if (response.redirected && /login/i.test(response.url)) {
            throw new Error('Sesi SIMKAH4 berakhir. Login ulang lalu jalankan lagi.');
        }
        if (!response.ok) {
            throw new Error('SIMKAH4 membalas HTTP ' + response.status + '.');
        }

        var text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Respons SIMKAH4 bukan JSON. Sesi login mungkin berakhir.');
        }
    }

    async function cekSatuCatin(noDaftar, catin, endpoints) {
        var body = new URLSearchParams();
        body.append('no_daftar', noDaftar);
        body.append('catin', catin);
        var result = await fetchJson(endpoints.cek, body);
        return !!result.success;
    }

    async function cekBelum(endpoints) {
        if (!csrfToken()) throw new Error('CSRF token SIMKAH4 tidak ditemukan. Muat ulang halaman.');

        var grid = document.querySelector('#grid, #gridPencatatan');
        if (!grid) throw new Error('Grid SIMKAH4 tidak ditemukan. Buka halaman daftar nikah dulu.');

        var rows = grid.querySelectorAll('.dx-data-row');
        if (!rows.length) throw new Error('Tidak ada baris data pada grid SIMKAH4.');

        var hasil = [];
        var errors = [];
        var sudahDicek = {};

        for (var i = 0; i < rows.length; i++) {
            if (stopRequested) {
                log('Pengecekan dihentikan oleh pengguna.', 'warn');
                break;
            }

            var row = rows[i];
            var noUrut = row.getAttribute('aria-rowindex') || '?';
            var noDaftar = '';
            var indexes = ['4', '5'];
            for (var j = 0; j < indexes.length; j++) {
                var cell = row.querySelector('[aria-colindex="' + indexes[j] + '"]');
                if (cell && cell.innerText.trim().startsWith('ND')) {
                    noDaftar = cell.innerText.trim();
                    break;
                }
            }
            if (!noDaftar || sudahDicek[noDaftar]) continue;
            sudahDicek[noDaftar] = true;

            var tglEl = row.querySelector('[aria-colindex="11"]');
            var tglAkad = tglEl ? tglEl.innerText.trim() : '-';

            try {
                var status = {
                    SUAMI: await cekSatuCatin(noDaftar, 'SUAMI', endpoints),
                    ISTRI: await cekSatuCatin(noDaftar, 'ISTRI', endpoints)
                };
                var belum = [];
                if (!status.SUAMI) belum.push('suami');
                if (!status.ISTRI) belum.push('istri');

                if (belum.length) {
                    log('BELUM: [No. ' + noUrut + '] ' + tglAkad + ' | ' + noDaftar + ' (' + belum.join(', ') + ')', 'warn');
                    hasil.push({ noDaftar: noDaftar, noUrut: noUrut, tglAkad: tglAkad, belum: belum });
                } else {
                    log('AMAN: [No. ' + noUrut + '] ' + tglAkad + ' | ' + noDaftar, 'ok');
                }
            } catch (e) {
                errors.push({ noDaftar: noDaftar, message: e.message });
                log('Gagal cek: [No. ' + noUrut + '] ' + noDaftar + ' — ' + e.message, 'err');
            }
        }

        return { belum: hasil, errors: errors };
    }

    function jqueryGet(url, data) {
        return new Promise(function (resolve, reject) {
            window.jQuery.get(url, data).done(resolve).fail(function (xhr) {
                reject(new Error('Gagal memuat dialog (HTTP ' + (xhr && xhr.status) + ').'));
            });
        });
    }

    function jqueryPost(url, data) {
        return new Promise(function (resolve, reject) {
            window.jQuery.ajax({
                type: 'POST',
                url: url,
                data: data,
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(resolve).fail(function (xhr) {
                reject(new Error('Gagal menyimpan status selesai (HTTP ' + (xhr && xhr.status) + ').'));
            });
        });
    }

    function jqueryAjaxForm(formElement, endpoint) {
        var formData = new FormData(formElement);
        return new Promise(function (resolve, reject) {
            window.jQuery.ajax({
                type: 'POST',
                url: endpoint,
                enctype: 'multipart/form-data',
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(resolve).fail(function (xhr) {
                reject(new Error('Gagal menyimpan BIMWIN (HTTP ' + (xhr && xhr.status) + ').'));
            });
        });
    }

    async function bukaDialog(noDaftar, endpoint) {
        if (!window.jQuery) throw new Error('jQuery SIMKAH4 tidak tersedia. Muat ulang halaman.');

        window.jQuery('#bimwin-auto-wrap').remove();
        if (window.jQuery('#bimwin').length) {
            throw new Error('Dialog BIMWIN lain masih terbuka. Tutup dulu lalu jalankan lagi.');
        }

        var html = await jqueryGet(endpoint, { no_daftar: noDaftar });
        var wrap = window.jQuery('<div id="bimwin-auto-wrap" style="position:fixed;left:-99999px;top:0;width:1100px"></div>').appendTo('body');
        wrap.html(typeof html === 'string' ? html : (html.html || html.data || JSON.stringify(html)));

        for (var i = 0; i < 40; i++) {
            if (window.jQuery('#bimwin').length && window.jQuery('#bimwin').data('dxForm')) return true;
            await sleep(250);
        }

        return false;
    }

    function tutupDialog() {
        try {
            window.jQuery('#bimwin-auto-wrap').remove();
        } catch (e) {}
    }

    async function loadItems(editor) {
        var items = [];
        for (var i = 0; i < 20; i++) {
            var dataSource = editor.getDataSource();
            if (dataSource) {
                dataSource.paginate(false);
                await dataSource.reload();
                items = dataSource.items();
                if (items.length) break;
            }
            await sleep(300);
        }
        return items;
    }

    async function pilih(form, field, cocok) {
        var editor = form.getEditor(field);
        if (!editor) {
            log('Field ' + field + ' tidak ditemukan pada form BIMWIN.', 'err');
            return false;
        }

        var items = await loadItems(editor);
        var item = items.find(function (candidate) {
            return cocok(norm(candidate.Name));
        });

        if (!item) {
            log('Tidak ditemukan pada ' + field + ': ' + items.map(function (candidate) { return candidate.Name; }).join(', '), 'err');
            return false;
        }

        editor.option('value', item.ID);
        await sleep(800);
        return true;
    }

    async function prosesBimwin(noDaftar, catinBelum, config, markComplete) {
        if (!await bukaDialog(noDaftar, config.endpoints.dialog)) {
            throw new Error('Dialog BIMWIN gagal dibuka.');
        }

        try {
            var form = window.jQuery('#bimwin').dxForm('instance');
            if (!form) throw new Error('Form BIMWIN tidak siap.');

            var provinsi = norm(config.provinsi);
            var kabkota = norm(config.kabkota);
            var kecamatan = norm(config.kecamatan);

            for (var i = 0; i < 2; i++) {
                var catin = i === 0 ? 'suami' : 'istri';
                if (!await pilih(form, 'id_provinsi_' + catin, function (name) { return name === provinsi; })) {
                    throw new Error('Provinsi tidak cocok untuk ' + catin + '.');
                }
                if (!await pilih(form, 'id_kabkota_' + catin, function (name) { return name === kabkota; })) {
                    throw new Error('Kabupaten/kota tidak cocok untuk ' + catin + '.');
                }
                await sleep(1500);
                if (!await pilih(form, 'id_kecamatan_' + catin, function (name) { return name === kecamatan; })) {
                    throw new Error('Kecamatan tidak cocok untuk ' + catin + '.');
                }
            }

            var kandidat = { suami: [], istri: [] };
            var terpakai = { suami: new Set(), istri: new Set() };

            for (var c = 0; c < 2; c++) {
                var peran = c === 0 ? 'suami' : 'istri';
                var editor = form.getEditor('id_jadwal_' + peran);
                if (!editor) throw new Error('Field jadwal ' + peran + ' tidak ditemukan.');

                var items = await loadItems(editor);
                var kata = norm(peran === 'suami' ? config.fasilitatorSuami : config.fasilitatorIstri);
                var list = items
                    .filter(function (item) {
                        return (!kata || norm(item.Name).includes(kata)) && Number(item.Sisa) > 0;
                    })
                    .sort(function (a, b) {
                        return Number(b.Sisa) - Number(a.Sisa);
                    });

                kandidat[peran] = list;
                var current = editor.option('value');
                var currentKey = current === undefined || current === null ? '' : String(current);
                var currentItem = items.find(function (item) { return String(item.ID) === currentKey; });

                if (currentKey && currentItem && Number(currentItem.Sisa) > 0) {
                    terpakai[peran].add(currentKey);
                    log('  fasilitator ' + peran + ' => ' + currentItem.Name + ' (sudah terpilih)', 'ok');
                } else if (list.length) {
                    editor.option('value', list[0].ID);
                    terpakai[peran].add(String(list[0].ID));
                    log('  fasilitator ' + peran + ' => ' + list[0].Name, 'ok');
                } else {
                    throw new Error('Tidak ada fasilitator berkuota untuk ' + peran + '.');
                }

                await sleep(800);
            }

            var sukses = false;
            for (var attempt = 0; attempt < config.maxCoba; attempt++) {
                if (stopRequested) throw new Error('Dihentikan oleh pengguna sebelum menyimpan.');

                var result = await jqueryAjaxForm(window.jQuery('#bimwin')[0], config.endpoints.simpan);
                log('  simpan percobaan ' + (attempt + 1) + ': ' + JSON.stringify(result), 'info');

                if (result && Number(result.code) === 200) {
                    sukses = true;
                    break;
                }

                var pesan = norm(result && result.message);
                var catinError = pesan.includes('SUAMI') ? 'suami' : (pesan.includes('ISTRI') ? 'istri' : null);
                if (!catinError || !pesan.includes('KUOTA')) {
                    throw new Error('Gagal simpan: ' + (result && result.message ? result.message : 'respons tidak dikenali'));
                }

                var sisa = kandidat[catinError].filter(function (item) {
                    return !terpakai[catinError].has(String(item.ID));
                });
                if (!sisa.length) throw new Error('Kuota fasilitator habis untuk ' + catinError + '.');

                terpakai[catinError].add(String(sisa[0].ID));
                form.getEditor('id_jadwal_' + catinError).option('value', sisa[0].ID);
                log('  ganti jadwal ' + catinError + ' => ' + sisa[0].Name, 'warn');
                await sleep(1200);
            }

            if (!sukses) throw new Error('Batas percobaan simpan tercapai.');
            await sleep(1000);

            if (markComplete) {
                if (stopRequested) {
                    log('  Data tersimpan, tetapi tandai selesai dilewati karena dihentikan.', 'warn');
                    return true;
                }

                var noDaftarForm = form.getEditor('no_daftar')?.option('value') || noDaftar;
                for (var k = 0; k < catinBelum.length; k++) {
                    var peranSelesai = catinBelum[k];
                    var idJadwal = form.getEditor('id_jadwal_' + peranSelesai)?.option('value');
                    if (!idJadwal) {
                        log('  id_jadwal kosong untuk ' + peranSelesai + ', lewati tandai selesai.', 'warn');
                        continue;
                    }
                    var selesaiResult = await jqueryPost(config.endpoints.selesai, {
                        id_jadwal: idJadwal,
                        no_daftar: noDaftarForm,
                        catin: peranSelesai.toUpperCase(),
                        value: 'SS_ALL'
                    });
                    log('  tandai selesai ' + peranSelesai + ': ' + JSON.stringify(selesaiResult), 'ok');
                    await sleep(500);
                }
            }

            return true;
        } finally {
            tutupDialog();
        }
    }

    function refreshGrids() {
        try {
            if (!window.jQuery) return;
            window.jQuery('#detail, #grid, #gridPencatatan').each(function () {
                var grid = window.jQuery(this).data('dxDataGrid');
                if (grid) grid.refresh();
            });
        } catch (e) {}
    }

    async function run() {
        if (running) return;

        var config = readConfigFromPanel();
        if (recording) {
            log('Hentikan perekaman endpoint dulu sebelum menjalankan BIMWIN.', 'err');
            return;
        }
        if (!endpointsComplete(config.endpoints)) {
            log('Endpoint BIMWIN belum lengkap. Rekam sampai 4/4 lalu simpan di Laporan.', 'err');
            return;
        }
        if (!config.provinsi || !config.kabkota || !config.kecamatan) {
            log('Provinsi, kabupaten/kota, dan kecamatan wajib diisi.', 'err');
            return;
        }
        if (!window.jQuery) {
            log('jQuery SIMKAH4 tidak tersedia. Muat ulang halaman.', 'err');
            return;
        }

        var mode = panelElement('bw-mode')?.value || 'cek';
        var writeMode = mode !== 'cek';
        var notifOriginal = null;

        running = true;
        stopRequested = false;
        storeConfig(config);
        panelElement('bw-start').disabled = true;
        panelElement('bw-stop').disabled = false;

        if (config.redamNotif && window.SimKah && typeof window.SimKah.WindowNotif === 'function') {
            notifOriginal = window.SimKah.WindowNotif;
            window.SimKah.WindowNotif = function (title, message, status) {
                log('[notif] ' + title + ' — ' + message + ' — ' + status, 'warn');
            };
        }

        log('Mulai mode ' + mode + ' untuk ' + config.provinsi + ' / ' + config.kabkota + ' / ' + config.kecamatan, 'info');

        try {
            var hasil = await cekBelum(config.endpoints);
            if (stopRequested) {
                log('Dihentikan setelah pengecekan.', 'warn');
                return;
            }

            if (!hasil.belum.length) {
                if (hasil.errors.length) {
                    log('Tidak ada data yang bisa diproses. ' + hasil.errors.length + ' data gagal dicek.', 'warn');
                } else {
                    log('Semua data pada grid sudah lengkap. Tidak ada yang diproses.', 'ok');
                }
                return;
            }

            log(hasil.belum.length + ' data belum lengkap, ' + hasil.errors.length + ' data gagal dicek.', 'warn');

            if (!writeMode) {
                log('Mode Cek saja selesai. Tidak ada data yang ditulis.', 'ok');
                return;
            }

            var konfirmasi = 'Akan memproses ' + hasil.belum.length + ' data pada grid SIMKAH4.\n\n'
                + 'Wilayah: ' + config.provinsi + ' / ' + config.kabkota + ' / ' + config.kecamatan + '\n'
                + 'Mode: ' + (mode === 'lengkap' ? 'Isi + tandai selesai' : 'Isi jadwal') + '\n\n'
                + 'Lanjutkan?';

            if (!window.confirm(konfirmasi)) {
                log('Dibatalkan oleh pengguna.', 'warn');
                return;
            }

            var berhasil = 0;
            var gagal = [];

            for (var i = 0; i < hasil.belum.length; i++) {
                if (stopRequested) {
                    log('Dihentikan. Sisa data tidak diproses.', 'warn');
                    break;
                }

                var data = hasil.belum[i];
                progress('[' + (i + 1) + '/' + hasil.belum.length + '] ' + data.noDaftar);
                log('>> [No. ' + data.noUrut + '] ' + data.noDaftar + ' (' + data.belum.join(', ') + ')', 'info');

                try {
                    await prosesBimwin(data.noDaftar, data.belum, config, mode === 'lengkap');
                    berhasil++;
                    log('  OK ' + data.noDaftar, 'ok');
                } catch (e) {
                    gagal.push(data.noDaftar);
                    log('  GAGAL ' + data.noDaftar + ': ' + e.message, 'err');
                    tutupDialog();
                }

                await sleep(1000);
            }

            log('Selesai. Berhasil: ' + berhasil + ', gagal: ' + gagal.length + '.', gagal.length ? 'warn' : 'ok');
            if (gagal.length) log('Gagal: ' + gagal.join(', '), 'err');
        } catch (e) {
            log('ERROR: ' + e.message, 'err');
        } finally {
            if (notifOriginal && window.SimKah) window.SimKah.WindowNotif = notifOriginal;
            running = false;
            stopRequested = false;
            panelElement('bw-start').disabled = false;
            panelElement('bw-stop').disabled = true;
            progress('');
            refreshGrids();
        }
    }

    if (document.body) {
        initPanel();
    } else {
        window.addEventListener('DOMContentLoaded', initPanel);
    }
})();
