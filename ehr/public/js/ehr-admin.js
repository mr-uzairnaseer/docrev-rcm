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
            apptTab: 'scheduled',
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
        };
    },
    computed: {
        syncedCount() { return this.encounters.filter(e => e.billing_sync_status === 'synced').length; },
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
        setView(v) { 
            this.tabLoading = true;
            this.view = v; 
            setTimeout(async () => {
                await this.refreshView();
                this.tabLoading = false;
            }, 350);
        },
        async refreshView() {
            if (this.view === 'prescriptions') await this.loadPrescriptions();
            else if (this.view === 'labs') await this.loadLabs();
            else if (this.view === 'hie') await this.loadHie();
            else if (this.view === 'integrations') await this.loadIntegrations();
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
        async loadEncounters() {
            const { data } = await this.api().get('/encounters?per_page=100');
            this.encounters = data.data;
        },
        async loadAppointments() {
            const { data } = await this.api().get('/appointments?per_page=50&from=' + new Date().toISOString().slice(0, 10));
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
            const { data } = await this.api().get('/integration/requirements');
            this.requirements = data;
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
            this.view = 'patients';
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
        billingBadge(s) {
            if (s === 'synced') return 'badge badge-green';
            if (s === 'pending' || s === 'failed') return 'badge badge-yellow';
            return 'badge';
        },
    },
}).mount('#app');
