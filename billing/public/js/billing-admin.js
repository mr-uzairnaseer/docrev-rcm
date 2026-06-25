const API = '/api';
const TOKEN_KEY = 'billing_token';
const PREFS_KEY = 'docrev_clearinghouse_prefs';
const { createApp } = Vue;

function defaultPrefs() {
    return {
        default_cms_tab: 'payers',
        default_per_page: '200',
        default_place_of_service: '11',
        eligibility_auto_refresh: true,
        cms_export_max_rows: '1000',
    };
}

createApp({
    data() {
        const today = new Date().toISOString().slice(0, 10);
        const prefs = { ...defaultPrefs(), ...(JSON.parse(localStorage.getItem(PREFS_KEY) || '{}')) };
        return {
            token: localStorage.getItem(TOKEN_KEY) || '',
            view: 'dashboard',
            charges: [],
            claims: [],
            eras: [],
            patientPayments: [],
            eligibility: [],
            denials: [],
            dash: {},
            requirements: null,
            features: null,
            setupLoading: false,
            cmsLoading: false,
            onboardingGuide: null,
            orgProfile: null,
            referenceData: null,
            npiLookup: '',
            npiResult: null,
            orgTaxId: '',
            npiProviderCandidates: null,
            selectedProviderNpis: [],
            payerDirectory: null,
            posReference: null,
            clearinghouseTest: '',
            eligibilityTest: '',
            patients: [],
            payers: [],
            loginForm: { email: 'billing@demo-medical.test', password: 'password' },
            buildForm: { patient_id: '1', payer_id: '1', icd10: 'Z00.00', charge_ids: '', place_of_service: prefs.default_place_of_service },
            eligForm: { patient_id: '1', payer_id: '1', service_date: today, member_id: '' },
            eligFilters: { coverage_status: '', patient_id: '' },
            exportForm: { status: '', from: '', to: '' },
            prefs,
            cmsSummary: null,
            cmsTab: prefs.default_cms_tab,
            cmsFilters: { program: '', ownership: '', state: '', q: '', category: '', level: '', group_code: '', billable: '', mac_type: '', part_d: '', per_page: prefs.default_per_page },
            cmsImportOptions: { fresh: true, download: false, only: [] },
            cmsStateOptions: [],
            cmsStateDetail: null,
            cmsPagination: null,
            cmsPayers: [],
            cmsStates: [],
            cmsMacs: [],
            cmsPos: [],
            cmsTaxonomy: [],
            cmsMaContracts: [],
            cmsQhpIssuers: [],
            cmsHcpcs: [],
            cmsIcd10: [],
            cmsModifiers: [],
            cmsCarc: [],
            cmsRarc: [],
            cmsTob: [],
            cmsRevenue: [],
            showCmsImport: false,
            showBuildClaim: false,
            // Appeal Scribe State
            activeDenialForAppeal: null,
            appealTemplateType: 'medical_necessity',
            appealLetterText: '',
            ediPreview: '',
            eraImport: '',
            qaTracker: null,
            orgNpi: '1245319599',
            dashLastUpdated: null,
            realtimeTimer: null,
            error: '',
            toast: '',
            setupReadyDismissed: false,
            // EFT/ERA Onboarding & Reconciliation Hub state
            eftEnrollment: null,
            eftDeposits: [],
            eftRules: {},
            eftReconciliation: {},
            eftSubView: 'dashboard',
            newDeposit: { trace_number: '', amount: '', deposit_date: new Date().toISOString().slice(0, 10), payer_id: '' },
            manualReassociateForm: { deposit_id: null, era_remittance_id: null },
            showAddDeposit: false,
            savingEft: false,
        };
    },
    computed: {
        readyCharges() { return this.charges.filter(c => c.status === 'ready'); },
        readyChargeIds() { return this.readyCharges.map(c => c.id).join(','); },
        deniedClaims() { return this.claims.filter(c => c.status === 'denied'); },
        cmsTabHasSearch() {
            return ['payers', 'medicare-advantage', 'qhp', 'hcpcs', 'icd10', 'modifiers', 'carc', 'rarc', 'tob', 'revenue', 'taxonomy'].includes(this.cmsTab);
        },
    },
    mounted() {
        if (this.token) {
            this.refresh();
            this.startRealtimeSync();
        }
    },
    beforeUnmount() {
        this.stopRealtimeSync();
    },
    methods: {
        api() { return axios.create({ baseURL: API, headers: { Authorization: 'Bearer ' + this.token } }); },
        async login() {
            this.error = '';
            try {
                const { data } = await axios.post(API + '/auth/login', this.loginForm);
                this.token = data.token;
                localStorage.setItem(TOKEN_KEY, data.token);
                await this.refresh();
                this.startRealtimeSync();
            } catch (e) {
                this.error = (e.response && e.response.data && e.response.data.message) || 'Login failed';
            }
        },
        logout() {
            this.stopRealtimeSync();
            this.token = '';
            localStorage.removeItem(TOKEN_KEY);
        },
        startRealtimeSync() {
            this.stopRealtimeSync();
            this.realtimeTimer = setInterval(() => {
                if (!this.token) return;
                if (['dashboard', 'eras', 'denials', 'qa'].includes(this.view)) {
                    this.refreshViewData();
                }
            }, 20000);
        },
        stopRealtimeSync() {
            if (this.realtimeTimer) {
                clearInterval(this.realtimeTimer);
                this.realtimeTimer = null;
            }
        },
        agingBarPercent(bucket) {
            const total = parseFloat(this.dash.aging && this.dash.aging.total) || 0;
            const val = parseFloat(this.dash.aging && this.dash.aging[bucket]) || 0;
            if (total <= 0) return 0;
            return Math.min(100, (val / total) * 100);
        },
        qaStatusClass(status) {
            return {
                pass: 'badge-green',
                fail: 'badge-red',
                in_progress: 'badge-yellow',
                untested: '',
            }[status] || '';
        },
        setView(v) { this.view = v; this.refresh(); },
        async refresh() {
            if (this.token && !this.orgProfile) {
                try {
                    const { data } = await this.api().get('/integration/requirements');
                    this.orgProfile = data.organization || null;
                    if (this.orgProfile && this.orgProfile.npi) {
                        this.orgNpi = this.orgProfile.npi;
                    }
                } catch (_) { /* ignore */ }
            }
            await Promise.all([
                this.refreshCoreDataIfNeeded(),
                this.refreshViewData(),
            ]);
        },
        async refreshCoreDataIfNeeded() {
            if (['setup', 'cms', 'dashboard', 'eras', 'denials', 'qa'].includes(this.view)) {
                if (this.view === 'setup' && !this.orgProfile) {
                    try {
                        const { data } = await this.api().get('/integration/requirements');
                        this.orgProfile = data.organization || null;
                        if (this.orgProfile && this.orgProfile.npi) {
                            this.orgNpi = this.orgProfile.npi;
                        }
                    } catch (_) { /* ignore */ }
                }
                return;
            }

            const tasks = [];

            if (['charges', 'claims'].includes(this.view)) {
                tasks.push(
                    this.api().get('/charges?per_page=100').then((response) => {
                        this.charges = this.cmsPaginatedRows(response);
                    })
                );
            }
            if (this.view === 'claims') {
                tasks.push(
                    this.api().get('/claims?per_page=100').then((response) => {
                        this.claims = this.cmsPaginatedRows(response);
                    })
                );
            }
            if (['claims', 'eligibility', 'charges'].includes(this.view)) {
                tasks.push(
                    this.api().get('/patients?per_page=100').then((response) => {
                        this.patients = this.cmsPaginatedRows(response);
                    })
                );
            }
            if (['claims', 'eligibility'].includes(this.view)) {
                tasks.push(
                    this.api().get('/payers?per_page=100').then((response) => {
                        this.payers = this.cmsPaginatedRows(response);
                    })
                );
            }

            if (tasks.length) {
                await Promise.all(tasks);
            }

            if (!this.buildForm.charge_ids) {
                this.buildForm.charge_ids = this.readyChargeIds;
            }
            if (this.patients.length && !this.eligForm.patient_id) {
                this.eligForm.patient_id = String(this.patients[0].id);
                this.buildForm.patient_id = String(this.patients[0].id);
            }
        },
        async refreshViewData() {
            if (this.view === 'setup') {
                this.setupLoading = true;
                try {
                    const [req, feat, pos, payers] = await Promise.all([
                        this.api().get('/integration/requirements'),
                        this.api().get('/integration/features'),
                        this.api().get('/integration/place-of-service'),
                        this.api().get('/integration/payer-directory?limit=50'),
                    ]);
                    this.requirements = req.data;
                    this.onboardingGuide = req.data.guide || null;
                    this.orgProfile = req.data.organization || null;
                    this.orgTaxId = (req.data.organization && req.data.organization.tax_id) || '';
                    this.referenceData = req.data.reference_data || null;
                    this.features = feat.data;
                    this.posReference = pos.data.data;
                    this.payerDirectory = payers.data.data;
                } finally {
                    this.setupLoading = false;
                }
                return;
            }
            if (this.view === 'dashboard') {
                const { data } = await this.api().get('/dashboard');
                this.dash = data.data || {};
                this.dashLastUpdated = this.dash.synced_at || new Date().toISOString();
                return;
            }
            if (this.view === 'qa') {
                const { data } = await this.api().get('/qa-tracker');
                this.qaTracker = data.data || null;
                return;
            }
            if (this.view === 'eras') {
                const [erasRes, paymentsRes, enrollmentRes, depositsRes, reportRes, payersRes] = await Promise.all([
                    this.api().get('/eras?per_page=50'),
                    this.api().get('/patient-payments?per_page=50'),
                    this.api().get('/eft/enrollment'),
                    this.api().get('/eft/deposits'),
                    this.api().get('/eft/reconciliation-report'),
                    this.api().get('/payers?per_page=100'),
                ]);
                this.eras = this.cmsPaginatedRows(erasRes);
                this.patientPayments = this.cmsPaginatedRows(paymentsRes);
                this.eftEnrollment = enrollmentRes.data || null;
                this.eftDeposits = depositsRes.data || [];
                this.eftReconciliation = reportRes.data || {};
                this.payers = this.cmsPaginatedRows(payersRes);
                return;
            }
            if (this.view === 'eligibility') {
                const params = new URLSearchParams({ per_page: '50' });
                if (this.eligFilters.coverage_status) params.set('coverage_status', this.eligFilters.coverage_status);
                if (this.eligFilters.patient_id) params.set('patient_id', this.eligFilters.patient_id);
                const response = await this.api().get('/eligibility?' + params.toString());
                this.eligibility = this.cmsPaginatedRows(response);
                return;
            }
            if (this.view === 'denials') {
                const response = await this.api().get('/denials?per_page=50');
                this.denials = this.cmsPaginatedRows(response);
                return;
            }
            if (this.view === 'cms') {
                this.cmsLoading = true;
                try {
                    const [summary, states, tab] = await Promise.all([
                        this.api().get('/cms/summary'),
                        this.api().get('/cms/states?per_page=100'),
                        this.loadCmsTabRequest(),
                    ]);
                    this.cmsSummary = summary.data.data;
                    this.cmsStateOptions = this.cmsPaginatedRows(states);
                    this.applyCmsTabResult(tab);
                } finally {
                    this.cmsLoading = false;
                }
            }
        },
        patientName(id) {
            const p = this.patients.find(x => x.id === id);
            return p ? p.first_name + ' ' + p.last_name : '#' + id;
        },
        formatDate(d) { return d ? new Date(d).toLocaleString() : '—'; },
        async exportClaims() {
            try {
                const params = {};
                if (this.exportForm.status) params.status = this.exportForm.status;
                if (this.exportForm.from) params.from = this.exportForm.from;
                if (this.exportForm.to) params.to = this.exportForm.to;
                const { data } = await this.api().get('/claims/export', { params, responseType: 'blob' });
                const url = URL.createObjectURL(data);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'docrev-claims-' + new Date().toISOString().slice(0, 10) + '.csv';
                link.click();
                URL.revokeObjectURL(url);
                this.toast = 'Claims exported to CSV.';
            } catch (e) {
                this.toast = 'Export failed.';
            }
        },
        async buildClaim() {
            const payload = {
                patient_id: parseInt(this.buildForm.patient_id),
                payer_id: parseInt(this.buildForm.payer_id),
                charge_ids: this.buildForm.charge_ids.split(',').map(s => parseInt(s.trim())).filter(Boolean),
                icd10_codes: this.buildForm.icd10.split(',').map(s => s.trim()).filter(Boolean),
                rendering_provider_npi: this.orgNpi,
                billing_provider_npi: this.orgNpi,
                place_of_service: this.buildForm.place_of_service || '11',
            };
            await this.api().post('/claims', payload);
            this.toast = 'Draft claim created.';
            this.showBuildClaim = false;
            await this.refresh();
        },
        async markReady(id) {
            try {
                const { data } = await this.api().post('/claims/' + id + '/ready');
                this.toast = data.warning || 'Claim scrubbed and EDI generated.';
            } catch (e) {
                const msg = (e.response && e.response.data && e.response.data.errors) ? e.response.data.errors.join(', ') : 'Scrub failed';
                this.toast = msg;
            }
            await this.refresh();
        },
        async submitClaim(id) {
            const { data } = await this.api().post('/claims/' + id + '/submit');
            this.toast = data.submission.response_message || 'Submitted.';
            await this.refresh();
        },
        async simulateEra(id) {
            const { data } = await this.api().post('/claims/' + id + '/simulate-era');
            this.toast = data.message || 'ERA posted.';
            this.eraImport = data.edi_835 || '';
            await this.refresh();
        },
        async simulateDenial(id) {
            const { data } = await this.api().post('/claims/' + id + '/simulate-denial');
            this.toast = data.message || 'Denial ERA posted.';
            await this.refresh();
        },
        async createCorrected(id) {
            try {
                const { data } = await this.api().post('/claims/' + id + '/correct');
                this.toast = data.message || 'Corrected draft created.';
                this.view = 'claims';
                await this.refresh();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Correction failed.';
            }
        },
        async correctAndResubmit(id) {
            try {
                const { data } = await this.api().post('/claims/' + id + '/correct-and-resubmit');
                this.toast = data.message || data.submission.response_message || 'Corrected claim resubmitted.';
                await this.refresh();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Resubmit failed.';
            }
        },
        async correctFromDenial(denial) {
            if (!denial.claim_id) return;
            await this.correctAndResubmit(denial.claim_id);
            this.view = 'claims';
        },
        async testClearinghouse() {
            this.clearinghouseTest = '';
            try {
                const { data } = await this.api().post('/integration/clearinghouse-test');
                this.clearinghouseTest = data.message || 'Connection OK.';
            } catch (e) {
                this.clearinghouseTest = (e.response && e.response.data && e.response.data.message) || 'Connection test failed.';
            }
        },
        async testEligibility() {
            this.eligibilityTest = '';
            try {
                const { data } = await this.api().post('/integration/eligibility-test');
                this.eligibilityTest = data.message || 'Connection OK.';
            } catch (e) {
                this.eligibilityTest = (e.response && e.response.data && e.response.data.message) || 'Eligibility test failed.';
            }
        },
        savePrefs() {
            localStorage.setItem(PREFS_KEY, JSON.stringify(this.prefs));
            this.cmsTab = this.prefs.default_cms_tab;
            this.cmsFilters.per_page = this.prefs.default_per_page;
            this.buildForm.place_of_service = this.prefs.default_place_of_service;
            this.toast = 'Workspace preferences saved.';
        },
        applyDefaultPrefs() {
            this.prefs = defaultPrefs();
            this.savePrefs();
        },
        async lookupNpi() {
            if (!this.npiLookup || this.npiLookup.length !== 10) {
                this.toast = 'Enter a 10-digit NPI.';
                return;
            }
            try {
                const { data } = await this.api().get('/integration/npi-lookup?npi=' + encodeURIComponent(this.npiLookup));
                this.npiResult = data.data;
            } catch (e) {
                this.npiResult = null;
                this.toast = (e.response && e.response.data && e.response.data.message) || 'NPI lookup failed.';
            }
        },
        async applyNppes(applyAs) {
            if (!this.npiLookup || this.npiLookup.length !== 10) {
                this.toast = 'Enter a 10-digit NPI first.';
                return;
            }
            try {
                const payload = { npi: this.npiLookup };
                if (applyAs) payload.apply_as = applyAs;
                const { data } = await this.api().post('/integration/apply-nppes', payload);
                this.toast = data.message || 'Applied from NPPES.';
                if (data.organization) {
                    this.orgProfile = data.organization;
                    this.orgTaxId = data.organization.tax_id || '';
                    if (this.requirements) this.requirements.organization = data.organization;
                }
                await this.refreshViewData();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Could not apply NPPES record.';
            }
        },
        async saveTaxId() {
            if (!this.orgTaxId || !this.orgTaxId.trim()) {
                this.toast = 'Enter your Federal Tax ID (EIN).';
                return;
            }
            try {
                const { data } = await this.api().patch('/integration/organization', { tax_id: this.orgTaxId.trim() });
                this.toast = data.message || 'EIN saved.';
                if (data.organization) {
                    this.orgProfile = data.organization;
                    this.orgTaxId = data.organization.tax_id || '';
                }
                await this.refreshViewData();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Could not save EIN.';
            }
        },
        async discoverProviders() {
            try {
                const { data } = await this.api().get('/integration/npi-provider-search');
                this.npiProviderCandidates = data.data;
                this.selectedProviderNpis = (data.data.candidates || []).map(c => c.npi);
                if (!data.data.candidates || !data.data.candidates.length) {
                    this.toast = 'No new Type-1 providers found at this practice name in NPPES.';
                }
            } catch (e) {
                this.npiProviderCandidates = null;
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Provider search failed.';
            }
        },
        async applySelectedProviders() {
            if (!this.selectedProviderNpis.length) {
                this.toast = 'Select at least one provider.';
                return;
            }
            try {
                const { data } = await this.api().post('/integration/apply-nppes-providers', { npis: this.selectedProviderNpis });
                this.toast = data.message || 'Providers added.';
                if (data.organization) {
                    this.orgProfile = data.organization;
                    this.orgTaxId = data.organization.tax_id || '';
                }
                this.npiProviderCandidates = null;
                this.selectedProviderNpis = [];
                await this.refreshViewData();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Could not add providers.';
            }
        },
        toggleProviderNpi(npi) {
            const idx = this.selectedProviderNpis.indexOf(npi);
            if (idx === -1) this.selectedProviderNpis.push(npi);
            else this.selectedProviderNpis.splice(idx, 1);
        },
        async syncPayerIds() {
            const { data } = await this.api().post('/integration/sync-payers');
            this.toast = data.message || 'Payers synced.';
            await this.refreshViewData();
        },
        openAppealScribe(denial) {
            this.activeDenialForAppeal = denial;
            this.appealTemplateType = 'medical_necessity';
            this.generateAppealLetterText();
        },
        generateAppealLetterText() {
            if (!this.activeDenialForAppeal) return;
            const d = this.activeDenialForAppeal;
            const patientName = d.claim && d.claim.patient ? `${d.claim.patient.first_name} ${d.claim.patient.last_name}` : 'Patient';
            const claimNum = d.claim ? d.claim.claim_number : 'N/A';
            const serviceDate = (d.claim && (d.claim.service_date || d.claim.service_date_from))
                ? (d.claim.service_date || d.claim.service_date_from)
                : new Date().toLocaleDateString();
            const cpt = (d.claim && d.claim.cpt_code) ? d.claim.cpt_code : '99213';
            const deniedAmt = d.denied_amount;
            const code = d.reason_code || 'CO-97';
            const desc = d.reason_description || 'Benefit maximum reached or service not covered.';

            let bodyText = '';
            if (this.appealTemplateType === 'timely_filing') {
                bodyText = `We are writing to appeal the denial for claim #${claimNum} based on timely filing limit. The patient, ${patientName}, received service on ${serviceDate}. However, due to administrative delay or cross-app synchronization, the claim was delayed. Enclosed, please find the proof of prior system submission showing the claim was originally prepared within the timely filing window. We request a waiver of the timely filing limit and that the claim be re-processed for payment.`;
            } else if (this.appealTemplateType === 'incorrect_modifier') {
                bodyText = `We are appealing the coding/modifier denial for claim #${claimNum}. The service code billed was CPT ${cpt} on service date ${serviceDate} for patient ${patientName}. The denial code cited is ${code}: ${desc}. Upon clinical chart review, we have verified that the modifiers applied were accurate to represent the separate and distinct nature of the evaluation performed. We request a review of the clinical chart notes to process this claim.`;
            } else {
                bodyText = `We are writing to formally appeal the medical necessity denial of claim #${claimNum} for patient ${patientName}. The service date was ${serviceDate} for procedure CPT ${cpt}. The payer cited denial code ${code}: ${desc}. Based on the patient's presenting symptoms and clinical history, the service was medically reasonable and necessary. We request a full clinical review of the attached medical records and that the denied amount of $${deniedAmt} be paid in full.`;
            }

            this.appealLetterText = `Date: ${new Date().toLocaleDateString()}
To: Appeals & Grievances Department
Re: Claim Appeal for Patient ${patientName}

Patient Name: ${patientName}
Claim Number: ${claimNum}
Date of Service: ${serviceDate}
Procedure Code: CPT ${cpt}
Denied Amount: $${deniedAmt}
Denial Code/Reason: ${code} - ${desc}

Dear Claims Appeals Committee,

${bodyText}

Sincerely,

Billing Department
DocRev Medical Group`;
        },
        copyAppealLetterText() {
            navigator.clipboard.writeText(this.appealLetterText);
            this.toast = 'Appeal letter copied to clipboard!';
        },
        async submitScribeAppeal() {
            if (!this.activeDenialForAppeal) return;
            try {
                const notes = `Appealed using Appeal Letter Scribe (${this.appealTemplateType}):\n\n` + this.appealLetterText;
                const { data } = await this.api().post('/denials/' + this.activeDenialForAppeal.id + '/appeal', {
                    notes,
                    template_type: this.appealTemplateType,
                });
                this.toast = data.message || 'Appeal letter submitted successfully!';
                this.activeDenialForAppeal = null;
                await this.refresh();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Appeal submission failed.';
            }
        },
        async appealDenial(id) {
            const notes = prompt('Appeal notes:');
            if (!notes) return;
            const { data } = await this.api().post('/denials/' + id + '/appeal', { notes });
            this.toast = data.message || 'Appeal filed.';
            await this.refresh();
        },
        async importEra() {
            if (!this.eraImport.trim()) {
                this.toast = 'Paste ERA 835 content first.';
                return;
            }
            const { data } = await this.api().post('/eras/import', { edi_835: this.eraImport });
            this.toast = data.message || 'ERA posted.';
            this.eraImport = '';
            this.view = 'eras';
            await this.refresh();
        },
        async checkEligibility() {
            const payload = {
                patient_id: parseInt(this.eligForm.patient_id),
                payer_id: parseInt(this.eligForm.payer_id),
                service_date: this.eligForm.service_date,
            };
            if (this.eligForm.member_id) payload.member_id = this.eligForm.member_id;
            const { data } = await this.api().post('/eligibility/check', payload);
            this.toast = (data.inquiry && data.inquiry.coverage_status === 'active')
                ? 'Coverage active. Copay: $' + (data.inquiry.copay_amount || '0')
                : (data.message || 'Eligibility check complete.');
            if (this.prefs.eligibility_auto_refresh) {
                await this.refresh();
            }
        },
        async viewEdi(id) {
            const { data } = await this.api().get('/claims/' + id + '/edi');
            this.ediPreview = data.edi_837;
        },
        loadCmsTabRequest() {
            const params = new URLSearchParams({ per_page: this.cmsFilters.per_page || '200' });
            if (this.cmsTab === 'payers') {
                if (this.cmsFilters.program) params.set('program', this.cmsFilters.program);
                if (this.cmsFilters.ownership) params.set('ownership', this.cmsFilters.ownership);
                if (this.cmsFilters.state) params.set('state', this.cmsFilters.state);
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/payers?' + params.toString());
            }
            if (this.cmsTab === 'medicare-advantage') {
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                if (this.cmsFilters.part_d === 'yes') params.set('offers_part_d', '1');
                if (this.cmsFilters.part_d === 'no') params.set('offers_part_d', '0');
                return this.api().get('/cms/medicare-advantage?' + params.toString());
            }
            if (this.cmsTab === 'qhp') {
                if (this.cmsFilters.state) params.set('state', this.cmsFilters.state);
                if (this.cmsFilters.ownership) params.set('ownership', this.cmsFilters.ownership);
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/qhp-issuers?' + params.toString());
            }
            if (this.cmsTab === 'states') {
                return this.api().get('/cms/states?per_page=100');
            }
            if (this.cmsTab === 'macs') {
                if (this.cmsFilters.state) params.set('state', this.cmsFilters.state);
                if (this.cmsFilters.mac_type) params.set('mac_type', this.cmsFilters.mac_type);
                return this.api().get('/cms/macs?' + params.toString());
            }
            if (this.cmsTab === 'pos') {
                return this.api().get('/cms/place-of-service?' + params.toString());
            }
            if (this.cmsTab === 'hcpcs') {
                if (this.cmsFilters.category) params.set('category', this.cmsFilters.category);
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/hcpcs?' + params.toString());
            }
            if (this.cmsTab === 'icd10') {
                if (this.cmsFilters.billable === 'yes') params.set('billable', '1');
                if (this.cmsFilters.billable === 'no') params.set('billable', '0');
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/icd10?' + params.toString());
            }
            if (this.cmsTab === 'modifiers') {
                if (this.cmsFilters.level) params.set('level', this.cmsFilters.level);
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/modifiers?' + params.toString());
            }
            if (this.cmsTab === 'carc') {
                if (this.cmsFilters.group_code) params.set('group_code', this.cmsFilters.group_code);
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/claim-adjustments?' + params.toString());
            }
            if (this.cmsTab === 'rarc') {
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/remittance-remarks?' + params.toString());
            }
            if (this.cmsTab === 'tob') {
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/type-of-bill?' + params.toString());
            }
            if (this.cmsTab === 'revenue') {
                if (this.cmsFilters.category) params.set('category', this.cmsFilters.category);
                if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
                return this.api().get('/cms/revenue-codes?' + params.toString());
            }
            if (this.cmsFilters.q) params.set('q', this.cmsFilters.q);
            return this.api().get('/cms/taxonomy?' + params.toString());
        },
        cmsPaginatedRows(response) {
            const body = response && response.data !== undefined ? response.data : response;
            const payload = body && body.data !== undefined ? body.data : body;
            if (Array.isArray(payload)) {
                return payload;
            }
            return payload && Array.isArray(payload.data) ? payload.data : [];
        },
        cmsPaginationMeta(response) {
            const body = response && response.data && response.data.data !== undefined ? response.data.data : null;
            if (body && body.total !== undefined) {
                return {
                    from: body.from,
                    to: body.to,
                    total: body.total,
                    current_page: body.current_page,
                    last_page: body.last_page,
                };
            }
            return null;
        },
        applyCmsTabResult(response) {
            const rows = this.cmsPaginatedRows(response);
            this.cmsPagination = this.cmsPaginationMeta(response);
            if (this.cmsTab === 'payers') this.cmsPayers = rows;
            if (this.cmsTab === 'states') this.cmsStates = rows;
            if (this.cmsTab === 'macs') this.cmsMacs = rows;
            if (this.cmsTab === 'pos') this.cmsPos = rows;
            if (this.cmsTab === 'taxonomy') this.cmsTaxonomy = rows;
            if (this.cmsTab === 'medicare-advantage') this.cmsMaContracts = rows;
            if (this.cmsTab === 'qhp') this.cmsQhpIssuers = rows;
            if (this.cmsTab === 'hcpcs') this.cmsHcpcs = rows;
            if (this.cmsTab === 'icd10') this.cmsIcd10 = rows;
            if (this.cmsTab === 'modifiers') this.cmsModifiers = rows;
            if (this.cmsTab === 'carc') this.cmsCarc = rows;
            if (this.cmsTab === 'rarc') this.cmsRarc = rows;
            if (this.cmsTab === 'tob') this.cmsTob = rows;
            if (this.cmsTab === 'revenue') this.cmsRevenue = rows;
        },
        async loadCmsTab() {
            try {
                const response = await this.loadCmsTabRequest();
                this.applyCmsTabResult(response);
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Failed to load CMS data.';
            }
        },
        async importCmsData() {
            const payload = {
                fresh: this.cmsImportOptions.fresh,
                download: this.cmsImportOptions.download,
            };
            if (this.cmsImportOptions.only.length) {
                payload.only = this.cmsImportOptions.only;
            }
            const { data } = await this.api().post('/cms/import', payload);
            this.toast = data.message || 'CMS data imported.';
            this.showCmsImport = false;
            await this.refresh();
        },
        resetCmsFilters() {
            const perPage = this.prefs.default_per_page || '200';
            this.cmsFilters = {
                program: '', ownership: '', state: '', q: '', category: '', level: '',
                group_code: '', billable: '', mac_type: '', part_d: '', per_page: perPage,
            };
            this.loadCmsTab();
        },
        cmsExportParams() {
            const params = new URLSearchParams({
                dataset: this.cmsTab,
                limit: this.prefs.cms_export_max_rows || '1000',
            });
            const f = this.cmsFilters;
            if (f.program) params.set('program', f.program);
            if (f.ownership) params.set('ownership', f.ownership);
            if (f.state) params.set('state', f.state);
            if (f.mac_type) params.set('mac_type', f.mac_type);
            if (f.part_d === 'yes') params.set('offers_part_d', '1');
            if (f.part_d === 'no') params.set('offers_part_d', '0');
            if (f.category) params.set('category', f.category);
            if (f.level) params.set('level', f.level);
            if (f.group_code) params.set('group_code', f.group_code);
            if (f.billable === 'yes') params.set('billable', '1');
            if (f.billable === 'no') params.set('billable', '0');
            if (f.q) params.set('q', f.q);
            return params;
        },
        async exportCmsTab() {
            try {
                const { data } = await this.api().get('/cms/export?' + this.cmsExportParams().toString(), { responseType: 'blob' });
                const url = URL.createObjectURL(data);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'cms-' + this.cmsTab + '-' + new Date().toISOString().slice(0, 10) + '.csv';
                link.click();
                URL.revokeObjectURL(url);
                this.toast = 'CMS data exported.';
            } catch (e) {
                this.toast = 'CMS export failed.';
            }
        },
        async openStateDetail(code) {
            if (!code) return;
            try {
                const { data } = await this.api().get('/cms/states/' + encodeURIComponent(code));
                this.cmsStateDetail = data.data;
            } catch (e) {
                this.toast = 'Could not load state details.';
            }
        },
        closeStateDetail() {
            this.cmsStateDetail = null;
        },
        async saveEftEnrollment() {
            this.savingEft = true;
            try {
                const { data } = await this.api().post('/eft/enrollment', this.eftEnrollment);
                this.eftEnrollment = data.enrollment;
                this.toast = data.message || 'EFT enrollment profile updated.';
                await this.refreshViewData();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Failed to update EFT profile.';
            } finally {
                this.savingEft = false;
            }
        },
        async toggleOnboardingChecklist(key) {
            if (!this.eftEnrollment) return;
            if (!this.eftEnrollment.onboarding_checklist) {
                this.eftEnrollment.onboarding_checklist = {};
            }
            this.eftEnrollment.onboarding_checklist[key] = !this.eftEnrollment.onboarding_checklist[key];
            await this.saveEftEnrollment();
        },
        async addEftDeposit() {
            try {
                const { data } = await this.api().post('/eft/deposits', this.newDeposit);
                this.toast = data.message || 'EFT Deposit added successfully.';
                this.newDeposit = { trace_number: '', amount: '', deposit_date: new Date().toISOString().slice(0, 10), payer_id: '' };
                this.showAddDeposit = false;
                await this.refreshViewData();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Failed to add EFT Deposit.';
            }
        },
        async manualMatchDeposit() {
            try {
                const { data } = await this.api().post('/eft/reassociate', this.manualReassociateForm);
                this.toast = data.message || 'Manual match posted.';
                this.manualReassociateForm = { deposit_id: null, era_remittance_id: null };
                await this.refreshViewData();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Failed to match deposit.';
            }
        },
        async updatePayerEftStatus(payerId, field, status) {
            if (!this.eftEnrollment) return;
            // Ensure object initialization
            if (!this.eftEnrollment[field] || typeof this.eftEnrollment[field] !== 'object') {
                this.eftEnrollment[field] = {};
            }
            this.eftEnrollment[field][payerId] = status;
            await this.saveEftEnrollment();
        },
    },
}).mount('#app');
