/**
 * CMS-1500 editable HTML overlays positioned from official PDF AcroForm field rects.
 */
(function (global) {
    let positionsPromise = null;

    function loadPositions() {
        if (!positionsPromise) {
            positionsPromise = fetch('/js/hcfa-field-positions.json')
                .then((r) => {
                    if (!r.ok) throw new Error('HCFA field map failed to load.');
                    return r.json();
                });
        }
        return positionsPromise;
    }

    function fieldEntries(fields, name) {
        const def = fields[name];
        if (!def) return [];
        return Array.isArray(def) ? def : [def];
    }

    function createInput(def, name, value, width, height, layer) {
        const isCheck = def.type === 'CheckBox';
        const el = document.createElement('input');
        el.className = 'claim-form-overlay-input' + (isCheck ? ' claim-form-overlay-input--check' : '');
        el.dataset.acroName = name;
        el.name = name;

        if (isCheck) {
            el.type = 'checkbox';
            el.value = def.on || 'Yes';
            el.checked = value === el.value || value === true || value === 'Yes' || value === 'YES';
            el.addEventListener('change', () => {
                if (!el.checked || !layer) return;
                layer.querySelectorAll('[data-acro-name="' + name.replace(/"/g, '\\"') + '"]').forEach((other) => {
                    if (other !== el) other.checked = false;
                });
            });
        } else {
            el.type = 'text';
            el.value = value != null ? String(value) : '';
        }

        el.style.left = (def.x / 100 * width) + 'px';
        el.style.top = (def.y / 100 * height) + 'px';
        el.style.width = Math.max(isCheck ? 12 : 8, def.w / 100 * width) + 'px';
        el.style.height = Math.max(isCheck ? 12 : 14, def.h / 100 * height) + 'px';
        if (!isCheck) {
            el.style.fontSize = Math.max(7, Math.min(11, def.h / 100 * height * 0.85)) + 'px';
        }
        return el;
    }

    async function render(layer, hcfa, width, height, overrides) {
        const data = await loadPositions();
        const fields = data.fields || {};
        layer.innerHTML = '';

        const mapped = global.HcfaAcroMap
            ? global.HcfaAcroMap.mapHcfaToAcro(hcfa || {})
            : { text: {}, radios: {} };
        const text = Object.assign({}, mapped.text, overrides || {});
        const radios = Object.assign({}, mapped.radios);

        Object.keys(fields).forEach((name) => {
            fieldEntries(fields, name).forEach((def) => {
                if (def.type === 'Button') return;
                let value = '';
                if (def.type === 'CheckBox') {
                    value = radios[name] || '';
                } else {
                    value = text[name] || '';
                }
                layer.appendChild(createInput(def, name, value, width, height, layer));
            });
        });
    }

    function collectOverrides(container) {
        const fields = {};
        container.querySelectorAll('[data-acro-name]').forEach((el) => {
            const name = el.dataset.acroName;
            if (!name) return;
            if (el.type === 'checkbox') {
                if (el.checked) {
                    fields[name] = el.value || 'Yes';
                }
            } else if (el.value) {
                fields[name] = el.value;
            }
        });
        return fields;
    }

    global.HcfaOverlays = {
        render,
        collectOverrides,
        loadPositions,
    };
})(window);
