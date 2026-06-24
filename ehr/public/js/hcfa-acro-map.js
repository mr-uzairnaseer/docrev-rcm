/**
 * Map CMS-1500 (HCFA) JSON payload to official PDF AcroForm field names.
 */
(function (global) {
    function splitDob(dob) {
        if (!dob) return ['', '', ''];
        const parts = String(dob).replace(/\//g, ' ').trim().split(/\s+/);
        if (parts.length >= 3) {
            return [parts[0].slice(0, 2), parts[1].slice(0, 2), parts[2].slice(-2)];
        }
        return ['', '', ''];
    }

    function pickChecked(options) {
        for (const opt of options || []) {
            if (opt.checked) {
                return opt.key || opt.label || '';
            }
        }
        return '';
    }

    const INSURANCE = {
        medicare: 'Medicare', medicaid: 'Medicaid', tricare: 'Tricare',
        champva: 'Champva', group: 'Group', feca: 'Feca', other: 'Other',
    };

    const RELATION = { self: 'S', spouse: 'M', child: 'C', other: 'O' };

    function mapHcfaToAcro(hcfa) {
        if (!hcfa) return { text: {}, radios: {} };
        const text = {};
        const radios = {};

        text.insurance_id = hcfa.box1a_insured_id || '';
        text.pt_name = hcfa.box2_patient_name || '';
        text.ins_name = hcfa.box4_insured_name || '';

        const [mm, dd, yy] = splitDob(hcfa.box3_dob);
        text.birth_mm = mm;
        text.birth_dd = dd;
        text.birth_yy = yy;

        const addr = hcfa.box5_patient_address || {};
        text.pt_street = addr.street || '';
        text.pt_city = addr.city || '';
        text.pt_state = addr.state || '';
        text.pt_zip = addr.zip || '';

        const insAddr = hcfa.box7_insured_address || addr;
        text.ins_street = insAddr.street || '';
        text.ins_city = insAddr.city || '';
        text.ins_state = insAddr.state || '';
        text.ins_zip = insAddr.zip || '';

        const policy = hcfa.box11_insured_policy || {};
        text.grp = policy.group_number || '';
        text.ins_plan_name = policy.c_insurance_plan || '';
        text.ins_policy = policy.group_number || '';

        const ill = hcfa.box14_illness_date || {};
        text.cur_ill_mm = ill.mm || '';
        text.cur_ill_dd = ill.dd || '';
        text.cur_ill_yy = ill.yy || '';

        text['99icd'] = hcfa.box21_icd_indicator || '0';
        text.prior_auth = hcfa.box23_prior_auth || '';
        text.original_ref = (hcfa.box22_resubmission || {}).original_ref || '';
        text.medicaid_resub = (hcfa.box22_resubmission || {}).code || '';

        const ref = hcfa.box17_referring || {};
        text.ref_physician = ref.name || '';
        text['physician number 17a'] = ref.npi || '';

        text.pt_signature = hcfa.box12_patient_signature || '';
        text.pt_date = hcfa.box12_date || '';
        text.ins_signature = hcfa.box13_insured_signature || '';

        text.tax_id = hcfa.box25_tax_id || '';
        text.pt_account = hcfa.box26_account || '';
        text.t_charge = hcfa.box28_total_charge || '';
        text.amt_paid = hcfa.box29_amount_paid || '';

        const phys = hcfa.box31_physician || {};
        text.physician_signature = phys.signature || '';
        text.physician_date = phys.date || '';

        const fac = hcfa.box32_service_facility || {};
        text.fac_name = fac.name || '';
        text.fac_street = fac.address || '';
        text.fac_location = fac.npi || '';

        const bill = hcfa.box33_billing_provider || {};
        text.doc_name = bill.name || '';
        text.doc_street = bill.address || '';
        text.pin = bill.npi || '';
        const phone = bill.phone || '';
        const digits = phone.replace(/\D/g, '');
        if (digits.length >= 10) {
            text['doc_phone area'] = digits.slice(0, 3);
            text.doc_phone = digits.slice(3);
        }

        text['NUCC USE'] = hcfa.box8_reserved || '';
        text.Suppl = hcfa.box19_additional || '';

        (hcfa.box21_diagnoses || []).slice(0, 12).forEach((dx, i) => {
            const code = (dx.code || '').replace(/\./g, '');
            text['diagnosis' + (i + 1)] = code;
            if (i < 6) text['diag' + (i + 1)] = code;
        });

        (hcfa.box24_lines || []).slice(0, 6).forEach((line, i) => {
            const n = i + 1;
            if (!line.cpt_hcpcs) return;
            text['sv' + n + '_mm_from'] = line.from_mm || '';
            text['sv' + n + '_dd_from'] = line.from_dd || '';
            text['sv' + n + '_yy_from'] = line.from_yy || '';
            text['sv' + n + '_mm_end'] = line.to_mm || '';
            text['sv' + n + '_dd_end'] = line.to_dd || '';
            text['sv' + n + '_yy_end'] = line.to_yy || '';
            text['place' + n] = line.place_of_service || '';
            text['cpt' + n] = line.cpt_hcpcs || '';
            text['mod' + n] = line.modifier_1 || '';
            text['mod' + n + 'a'] = line.modifier_2 || '';
            text['mod' + n + 'b'] = line.modifier_3 || '';
            text['mod' + n + 'c'] = line.modifier_4 || '';
            text['plan' + n] = line.diagnosis_pointer || '';
            text['ch' + n] = line.charges || '';
            text['type' + n] = line.units || '';
            text['emg' + n] = line.emg || '';
            const npi = line.rendering_npi || '';
            text['local' + n] = npi.slice(-5);
            text['local' + n + 'a'] = npi;
        });

        const insKey = pickChecked(hcfa.box1_insurance_types) || 'group';
        radios.insurance_type = INSURANCE[insKey] || 'Group';

        const sex = hcfa.box3_sex || {};
        if (sex.M) radios.sex = 'M';
        else if (sex.F) radios.sex = 'F';

        const relKey = pickChecked(hcfa.box6_relationship) || 'self';
        radios.rel_to_ins = RELATION[relKey] || 'S';

        const emp = (hcfa.box10_condition || {}).employment || {};
        if (emp.yes) radios.employment = 'YES';
        else if (emp.no) radios.employment = 'NO';

        const lab = hcfa.box20_outside_lab || {};
        if (lab.yes) radios.lab = 'YES';
        else if (lab.no) radios.lab = 'NO';

        const assign = hcfa.box27_assignment || {};
        if (assign.yes) radios.assignment = 'YES';
        else if (assign.no) radios.assignment = 'NO';

        const taxType = hcfa.box25_tax_id_type || {};
        if (taxType.ssn) radios.ssn = 'SSN';
        else if (taxType.ein) radios.ssn = 'EIN';

        return { text, radios };
    }

    function setFieldValue(root, name, value) {
        if (value === undefined || value === null || value === '') return;
        const nodes = root.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]');
        nodes.forEach((el) => {
            if (el.type === 'radio') {
                el.checked = (el.value === value || el.getAttribute('exportValue') === value);
            } else if (el.type === 'checkbox') {
                el.checked = value === true || value === 'Yes' || value === 'YES' || value === el.value;
            } else {
                el.value = value;
            }
        });
    }

    function populateAcroFields(container, hcfa, overrides) {
        const mapped = mapHcfaToAcro(hcfa);
        const text = Object.assign({}, mapped.text, overrides || {});
        const radios = Object.assign({}, mapped.radios);

        Object.keys(text).forEach((k) => setFieldValue(container, k, text[k]));
        Object.keys(radios).forEach((k) => setFieldValue(container, k, radios[k]));
    }

    global.HcfaAcroMap = {
        mapHcfaToAcro,
        populateAcroFields,
        collectOverrides: function (container) {
            const fields = {};
            container.querySelectorAll('.annotationLayer input, .annotationLayer textarea, .annotationLayer select').forEach((el) => {
                const name = el.getAttribute('name');
                if (!name) return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (el.checked) fields[name] = el.value || 'Yes';
                } else if (el.value) {
                    fields[name] = el.value;
                }
            });
            return fields;
        },
    };
})(window);
