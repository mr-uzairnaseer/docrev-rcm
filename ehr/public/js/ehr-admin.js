const API = '/api';
const TOKEN_KEY = 'ehr_token';
const { createApp } = Vue;

createApp({
    data() {
        const dtLocal = new Date(Date.now() + 86400000).toISOString().slice(0, 16);
        return {
            token: localStorage.getItem(TOKEN_KEY) || '',
            view: 'dashboard',
            patients: [],
            encounters: [],
            appointments: [],
            providers: [],
            locations: [],
            activeEncounter: null,
            showApptForm: false,
            loginForm: { email: 'admin@demo-medical.test', password: 'password' },
            patientForm: { first_name: '', last_name: '', date_of_birth: '', mrn: '', email: '', phone: '', gender: 'male' },
            apptForm: { patient_id: '', provider_id: '', location_id: '', scheduled_at: dtLocal, appointment_type: 'office_visit' },
            rxForm: { patient_id: '', provider_id: '', pharmacy_id: '', drug_name: 'Lisinopril 10mg', ndc: '68180098103', quantity: 30, days_supply: 30, refills: 0, sig: 'Take 1 tablet by mouth daily' },
            labForm: { patient_id: '', provider_id: '', lab_vendor_id: '', test_code: '80053', test_name: 'Comprehensive Metabolic Panel' },
            prescriptions: [],
            pharmacies: [],
            enrollments: [],
            labOrders: [],
            labVendors: [],
            hieConnections: [],
            hieExchanges: [],
            requirements: null,
            showRxForm: false,
            showLabForm: false,
            integrationTest: '',
            hiePatientId: '',
            dxForm: { icd10_code: 'Z00.00', description: '' },
            chargeForm: { cpt_code: '99213', charge_amount: '150.00', units: 1, diagnosis_pointers: [1] },
            apptTab: 'calendar',
            calendarDate: new Date().toISOString().slice(0, 10),
            calendarDateInput: new Date().toISOString().slice(0, 10),
            patientPrescriptions: [],
            patientForms: [],
            selectedFormTemplate: '',
            showFormsForm: false,
            scribeLanguage: 'en',
            isScribing: false,
            scribeProgress: '',
            // New view sub-tab states
            patientTab: 'directory',
            selectedPatient: null,
            patientChart: null,
            patientChartLoading: false,
            projectPlan: null,
            reportMetrics: null,
            qualityMetrics: null,
            productivityMetrics: null,
            auditLogs: [],
            trainingModules: [],
            opsStatus: null,
            patientSubTabs: [
                { id: 'directory', label: 'All Patients' },
                { id: 'demographics', label: 'Patient Demographics', needsPatient: true },
                { id: 'insurance', label: 'Insurance / Eligibility', needsPatient: true },
                { id: 'care-team', label: 'Care Team', needsPatient: true },
                { id: 'problems', label: 'Problem List', needsPatient: true },
                { id: 'medications', label: 'Medications', needsPatient: true },
                { id: 'allergies', label: 'Allergies', needsPatient: true },
                { id: 'history', label: 'Visit History', needsPatient: true },
            ],
            billingTab: 'claims',
            reportTab: 'financial',
            agingType: 'service',
            selectedAgingBucket: null,
            agingFilters: {
                provider: 'all',
                payer: 'all',
                location: 'all',
                statusGroup: 'all',
                patientName: ''
            },
            // Telehealth meeting state
            isMeetingActive: false,
            meetingAppointment: null,
            meetingTimer: '00:00',
            meetingTimerInterval: null,
            meetingMessages: [],
            meetingNewMsg: '',
            isLocalCameraOn: true,
            isLocalMicOn: true,
            isPatientCameraOn: true,
            meetingSeconds: 0,
            tabLoading: false,
            loading: false,
            error: '',
            toast: '',
            selectedApptDetail: null,
            isEligibilityChecking: false,
            eligibilityResult: null,
            selectedClaimForCMS1500: null,
            claimFormPreview: null,
            claimFormPdfUrl: null,
            claimFormLoading: false,
            claimFormEncounterId: null,
            claimFormType: null,
            claimFormSaving: false,
            claimScrubResults: {},
            claimStatuses: {},
            eras: [
                { id: 1, payer: 'UnitedHealthcare', check_number: 'UHC-835-90812', amount: 120.00, status: 'pending', date: '2026-06-24' },
                { id: 2, payer: 'Medicare', check_number: 'CMS-835-11092', amount: 95.00, status: 'pending', date: '2026-06-23' }
            ],
            denials: [
                { id: 1, patient: 'Jane Doe', payer: 'UnitedHealthcare', code: 'CO-97', description: 'Procedure code is bundling', amount: 150.00, appeal_status: 'none' },
                { id: 2, patient: 'John Smith', payer: 'Blue Cross Blue Shield', code: 'PR-197', description: 'Pre-certification/authorization missing', amount: 280.00, appeal_status: 'none' }
            ],
            showAppealModal: false,
            generatedAppealLetter: '',
            selectedMailboxThreadId: 1,
            mailboxReplyDraft: '',
            mailboxSearch: '',
            mailboxThreads: [
                {
                    id: 1,
                    patientName: 'Jane Doe',
                    subject: 'Refill Request — Lisinopril',
                    unread: 1,
                    updatedAt: '2026-06-24T14:32:00',
                    messages: [
                        {
                            id: 1,
                            sender: 'patient',
                            author: 'Jane Doe',
                            role: 'Patient Portal',
                            body: 'Hello Doctor, can I get a refill on my Lisinopril prescription? I have about 3 days left. Thank you.',
                            sentAt: '2026-06-24T09:15:00',
                        },
                        {
                            id: 2,
                            sender: 'staff',
                            author: 'Dr. David Miller',
                            role: 'Provider',
                            body: 'Hi Jane — I received your refill request. I can approve a 30-day refill if your last blood pressure reading was stable. Have you had any dizziness or swelling since your last visit?',
                            sentAt: '2026-06-24T10:42:00',
                        },
                        {
                            id: 3,
                            sender: 'patient',
                            author: 'Jane Doe',
                            role: 'Patient Portal',
                            body: 'No dizziness or swelling. My home readings have been around 128/82 this week. Please send the refill to my usual pharmacy.',
                            sentAt: '2026-06-24T14:32:00',
                        },
                    ],
                },
                {
                    id: 2,
                    patientName: 'Bob Test',
                    subject: 'Lab results question',
                    unread: 0,
                    updatedAt: '2026-06-23T16:10:00',
                    messages: [
                        {
                            id: 1,
                            sender: 'patient',
                            author: 'Bob Test',
                            role: 'Patient Portal',
                            body: 'I saw my CMP results in the portal. The glucose line was flagged — should I schedule a follow-up?',
                            sentAt: '2026-06-23T16:10:00',
                        },
                        {
                            id: 2,
                            sender: 'staff',
                            author: 'Care Team',
                            role: 'Clinical Staff',
                            body: 'Thanks for reaching out, Bob. A nurse will review your results and call you within 1 business day to discuss next steps.',
                            sentAt: '2026-06-23T16:45:00',
                        },
                    ],
                },
                {
                    id: 3,
                    patientName: 'John Smith',
                    subject: 'Appointment reschedule',
                    unread: 0,
                    updatedAt: '2026-06-22T11:20:00',
                    messages: [
                        {
                            id: 1,
                            sender: 'patient',
                            author: 'John Smith',
                            role: 'Patient Portal',
                            body: 'I need to move my Thursday appointment to next Monday afternoon if possible.',
                            sentAt: '2026-06-22T11:20:00',
                        },
                    ],
                },
            ],
        };
    },
    computed: {
        syncedCount() { return this.encounters.filter(e => e.billing_sync_status === 'synced').length; },
        filteredMailboxThreads() {
            const q = this.mailboxSearch.trim().toLowerCase();
            if (!q) return this.mailboxThreads;
            return this.mailboxThreads.filter(t =>
                t.patientName.toLowerCase().includes(q)
                || t.subject.toLowerCase().includes(q)
                || (t.messages[t.messages.length - 1]?.body || '').toLowerCase().includes(q)
            );
        },
        selectedMailboxThread() {
            return this.mailboxThreads.find(t => t.id === this.selectedMailboxThreadId) || null;
        },
        mailboxUnreadCount() {
            return this.mailboxThreads.reduce((sum, t) => sum + (t.unread || 0), 0);
        },
    },
    mounted() { if (this.token) this.load(); },
    methods: {
        api() { return axios.create({ baseURL: API, headers: { Authorization: 'Bearer ' + this.token } }); },
        async login() {
            this.loading = true; this.error = '';
            try {
                const { data } = await axios.post(API + '/auth/login', this.loginForm);
                this.token = data.token;
                localStorage.setItem(TOKEN_KEY, data.token);
                await this.load();
            } catch (e) {
                this.error = (e.response && e.response.data && e.response.data.message) || 'Login failed';
            }
            this.loading = false;
        },
        logout() { this.token = ''; localStorage.removeItem(TOKEN_KEY); },
        setView(v) { this.view = v; this.refreshView(); },
        async refreshView() {
            if (this.view === 'prescriptions') await this.loadPrescriptions();
            else if (this.view === 'labs') await this.loadLabs();
            else if (this.view === 'hie') await this.loadHie();
            else if (this.view === 'integrations') await this.loadIntegrations();
            else if (this.view === 'reports') await this.loadReports();
            else await this.load();
        },
        async load() {
            await Promise.all([this.loadPatients(), this.loadEncounters(), this.loadAppointments()]);
        },
        async loadPatients() {
            const { data } = await this.api().get('/patients?per_page=100');
            this.patients = data.data;
            if (this.patients.length && !this.apptForm.patient_id) {
                this.apptForm.patient_id = this.patients[0].id;
            }
        },
        openPatients(tab = 'directory') {
            this.view = 'patients';
            this.patientTab = tab;
            this.loadPatients();
            if (this.selectedPatient && tab !== 'directory') {
                this.loadPatientChart();
            }
        },
        selectPatient(patient, tab = 'demographics') {
            this.selectedPatient = patient;
            this.patientTab = tab;
            this.view = 'patients';
            this.loadPatientChart();
        },
        async loadPatientChart() {
            if (!this.selectedPatient) {
                this.patientChart = null;
                return;
            }
            this.patientChartLoading = true;
            try {
                const { data } = await this.api().get('/patients/' + this.selectedPatient.id + '/chart');
                this.patientChart = data.data;
            } catch (e) {
                this.patientChart = null;
                this.toast = 'Unable to load patient chart.';
            } finally {
                this.patientChartLoading = false;
            }
        },
        async checkPatientEligibility(insurance) {
            if (!this.selectedPatient || !insurance) return;
            this.isEligibilityChecking = true;
            try {
                const { data } = await this.api().post(
                    '/patients/' + this.selectedPatient.id + '/insurances/' + insurance.id + '/check-eligibility'
                );
                this.toast = data.message || 'Eligibility verified.';
                await this.loadPatientChart();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Eligibility check failed.';
            } finally {
                this.isEligibilityChecking = false;
            }
        },
        activePatientSubTab() {
            return this.patientSubTabs.find((t) => t.id === this.patientTab) || this.patientSubTabs[0];
        },
        async loadEncounters() {
            const { data } = await this.api().get('/encounters?per_page=100');
            this.encounters = data.data;
        },
        async loadAppointments() {
            const { data } = await this.api().get('/appointments?per_page=200');
            this.appointments = data.data;
            await this.loadPatients();
            await this.loadProviders();
        },
        async loadProviders() {
            const [p, l] = await Promise.all([
                this.api().get('/providers?per_page=20'),
                this.api().get('/locations?per_page=20'),
            ]);
            this.providers = p.data.data;
            this.locations = l.data.data;
            if (this.providers.length && !this.apptForm.provider_id) {
                this.apptForm.provider_id = this.providers[0].id;
            }
            if (this.locations.length && !this.apptForm.location_id) {
                this.apptForm.location_id = this.locations[0].id;
            }
            if (this.providers.length && !this.rxForm.provider_id) this.rxForm.provider_id = this.providers[0].id;
            if (this.patients.length && !this.rxForm.patient_id) this.rxForm.patient_id = this.patients[0].id;
            if (this.patients.length && !this.labForm.patient_id) this.labForm.patient_id = this.patients[0].id;
            if (this.providers.length && !this.labForm.provider_id) this.labForm.provider_id = this.providers[0].id;
        },
        async loadPrescriptions() {
            await this.loadPatients();
            await this.loadProviders();
            const [rx, ph, en] = await Promise.all([
                this.api().get('/prescriptions?per_page=50'),
                this.api().get('/pharmacies?per_page=50'),
                this.api().get('/surescripts-enrollments?per_page=20'),
            ]);
            this.prescriptions = rx.data.data;
            this.pharmacies = ph.data.data;
            this.enrollments = en.data.data;
            if (this.pharmacies.length && !this.rxForm.pharmacy_id) this.rxForm.pharmacy_id = this.pharmacies[0].id;
        },
        async loadLabs() {
            await this.loadPatients();
            await this.loadProviders();
            const [orders, vendors] = await Promise.all([
                this.api().get('/lab-orders?per_page=50'),
                this.api().get('/lab-vendors?per_page=20'),
            ]);
            this.labOrders = orders.data.data;
            this.labVendors = vendors.data.data;
            if (this.labVendors.length && !this.labForm.lab_vendor_id) this.labForm.lab_vendor_id = this.labVendors[0].id;
        },
        async loadHie() {
            await this.loadPatients();
            const [conn, ex] = await Promise.all([
                this.api().get('/hie/connections?per_page=20'),
                this.api().get('/hie/exchanges?per_page=20'),
            ]);
            this.hieConnections = conn.data.data;
            this.hieExchanges = ex.data.data;
            if (this.patients.length && !this.hiePatientId) this.hiePatientId = this.patients[0].id;
        },
        async loadIntegrations() {
            const [reqRes, planRes, trainingRes, opsRes] = await Promise.all([
                this.api().get('/integration/requirements'),
                this.api().get('/project-plan'),
                this.api().get('/training/modules'),
                this.api().get('/operations/status'),
            ]);
            this.requirements = reqRes.data;
            this.projectPlan = planRes.data.data;
            this.trainingModules = trainingRes.data.data || [];
            this.opsStatus = opsRes.data.data || null;
        },
        async loadReports() {
            const [dash, quality, productivity] = await Promise.all([
                this.api().get('/reports/dashboard'),
                this.api().get('/reports/quality'),
                this.api().get('/reports/productivity'),
            ]);
            this.reportMetrics = dash.data.data;
            this.qualityMetrics = quality.data.data;
            this.productivityMetrics = productivity.data.data;
            try {
                const logs = await this.api().get('/audit-logs?per_page=15');
                this.auditLogs = logs.data.data || [];
            } catch (e) {
                this.auditLogs = [];
            }
        },
        async createPrescription() {
            await this.api().post('/prescriptions', {
                patient_id: parseInt(this.rxForm.patient_id),
                provider_id: parseInt(this.rxForm.provider_id),
                pharmacy_id: parseInt(this.rxForm.pharmacy_id),
                drug_name: this.rxForm.drug_name,
                ndc: this.rxForm.ndc,
                quantity: parseInt(this.rxForm.quantity),
                days_supply: parseInt(this.rxForm.days_supply),
                refills: parseInt(this.rxForm.refills),
                sig: this.rxForm.sig,
            });
            this.toast = 'Draft prescription created.';
            this.showRxForm = false;
            await this.loadPrescriptions();
        },
        async sendPrescription(id) {
            const { data } = await this.api().post('/prescriptions/' + id + '/send');
            this.toast = 'Rx sent: ' + (data.data.surescripts_message_id || data.data.status);
            await this.loadPrescriptions();
        },
        async createLabOrder() {
            await this.api().post('/lab-orders', {
                patient_id: parseInt(this.labForm.patient_id),
                provider_id: parseInt(this.labForm.provider_id),
                lab_vendor_id: parseInt(this.labForm.lab_vendor_id),
                test_code: this.labForm.test_code,
                test_name: this.labForm.test_name,
            });
            this.toast = 'Lab order created.';
            this.showLabForm = false;
            await this.loadLabs();
        },
        async sendLabOrder(id) {
            await this.api().post('/lab-orders/' + id + '/send');
            this.toast = 'HL7 ORM sent to lab interface.';
            await this.loadLabs();
        },
        async simulateLabResults(id) {
            await this.api().post('/lab-orders/' + id + '/simulate-results');
            this.toast = 'Lab results imported (ORU simulated).';
            await this.loadLabs();
        },
        async queryHie(connectionId) {
            const patientId = this.hiePatientId || (this.patients[0] && this.patients[0].id);
            const { data } = await this.api().post('/hie/connections/' + connectionId + '/patients/' + patientId + '/query');
            this.toast = data.message || 'HIE query complete.';
            await this.loadHie();
        },
        async pushHieSummary(connectionId) {
            const patientId = this.hiePatientId || (this.patients[0] && this.patients[0].id);
            const { data } = await this.api().post('/hie/connections/' + connectionId + '/patients/' + patientId + '/push-summary');
            this.toast = data.message || 'Summary pushed to HIE.';
            await this.loadHie();
        },
        async testSurescripts() {
            try {
                const { data } = await this.api().post('/integration/test-surescripts');
                this.integrationTest = data.message;
            } catch (e) {
                this.integrationTest = (e.response && e.response.data && e.response.data.message) || 'Test failed.';
            }
        },
        async testLab() {
            try {
                const { data } = await this.api().post('/integration/test-lab');
                this.integrationTest = data.message;
            } catch (e) {
                this.integrationTest = (e.response && e.response.data && e.response.data.message) || 'Test failed.';
            }
        },
        async createPatient() {
            await this.api().post('/patients', this.patientForm);
            this.toast = 'Patient created and synced to Billing & Portal.';
            this.patientForm = { first_name: '', last_name: '', date_of_birth: '', mrn: '', email: '', phone: '', gender: 'male' };
            await this.loadPatients();
            this.openPatients('directory');
        },
        async createAppointment() {
            const payload = {
                patient_id: parseInt(this.apptForm.patient_id),
                provider_id: parseInt(this.apptForm.provider_id),
                location_id: this.apptForm.location_id ? parseInt(this.apptForm.location_id) : null,
                scheduled_at: this.apptForm.scheduled_at,
                appointment_type: this.apptForm.appointment_type || 'office_visit',
            };
            await this.api().post('/appointments', payload);
            this.toast = 'Appointment scheduled and synced to portal.';
            this.showApptForm = false;
            await this.loadAppointments();
        },
        startTelehealthMeeting(appt) {
            this.isMeetingActive = true;
            this.meetingAppointment = appt;
            this.meetingMessages = [
                { sender: 'System', text: 'Connecting to secure telehealth room...', time: new Date().toLocaleTimeString() },
                { sender: 'System', text: 'Doctor joined the room.', time: new Date().toLocaleTimeString() }
            ];
            this.meetingSeconds = 0;
            this.meetingTimer = '00:00';
            if (this.meetingTimerInterval) clearInterval(this.meetingTimerInterval);
            this.meetingTimerInterval = setInterval(() => {
                this.meetingSeconds++;
                const mins = String(Math.floor(this.meetingSeconds / 60)).padStart(2, '0');
                const secs = String(this.meetingSeconds % 60).padStart(2, '0');
                this.meetingTimer = `${mins}:${secs}`;
                
                // Simulate patient joining after 4 seconds
                if (this.meetingSeconds === 4) {
                    this.meetingMessages.push({ sender: 'System', text: 'Patient (Jane Doe) has connected.', time: new Date().toLocaleTimeString() });
                    this.meetingMessages.push({ sender: 'Patient', text: 'Hello doctor, can you hear me?', time: new Date().toLocaleTimeString() });
                }
            }, 1000);
        },
        endTelehealthMeeting() {
            this.isMeetingActive = false;
            if (this.meetingTimerInterval) clearInterval(this.meetingTimerInterval);
            this.meetingTimerInterval = null;
            this.meetingAppointment = null;
            this.toast = 'Telehealth consultation ended.';
        },
        sendMeetingMessage() {
            if (!this.meetingNewMsg.trim()) return;
            this.meetingMessages.push({
                sender: 'Provider',
                text: this.meetingNewMsg,
                time: new Date().toLocaleTimeString()
            });
            const providerMsg = this.meetingNewMsg.toLowerCase();
            this.meetingNewMsg = '';
            
            // Auto reply mockup
            setTimeout(() => {
                let reply = "I understand. Let me check.";
                if (providerMsg.includes("hello") || providerMsg.includes("hi")) {
                    reply = "Hi Doctor, thanks for seeing me today.";
                } else if (providerMsg.includes("feel") || providerMsg.includes("symptom")) {
                    reply = "I have been having a bad cough and congestion for a few days.";
                } else if (providerMsg.includes("cough")) {
                    reply = "Yes, it is a dry cough mostly, worse at night.";
                } else if (providerMsg.includes("prescribe") || providerMsg.includes("medication")) {
                    reply = "Thank you, doctor. I will pick it up at the pharmacy.";
                }
                this.meetingMessages.push({
                    sender: 'Patient',
                    text: reply,
                    time: new Date().toLocaleTimeString()
                });
            }, 1500);
        },
        async checkInAppt(id) {
            const { data } = await this.api().post('/appointments/' + id + '/check-in');
            this.toast = 'Checked in — encounter #' + data.data.encounter_id + ' opened for charting.';
            if (data.data.encounter_id) {
                await this.openEncounter(data.data.encounter_id);
            } else {
                await this.loadAppointments();
            }
        },
        async cancelAppt(id) {
            await this.api().post('/appointments/' + id + '/cancel');
            this.toast = 'Appointment cancelled.';
            await this.loadAppointments();
        },
        async approveAppt(id) {
            await this.api().post('/appointments/' + id + '/approve');
            this.toast = 'Appointment request approved!';
            await this.loadAppointments();
        },
        async declineAppt(id) {
            await this.api().post('/appointments/' + id + '/decline');
            this.toast = 'Appointment request declined.';
            await this.loadAppointments();
        },
        async savePatientAllergies(patient) {
            try {
                await this.api().put('/patients/' + patient.id, {
                    first_name: patient.first_name,
                    last_name: patient.last_name,
                    date_of_birth: patient.date_of_birth,
                    allergies: patient.allergies
                });
                this.toast = 'Patient allergies updated.';
            } catch (e) {
                alert('Failed to update allergies: ' + ((e.response && e.response.data && e.response.data.message) || 'Error'));
            }
        },
        async openNewEncounter() {
            if (!this.patients.length) { this.toast = 'Create a patient first.'; this.view = 'new-patient'; return; }
            const patientId = this.apptForm.patient_id || this.patients[0].id;
            const providerId = (this.providers[0] && this.providers[0].id) || 1;
            const locationId = (this.locations[0] && this.locations[0].id) || 1;
            const { data } = await this.api().post('/encounters', {
                patient_id: patientId,
                provider_id: providerId,
                location_id: locationId,
                encounter_date: new Date().toISOString().slice(0, 19),
            });
            await this.openEncounter(data.data.id);
        },
        async openEncounter(id) {
            const { data } = await this.api().get('/encounters/' + id);
            this.activeEncounter = data.data;
            this.view = 'new-encounter';
            // Load prescriptions for this patient
            try {
                const rxRes = await this.api().get('/prescriptions?per_page=100');
                const allRxs = rxRes.data.data;
                this.patientPrescriptions = allRxs.filter(r => r.patient_id === this.activeEncounter.patient_id);
            } catch (e) {
                this.patientPrescriptions = [];
            }
            await this.loadPatientForms(this.activeEncounter.patient_id);
        },
        async loadPatientForms(patientId) {
            try {
                const { data } = await this.api().get('/patients/' + patientId + '/forms');
                this.patientForms = data.forms || [];
            } catch (e) {
                this.patientForms = [];
            }
        },
        async sendFormOnDemand() {
            if (!this.selectedFormTemplate) {
                alert('Please select a form template');
                return;
            }
            try {
                await this.api().post('/patients/' + this.activeEncounter.patient_id + '/forms', {
                    form_name: this.selectedFormTemplate
                });
                this.toast = 'Form sent to patient portal successfully!';
                this.selectedFormTemplate = '';
                this.showFormsForm = false;
                await this.loadPatientForms(this.activeEncounter.patient_id);
            } catch (e) {
                alert('Failed to send form.');
            }
        },
        async runAmbientScribe() {
            this.isScribing = true;
            this.scribeProgress = 'Listening to patient-doctor conversation...';
            
            setTimeout(() => {
                this.scribeProgress = 'Transcribing dialog and analyzing clinical details...';
                
                setTimeout(() => {
                    let soapNotes = '';
                    let diagnoses = [];
                    let charges = [];
                    
                    if (this.scribeLanguage === 'es') {
                        soapNotes = "SUBJETIVO: El paciente refiere tos persistente y congestión nasal desde hace 3 días, acompañada de malestar general. Sin fiebre ni dolor torácico.\nOBJETIVO: Frecuencia respiratoria 18/min. Oximetría 98%. Mucosa nasal eritematosa. Pulmones limpios a la auscultación.\nEVALUACIÓN: Infección respiratoria aguda de vías superiores (J06.9).\nPLAN: Reposo, abundante hidratación, sintomáticos (paracetamol 500mg cada 8h si hay dolor/fiebre).";
                        diagnoses = [{ icd10_code: 'J06.9', description: 'Infección respiratoria aguda de vías superiores' }];
                        charges = [{ cpt_code: '99213', charge_amount: '150.00' }];
                    } else if (this.scribeLanguage === 'fr') {
                        soapNotes = "SUBJECTIF: Le patient signale une toux sèche et de la fatigue depuis 4 jours. Pas de fièvre.\nOBJECTIF: Poumons clairs. Pas de détresse respiratoire.\nÉVALUATION: Rhinopharyngite aiguë (J00).\nPLAN: Hydratation, repos, sirop pour la toux si nécessaire.";
                        diagnoses = [{ icd10_code: 'J00', description: 'Rhinopharyngite aiguë' }];
                        charges = [{ cpt_code: '99213', charge_amount: '150.00' }];
                    } else {
                        soapNotes = "SUBJECTIVE: Patient presents with persistent productive cough, runny nose, and mild throat irritation for the past 3 days. Denies shortness of breath, chest pain, or fever.\nOBJECTIVE: Lungs are clear to auscultation bilaterally. Pharynx is mildly erythematous. Heart rate 72 bpm, O2 sat 99%.\nASSESSMENT: Acute upper respiratory infection, unspecified (J06.9).\nPLAN: Rest, warm fluids, over-the-counter decongestants. Follow up if symptoms worsen.";
                        diagnoses = [{ icd10_code: 'J06.9', description: 'Acute upper respiratory infection, unspecified' }];
                        charges = [{ cpt_code: '99213', charge_amount: '150.00' }];
                    }
                    
                    this.dxForm = { icd10_code: diagnoses[0].icd10_code, description: diagnoses[0].description };
                    this.chargeForm = { cpt_code: charges[0].cpt_code, charge_amount: charges[0].charge_amount };
                    
                    this.activeEncounter.clinical_notes = (this.activeEncounter.clinical_notes || '') + "\n\n=== AI AMBIENT SCRIBE NOTE (" + this.scribeLanguage.toUpperCase() + ") ===\n" + soapNotes;
                    
                    this.isScribing = false;
                    this.scribeProgress = '';
                    this.toast = 'AI Ambient SOAP Note generated and applied to Chart!';
                }, 2000);
            }, 2000);
        },
        async addDiagnosis() {
            await this.api().post('/encounters/' + this.activeEncounter.id + '/diagnoses', this.dxForm);
            await this.openEncounter(this.activeEncounter.id);
            this.toast = 'Diagnosis added.';
        },
        async addCharge() {
            await this.api().post('/encounters/' + this.activeEncounter.id + '/charges', {
                cpt_code: this.chargeForm.cpt_code,
                charge_amount: parseFloat(this.chargeForm.charge_amount),
                units: 1,
                diagnosis_pointers: [1],
            });
            await this.openEncounter(this.activeEncounter.id);
            this.toast = 'Charge added.';
        },
        async signEncounter() {
            const { data } = await this.api().post('/encounters/' + this.activeEncounter.id + '/sign');
            this.activeEncounter = data.data;
            this.toast = 'Signed. Billing sync: ' + (data.data.billing_sync_status || 'pending');
            await this.loadEncounters();
        },
        formatDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; },
        formatDateTime(d) { return d ? new Date(d).toLocaleString() : '—'; },
        getApptStyle(appt) {
            if (!appt || !appt.scheduled_at) return { display: 'none' };
            const d = new Date(appt.scheduled_at);
            if (isNaN(d.getTime())) return { display: 'none' };
            const hours = d.getHours() + d.getMinutes() / 60;
            const startOffset = 8.0;
            const totalHours = 10.0;
            let leftPercent = ((hours - startOffset) / totalHours) * 100;
            if (leftPercent < 0) leftPercent = 0;
            if (leftPercent > 90) leftPercent = 90;
            let bg = '#48bb78';
            let color = '#fff';
            if (appt.appointment_type === 'telehealth') {
                bg = '#3182ce';
            } else if (appt.status === 'requested') {
                bg = '#ecc94b';
                color = '#744210';
            }
            return {
                left: leftPercent + '%',
                width: '9.5%',
                background: bg,
                color: color
            };
        },
        clickGridSlot(provider, hour) {
            this.apptForm.provider_id = provider.id;
            const today = new Date();
            today.setHours(hour, 0, 0, 0);
            const offset = today.getTimezoneOffset();
            const localToday = new Date(today.getTime() - (offset * 60 * 1000));
            this.apptForm.scheduled_at = localToday.toISOString().slice(0, 16);
            this.showApptForm = true;
            this.toast = `Pre-filled slot for Dr. ${provider.last_name} at ${hour}:00.`;
            this.$nextTick(() => {
                const el = document.querySelector('.panel');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            });
        },
        openApptDetails(appt) {
            this.selectedApptDetail = JSON.parse(JSON.stringify(appt));
            if (this.selectedApptDetail.scheduled_at) {
                const d = new Date(this.selectedApptDetail.scheduled_at);
                const offset = d.getTimezoneOffset();
                const localD = new Date(d.getTime() - (offset * 60 * 1000));
                this.selectedApptDetail.scheduled_at = localD.toISOString().slice(0, 16);
            }
            this.eligibilityResult = null;
        },
        async updateApptDetails() {
            if (!this.selectedApptDetail) return;
            try {
                await this.api().put('/appointments/' + this.selectedApptDetail.id, {
                    provider_id: parseInt(this.selectedApptDetail.provider_id),
                    location_id: this.selectedApptDetail.location_id ? parseInt(this.selectedApptDetail.location_id) : null,
                    scheduled_at: this.selectedApptDetail.scheduled_at,
                    duration_minutes: this.selectedApptDetail.duration_minutes ? parseInt(this.selectedApptDetail.duration_minutes) : null,
                    appointment_type: this.selectedApptDetail.appointment_type,
                    status: this.selectedApptDetail.status,
                    notes: this.selectedApptDetail.notes
                });
                this.toast = 'Appointment updated successfully!';
                this.selectedApptDetail = null;
                await this.loadAppointments();
            } catch (e) {
                alert('Failed to update appointment details: ' + ((e.response && e.response.data && e.response.data.message) || 'Error'));
            }
        },
        async checkEligibility() {
            if (!this.selectedApptDetail) return;
            this.isEligibilityChecking = true;
            this.eligibilityResult = null;
            setTimeout(() => {
                this.isEligibilityChecking = false;
                this.eligibilityResult = {
                    status: 'ACTIVE ✅',
                    payer: 'UnitedHealthcare (UHC)',
                    copay: '$20.00',
                    deductible: '$250.00 remaining',
                    verifiedAt: new Date().toLocaleTimeString()
                };
                this.toast = 'Eligibility verified in real-time.';
            }, 1500);
        },
        sendAppointmentReminder() {
            this.toast = 'Reminder notification queued for email & SMS.';
        },
        prevDay() {
            const d = new Date(this.calendarDate);
            d.setDate(d.getDate() - 1);
            this.calendarDate = d.toISOString().slice(0, 10);
            this.calendarDateInput = this.calendarDate;
        },
        nextDay() {
            const d = new Date(this.calendarDate);
            d.setDate(d.getDate() + 1);
            this.calendarDate = d.toISOString().slice(0, 10);
            this.calendarDateInput = this.calendarDate;
        },
        goToToday() {
            this.calendarDate = new Date().toISOString().slice(0, 10);
            this.calendarDateInput = this.calendarDate;
        },
        formatCalendarHeader() {
            const d = new Date(this.calendarDate);
            return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        },
        setDateFromInput() {
            this.calendarDate = this.calendarDateInput;
        },
        getApptsForProviderAndDate(providerId) {
            const selDate = new Date(this.calendarDate).toDateString();
            return this.appointments.filter(a => {
                return a.provider_id === providerId && new Date(a.scheduled_at).toDateString() === selDate;
            });
        },
        scrubClaim(encounterId) {
            this.claimScrubResults[encounterId] = 'scrubbing';
            setTimeout(() => {
                this.claimScrubResults[encounterId] = 'PASS ✅';
                this.toast = 'Claim scrubbing complete. No errors found.';
            }, 1000);
        },
        submitClaimEDI(encounterId) {
            this.claimStatuses[encounterId] = 'submitting';
            setTimeout(() => {
                this.claimStatuses[encounterId] = 'EDI Transmitted 🚀';
                this.toast = 'EDI 837 Claim File transmitted successfully to clearinghouse.';
            }, 1200);
        },
        openCMS1500Preview(encounter) {
            this.openClaimForm(encounter, 'hcfa');
        },
        async openClaimForm(encounter, formType) {
            this.claimFormLoading = true;
            this.claimFormEncounterId = encounter.id;
            this.claimFormType = formType;
            this.claimFormPreview = {
                title: formType === 'ub04' ? 'UB-04 (CMS-1450)' : 'CMS-1500 (02/12)',
                standard: formType === 'ub04'
                    ? 'Official CMS-1450 (UB-04) PDF template — click fields to edit'
                    : 'Official NUCC/CMS PDF template — click fields to edit',
                claim_number: '…',
                encounter_uuid: encounter.uuid || '',
                generated_at: new Date().toISOString(),
            };
            this.revokeClaimFormPdf();
            this.error = '';
            try {
                const { data } = await this.api().get(`/encounters/${encounter.id}/claim-form/${formType}`);
                this.claimFormPreview = data.data;

                const usesBlankTemplate = formType === 'hcfa' || formType === 'ub04';

                if (usesBlankTemplate) {
                    this.claimFormLoading = false;
                    await this.$nextTick();
                    await this.renderClaimFormPdfView();
                }

                const pdfResponse = await this.api().get(
                    `/encounters/${encounter.id}/claim-form/${formType}/pdf`,
                    { responseType: 'blob', timeout: 120000 }
                );
                const blob = pdfResponse.data;
                if (!(blob instanceof Blob) || blob.size < 100) {
                    if (!usesBlankTemplate) {
                        throw new Error('Empty PDF response.');
                    }
                } else if (blob.type && blob.type.includes('json')) {
                    const errText = await blob.text();
                    let msg = 'Unable to render claim form PDF.';
                    try {
                        const parsed = JSON.parse(errText);
                        msg = parsed.message || msg;
                    } catch (_) {
                        if (errText) msg = errText.slice(0, 200);
                    }
                    if (!usesBlankTemplate) {
                        throw new Error(msg);
                    }
                } else {
                    this.claimFormPdfUrl = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
                }

                if (usesBlankTemplate) {
                    const container = document.getElementById('claim-form-pdf-pages');
                    const selector = formType === 'hcfa' ? '[data-acro-name]' : '[data-ub04-key]';
                    const hasFields = container && container.querySelector(selector);
                    if (!hasFields) {
                        await this.$nextTick();
                        await this.renderClaimFormPdfView();
                    }
                }
            } catch (e) {
                let msg = e.message || 'Unable to load claim form.';
                if (e.response && e.response.data instanceof Blob) {
                    try {
                        const parsed = JSON.parse(await e.response.data.text());
                        msg = parsed.message || msg;
                    } catch (_) { /* keep msg */ }
                }
                this.error = msg;
                this.toast = msg;
            }
            this.claimFormLoading = false;
        },
        buildClaimFormPayload() {
            const payload = JSON.parse(JSON.stringify(this.claimFormPreview || {}));
            const container = document.getElementById('claim-form-pdf-pages');
            if (!container) {
                return payload;
            }
            if (this.claimFormType === 'hcfa' && window.ClaimFormViewer) {
                payload.acro_overrides = window.ClaimFormViewer.collectAcroFields(container);
            }
            if (this.claimFormType === 'ub04' && window.Ub04Overlays && payload.ub04) {
                payload.ub04 = window.Ub04Overlays.applyToModel(payload.ub04, container);
            }
            return payload;
        },
        async regenerateClaimFormPdf() {
            if (!this.claimFormEncounterId || !this.claimFormType) {
                return false;
            }
            this.claimFormSaving = true;
            try {
                const payload = this.buildClaimFormPayload();
                const pdfResponse = await this.api().post(
                    `/encounters/${this.claimFormEncounterId}/claim-form/${this.claimFormType}/pdf`,
                    payload,
                    { responseType: 'blob', timeout: 120000 }
                );
                const blob = pdfResponse.data;
                if (!(blob instanceof Blob) || blob.size < 100) {
                    throw new Error('Failed to apply edits to PDF.');
                }
                this.revokeClaimFormPdf();
                this.claimFormPdfUrl = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
                if (payload.ub04) {
                    this.claimFormPreview.ub04 = payload.ub04;
                }
                if (payload.acro_overrides) {
                    this.claimFormPreview.acro_overrides = payload.acro_overrides;
                }
                await this.$nextTick();
                await this.renderClaimFormPdfView();
                return true;
            } catch (e) {
                const msg = e.message || 'Could not save form edits.';
                this.toast = msg;
                return false;
            } finally {
                this.claimFormSaving = false;
            }
        },
        async renderClaimFormPdfView() {
            if (!window.ClaimFormViewer) {
                this.error = 'Claim form viewer failed to load.';
                return;
            }
            const container = document.getElementById('claim-form-pdf-pages');
            if (!container) {
                return;
            }
            if (this.claimFormType !== 'hcfa' && this.claimFormType !== 'ub04' && !this.claimFormPdfUrl) {
                return;
            }
            try {
                await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
                await window.ClaimFormViewer.render(this.claimFormPdfUrl, container, {
                    onlyFirstPage: true,
                    formType: this.claimFormType || 'hcfa',
                    ub04: this.claimFormPreview && this.claimFormPreview.ub04,
                    hcfa: this.claimFormPreview && this.claimFormPreview.hcfa,
                    acroOverrides: this.claimFormPreview && this.claimFormPreview.acro_overrides,
                });
            } catch (e) {
                this.error = e.message || 'Unable to display PDF.';
                this.toast = this.error;
                if (container) {
                    container.innerHTML = '<p class="claim-form-error">' + this.error + '</p>';
                }
            }
        },
        revokeClaimFormPdf() {
            const container = document.getElementById('claim-form-pdf-pages');
            if (container) {
                container.innerHTML = '';
            }
            if (this.claimFormPdfUrl) {
                URL.revokeObjectURL(this.claimFormPdfUrl);
                this.claimFormPdfUrl = null;
            }
        },
        closeClaimForm() {
            this.revokeClaimFormPdf();
            this.claimFormPreview = null;
            this.claimFormEncounterId = null;
            this.claimFormType = null;
            this.selectedClaimForCMS1500 = null;
        },
        async printClaimForm() {
            if (!this.claimFormEncounterId || !this.claimFormType) {
                this.toast = this.error || 'Nothing to print.';
                return;
            }
            const ok = await this.regenerateClaimFormPdf();
            if (!ok) {
                this.toast = 'Could not apply edits before printing.';
                return;
            }
            const printUrl = this.claimFormPdfUrl;
            const win = window.open(printUrl, '_blank');
            if (!win) {
                this.toast = 'Allow pop-ups to print the claim form.';
                return;
            }
            win.addEventListener('load', () => {
                win.focus();
                win.print();
            });
            this.toast = 'Claim form sent to printer.';
        },
        encounterPrimaryCpt(encounter) {
            const charge = encounter.charges && encounter.charges[0];
            if (!charge) return '—';
            return charge.cpt_code || charge.hcpcs_code || '—';
        },
        encounterChargeTotal(encounter) {
            if (!encounter.charges || !encounter.charges.length) return '$0.00';
            const total = encounter.charges.reduce((sum, c) => sum + Number(c.charge_amount || 0), 0);
            return '$' + total.toFixed(2);
        },
        autoPostERA(era) {
            era.status = 'posting';
            setTimeout(() => {
                era.status = 'posted';
                this.toast = `Posted $${era.amount.toFixed(2)} to patient account balance. EOB applied.`;
            }, 1000);
        },
        generateAIAppeal(denial) {
            denial.appeal_status = 'generating';
            setTimeout(() => {
                denial.appeal_status = 'Appealed ✉️';
                this.generatedAppealLetter = `TO: ${denial.payer} Claims Appeals Department\nDATE: ${new Date().toLocaleDateString()}\nRE: Claim Appeal for Patient ${denial.patient}\nDenial Code: ${denial.code} (${denial.description})\nClaim Amount: $${denial.amount.toFixed(2)}\n\nDear Appeals Committee,\n\nWe are formally appealing the denial of claim line CPT 99213 under denial code ${denial.code}. \nUpon reviewing the clinical documentation, the service was medically necessary and meets the criteria outlined in your policy guidelines. The clinical note details direct provider face-to-face time addressing chronic hypertension and diabetes management. \n\nWe request immediate re-adjudication and payment of the allowed amount.\n\nSincerely,\nDocRev Medical Billing Team`;
                this.showAppealModal = true;
                this.toast = 'AI Ambient Appeal Letter generated!';
            }, 1500);
        },
        billingBadge(s) {
            if (s === 'synced') return 'badge badge-green';
            if (s === 'pending' || s === 'failed') return 'badge badge-yellow';
            return 'badge';
        },
        selectMailboxThread(threadId) {
            this.selectedMailboxThreadId = threadId;
            const thread = this.mailboxThreads.find(t => t.id === threadId);
            if (thread) thread.unread = 0;
            this.mailboxReplyDraft = '';
        },
        sendMailboxReply() {
            const thread = this.selectedMailboxThread;
            const body = this.mailboxReplyDraft.trim();
            if (!thread || !body) return;
            thread.messages.push({
                id: Date.now(),
                sender: 'staff',
                author: 'Dr. David Miller',
                role: 'Provider',
                body,
                sentAt: new Date().toISOString(),
            });
            thread.updatedAt = new Date().toISOString();
            thread.unread = 0;
            this.mailboxReplyDraft = '';
            this.toast = 'Reply sent to ' + thread.patientName;
        },
        formatMailboxTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            const now = new Date();
            const sameDay = d.toDateString() === now.toDateString();
            if (sameDay) {
                return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
            }
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        },
        mailboxPreview(thread) {
            const last = thread.messages[thread.messages.length - 1];
            if (!last) return '';
            const prefix = last.sender === 'staff' ? 'You: ' : '';
            return prefix + last.body;
        },
        mailboxInitials(name) {
            return (name || '?').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
        },
    },
}).mount('#app');
