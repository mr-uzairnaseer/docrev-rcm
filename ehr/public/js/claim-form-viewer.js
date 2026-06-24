/**
 * Editable claim-form PDF viewer — canvas + HTML overlays (CMS-1500, UB-04).
 */
(function (global) {
    const PDFJS_BASE = '/vendor/pdfjs';
    const CMS1500_BLANK = '/forms/cms1500-blank.pdf';
    const UB04_BLANK = '/forms/ub04-blank.pdf';

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector('script[src="' + src + '"]');
            if (existing) {
                if (existing.dataset.loaded === '1') resolve();
                else existing.addEventListener('load', () => resolve());
                return;
            }
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => { script.dataset.loaded = '1'; resolve(); };
            script.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(script);
        });
    }

    async function ensurePdfJs() {
        await loadScript(PDFJS_BASE + '/pdf.min.js');
        if (!global.pdfjsLib) {
            throw new Error('PDF.js failed to load.');
        }
        global.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_BASE + '/pdf.worker.min.js';
        return global.pdfjsLib;
    }

    function resolvePdfUrl(url) {
        if (!url) return url;
        if (url.startsWith('blob:') || url.startsWith('http://') || url.startsWith('https://')) {
            return url;
        }
        return new URL(url, global.location.origin).href;
    }

    async function waitForLayout(container) {
        for (let i = 0; i < 24; i++) {
            const w = container.clientWidth;
            if (w && w > 80) return w;
            await new Promise((r) => requestAnimationFrame(r));
        }
        return Math.min(816, global.innerWidth ? global.innerWidth - 80 : 816);
    }

    async function renderHcfaOverlays(parent, canvas, hcfa, acroOverrides) {
        if (!global.HcfaOverlays) {
            return false;
        }
        const layer = document.createElement('div');
        layer.className = 'claim-form-overlay-layer claim-form-hcfa-layer';
        layer.style.width = canvas.width + 'px';
        layer.style.height = canvas.height + 'px';
        parent.appendChild(layer);
        await global.HcfaOverlays.render(layer, hcfa, canvas.width, canvas.height, acroOverrides);
        return layer.querySelector('[data-acro-name]') !== null;
    }

    async function renderUb04Overlays(parent, canvas, ub04) {
        if (!global.Ub04Overlays) return false;
        const layer = document.createElement('div');
        layer.className = 'claim-form-overlay-layer claim-form-ub04-layer';
        layer.style.width = canvas.width + 'px';
        layer.style.height = canvas.height + 'px';
        parent.appendChild(layer);
        await global.Ub04Overlays.render(layer, ub04, canvas.width, canvas.height);
        return layer.querySelector('[data-ub04-key]') !== null;
    }

    async function renderPdfPage(pdfjsLib, pdfSource, pageNum, maxWidth, options) {
        const pdf = await pdfjsLib.getDocument(pdfSource).promise;
        const page = await pdf.getPage(pageNum);
        const baseViewport = page.getViewport({ scale: 1 });
        const scale = maxWidth / baseViewport.width;
        const viewport = page.getViewport({ scale });

        const sheet = document.createElement('div');
        sheet.className = 'claim-form-page-sheet claim-form-page-sheet--editable';

        const inner = document.createElement('div');
        inner.className = 'claim-form-page-inner';
        inner.style.width = viewport.width + 'px';
        inner.style.height = viewport.height + 'px';

        const canvas = document.createElement('canvas');
        canvas.className = 'claim-form-page-canvas';
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        const context = canvas.getContext('2d');
        await page.render({ canvasContext: context, viewport }).promise;
        inner.appendChild(canvas);

        const formType = options.formType || 'hcfa';
        let fieldsOk = false;

        if (formType === 'hcfa') {
            fieldsOk = await renderHcfaOverlays(inner, canvas, options.hcfa, options.acroOverrides);
        } else if (formType === 'ub04') {
            fieldsOk = await renderUb04Overlays(inner, canvas, options.ub04);
        }

        sheet.appendChild(inner);
        return { sheet, fieldsOk, pdf };
    }

    async function renderClaimFormPdf(blobUrl, container, options) {
        const opts = options || {};
        const onlyFirstPage = opts.onlyFirstPage !== false;
        const formType = opts.formType || 'hcfa';
        const ub04 = opts.ub04 || null;
        const hcfa = opts.hcfa || null;

        if (!container) {
            throw new Error('PDF container missing.');
        }

        container.innerHTML = '<p class="claim-form-render-status">Loading form…</p>';

        const pdfjsLib = await ensurePdfJs();
        const containerWidth = await waitForLayout(container);
        const maxWidth = Math.min(containerWidth, 816);

        container.innerHTML = '';

        const pdfSource = formType === 'hcfa'
            ? resolvePdfUrl(CMS1500_BLANK)
            : (formType === 'ub04' ? resolvePdfUrl(UB04_BLANK) : resolvePdfUrl(blobUrl));

        if (!pdfSource) {
            throw new Error('PDF source missing.');
        }

        const result = await renderPdfPage(pdfjsLib, pdfSource, 1, maxWidth, {
            formType,
            hcfa,
            ub04,
            acroOverrides: opts.acroOverrides,
        });

        if ((formType === 'hcfa' || formType === 'ub04') && !result.fieldsOk) {
            const note = document.createElement('p');
            note.className = 'claim-form-page-hint claim-form-page-hint--warn';
            note.textContent = 'Editable fields could not be placed. Hard refresh (Ctrl+F5) and try again.';
            result.sheet.appendChild(note);
        }

        const hint = document.createElement('p');
        hint.className = 'claim-form-page-hint';
        hint.textContent = 'Click any highlighted field to edit. Print applies your changes to the official PDF.';
        result.sheet.appendChild(hint);

        container.appendChild(result.sheet);

        if (!onlyFirstPage && result.pdf && result.pdf.numPages > 1) {
            for (let pageNum = 2; pageNum <= result.pdf.numPages; pageNum++) {
                const extra = await renderPdfPage(pdfjsLib, pdfSource, pageNum, maxWidth, {
                    formType,
                    hcfa,
                    ub04,
                    acroOverrides: opts.acroOverrides,
                });
                container.appendChild(extra.sheet);
            }
        }
    }

    function collectAcroFields(container) {
        if (global.HcfaOverlays && container) {
            return global.HcfaOverlays.collectOverrides(container);
        }
        return {};
    }

    global.ClaimFormViewer = {
        render: renderClaimFormPdf,
        collectAcroFields,
    };
})(window);
