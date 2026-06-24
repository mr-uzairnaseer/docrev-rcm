<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocRev EHR</title>
    <link rel="icon" href="/img/logo.png" type="image/png">
    <link rel="icon" href="/img/logo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/img/logo.png">
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="/css/docrev-theme.css">
</head>
<body>
<div id="app">
@verbatim
    <div v-if="!token" class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <img src="/img/logo.png" alt="DocRev" class="docrev-brand-logo">
                <p class="docrev-brand-product">EHR</p>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input v-model="loginForm.email" type="email" @keyup.enter="login">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input v-model="loginForm.password" type="password" @keyup.enter="login">
            </div>
            <button class="btn btn-primary" style="width:100%" @click="login" :disabled="loading">Sign In</button>
            <p v-if="error" class="error">{{ error }}</p>
        </div>
    </div>

    <div v-else class="layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="/img/logo.png" alt="DocRev" class="docrev-brand-logo">
                <span class="docrev-brand-product">EHR</span>
            </div>
            <nav>
                <a :class="{active: view==='dashboard'}" @click="view='dashboard';load()">Dashboard</a>
                <a :class="{active: view==='patients'}" @click="view='patients';loadPatients()">Patients</a>
                <a :class="{active: view==='encounters'}" @click="view='encounters';loadEncounters()">Encounters</a>
                <a :class="{active: view==='appointments'}" @click="view='appointments';loadAppointments()">Appointments</a>
                <a :class="{active: view==='prescriptions'}" @click="setView('prescriptions')">E-Prescribing</a>
                <a :class="{active: view==='labs'}" @click="setView('labs')">Labs</a>
                <a :class="{active: view==='hie'}" @click="setView('hie')">HIE / FHIR</a>
                <a :class="{active: view==='billing-rcm'}" @click="setView('billing-rcm')">Billing / RCM</a>
                <a :class="{active: view==='messages'}" @click="setView('messages')">Messages &amp; Inbox</a>
                <a :class="{active: view==='reports'}" @click="setView('reports')">Reports &amp; Analytics</a>
                <a :class="{active: view==='integrations'}" @click="setView('integrations')">Integrations</a>
                <a :class="{active: view==='new-patient'}" @click="view='new-patient'">+ Patient</a>
                <a :class="{active: view==='new-encounter'}" @click="openNewEncounter()">+ Encounter</a>
            </nav>
            <div class="sidebar-footer">
                <a @click="logout" class="logout">Logout</a>
            </div>
        </aside>
        <main class="main">
            <p v-if="toast" class="toast">{{ toast }}</p>
            <div v-if="view==='dashboard'">
                <div class="stats">
                    <div class="stat"><div class="num">{{ patients.length }}</div><div class="label">Patients</div></div>
                    <div class="stat"><div class="num">{{ encounters.length }}</div><div class="label">Encounters</div></div>
                    <div class="stat"><div class="num">{{ appointments.length }}</div><div class="label">Appointments</div></div>
                    <div class="stat"><div class="num">{{ syncedCount }}</div><div class="label">Billing Synced</div></div>
                </div>
                <div class="card">
                    <h2>Recent Encounters</h2>
                    <table>
                        <thead><tr><th>Patient</th><th>Date</th><th>Status</th><th>Billing</th></tr></thead>
                        <tbody>
                            <tr v-for="e in encounters.slice(0,5)" :key="e.id">
                                <td>{{ (e.patient && e.patient.full_name) || '—' }}</td>
                                <td>{{ formatDate(e.encounter_date) }}</td>
                                <td><span class="badge badge-blue">{{ e.status }}</span></td>
                                <td><span :class="billingBadge(e.billing_sync_status)">{{ e.billing_sync_status || '—' }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="view==='patients'" class="card">
                <div class="row-between">
                    <h2>Patients Directory</h2>
                    <button class="btn btn-primary" @click="view='new-patient'">Add Patient</button>
                </div>
                
                <!-- Patient Sub-navigation & Details Tab View -->
                <div class="portal-nav" style="margin: 1rem 0; display:flex; gap:0.5rem; flex-wrap: wrap; background: #f7fafc; padding: 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <button class="btn btn-sm" :class="patientTab==='directory'?'btn-primary':''" @click="patientTab='directory'">All Patients</button>
                    <button class="btn btn-sm" :class="patientTab==='demographics'?'btn-primary':''" @click="patientTab='demographics'" :disabled="!selectedPatient">Patient Demographics</button>
                    <button class="btn btn-sm" :class="patientTab==='insurance'?'btn-primary':''" @click="patientTab='insurance'" :disabled="!selectedPatient">Insurance / Eligibility</button>
                    <button class="btn btn-sm" :class="patientTab==='care-team'?'btn-primary':''" @click="patientTab='care-team'" :disabled="!selectedPatient">Care Team</button>
                    <button class="btn btn-sm" :class="patientTab==='problems'?'btn-primary':''" @click="patientTab='problems'" :disabled="!selectedPatient">Problem List</button>
                    <button class="btn btn-sm" :class="patientTab==='medications'?'btn-primary':''" @click="patientTab='medications'" :disabled="!selectedPatient">Medications</button>
                    <button class="btn btn-sm" :class="patientTab==='allergies'?'btn-primary':''" @click="patientTab='allergies'" :disabled="!selectedPatient">Allergies</button>
                    <button class="btn btn-sm" :class="patientTab==='history'?'btn-primary':''" @click="patientTab='history'" :disabled="!selectedPatient">Visit History</button>
                </div>

                <!-- All Patients Directory -->
                <div v-if="patientTab==='directory'">
                    <table>
                        <thead><tr><th>Name</th><th>MRN</th><th>DOB</th><th>Phone</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="p in patients" :key="p.id" :style="selectedPatient && selectedPatient.id === p.id ? 'background:#e6fffa; border-left:4px solid #319795' : ''">
                                <td><strong>{{ p.full_name }}</strong></td>
                                <td>{{ p.mrn || '—' }}</td>
                                <td>{{ p.date_of_birth }}</td>
                                <td>{{ p.phone || '—' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" @click="selectedPatient=p; patientTab='demographics'" style="padding:0.25rem 0.5rem">Select Clinical File</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Patient Demographics Sub-tab -->
                <div v-if="patientTab==='demographics' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Demographics: {{ selectedPatient.full_name }}</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-top:1rem">
                        <div><span style="font-weight:bold; color:#718096">First Name:</span> <p style="font-size:1.1rem">{{ selectedPatient.first_name }}</p></div>
                        <div><span style="font-weight:bold; color:#718096">Last Name:</span> <p style="font-size:1.1rem">{{ selectedPatient.last_name }}</p></div>
                        <div><span style="font-weight:bold; color:#718096">Date of Birth:</span> <p style="font-size:1.1rem">{{ selectedPatient.date_of_birth }}</p></div>
                        <div><span style="font-weight:bold; color:#718096">Medical Record Number (MRN):</span> <p style="font-size:1.1rem">{{ selectedPatient.mrn || 'N/A' }}</p></div>
                        <div><span style="font-weight:bold; color:#718096">Email:</span> <p style="font-size:1.1rem">{{ selectedPatient.email }}</p></div>
                        <div><span style="font-weight:bold; color:#718096">Phone:</span> <p style="font-size:1.1rem">{{ selectedPatient.phone || '—' }}</p></div>
                    </div>
                </div>

                <!-- Insurance & Eligibility Sub-tab -->
                <div v-if="patientTab==='insurance' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Insurance &amp; Eligibility: {{ selectedPatient.full_name }}</h3>
                    <div style="margin-top:1rem; display:flex; gap:1.5rem; flex-wrap:wrap">
                        <div style="flex:1; background:#f7fafc; padding:1rem; border-radius:6px; border:1px solid #e2e8f0">
                            <h4 style="margin-top:0">Primary Insurance Policy</h4>
                            <p style="margin:0.25rem 0"><strong>Payer:</strong> UnitedHealthcare (UHC)</p>
                            <p style="margin:0.25rem 0"><strong>Member ID:</strong> UHC-{{ selectedPatient.id }}09876</p>
                            <p style="margin:0.25rem 0"><strong>Group #:</strong> 9008127</p>
                        </div>
                        <div style="flex:1; background:#ebf8ff; padding:1rem; border-radius:6px; border:1px solid #bee3f8; display:flex; flex-direction:column; justify-content:center; align-items:center">
                            <span class="badge badge-green" style="font-size:1rem; padding:0.4rem 0.8rem; margin-bottom:0.5rem">Coverage Status: ACTIVE ✅</span>
                            <span style="font-size:0.9rem; color:#4a5568">Copay Amount: $20.00</span>
                        </div>
                    </div>
                </div>

                <!-- Care Team Sub-tab -->
                <div v-if="patientTab==='care-team' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Care Team: {{ selectedPatient.full_name }}</h3>
                    <table style="margin-top:1rem">
                        <thead><tr><th>Name</th><th>Role / Specialty</th><th>Contact</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>Dr. David Miller</strong></td>
                                <td>Primary Care Physician (PCP)</td>
                                <td>doctor.miller@demo-medical.test</td>
                            </tr>
                            <tr>
                                <td><strong>Sarah Jenkins, NP</strong></td>
                                <td>Nurse Practitioner</td>
                                <td>np.jenkins@demo-medical.test</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Problem List Sub-tab -->
                <div v-if="patientTab==='problems' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Clinical Problem List: {{ selectedPatient.full_name }}</h3>
                    <table style="margin-top:1rem">
                        <thead><tr><th>ICD-10</th><th>Problem Description</th><th>Onset Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-blue">I10</span></td>
                                <td>Essential (primary) hypertension</td>
                                <td>2025-03-12</td>
                                <td><span class="badge badge-green">Active</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-blue">E11.9</span></td>
                                <td>Type 2 diabetes mellitus without complications</td>
                                <td>2025-11-20</td>
                                <td><span class="badge badge-green">Active</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Medications Sub-tab -->
                <div v-if="patientTab==='medications' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Active Medications: {{ selectedPatient.full_name }}</h3>
                    <table style="margin-top:1rem">
                        <thead><tr><th>Medication</th><th>Instructions</th><th>Prescriber</th><th>Refills Left</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>Lisinopril 10mg</strong></td>
                                <td>Take 1 tablet by mouth daily</td>
                                <td>Dr. David Miller</td>
                                <td>3 refills</td>
                            </tr>
                            <tr>
                                <td><strong>Metformin 500mg</strong></td>
                                <td>Take 1 tablet twice daily with meals</td>
                                <td>Dr. David Miller</td>
                                <td>5 refills</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Allergies Sub-tab -->
                <div v-if="patientTab==='allergies' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Allergies &amp; Intolerances: {{ selectedPatient.full_name }}</h3>
                    <div style="margin-top:1rem">
                        <label style="font-weight:bold; display:block">Active Allergies</label>
                        <div style="display:flex; gap:0.5rem; margin-top:0.5rem">
                            <input v-model="selectedPatient.allergies" style="padding:0.4rem 0.5rem; border-radius:4px; border:1px solid #cbd5e0; flex:1" placeholder="e.g. Penicillin, Peanuts">
                            <button class="btn btn-sm btn-primary" @click="savePatientAllergies(selectedPatient)">Save Allergies</button>
                        </div>
                    </div>
                </div>

                <!-- Visit History Sub-tab -->
                <div v-if="patientTab==='history' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Visit History: {{ selectedPatient.full_name }}</h3>
                    <table style="margin-top:1rem">
                        <thead><tr><th>Date</th><th>Encounter Type</th><th>Provider</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="e in encounters.filter(enc => enc.patient_id === selectedPatient.id)" :key="e.id">
                                <td>{{ formatDate(e.encounter_date) }}</td>
                                <td>Standard Encounter (#{{ e.id }})</td>
                                <td>{{ e.provider ? e.provider.first_name+' '+e.provider.last_name : '—' }}</td>
                                <td><span class="badge badge-green">{{ e.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="view==='encounters'" class="card">
                <div class="row-between"><h2>Encounters</h2><button class="btn btn-primary" @click="openNewEncounter()">New Encounter</button></div>
                <table>
                    <thead><tr><th>Patient</th><th>Date</th><th>Status</th><th>Billing</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="e in encounters" :key="e.id">
                            <td>{{ (e.patient && e.patient.full_name) || '—' }}</td>
                            <td>{{ formatDate(e.encounter_date) }}</td>
                            <td><span class="badge badge-blue">{{ e.status }}</span></td>
                            <td><span :class="billingBadge(e.billing_sync_status)">{{ e.billing_sync_status || 'n/a' }}</span></td>
                            <td><button v-if="!e.signed_at" class="btn btn-primary" @click="openEncounter(e.id)">Chart</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='new-patient'" class="card">
                <h2>Register Patient</h2>
                <div class="form-row">
                    <div class="form-group"><label>First Name</label><input v-model="patientForm.first_name"></div>
                    <div class="form-group"><label>Last Name</label><input v-model="patientForm.last_name"></div>
                    <div class="form-group"><label>DOB</label><input v-model="patientForm.date_of_birth" type="date"></div>
                    <div class="form-group"><label>MRN</label><input v-model="patientForm.mrn"></div>
                    <div class="form-group"><label>Email</label><input v-model="patientForm.email" type="email"></div>
                    <div class="form-group"><label>Phone</label><input v-model="patientForm.phone"></div>
                </div>
                <button class="btn btn-primary" @click="createPatient">Save Patient (syncs to Billing & Portal)</button>
            </div>

            <div v-if="view==='appointments'" class="card">
                <div class="row-between">
                    <h2>Appointments</h2>
                    <button class="btn btn-primary" @click="showApptForm = !showApptForm">Schedule</button>
                </div>
                <div v-if="showApptForm" class="panel">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Patient</label>
                            <select v-model="apptForm.patient_id">
                                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name || (p.first_name + ' ' + p.last_name) }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Provider</label>
                            <select v-model="apptForm.provider_id">
                                <option v-for="pr in providers" :key="pr.id" :value="pr.id">{{ pr.first_name }} {{ pr.last_name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Visit Type</label>
                            <select v-model="apptForm.appointment_type">
                                <option value="office_visit">Office Visit</option>
                                <option value="telehealth">Telehealth (Video Visit)</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Date/Time</label><input v-model="apptForm.scheduled_at" type="datetime-local"></div>
                    </div>
                    <button class="btn btn-primary" @click="createAppointment">Book & Sync to Portal</button>
                </div>

                <!-- Telehealth Video Meeting Room -->
                <div v-if="isMeetingActive" class="panel" style="background:#1a202c; color:white; border-radius:8px; padding:1.5rem; margin-bottom:1.5rem; position:relative; box-shadow: 0 10px 25px rgba(0,0,0,0.3)">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #4a5568; padding-bottom:0.75rem">
                        <div style="display:flex; align-items:center; gap:0.5rem">
                            <span style="color:#e53e3e; font-size:1.5rem; animation: pulse 1.5s infinite">🔴</span>
                            <h3 style="margin:0; color:white">Telehealth Video Consult: {{ meetingAppointment ? meetingAppointment.patient.first_name + ' ' + meetingAppointment.patient.last_name : '' }}</h3>
                        </div>
                        <div style="display:flex; align-items:center; gap:1rem">
                            <span style="background:#2d3748; padding:0.25rem 0.75rem; border-radius:4px; font-family:monospace; font-size:0.9rem">{{ meetingTimer }}</span>
                            <span class="badge" style="background:#38a169; color:white">HD Quality 📶</span>
                        </div>
                    </div>

                    <!-- Video Grid Mockup -->
                    <div style="display:flex; gap:1rem; height:320px; margin-bottom:1rem">
                        <!-- Patient Remote Stream -->
                        <div style="flex:1; background:#2d3748; border-radius:6px; overflow:hidden; position:relative; display:flex; justify-content:center; align-items:center; border:2px solid #4a5568">
                            <div v-if="isPatientCameraOn" style="text-align:center">
                                <span style="font-size:4rem">🤢</span>
                                <p style="margin-top:0.5rem; color:#cbd5e0; font-size:0.9rem">Patient (Jane Doe) - Video Feed</p>
                            </div>
                            <div v-else style="text-align:center; color:#a0aec0">
                                <span style="font-size:3rem">🔇</span>
                                <p style="margin-top:0.5rem; font-size:0.9rem">Patient camera is off</p>
                            </div>
                            <span style="position:absolute; bottom:0.5rem; left:0.5rem; background:rgba(0,0,0,0.6); padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem">Patient (Remote)</span>
                        </div>
                        <!-- Provider Local Stream -->
                        <div style="flex:1; background:#2d3748; border-radius:6px; overflow:hidden; position:relative; display:flex; justify-content:center; align-items:center; border:2px solid #3182ce">
                            <div v-if="isLocalCameraOn" style="text-align:center">
                                <span style="font-size:4rem">👨‍⚕️</span>
                                <p style="margin-top:0.5rem; color:#cbd5e0; font-size:0.9rem">You (Provider) - Preview</p>
                            </div>
                            <div v-else style="text-align:center; color:#a0aec0">
                                <span style="font-size:3rem">🎥</span>
                                <p style="margin-top:0.5rem; font-size:0.9rem">Your camera is off</p>
                            </div>
                            <span style="position:absolute; bottom:0.5rem; left:0.5rem; background:rgba(0,0,0,0.6); padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem">You (Local)</span>
                        </div>
                        
                        <!-- Chat Side Panel -->
                        <div style="width:280px; background:#2d3748; border-radius:6px; border:1px solid #4a5568; display:flex; flex-direction:column; overflow:hidden">
                            <div style="background:#1a202c; padding:0.5rem; font-weight:bold; font-size:0.85rem; border-bottom:1px solid #4a5568">Live Consult Chat</div>
                            <div style="flex:1; padding:0.5rem; overflow-y:auto; font-size:0.85rem; display:flex; flex-direction:column; gap:0.5rem">
                                <div v-for="msg in meetingMessages" :key="msg.time" :style="{textAlign: msg.sender==='Provider'?'right':'left'}">
                                    <span style="color:#a0aec0; font-size:0.7rem">{{ msg.sender }} - {{ msg.time }}</span>
                                    <div :style="{background: msg.sender==='Provider'?'#3182ce':'#4a5568', padding: '0.4rem 0.6rem', borderRadius: '6px', display: 'inline-block', maxWidth: '85%', marginTop: '0.1rem', wordBreak: 'break-word', color: 'white'}">
                                        {{ msg.text }}
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; border-top:1px solid #4a5568; background:#1a202c">
                                <input v-model="meetingNewMsg" @keyup.enter="sendMeetingMessage" placeholder="Type message..." style="flex:1; background:transparent; border:none; padding:0.5rem; color:white; outline:none; font-size:0.85rem">
                                <button @click="sendMeetingMessage" style="background:#3182ce; border:none; color:white; padding:0 0.75rem; cursor:pointer">Send</button>
                            </div>
                        </div>
                    </div>

                    <!-- Call Control Bar -->
                    <div style="display:flex; justify-content:center; gap:1rem; border-top:1px solid #4a5568; padding-top:1rem">
                        <button class="btn btn-sm" :class="isLocalCameraOn?'btn-primary':'btn-secondary'" @click="isLocalCameraOn=!isLocalCameraOn" style="background:none; border:1px solid #cbd5e0; color:white">
                            {{ isLocalCameraOn ? '📹 Stop Camera' : '🎥 Start Camera' }}
                        </button>
                        <button class="btn btn-sm" :class="isLocalMicOn?'btn-primary':'btn-secondary'" @click="isLocalMicOn=!isLocalMicOn" style="background:none; border:1px solid #cbd5e0; color:white">
                            {{ isLocalMicOn ? '🎙️ Mute' : '🔇 Unmute' }}
                        </button>
                        <button class="btn btn-sm" style="background:#e53e3e; border:none; color:white" @click="endTelehealthMeeting">🔴 End Consultation</button>
                    </div>
                </div>

                <div class="portal-nav" style="margin: 1rem 0; display:flex; gap:0.5rem">
                    <button class="btn btn-sm" :class="apptTab==='scheduled'?'btn-primary':''" @click="apptTab='scheduled'">Scheduled Appointments</button>
                    <button class="btn btn-sm" :class="apptTab==='requested'?'btn-primary':''" @click="apptTab='requested'">Patient Requests ({{ appointments.filter(a=>a.status==='requested').length }})</button>
                </div>

                <div v-if="apptTab==='scheduled'">
                    <table>
                        <thead><tr><th>Patient</th><th>Provider</th><th>When</th><th>Type</th><th>Status</th><th>Portal</th><th>Encounter</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="a in appointments.filter(x=>x.status!=='requested')" :key="a.id">
                                <td>{{ a.patient ? (a.patient.first_name + ' ' + a.patient.last_name) : '—' }}</td>
                                <td>{{ a.provider ? (a.provider.first_name + ' ' + a.provider.last_name) : '—' }}</td>
                                <td>{{ formatDateTime(a.scheduled_at) }}</td>
                                <td><span class="badge" :class="a.appointment_type==='telehealth'?'badge-blue':'badge-secondary'">{{ a.appointment_type }}</span></td>
                                <td><span class="badge badge-blue">{{ a.status }}</span></td>
                                <td><span class="badge badge-green">{{ a.portal_sync_status || '—' }}</span></td>
                                <td>
                                    <button v-if="a.encounter_id" class="btn btn-sm btn-primary" @click="openEncounter(a.encounter_id)">#{{ a.encounter_id }}</button>
                                    <span v-else>—</span>
                                </td>
                                <td>
                                    <button v-if="a.appointment_type==='telehealth' && a.status==='scheduled'" class="btn btn-sm btn-primary" @click="startTelehealthMeeting(a)" style="background:#38a169; border-color:#38a169; margin-right:0.25rem">Launch Video Room</button>
                                    <button v-if="a.status==='scheduled'" class="btn btn-sm btn-primary" @click="checkInAppt(a.id)">Check In</button>
                                    <button v-if="a.status==='scheduled'" class="btn btn-sm" @click="cancelAppt(a.id)">Cancel</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="apptTab==='requested'">
                    <table>
                        <thead><tr><th>Patient</th><th>Provider</th><th>When</th><th>Notes</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="a in appointments.filter(x=>x.status==='requested')" :key="a.id">
                                <td>{{ a.patient ? (a.patient.first_name + ' ' + a.patient.last_name) : '—' }}</td>
                                <td>{{ a.provider ? (a.provider.first_name + ' ' + a.provider.last_name) : '—' }}</td>
                                <td>{{ formatDateTime(a.scheduled_at) }}</td>
                                <td>{{ a.notes || '—' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" @click="approveAppt(a.id)">Approve</button>
                                    <button class="btn btn-sm" style="background:#e53e3e; color:white;" @click="declineAppt(a.id)">Decline</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!appointments.filter(x=>x.status==='requested').length" style="color:#718096; margin-top:1rem">No pending appointment requests.</p>
                </div>
            </div>

            <div v-if="view==='new-encounter' && activeEncounter" class="card">
                <h2>Chart Encounter #{{ activeEncounter.id }}</h2>

                <div class="panel" style="background:#f7fafc; border-left: 4px solid #4a5568; margin-top:1rem">
                    <h3 style="color:#2d3748">Patient Health History: {{ activeEncounter.patient ? activeEncounter.patient.full_name : '' }}</h3>
                    <div style="margin-top:0.75rem">
                        <label style="font-weight:bold; display:block">Active Allergies</label>
                        <div style="display:flex; gap:0.5rem; margin-top:0.25rem">
                            <input v-model="activeEncounter.patient.allergies" style="padding:0.4rem 0.5rem; border-radius:4px; border:1px solid #cbd5e0; flex:1" placeholder="e.g. Penicillin, Peanuts">
                            <button class="btn btn-sm" @click="savePatientAllergies(activeEncounter.patient)">Save Allergies</button>
                        </div>
                    </div>
                    <div style="margin-top:1rem">
                        <label style="font-weight:bold; display:block">Prescriptions History</label>
                        <ul v-if="patientPrescriptions && patientPrescriptions.length" style="padding-left:1.25rem; margin-top:0.25rem; font-size:0.9rem">
                            <li v-for="rx in patientPrescriptions" :key="rx.id">
                                <strong>{{ rx.drug_name }}</strong> ({{ rx.sig }}) - status: <span class="badge">{{ rx.status }}</span>
                            </li>
                        </ul>
                        <p v-else style="color:#718096; font-size:0.9rem; margin-top:0.25rem">No clinical prescriptions history on file.</p>
                    </div>
                </div>

                <!-- Forms on Demand Panel -->
                <div class="panel" style="background:#f7fafc; border-left: 4px solid #3182ce; margin-top:1rem">
                    <div style="display:flex; justify-content:space-between; align-items:center">
                        <h3 style="color:#2d3748">Patient Forms on Demand (Digital Signatures)</h3>
                        <button class="btn btn-sm btn-primary" @click="showFormsForm = !showFormsForm">Send Form</button>
                    </div>
                    
                    <div v-if="showFormsForm" style="background:#edf2f7; padding:1rem; border-radius:4px; margin-top:0.75rem">
                        <label style="font-weight:bold; display:block">Select Form Template</label>
                        <div style="display:flex; gap:0.5rem; margin-top:0.25rem">
                            <select v-model="selectedFormTemplate" style="padding:0.4rem 0.5rem; border-radius:4px; border:1px solid #cbd5e0; flex:1">
                                <option value="">-- Choose a Form --</option>
                                <option value="HIPAA Privacy Consent">HIPAA Privacy Consent</option>
                                <option value="COVID-19 Health Screening">COVID-19 Health Screening</option>
                                <option value="Patient Intake & History">Patient Intake &amp; History</option>
                            </select>
                            <button class="btn btn-sm btn-primary" @click="sendFormOnDemand">Send to Portal</button>
                        </div>
                    </div>

                    <div style="margin-top:1rem">
                        <label style="font-weight:bold; display:block">Forms History</label>
                        <ul v-if="patientForms && patientForms.length" style="padding-left:1.25rem; margin-top:0.25rem; font-size:0.9rem">
                            <li v-for="f in patientForms" :key="f.id">
                                <strong>{{ f.form_name }}</strong> - status: 
                                <span class="badge" :class="f.status==='signed'?'badge-green':'badge-blue'">{{ f.status }}</span>
                                <span v-if="f.status==='signed'"> (Signed by: <code>{{ f.signature_name }}</code> at {{ formatDate(f.signed_at) }})</span>
                            </li>
                        </ul>
                        <p v-else style="color:#718096; font-size:0.9rem; margin-top:0.25rem">No forms sent yet.</p>
                    </div>
                </div>

                <!-- AI Multilingual Ambient Scribe Panel -->
                <div class="panel" style="background:#f0fff4; border-left: 4px solid #38a169; margin-top:1rem">
                    <h3 style="color:#276749">AI Multilingual Ambient Scribe</h3>
                    <p style="font-size:0.85rem; color:#2f855a; margin-top:0.25rem">AthenaOne intelligent AI ambient notes capture in multiple languages.</p>
                    
                    <div style="display:flex; gap:1rem; align-items:center; margin-top:1rem">
                        <div class="form-group" style="margin:0">
                            <select v-model="scribeLanguage" style="padding:0.4rem 0.5rem; border-radius:4px; border:1px solid #cbd5e0;">
                                <option value="en">English (US)</option>
                                <option value="es">Español (Spanish)</option>
                                <option value="fr">Français (French)</option>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm" :disabled="isScribing" @click="runAmbientScribe" style="background:#38a169; border-color:#38a169">
                            {{ isScribing ? 'Scribing...' : 'Start Ambient Scribe' }}
                        </button>
                    </div>
                    
                    <div v-if="isScribing || scribeProgress" style="margin-top:1rem; background:#e6fffa; padding:0.75rem; border-radius:4px; border:1px solid #b2f5ea; color:#00a389; font-size:0.9rem">
                        <span class="spinner" style="display:inline-block; margin-right:0.5rem">🎙️</span> {{ scribeProgress }}
                    </div>
                </div>

                <!-- Encounter Notes Panel -->
                <div class="panel">
                    <h3>Encounter Notes &amp; Findings</h3>
                    <textarea v-model="activeEncounter.clinical_notes" rows="6" style="width:100%; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e0; font-family:inherit; margin-top:0.5rem" placeholder="Type clinical findings or generate via AI Ambient Scribe..."></textarea>
                </div>

                <div class="panel">
                    <h3>Diagnoses</h3>
                    <div class="form-row">
                        <div class="form-group"><label>ICD-10</label><input v-model="dxForm.icd10_code"></div>
                        <div class="form-group"><label>Description</label><input v-model="dxForm.description"></div>
                    </div>
                    <button class="btn btn-primary" @click="addDiagnosis">Add</button>
                    <ul style="margin-top:0.5rem"><li v-for="d in activeEncounter.diagnoses || []" :key="d.id">{{ d.icd10_code }}</li></ul>
                </div>
                <div class="panel">
                    <h3>Charges</h3>
                    <div class="form-row">
                        <div class="form-group"><label>CPT</label><input v-model="chargeForm.cpt_code"></div>
                        <div class="form-group"><label>Amount</label><input v-model="chargeForm.charge_amount" type="number"></div>
                    </div>
                    <button class="btn btn-primary" @click="addCharge">Add</button>
                    <ul style="margin-top:0.5rem"><li v-for="c in activeEncounter.charges || []" :key="c.id">{{ c.cpt_code }} ${{ c.charge_amount }}</li></ul>
                </div>
                <button class="btn btn-primary" @click="signEncounter">Sign & Sync to Billing</button>
            </div>

            <div v-if="view==='prescriptions'" class="card">
                <div class="row-between">
                    <h2>E-Prescribing (Surescripts)</h2>
                    <button class="btn btn-primary" @click="showRxForm = !showRxForm">New Rx</button>
                </div>
                <div v-if="showRxForm" class="panel">
                    <div class="form-row">
                        <div class="form-group"><label>Patient</label><select v-model="rxForm.patient_id"><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name || (p.first_name+' '+p.last_name) }}</option></select></div>
                        <div class="form-group"><label>Provider</label><select v-model="rxForm.provider_id"><option v-for="pr in providers" :key="pr.id" :value="pr.id">{{ pr.first_name }} {{ pr.last_name }}</option></select></div>
                        <div class="form-group"><label>Pharmacy</label><select v-model="rxForm.pharmacy_id"><option v-for="ph in pharmacies" :key="ph.id" :value="ph.id">{{ ph.name }}</option></select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Drug</label><input v-model="rxForm.drug_name"></div>
                        <div class="form-group"><label>NDC</label><input v-model="rxForm.ndc"></div>
                        <div class="form-group"><label>Qty</label><input v-model="rxForm.quantity" type="number"></div>
                    </div>
                    <div class="form-group"><label>Sig</label><input v-model="rxForm.sig"></div>
                    <button class="btn btn-primary" @click="createPrescription">Create Draft</button>
                </div>
                <h3 style="margin-top:1rem">Prescriptions</h3>
                <table>
                    <thead><tr><th>Patient</th><th>Drug</th><th>Sig</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="rx in prescriptions" :key="rx.id">
                            <td>{{ rx.patient ? rx.patient.first_name+' '+rx.patient.last_name : '—' }}</td>
                            <td>{{ rx.drug_name }}</td>
                            <td>{{ rx.sig }}</td>
                            <td><span class="badge">{{ rx.status }}</span></td>
                            <td><button v-if="rx.status==='draft'" class="btn btn-sm btn-primary" @click="sendPrescription(rx.id)">Send to Surescripts</button></td>
                        </tr>
                    </tbody>
                </table>
                <h3 style="margin-top:1.5rem">Surescripts Enrollment</h3>
                <table>
                    <thead><tr><th>Provider</th><th>SPI</th><th>DEA</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr v-for="e in enrollments" :key="e.id">
                            <td>{{ e.provider ? e.provider.first_name+' '+e.provider.last_name : '—' }}</td>
                            <td>{{ e.spi || '—' }}</td>
                            <td>{{ e.dea_number || '—' }}</td>
                            <td><span class="badge badge-green">{{ e.status }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='labs'" class="card">
                <div class="row-between">
                    <h2>Lab Orders (HL7)</h2>
                    <button class="btn btn-primary" @click="showLabForm = !showLabForm">Order Lab</button>
                </div>
                <div v-if="showLabForm" class="panel">
                    <div class="form-row">
                        <div class="form-group"><label>Patient</label><select v-model="labForm.patient_id"><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option></select></div>
                        <div class="form-group"><label>Vendor</label><select v-model="labForm.lab_vendor_id"><option v-for="v in labVendors" :key="v.id" :value="v.id">{{ v.name }}</option></select></div>
                        <div class="form-group"><label>Test Code</label><input v-model="labForm.test_code" placeholder="80053"></div>
                        <div class="form-group"><label>Test Name</label><input v-model="labForm.test_name" placeholder="Comprehensive Metabolic Panel"></div>
                    </div>
                    <button class="btn btn-primary" @click="createLabOrder">Create Order</button>
                </div>
                <table>
                    <thead><tr><th>Patient</th><th>Test</th><th>Vendor</th><th>Status</th><th>Results</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="o in labOrders" :key="o.id">
                            <td>{{ o.patient ? o.patient.first_name+' '+o.patient.last_name : '—' }}</td>
                            <td>{{ o.test_name }} ({{ o.test_code }})</td>
                            <td>{{ o.lab_vendor ? o.lab_vendor.name : '—' }}</td>
                            <td><span class="badge">{{ o.status }}</span></td>
                            <td>{{ o.results && o.results.length ? o.results.map(r=>r.value+' '+r.unit).join(', ') : '—' }}</td>
                            <td>
                                <button v-if="o.status==='ordered'" class="btn btn-sm btn-primary" @click="sendLabOrder(o.id)">Send HL7</button>
                                <button v-if="o.status==='sent'" class="btn btn-sm" @click="simulateLabResults(o.id)">Simulate Results</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='hie'" class="card">
                <h2>HIE / FHIR Exchange</h2>
                <div class="panel">
                    <h3>Connections</h3>
                    <table>
                        <thead><tr><th>Network</th><th>Type</th><th>Status</th><th>Agreement</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="c in hieConnections" :key="c.id">
                                <td>{{ c.name }}</td>
                                <td>{{ c.network_type }}</td>
                                <td><span class="badge">{{ c.status }}</span></td>
                                <td>{{ c.agreement_signed_at ? formatDate(c.agreement_signed_at) : 'Pending' }}</td>
                                <td>
                                    <button class="btn btn-sm" @click="queryHie(c.id)">Query Patient</button>
                                    <button class="btn btn-sm btn-primary" @click="pushHieSummary(c.id)">Push Summary</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <h3>Recent Exchanges</h3>
                <table>
                    <thead><tr><th>Network</th><th>Patient</th><th>Direction</th><th>Resource</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr v-for="x in hieExchanges" :key="x.id">
                            <td>{{ x.hie_connection ? x.hie_connection.name : '—' }}</td>
                            <td>{{ x.patient ? x.patient.first_name+' '+x.patient.last_name : '—' }}</td>
                            <td>{{ x.direction }}</td>
                            <td>{{ x.resource_type }}</td>
                            <td><span class="badge badge-green">{{ x.status }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='integrations'" class="card">
                <div class="row-between">
                    <h2>Integration Setup</h2>
                    <div>
                        <button class="btn" @click="testSurescripts">Test Surescripts</button>
                        <button class="btn" @click="testLab">Test Lab</button>
                    </div>
                </div>
                <p v-if="integrationTest" class="toast">{{ integrationTest }}</p>
                <div v-for="(section, key) in (requirements && requirements.sections) || {}" :key="key" class="panel">
                    <div class="row-between">
                        <h3>{{ section.label }}</h3>
                        <span class="badge">{{ section.ready ? 'Ready' : 'Needs setup' }}</span>
                    </div>
                    <p v-if="section.note" style="font-size:0.875rem;color:#4a5568">{{ section.note }}</p>
                    <ul v-if="section.you_provide && section.you_provide.length" style="margin-top:0.5rem;padding-left:1.25rem;font-size:0.875rem">
                        <li v-for="item in section.you_provide" :key="item">{{ item }}</li>
                    </ul>
                </div>
            </div>

            <!-- Billing / RCM View with Sub-navigation tabs -->
            <div v-if="view==='billing-rcm'" class="card">
                <h2>Clinical Billing &amp; Revenue Cycle Management (RCM)</h2>
                
                <div class="portal-nav" style="margin:1rem 0; display:flex; gap:0.5rem; flex-wrap:wrap; background:#f7fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0">
                    <button class="btn btn-sm" :class="billingTab==='claims'?'btn-primary':''" @click="billingTab='claims'">Claims Management</button>
                    <button class="btn btn-sm" :class="billingTab==='posting'?'btn-primary':''" @click="billingTab='posting'">Payment Posting</button>
                    <button class="btn btn-sm" :class="billingTab==='era'?'btn-primary':''" @click="billingTab='era'">ERA / EOB</button>
                    <button class="btn btn-sm" :class="billingTab==='denials'?'btn-primary':''" @click="billingTab='denials'">Denial Management</button>
                    <button class="btn btn-sm" :class="billingTab==='statements'?'btn-primary':''" @click="billingTab='statements'">Patient Statements</button>
                    <button class="btn btn-sm" :class="billingTab==='fee-schedule'?'btn-primary':''" @click="billingTab='fee-schedule'">Fee Schedules</button>
                </div>

                <div v-if="billingTab==='claims'" class="panel">
                    <h3>Claims Tracking &amp; Electronic Submissions</h3>
                    <table>
                        <thead><tr><th>Patient</th><th>CPT / Code</th><th>Charge Amt</th><th>Claim status</th></tr></thead>
                        <tbody>
                            <tr v-for="e in encounters.filter(x=>x.billing_sync_status==='synced')" :key="e.id">
                                <td>{{ e.patient ? e.patient.full_name : '—' }}</td>
                                <td>CPT 99213</td>
                                <td>$150.00</td>
                                <td><span class="badge badge-green">EDI Acknowledged</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="billingTab==='posting'" class="panel">
                    <h3>Payment Posting Log</h3>
                    <table>
                        <thead><tr><th>Post Date</th><th>Patient</th><th>Method</th><th>Posted Amt</th></tr></thead>
                        <tbody>
                            <tr><td>2026-06-24</td><td>Jane Doe</td><td>Credit Card</td><td>$20.00</td></tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="billingTab==='era'" class="panel">
                    <h3>Electronic Remittance Advice (ERA 835)</h3>
                    <p style="color:#718096">Process ERA batches synced from clearinghouses automatically.</p>
                </div>

                <div v-if="billingTab==='denials'" class="panel">
                    <h3>Clinical Denial Management</h3>
                    <p style="color:#718096">No active clinical denials require attention.</p>
                </div>

                <div v-if="billingTab==='statements'" class="panel">
                    <h3>Generate Patient Statements</h3>
                    <button class="btn btn-sm btn-primary">Batch Print Statements</button>
                </div>

                <div v-if="billingTab==='fee-schedule'" class="panel">
                    <h3>Practice Fee Schedules</h3>
                    <table>
                        <thead><tr><th>CPT Code</th><th>Standard Fee</th><th>Allowed Medicare</th></tr></thead>
                        <tbody>
                            <tr><td>99213 (Outpatient Level 3)</td><td>$150.00</td><td>$110.00</td></tr>
                            <tr><td>99214 (Outpatient Level 4)</td><td>$220.00</td><td>$160.00</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Messages & Inbox Tab View -->
            <div v-if="view==='messages'" class="card">
                <h2>Clinical Mailbox &amp; Patient Messages</h2>
                <div style="display:flex; gap:1.5rem">
                    <div style="flex:1; border-right:1px solid #e2e8f0; padding-right:1rem">
                        <h3>Inbox Folder</h3>
                        <div style="background:#ebf8ff; padding:0.75rem; border-radius:6px; border:1px solid #bee3f8; cursor:pointer">
                            <strong>Jane Doe</strong>
                            <p style="margin:0.25rem 0; font-size:0.85rem">RE: Refill Request Lisinopril</p>
                        </div>
                    </div>
                    <div style="flex:2; background:#f7fafc; padding:1rem; border-radius:6px; border:1px solid #cbd5e0">
                        <h3>Message Thread</h3>
                        <p><strong>From:</strong> Jane Doe (Patient Portal)</p>
                        <p><strong>Message:</strong> Hello Doctor, can I get a refill on my prescription? Thank you.</p>
                        <hr>
                        <button class="btn btn-sm btn-primary">Reply Message</button>
                    </div>
                </div>
            </div>

            <!-- Reports & Analytics Tab View -->
            <div v-if="view==='reports'" class="card">
                <h2>Reports &amp; Analytical Insights</h2>
                
                <div class="portal-nav" style="margin:1rem 0; display:flex; gap:0.5rem; background:#f7fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0">
                    <button class="btn btn-sm" :class="reportTab==='financial'?'btn-primary':''" @click="reportTab='financial'">Financial Reports</button>
                    <button class="btn btn-sm" :class="reportTab==='quality'?'btn-primary':''" @click="reportTab='quality'">Clinical Quality Measures</button>
                    <button class="btn btn-sm" :class="reportTab==='productivity'?'btn-primary':''" @click="reportTab='productivity'">Provider Productivity</button>
                    <button class="btn btn-sm" :class="reportTab==='custom'?'btn-primary':''" @click="reportTab='custom'">Custom Reports</button>
                </div>

                <div v-if="reportTab==='financial'" class="panel">
                    <h3>Financial Performance Analytics</h3>
                    <p>Total Revenue Billed: $1,250.00</p>
                    <p>Total Payments Collected: $920.00</p>
                </div>

                <div v-if="reportTab==='quality'" class="panel">
                    <h3>CQM Quality Dashboard</h3>
                    <p>Hypertension Control Rate (CMS165): 88%</p>
                </div>

                <div v-if="reportTab==='productivity'" class="panel">
                    <h3>Provider Productivity &amp; Charting Volume</h3>
                    <p>Dr. David Miller: 12 Encounters Closed</p>
                </div>

                <div v-if="reportTab==='custom'" class="panel">
                    <h3>Custom Reporting Scribe</h3>
                    <button class="btn btn-sm btn-primary">Build New Custom SQL Report</button>
                </div>
            </div>
        </main>
    </div>
@endverbatim
</div>
<script src="/js/ehr-admin.js"></script>
</body>
</html>
