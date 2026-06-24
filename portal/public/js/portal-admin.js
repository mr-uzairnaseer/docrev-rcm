const API = '/api';
const TOKEN_KEY = 'portal_token';
const { createApp } = Vue;

createApp({
    data() {
        return {
            token: localStorage.getItem(TOKEN_KEY) || '',
            patient: {},
            appointments: [],
            statements: [],
            medications: [],
            providers: [],
            forms: [],
            selectedForm: null,
            signatureName: '',
            showSignModal: false,
            view: 'dashboard',
            calendarDate: new Date().toISOString().slice(0, 10),
            calendarDateInput: new Date().toISOString().slice(0, 10),
            apptForm: { provider_id: '', scheduled_at: '', notes: '' },
            loginForm: { email: 'jane.doe@patient.test', password: 'password' },
            // Telehealth meeting state
            isMeetingActive: false,
            meetingAppointment: null,
            meetingTimer: '00:00',
            meetingTimerInterval: null,
            meetingMessages: [],
            meetingNewMsg: '',
            isLocalCameraOn: true,
            isLocalMicOn: true,
            isProviderCameraOn: true,
            meetingSeconds: 0,
            error: '',
            toast: '',
        };
    },
    mounted() { if (this.token) this.load(); },
    methods: {
        api() { return axios.create({ baseURL: API, headers: { Authorization: 'Bearer ' + this.token } }); },
        async login() {
            this.error = '';
            try {
                const { data } = await axios.post(API + '/patient/login', this.loginForm);
                this.token = data.token;
                localStorage.setItem(TOKEN_KEY, data.token);
                this.patient = data.patient;
                await this.load();
            } catch (e) {
                this.error = (e.response && e.response.data && e.response.data.message) || 'Login failed';
            }
        },
        logout() { this.token = ''; localStorage.removeItem(TOKEN_KEY); },
        async load() {
            try {
                const me = await this.api().get('/patient/me');
                this.patient = me.data.data || me.data;
            } catch (e) {}
            try {
                const ap = await this.api().get('/patient/appointments');
                this.appointments = ap.data.data || ap.data;
            } catch (e) {}
            try {
                const st = await this.api().get('/patient/statements');
                this.statements = st.data.data || st.data;
            } catch (e) {}
            try {
                const meds = await this.api().get('/patient/medications');
                this.medications = meds.data.prescriptions || [];
            } catch (e) {}
            try {
                const provs = await this.api().get('/patient/providers');
                this.providers = provs.data.providers || [];
            } catch (e) {}
            try {
                const fRes = await this.api().get('/patient/forms');
                this.forms = fRes.data.forms || [];
            } catch (e) {}
        },
        openSignForm(form) {
            this.selectedForm = form;
            this.signatureName = '';
            this.showSignModal = true;
        },
        async submitSignature() {
            if (!this.signatureName.trim()) {
                alert('Please type your name to sign');
                return;
            }
            try {
                await this.api().post('/patient/forms/' + this.selectedForm.external_form_uuid + '/sign', {
                    signature_name: this.signatureName,
                });
                this.toast = 'Form signed successfully!';
                this.showSignModal = false;
                this.selectedForm = null;
                this.signatureName = '';
                await this.load();
            } catch (e) {
                alert((e.response && e.response.data && e.response.data.message) || 'Signature submission failed.');
            }
        },
        async submitRequest() {
            const provider = this.providers.find(p => p.id === parseInt(this.apptForm.provider_id));
            if (!provider) {
                alert('Please select a provider');
                return;
            }
            if (!this.apptForm.scheduled_at) {
                alert('Please select a date and time');
                return;
            }
            try {
                await this.api().post('/patient/appointments/request', {
                    provider_id: this.apptForm.provider_id,
                    provider_name: provider.name,
                    scheduled_at: this.apptForm.scheduled_at,
                    notes: this.apptForm.notes,
                });
                this.toast = 'Appointment requested successfully!';
                this.apptForm = { provider_id: '', scheduled_at: '', notes: '' };
                await this.load();
            } catch (e) {
                alert((e.response && e.response.data && e.response.data.message) || 'Request failed');
            }
        },
        async payStatement(statement) {
            const amount = parseFloat(statement.balance_due);
            if (!amount || amount <= 0) return;
            if (!confirm('Pay $' + amount.toFixed(2) + ' now? (Demo — no real card charged)')) return;
            try {
                const { data } = await this.api().post('/patient/pay', {
                    statement_id: statement.id,
                    amount: amount,
                });
                this.toast = data.message || 'Payment recorded.';
                await this.load();
            } catch (e) {
                this.toast = (e.response && e.response.data && e.response.data.message) || 'Payment failed.';
            }
        },
        startTelehealthMeeting(appt) {
            this.isMeetingActive = true;
            this.meetingAppointment = appt;
            this.meetingMessages = [
                { sender: 'System', text: 'Connecting to secure telehealth room...', time: new Date().toLocaleTimeString() },
                { sender: 'System', text: 'Doctor has connected.', time: new Date().toLocaleTimeString() },
                { sender: 'Doctor', text: 'Hello Jane! How can I help you today?', time: new Date().toLocaleTimeString() }
            ];
            this.meetingSeconds = 0;
            this.meetingTimer = '00:00';
            if (this.meetingTimerInterval) clearInterval(this.meetingTimerInterval);
            this.meetingTimerInterval = setInterval(() => {
                this.meetingSeconds++;
                const mins = String(Math.floor(this.meetingSeconds / 60)).padStart(2, '0');
                const secs = String(this.meetingSeconds % 60).padStart(2, '0');
                this.meetingTimer = `${mins}:${secs}`;
            }, 1000);
        },
        endTelehealthMeeting() {
            this.isMeetingActive = false;
            if (this.meetingTimerInterval) clearInterval(this.meetingTimerInterval);
            this.meetingTimerInterval = null;
            this.meetingAppointment = null;
            this.toast = 'Left telehealth virtual room.';
        },
        sendMeetingMessage() {
            if (!this.meetingNewMsg.trim()) return;
            this.meetingMessages.push({
                sender: 'Patient',
                text: this.meetingNewMsg,
                time: new Date().toLocaleTimeString()
            });
            const patientMsg = this.meetingNewMsg.toLowerCase();
            this.meetingNewMsg = '';
            
            // Auto reply mockup from provider
            setTimeout(() => {
                let reply = "Let me make a note of that in your chart.";
                if (patientMsg.includes("cough") || patientMsg.includes("sick")) {
                    reply = "Are you experiencing any shortness of breath or fever as well?";
                } else if (patientMsg.includes("no") || patientMsg.includes("don't have")) {
                    reply = "That is good. It sounds like an upper respiratory infection. I will write a prescription for Lisinopril for your blood pressure and suggest rest.";
                } else if (patientMsg.includes("thank") || patientMsg.includes("bye")) {
                    reply = "You're welcome! Take care and call if symptoms get worse.";
                }
                this.meetingMessages.push({
                    sender: 'Doctor',
                    text: reply,
                    time: new Date().toLocaleTimeString()
                });
            }, 1500);
        },
        formatDate(d) { return d ? new Date(d).toLocaleString() : '—'; },
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
        getApptsForDate() {
            const selDate = new Date(this.calendarDate).toDateString();
            return this.appointments.filter(a => {
                return a.status !== 'requested' && new Date(a.scheduled_at).toDateString() === selDate;
            });
        },
    },
}).mount('#app');
