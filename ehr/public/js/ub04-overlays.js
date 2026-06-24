/**
 * UB-04 (CMS-1450) editable HTML overlays — positions from official blank PDF.
 */
(function (global) {
    let positionsPromise = null;

    function loadPositions() {
        if (!positionsPromise) {
            positionsPromise = fetch('/js/ub04-field-positions.json')
                .then((r) => {
                    if (!r.ok) throw new Error('UB-04 field map failed to load.');
                    return r.json();
                });
        }
        return positionsPromise;
    }

    function selectedCode(opts) {
        if (!opts) return '';
        if (typeof opts === 'string') return opts;
        const hit = (opts || []).find((o) => o.selected);
        return hit ? (hit.code || '') : '';
    }

    function setSelectedCode(opts, code) {
        if (!Array.isArray(opts)) return code;
        opts.forEach((o) => { o.selected = o.code === code; });
        return code;
    }

    function ensureArray(arr, len, factory) {
        const out = arr || [];
        while (out.length < len) out.push(factory());
        return out;
    }

    function readUb04Value(ub, key) {
        if (!ub) return '';

        if (key === 'fl1.name') return (ub.fl1_billing_provider || {}).name || '';
        if (key === 'fl1.address') return (ub.fl1_billing_provider || {}).address || '';
        if (key === 'fl1.city') return (ub.fl1_billing_provider || {}).city_state_zip || '';
        if (key === 'fl1.phone') return (ub.fl1_billing_provider || {}).phone || '';
        if (key === 'fl2.payto') return ub.fl2_pay_to || '';
        if (key === 'fl3a') return ub.fl3a_patient_control || '';
        if (key === 'fl3b') return ub.fl3b_medical_record || '';
        if (key === 'fl4') return (ub.fl4_type_of_bill || '').replace(/-/g, '');
        if (key === 'fl5') return ub.fl5_fed_tax_id || '';
        if (key === 'fl6.from') return (ub.fl6_statement_period || {}).from || '';
        if (key === 'fl6.through') return (ub.fl6_statement_period || {}).through || '';
        if (key === 'fl7') return ub.fl7 || '';
        if (key === 'fl8') return ub.fl8_patient_name || '';
        if (key === 'fl9.street') return (ub.fl9_patient_address || {}).street || '';
        if (key === 'fl9.city') {
            const a = ub.fl9_patient_address || {};
            return [a.city, a.state, a.zip].filter(Boolean).join(', ');
        }
        if (key === 'fl10.dob') return ub.fl10_birth_date || '';
        if (key === 'fl10.sex') {
            const s = ub.fl10_sex || {};
            return s.M ? 'M' : (s.F ? 'F' : '');
        }
        if (key === 'fl12') return ub.fl12_admission_date || '';
        if (key === 'fl13') return ub.fl13_admission_hour || '';
        if (key === 'fl14') return ub.fl14_admission_type_code || selectedCode(ub.fl14_admission_type);
        if (key === 'fl15') return ub.fl15_point_of_origin_code || selectedCode(ub.fl15_point_of_origin);
        if (key === 'fl16') return ub.fl16_discharge_hour || '';
        if (key === 'fl17') return ub.fl17_discharge_status_code || selectedCode(ub.fl17_discharge_status);
        if (key === 'fl29') return ub.fl29_accident_state || '';
        if (key === 'fl30') return ub.fl30 || '';
        if (key === 'fl37') return ub.fl37 || '';
        if (key === 'fl47total') return ub.fl47_total_charges || '';
        if (key === 'fl48total') return ub.fl48_non_covered_total || '';
        if (key === 'fl49total') return ub.fl49_page_total || '';
        if (key === 'fl56') return ub.fl56_billing_provider_npi || '';
        if (key === 'fl57') return ub.fl57_other_provider_id || '';
        if (key === 'fl63') return ub.fl63_treatment_authorization || '';
        if (key === 'fl64') return ub.fl64_document_control_number || '';
        if (key === 'fl65') return ub.fl65_employer_name || '';
        if (key === 'fl66') return ub.fl66_diagnosis_qualifier || '';
        if (key === 'fl69') return ub.fl69_admitting_diagnosis || '';
        if (key === 'fl70') return ub.fl70_patient_reason_dx || '';
        if (key === 'fl71') return ub.fl71_pps_code || '';
        if (key === 'fl72') return ub.fl72_eci || '';
        if (key === 'fl74.code') return (ub.fl74_principal_procedure || {}).code || '';
        if (key === 'fl74.date') return (ub.fl74_principal_procedure || {}).date || '';
        if (key === 'fl76.qualifier') return (ub.fl76_attending || {}).qualifier || '';
        if (key === 'fl76.npi') return (ub.fl76_attending || {}).npi || '';
        if (key === 'fl76.name') return (ub.fl76_attending || {}).name || '';
        if (key === 'fl80') return ub.fl80_remarks || '';

        const condMatch = key.match(/^fl(1[89]|2[0-8])$/);
        if (condMatch) {
            const idx = parseInt(condMatch[1], 10) - 18;
            const row = (ub.fl18_28_condition_codes || [])[idx] || {};
            return row.code || '';
        }

        const occMatch = key.match(/^fl31_34\.(\d+)\.(code|date)$/);
        if (occMatch) {
            const row = (ub.fl31_34_occurrence_codes || [])[parseInt(occMatch[1], 10)] || {};
            return row[occMatch[2]] || '';
        }

        const spanMatch = key.match(/^fl35_36\.(\d+)\.(code|from|through)$/);
        if (spanMatch) {
            const spans = ub.fl35_36_occurrence_span || [];
            const row = spans[parseInt(spanMatch[1], 10)] || {};
            return row[spanMatch[2]] || '';
        }

        const valMatch = key.match(/^fl39_41\.(\d+)\.(code|amount)$/);
        if (valMatch) {
            const row = (ub.fl39_41_value_codes || [])[parseInt(valMatch[1], 10)] || {};
            return row[valMatch[2]] || '';
        }

        const payerMatch = key.match(/^fl50\.(\d+)\.(\w+)$/);
        if (payerMatch) {
            const payer = (ub.fl50_payers || [])[parseInt(payerMatch[1], 10)] || {};
            const field = payerMatch[2] === 'health_plan_id' ? 'health_plan_id' : payerMatch[2];
            return payer[field] || '';
        }

        const insuredMatch = key.match(/^fl(58|59|60|61|62)\.(\d+)\.(\w+)$/);
        if (insuredMatch) {
            const idx = parseInt(insuredMatch[2], 10);
            const insured = (ub.fl58_62_insured || [])[idx] || {};
            const map = { 58: 'name', 59: 'rel', 60: 'id', 61: 'group_name', 62: 'group_no' };
            return insured[map[parseInt(insuredMatch[1], 10)]] || '';
        }

        const dxMatch = key.match(/^fl67\.(\d+)$/);
        if (dxMatch) {
            const dx = (ub.fl67_diagnoses || [])[parseInt(dxMatch[1], 10)] || {};
            return (dx.code || '').replace(/\./g, '');
        }

        const procMatch = key.match(/^fl75\.(\d+)\.(code|date)$/);
        if (procMatch) {
            const row = (ub.fl75_other_procedures || [])[parseInt(procMatch[1], 10)] || {};
            return row[procMatch[2]] || '';
        }

        const provMatch = key.match(/^fl77_79\.(\d+)\.(\w+)$/);
        if (provMatch) {
            const row = (ub.fl77_79_providers || [])[parseInt(provMatch[1], 10)] || {};
            return row[provMatch[2]] || '';
        }

        const lineMatch = key.match(/^line(\d+)\.(fl42|fl43|fl44|fl45|fl46|fl47|fl48)$/);
        if (lineMatch) {
            const line = (ub.fl42_47_lines || [])[parseInt(lineMatch[1], 10)] || {};
            const field = lineMatch[2];
            if (field === 'fl45') return line.fl45_service_date || line.fl45_date || '';
            if (field === 'fl46') return line.fl46 || line.fl45 || '';
            if (field === 'fl47') return line.fl47 || line.fl46 || '';
            if (field === 'fl48') return line.fl48 || '';
            return line[field] || '';
        }

        return '';
    }

    function writeUb04Value(ub, key, value) {
        if (key === 'fl1.name') {
            ub.fl1_billing_provider = ub.fl1_billing_provider || {};
            ub.fl1_billing_provider.name = value;
            return;
        }
        if (key === 'fl1.address') {
            ub.fl1_billing_provider = ub.fl1_billing_provider || {};
            ub.fl1_billing_provider.address = value;
            return;
        }
        if (key === 'fl1.city') {
            ub.fl1_billing_provider = ub.fl1_billing_provider || {};
            ub.fl1_billing_provider.city_state_zip = value;
            return;
        }
        if (key === 'fl1.phone') {
            ub.fl1_billing_provider = ub.fl1_billing_provider || {};
            ub.fl1_billing_provider.phone = value;
            return;
        }
        if (key === 'fl2.payto') { ub.fl2_pay_to = value; return; }
        if (key === 'fl3a') { ub.fl3a_patient_control = value; return; }
        if (key === 'fl3b') { ub.fl3b_medical_record = value; return; }
        if (key === 'fl4') { ub.fl4_type_of_bill = value; return; }
        if (key === 'fl5') { ub.fl5_fed_tax_id = value; return; }
        if (key === 'fl6.from') {
            ub.fl6_statement_period = ub.fl6_statement_period || {};
            ub.fl6_statement_period.from = value;
            return;
        }
        if (key === 'fl6.through') {
            ub.fl6_statement_period = ub.fl6_statement_period || {};
            ub.fl6_statement_period.through = value;
            return;
        }
        if (key === 'fl7') { ub.fl7 = value; return; }
        if (key === 'fl8') { ub.fl8_patient_name = value; return; }
        if (key === 'fl9.street') {
            ub.fl9_patient_address = ub.fl9_patient_address || {};
            ub.fl9_patient_address.street = value;
            return;
        }
        if (key === 'fl9.city') {
            ub.fl9_patient_address = ub.fl9_patient_address || {};
            const parts = value.split(',').map((s) => s.trim());
            ub.fl9_patient_address.city = parts[0] || '';
            if (parts.length >= 2) {
                const stateZip = parts.slice(1).join(' ').trim().split(/\s+/);
                ub.fl9_patient_address.state = stateZip[0] || '';
                ub.fl9_patient_address.zip = stateZip.slice(1).join(' ') || '';
            }
            return;
        }
        if (key === 'fl10.dob') { ub.fl10_birth_date = value; return; }
        if (key === 'fl10.sex') {
            ub.fl10_sex = { M: value.toUpperCase() === 'M', F: value.toUpperCase() === 'F' };
            return;
        }
        if (key === 'fl12') { ub.fl12_admission_date = value; return; }
        if (key === 'fl13') { ub.fl13_admission_hour = value; return; }
        if (key === 'fl14') {
            ub.fl14_admission_type_code = value;
            if (Array.isArray(ub.fl14_admission_type)) setSelectedCode(ub.fl14_admission_type, value);
            return;
        }
        if (key === 'fl15') {
            ub.fl15_point_of_origin_code = value;
            if (Array.isArray(ub.fl15_point_of_origin)) setSelectedCode(ub.fl15_point_of_origin, value);
            return;
        }
        if (key === 'fl16') { ub.fl16_discharge_hour = value; return; }
        if (key === 'fl17') {
            ub.fl17_discharge_status_code = value;
            if (Array.isArray(ub.fl17_discharge_status)) setSelectedCode(ub.fl17_discharge_status, value);
            return;
        }
        if (key === 'fl29') { ub.fl29_accident_state = value; return; }
        if (key === 'fl30') { ub.fl30 = value; return; }
        if (key === 'fl37') { ub.fl37 = value; return; }
        if (key === 'fl47total') { ub.fl47_total_charges = value; return; }
        if (key === 'fl48total') { ub.fl48_non_covered_total = value; return; }
        if (key === 'fl49total') { ub.fl49_page_total = value; return; }
        if (key === 'fl56') { ub.fl56_billing_provider_npi = value; return; }
        if (key === 'fl57') { ub.fl57_other_provider_id = value; return; }
        if (key === 'fl63') { ub.fl63_treatment_authorization = value; return; }
        if (key === 'fl64') { ub.fl64_document_control_number = value; return; }
        if (key === 'fl65') { ub.fl65_employer_name = value; return; }
        if (key === 'fl66') { ub.fl66_diagnosis_qualifier = value; return; }
        if (key === 'fl69') { ub.fl69_admitting_diagnosis = value; return; }
        if (key === 'fl70') { ub.fl70_patient_reason_dx = value; return; }
        if (key === 'fl71') { ub.fl71_pps_code = value; return; }
        if (key === 'fl72') { ub.fl72_eci = value; return; }
        if (key === 'fl74.code') {
            ub.fl74_principal_procedure = ub.fl74_principal_procedure || {};
            ub.fl74_principal_procedure.code = value;
            return;
        }
        if (key === 'fl74.date') {
            ub.fl74_principal_procedure = ub.fl74_principal_procedure || {};
            ub.fl74_principal_procedure.date = value;
            return;
        }
        if (key === 'fl76.qualifier') {
            ub.fl76_attending = ub.fl76_attending || {};
            ub.fl76_attending.qualifier = value;
            return;
        }
        if (key === 'fl76.npi') {
            ub.fl76_attending = ub.fl76_attending || {};
            ub.fl76_attending.npi = value;
            return;
        }
        if (key === 'fl76.name') {
            ub.fl76_attending = ub.fl76_attending || {};
            ub.fl76_attending.name = value;
            return;
        }
        if (key === 'fl80') { ub.fl80_remarks = value; return; }

        const condMatch = key.match(/^fl(1[89]|2[0-8])$/);
        if (condMatch) {
            const idx = parseInt(condMatch[1], 10) - 18;
            ub.fl18_28_condition_codes = ensureArray(ub.fl18_28_condition_codes, idx + 1, () => ({ code: '', description: '' }));
            ub.fl18_28_condition_codes[idx].code = value;
            return;
        }

        const occMatch = key.match(/^fl31_34\.(\d+)\.(code|date)$/);
        if (occMatch) {
            const idx = parseInt(occMatch[1], 10);
            ub.fl31_34_occurrence_codes = ensureArray(ub.fl31_34_occurrence_codes, idx + 1, () => ({ code: '', date: '' }));
            ub.fl31_34_occurrence_codes[idx][occMatch[2]] = value;
            return;
        }

        const spanMatch = key.match(/^fl35_36\.(\d+)\.(code|from|through)$/);
        if (spanMatch) {
            const idx = parseInt(spanMatch[1], 10);
            ub.fl35_36_occurrence_span = ensureArray(ub.fl35_36_occurrence_span, idx + 1, () => ({ code: '', from: '', through: '' }));
            ub.fl35_36_occurrence_span[idx][spanMatch[2]] = value;
            return;
        }

        const valMatch = key.match(/^fl39_41\.(\d+)\.(code|amount)$/);
        if (valMatch) {
            const idx = parseInt(valMatch[1], 10);
            ub.fl39_41_value_codes = ensureArray(ub.fl39_41_value_codes, idx + 1, () => ({ code: '', amount: '' }));
            ub.fl39_41_value_codes[idx][valMatch[2]] = value;
            return;
        }

        const payerMatch = key.match(/^fl50\.(\d+)\.(\w+)$/);
        if (payerMatch) {
            const idx = parseInt(payerMatch[1], 10);
            ub.fl50_payers = ensureArray(ub.fl50_payers, idx + 1, () => ({
                name: '', health_plan_id: '', release_info: '', assign_benefits: '', prior_payments: '', estimated_amount: '',
            }));
            ub.fl50_payers[idx][payerMatch[2]] = value;
            return;
        }

        const insuredMatch = key.match(/^fl(58|59|60|61|62)\.(\d+)\.(\w+)$/);
        if (insuredMatch) {
            const idx = parseInt(insuredMatch[2], 10);
            const fieldMap = { name: 'name', rel: 'rel', id: 'id', group_name: 'group_name', group_no: 'group_no' };
            ub.fl58_62_insured = ensureArray(ub.fl58_62_insured, idx + 1, () => ({
                name: '', rel: '', id: '', group_name: '', group_no: '',
            }));
            ub.fl58_62_insured[idx][fieldMap[insuredMatch[3]]] = value;
            return;
        }

        const dxMatch = key.match(/^fl67\.(\d+)$/);
        if (dxMatch) {
            const idx = parseInt(dxMatch[1], 10);
            ub.fl67_diagnoses = ensureArray(ub.fl67_diagnoses, idx + 1, () => ({ qualifier: '', code: '', description: '' }));
            ub.fl67_diagnoses[idx].code = value.replace(/\./g, '');
            return;
        }

        const procMatch = key.match(/^fl75\.(\d+)\.(code|date)$/);
        if (procMatch) {
            const idx = parseInt(procMatch[1], 10);
            ub.fl75_other_procedures = ensureArray(ub.fl75_other_procedures, idx + 1, () => ({ code: '', date: '' }));
            ub.fl75_other_procedures[idx][procMatch[2]] = value;
            return;
        }

        const provMatch = key.match(/^fl77_79\.(\d+)\.(\w+)$/);
        if (provMatch) {
            const idx = parseInt(provMatch[1], 10);
            ub.fl77_79_providers = ensureArray(ub.fl77_79_providers, idx + 1, () => ({ qualifier: '', npi: '', name: '' }));
            ub.fl77_79_providers[idx][provMatch[2]] = value;
            return;
        }

        const lineMatch = key.match(/^line(\d+)\.(fl42|fl43|fl44|fl45|fl46|fl47|fl48)$/);
        if (lineMatch) {
            const idx = parseInt(lineMatch[1], 10);
            const field = lineMatch[2];
            ub.fl42_47_lines = ensureArray(ub.fl42_47_lines, idx + 1, () => ({
                fl42: '', fl43: '', fl44: '', fl45: '', fl46: '', fl47: '', fl48: '',
            }));
            if (field === 'fl45') {
                ub.fl42_47_lines[idx].fl45_service_date = value;
                return;
            }
            if (field === 'fl46') {
                ub.fl42_47_lines[idx].fl46 = value;
                ub.fl42_47_lines[idx].fl45 = value;
                return;
            }
            if (field === 'fl47') {
                ub.fl42_47_lines[idx].fl47 = value;
                ub.fl42_47_lines[idx].fl46 = value;
                return;
            }
            ub.fl42_47_lines[idx][field] = value;
        }
    }

    function createInput(def, key, value, width, height) {
        const el = document.createElement('input');
        el.type = 'text';
        el.className = 'claim-form-overlay-input';
        el.dataset.ub04Key = key;
        el.value = value != null ? String(value) : '';
        el.style.left = (def.x / 100 * width) + 'px';
        el.style.top = (def.y / 100 * height) + 'px';
        el.style.width = Math.max(8, def.w / 100 * width) + 'px';
        el.style.height = Math.max(10, def.h / 100 * height) + 'px';
        el.style.fontSize = Math.max(5, Math.min(9, def.h / 100 * height * 0.72)) + 'px';
        if (key.indexOf('.fl43') !== -1 || key === 'fl80' || key === 'fl2.payto') {
            el.style.fontSize = Math.max(5, Math.min(7, def.h / 100 * height * 0.68)) + 'px';
        }
        return el;
    }

    async function render(layer, ub, width, height) {
        const data = await loadPositions();
        const fields = data.fields || [];
        layer.innerHTML = '';

        fields.forEach((def) => {
            const value = readUb04Value(ub || {}, def.key);
            layer.appendChild(createInput(def, def.key, value, width, height));
        });
    }

    function applyToModel(ub, container) {
        const model = JSON.parse(JSON.stringify(ub || {}));
        container.querySelectorAll('[data-ub04-key]').forEach((el) => {
            writeUb04Value(model, el.dataset.ub04Key, el.value);
        });
        return model;
    }

    global.Ub04Overlays = {
        render,
        applyToModel,
        readUb04Value,
        loadPositions,
    };
})(window);
