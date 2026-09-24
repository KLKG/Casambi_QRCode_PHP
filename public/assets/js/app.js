/*
 * Casambi QR-Code – client-side behaviour.
 * No framework, no inline scripts (CSP: script-src 'self' 'wasm-unsafe-eval').
 *
 *  1. Delete confirmation for forms with data-confirm
 *  2. Control page: send one command per element to control.php via fetch()
 *  3. Scan page: camera + QR detection with barcode-detector (ZXing WebAssembly, MIT)
 *
 * Translated strings are provided by the server as data-t-* attributes on <body>.
 */
(function () {
    'use strict';

    var T = document.body.dataset;
    function tr(key, fallback, params) {
        var text = T[key] || fallback;
        if (params) {
            Object.keys(params).forEach(function (name) {
                text = text.split('{' + name + '}').join(String(params[name]));
            });
        }
        return text;
    }

    /* ---- 0. Fixed navigation: keep the content below it ------------- */
    var nav = document.getElementById('mainNav');
    function updateNavHeight() {
        if (nav) {
            document.documentElement.style.setProperty('--nav-height', nav.offsetHeight + 'px');
        }
    }
    updateNavHeight();
    window.addEventListener('resize', updateNavHeight);
    window.addEventListener('load', updateNavHeight);

    /* ---- 1. Delete confirmation ------------------------------------- */
    document.querySelectorAll('form[data-confirm], button[data-confirm]').forEach(function (el) {
        var eventName = el.tagName === 'FORM' ? 'submit' : 'click';
        el.addEventListener(eventName, function (event) {
            if (!window.confirm(el.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    /* ---- 2. Control elements ------------------------------------------ */
    var statusEl = document.getElementById('control-status');

    function setStatus(text, isError) {
        if (!statusEl) { return; }
        statusEl.textContent = text;
        statusEl.classList.toggle('is-error', Boolean(isError));
    }

    function sendControl(form, actionIndex) {
        setStatus(tr('tSending', 'Sending…'));
        var data = new FormData(form);
        if (typeof actionIndex !== 'undefined') {
            data.append('action', String(actionIndex));
        }
        fetch(form.action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) {
                return response.json().then(function (json) {
                    return { ok: response.ok && json.ok === true, json: json };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    setStatus(result.json.error || tr('tFailed', 'Command failed.'), true);
                    return;
                }
                setStatus(result.json.demo
                    ? tr('tDemo', 'Saved (demo mode, nothing sent to the gateway).')
                    : tr('tSent', 'Command sent.'));
            })
            .catch(function () {
                setStatus(tr('tNetwork', 'Network error, command not sent.'), true);
            });
    }

    document.querySelectorAll('form[data-control]').forEach(function (form) {
        var timer = null;

        form.querySelectorAll('input[type="range"]').forEach(function (range) {
            var output = form.querySelector('output[for="' + range.id + '"]');

            range.addEventListener('input', function () {
                if (output) { output.value = range.value; }
            });
            // "change" fires once the user releases the slider; the short delay merges
            // the events of several sliders in the same element into a single request.
            range.addEventListener('change', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(function () { sendControl(form); }, 50);
            });
        });

        // Button elements (on/off, scene, resume automation): one request per press.
        form.querySelectorAll('button[data-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                form.querySelectorAll('button[data-action]').forEach(function (b) { b.classList.remove('is-active'); });
                button.classList.add('is-active');
                sendControl(form, button.dataset.action);
            });
        });

        // Push button elements: "pressed" while held (pointer or keyboard), "released" on let go.
        form.querySelectorAll('button[data-press]').forEach(function (button) {
            var held = false;
            function press(event) {
                if (held) { return; }
                held = true;
                button.classList.add('is-active');
                sendControl(form, button.dataset.press);
                if (event && event.preventDefault) { event.preventDefault(); }
            }
            function release() {
                if (!held) { return; }
                held = false;
                button.classList.remove('is-active');
                sendControl(form, button.dataset.release);
            }
            button.addEventListener('pointerdown', press);
            button.addEventListener('pointerup', release);
            button.addEventListener('pointercancel', release);
            button.addEventListener('pointerleave', release);
            button.addEventListener('keydown', function (event) {
                if (event.key === ' ' || event.key === 'Enter') { press(event); }
            });
            button.addEventListener('keyup', function (event) {
                if (event.key === ' ' || event.key === 'Enter') { release(); }
            });
            button.addEventListener('blur', release);
            button.addEventListener('contextmenu', function (event) { event.preventDefault(); });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (form.querySelector('input[type="range"]')) {
                sendControl(form);
            }
        });
    });

    /* ---- 3. QR scanner ----------------------------------------------- */
    var reader = document.getElementById('reader');
    if (!reader) { return; }

    var readerStatusEl = reader.querySelector('[data-role="status"]');
    var scanForm = document.getElementById('scanform');
    var scanInput = document.getElementById('scan_code');

    function readerStatus(text, isError) {
        if (!readerStatusEl) { return; }
        readerStatusEl.textContent = text;
        readerStatusEl.classList.toggle('is-error', Boolean(isError));
    }

    if (!window.BarcodeDetectionAPI || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        readerStatus(tr('tCamUnsupported', 'Camera scanning is not supported in this browser. Please enter the code manually.'), true);
        return;
    }
    if (!window.isSecureContext) {
        readerStatus(tr('tCamHttps', 'Camera access requires HTTPS (or localhost). Please enter the code manually.'), true);
        return;
    }

    var api = window.BarcodeDetectionAPI;
    var wasmUrl = reader.dataset.wasmUrl;

    // Load the WebAssembly decoder from this server instead of a public CDN (offline / CSP).
    api.prepareZXingModule({
        overrides: {
            locateFile: function (path, prefix) {
                return path.endsWith('.wasm') ? wasmUrl : prefix + path;
            }
        }
    });

    var detector = new api.BarcodeDetector({ formats: ['qr_code'] });
    var video = document.createElement('video');
    video.setAttribute('playsinline', '');
    video.muted = true;
    video.autoplay = true;
    reader.insertBefore(video, readerStatusEl);

    var stream = null;
    var stopped = false;

    function stopCamera() {
        stopped = true;
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
    }

    function extractCode(text) {
        // Accept plain codes as well as links to this app (…index.php?site=control&code=xxx)
        try {
            var url = new URL(text, window.location.href);
            var param = url.searchParams.get('code');
            if (param) { return param; }
        } catch (e) { /* not a URL */ }
        return String(text).trim().toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 10);
    }

    function onFound(rawValue) {
        stopCamera();
        var code = extractCode(rawValue);
        scanInput.value = code;
        readerStatus(tr('tFound', 'Code found: {code}', { code: code }));
        scanForm.submit();
    }

    function scheduleScan() {
        if (!stopped) { window.setTimeout(scan, 100); }
    }

    function scan() {
        if (stopped) { return; }
        if (video.readyState < 2) { scheduleScan(); return; }
        detector.detect(video)
            .then(function (codes) {
                if (codes.length > 0 && codes[0].rawValue) {
                    onFound(codes[0].rawValue);
                    return;
                }
                scheduleScan();
            })
            .catch(function (err) {
                console.error(err);
                scheduleScan();
            });
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
        .then(function (mediaStream) {
            stream = mediaStream;
            video.srcObject = mediaStream;
            return video.play();
        })
        .then(function () {
            readerStatus(tr('tCamPoint', 'Point the camera at a QR code.'));
            scheduleScan();
        })
        .catch(function (err) {
            readerStatus(tr('tCamError', 'Camera could not be started: {error}', { error: (err && err.message ? err.message : err) }), true);
        });

    window.addEventListener('pagehide', stopCamera);
})();
