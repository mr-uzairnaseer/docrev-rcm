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
    <link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/css/docrev-theme.css">
    <link rel="stylesheet" href="/css/claim-forms.css">
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
                <img src="/img/logo-white.png" alt="DocRev" class="docrev-brand-logo">
                <span class="docrev-brand-product">EHR</span>
            </div>
            <nav>
                <a :class="{active: view==='dashboard'}" @click="view='dashboard';load()"><i class="fas fa-chart-line" style="margin-right:8px; width:16px"></i>Dashboard</a>
                <a :class="{active: view==='patients'}" @click="openPatients('directory')"><i class="fas fa-user-injured" style="margin-right:8px; width:16px"></i>Patients</a>
                <div v-if="view==='patients'" class="sidebar-subnav">
                    <a
                        v-for="tab in patientSubTabs"
                        :key="tab.id"
                        :class="{ active: patientTab === tab.id }"
                        @click="openPatients(tab.id)"
                    >{{ tab.label }}</a>
                </div>
                <a :class="{active: view==='encounters'}" @click="view='encounters';loadEncounters()"><i class="fas fa-stethoscope" style="margin-right:8px; width:16px"></i>Encounters</a>
                <a :class="{active: view==='appointments'}" @click="view='appointments';loadAppointments()"><i class="fas fa-calendar-check" style="margin-right:8px; width:16px"></i>Appointments</a>
                <a :class="{active: view==='prescriptions'}" @click="setView('prescriptions')"><i class="fas fa-prescription-bottle-alt" style="margin-right:8px; width:16px"></i>E-Prescribing</a>
                <a :class="{active: view==='labs'}" @click="setView('labs')"><i class="fas fa-flask" style="margin-right:8px; width:16px"></i>Labs</a>
                <a :class="{active: view==='hie'}" @click="setView('hie')"><i class="fas fa-network-wired" style="margin-right:8px; width:16px"></i>HIE / FHIR</a>
                <a :class="{active: view==='billing-rcm'}" @click="setView('billing-rcm')"><i class="fas fa-file-invoice-dollar" style="margin-right:8px; width:16px"></i>Clearinghouse</a>
                <a :class="{active: view==='messages'}" @click="setView('messages')"><i class="fas fa-envelope-open-text" style="margin-right:8px; width:16px"></i>Messages &amp; Inbox</a>
                <a :class="{active: view==='reports'}" @click="setView('reports')"><i class="fas fa-chart-bar" style="margin-right:8px; width:16px"></i>Reports &amp; Analytics</a>
                <a :class="{active: view==='integrations'}" @click="setView('integrations')"><i class="fas fa-puzzle-piece" style="margin-right:8px; width:16px"></i>Integrations</a>
                <a :class="{active: view==='new-patient'}" @click="view='new-patient'"><i class="fas fa-user-plus" style="margin-right:8px; width:16px"></i>+ Patient</a>
                <a :class="{active: view==='new-encounter'}" @click="openNewEncounter()"><i class="fas fa-notes-medical" style="margin-right:8px; width:16px"></i>+ Encounter</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" @click.prevent="logout" class="logout"><i class="fas fa-sign-out-alt" style="margin-right:8px"></i>Logout</a>
            </div>
        </aside>
        <main class="main">
            <div v-if="toast" class="toast" role="status">
                <span class="toast__text">{{ toast }}</span>
                <button type="button" class="toast__close" @click="toast = ''" aria-label="Dismiss notification">&times;</button>
            </div>

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

                <!-- Task & Notifications Center -->
                <div class="card" style="margin-top:1.5rem">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
                        <h2 style="margin:0"><i class="fas fa-tasks" style="color:#3182ce; margin-right:8px"></i>Task Notification Center</h2>
                        <button class="btn btn-sm btn-primary" @click="showAddTask = !showAddTask">+ Create Task</button>
                    </div>

                    <!-- Create Task Form Overlay/Modal -->
                    <div v-if="showAddTask" style="background:#f7fafc; border:1px solid #cbd5e0; padding:1.25rem; border-radius:8px; margin-bottom:1rem">
                        <h3 style="margin-top:0; font-size:1rem; color:#2d3748">Add New Task</h3>
                        <div class="form-row">
                            <div class="form-group" style="flex:2">
                                <label>Task Title</label>
                                <input type="text" v-model="taskForm.title" placeholder="e.g. Call patient with CMP lab results" style="width:100%; padding:0.4rem">
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Priority</label>
                                <select v-model="taskForm.priority" style="width:100%; padding:0.4rem">
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Due Date</label>
                                <input type="date" v-model="taskForm.due_date" style="width:100%; padding:0.35rem">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description / Notes</label>
                            <textarea v-model="taskForm.description" rows="2" style="width:100%; font-family:inherit"></textarea>
                        </div>
                        <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:0.75rem">
                            <button class="btn btn-sm" @click="showAddTask=false">Cancel</button>
                            <button class="btn btn-sm btn-primary" @click="createTask">Save Task</button>
                        </div>
                    </div>

                    <!-- Tasks Table -->
                    <table v-if="tasks.length">
                        <thead><tr><th>Task</th><th>Priority</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="t in tasks" :key="t.id" :style="t.status==='completed'?'opacity:0.65; background:#f7fafc':''">
                                <td>
                                    <strong :style="t.status==='completed'?'text-decoration: line-through':''">{{ t.title }}</strong>
                                    <p v-if="t.description" style="margin:2px 0 0; font-size:0.8rem; color:#718096">{{ t.description }}</p>
                                </td>
                                <td>
                                    <span class="badge" :class="t.priority==='high'?'badge-red':(t.priority==='low'?'badge-blue':'badge-yellow')">{{ t.priority }}</span>
                                </td>
                                <td>{{ t.due_date ? formatDate(t.due_date) : 'No due date' }}</td>
                                <td>
                                    <span class="badge" :class="t.status==='completed'?'badge-green':'badge-yellow'">{{ t.status }}</span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.25rem">
                                        <button class="btn btn-xs btn-primary" @click="toggleTaskComplete(t)">
                                            {{ t.status==='completed'?'Mark Pending':'Mark Complete' }}
                                        </button>
                                        <button class="btn btn-xs" @click="deleteTask(t.id)" style="background:#e53e3e; color:white; border:none; padding:0.25rem 0.5rem; border-radius:4px; cursor:pointer">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else style="color:#718096; text-align:center; padding:1.5rem">All caught up! No active tasks.</p>
                </div>
            </div>

            <div v-if="view==='patients'" class="card">
                <div class="row-between">
                    <div>
                        <h2>{{ activePatientSubTab().label }}</h2>
                        <p v-if="selectedPatient && patientTab !== 'directory'" class="patient-subtitle">
                            Clinical file: <strong>{{ selectedPatient.full_name }}</strong>
                            <span v-if="selectedPatient.mrn"> · MRN {{ selectedPatient.mrn }}</span>
                        </p>
                    </div>
                    <button class="btn btn-primary" @click="view='new-patient'">Add Patient</button>
                </div>

                <nav class="patient-subnav" aria-label="Patient chart sections">
                    <button
                        v-for="tab in patientSubTabs"
                        :key="tab.id"
                        type="button"
                        class="btn btn-sm patient-subnav__btn"
                        :class="{ 'btn-primary': patientTab === tab.id }"
                        @click="openPatients(tab.id)"
                    >{{ tab.label }}</button>
                </nav>

                <div v-if="activePatientSubTab().needsPatient && !selectedPatient" class="patient-empty-state">
                    <p>Select a patient from <button type="button" class="btn btn-sm btn-primary" @click="openPatients('directory')">All Patients</button> to open this chart section.</p>
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
                                    <button class="btn btn-sm btn-primary" @click="selectPatient(p, 'demographics')" style="padding:0.25rem 0.5rem">Select Clinical File</button>
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
                    <div v-if="patientChart && patientChart.vitals && patientChart.vitals.length" style="margin-top:1.5rem">
                        <h4 style="color:#2d3748; margin-bottom:0.5rem">Recent Vitals</h4>
                        <table>
                            <thead><tr><th>Recorded</th><th>BP</th><th>HR</th><th>Temp</th><th>Weight</th><th>SpO2</th></tr></thead>
                            <tbody>
                                <tr v-for="(v, idx) in patientChart.vitals" :key="v.id">
                                    <td>{{ formatDate(v.recorded_at) }}</td>
                                    <td>
                                        <span :style="getVitalStyle('bp', v.bp_systolic, v.bp_diastolic)">
                                            {{ v.bp_systolic && v.bp_diastolic ? v.bp_systolic + '/' + v.bp_diastolic : '—' }}
                                        </span>
                                        <span :style="getVitalTrendStyle('bp_systolic', idx)">{{ getVitalTrend('bp_systolic', idx) }}</span>
                                    </td>
                                    <td>
                                        <span :style="getVitalStyle('hr', v.heart_rate)">{{ v.heart_rate || '—' }}</span>
                                        <span :style="getVitalTrendStyle('heart_rate', idx)">{{ getVitalTrend('heart_rate', idx) }}</span>
                                    </td>
                                    <td>
                                        <span :style="getVitalStyle('temp', v.temperature_f)">{{ v.temperature_f ? v.temperature_f + ' °F' : '—' }}</span>
                                        <span :style="getVitalTrendStyle('temperature_f', idx)">{{ getVitalTrend('temperature_f', idx) }}</span>
                                    </td>
                                    <td>
                                        <span>{{ v.weight_lb ? v.weight_lb + ' lb' : '—' }}</span>
                                        <span :style="getVitalTrendStyle('weight_lb', idx)">{{ getVitalTrend('weight_lb', idx) }}</span>
                                    </td>
                                    <td>
                                        <span :style="getVitalStyle('spo2', v.spo2)">{{ v.spo2 ? v.spo2 + '%' : '—' }}</span>
                                        <span :style="getVitalTrendStyle('spo2', idx)">{{ getVitalTrend('spo2', idx) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="patientChart && patientChart.documents" style="margin-top:1.5rem">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem">
                            <h4 style="color:#2d3748; margin:0">Chart Documents</h4>
                            <div style="display:flex; gap:0.5rem; align-items:center">
                                <select v-model="docFilters.document_type" @change="loadPatientChart" style="padding:0.3rem 0.6rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.85rem">
                                    <option value="">All Document Types</option>
                                    <option value="Referral Letter">Referral Letter</option>
                                    <option value="Lab Result">Lab Result</option>
                                    <option value="Consent Form">Consent Form</option>
                                    <option value="Clinical Summary">Clinical Summary</option>
                                </select>
                                <input type="date" v-model="docFilters.document_date" @change="loadPatientChart" style="padding:0.25rem 0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.85rem">
                                <button v-if="docFilters.document_type || docFilters.document_date" type="button" class="btn btn-sm btn-secondary" @click="docFilters.document_type=''; docFilters.document_date=''; loadPatientChart()" style="padding:0.25rem 0.5rem; font-size:0.8rem">Clear</button>
                            </div>
                        </div>
                        <table v-if="patientChart.documents.length">
                            <thead><tr><th>Title</th><th>Type</th><th>File</th></tr></thead>
                            <tbody>
                                <tr v-for="d in patientChart.documents" :key="d.id">
                                    <td>{{ d.title }}</td>
                                    <td>{{ d.document_type }}</td>
                                    <td>{{ d.file_name || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else style="color:#718096; text-align:center; padding:1rem; border:1px dashed #e2e8f0; border-radius:6px">No documents match the filter criteria.</p>
                    </div>
                </div>

                <!-- Insurance & Eligibility Sub-tab -->
                <div v-if="patientTab==='insurance' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Insurance &amp; Eligibility: {{ selectedPatient.full_name }}</h3>
                    <p v-if="patientChartLoading" style="margin-top:1rem;color:#718096">Loading insurance…</p>
                    <div v-else style="margin-top:1rem; display:flex; gap:1.5rem; flex-wrap:wrap">
                        <div v-for="ins in (patientChart && patientChart.insurances) || []" :key="ins.id" style="flex:1; min-width:260px; background:#f7fafc; padding:1rem; border-radius:6px; border:1px solid #e2e8f0">
                            <h4 style="margin-top:0">{{ ins.plan_type === 'primary' ? 'Primary' : ins.plan_type }} — {{ ins.payer_name }}</h4>
                            <p style="margin:0.25rem 0"><strong>Member ID:</strong> {{ ins.member_id || '—' }}</p>
                            <p style="margin:0.25rem 0"><strong>Group #:</strong> {{ ins.group_number || '—' }}</p>
                            <p style="margin:0.5rem 0 0">
                                <span class="badge" :class="ins.coverage_status === 'active' ? 'badge-green' : 'badge-yellow'">
                                    {{ ins.coverage_status || 'unknown' }}
                                </span>
                                <span v-if="ins.copay_amount != null" style="margin-left:0.5rem">Copay: ${{ ins.copay_amount }}</span>
                            </p>
                            <button class="btn btn-sm btn-primary" style="margin-top:0.75rem" :disabled="isEligibilityChecking" @click="checkPatientEligibility(ins)">
                                {{ isEligibilityChecking ? 'Checking…' : 'Verify Eligibility' }}
                            </button>
                        </div>
                        <p v-if="!patientChart || !patientChart.insurances || !patientChart.insurances.length" style="color:#718096">No insurance policies on file.</p>
                    </div>
                </div>

                <!-- Care Team Sub-tab -->
                <div v-if="patientTab==='care-team' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Care Team: {{ selectedPatient.full_name }}</h3>
                    <table style="margin-top:1rem">
                        <thead><tr><th>Name</th><th>Role / Specialty</th><th>Contact</th></tr></thead>
                        <tbody>
                            <tr v-for="m in (patientChart && patientChart.care_team) || []" :key="m.id">
                                <td><strong>{{ m.name }}</strong></td>
                                <td>{{ m.role }}<span v-if="m.specialty"> — {{ m.specialty }}</span></td>
                                <td>{{ m.contact || '—' }}</td>
                            </tr>
                            <tr v-if="!patientChart || !patientChart.care_team || !patientChart.care_team.length">
                                <td colspan="3" style="color:#718096">No care team members on file.</td>
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
                            <tr v-for="p in (patientChart && patientChart.problems) || []" :key="p.id">
                                <td><span class="badge badge-blue">{{ p.icd10_code }}</span></td>
                                <td>{{ p.description }}</td>
                                <td>{{ p.onset_date || '—' }}</td>
                                <td><span class="badge badge-green">{{ p.status }}</span></td>
                            </tr>
                            <tr v-if="!patientChart || !patientChart.problems || !patientChart.problems.length">
                                <td colspan="4" style="color:#718096">No problems documented.</td>
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
                            <tr v-for="rx in (patientChart && patientChart.medications) || []" :key="rx.id">
                                <td><strong>{{ rx.drug_name }}</strong></td>
                                <td>{{ rx.sig || '—' }}</td>
                                <td>{{ rx.prescriber || '—' }}</td>
                                <td>{{ rx.refills != null ? rx.refills + ' refills' : '—' }}</td>
                            </tr>
                            <tr v-if="!patientChart || !patientChart.medications || !patientChart.medications.length">
                                <td colspan="4" style="color:#718096">No medications on file.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Allergies Sub-tab -->
                <div v-if="patientTab==='allergies' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Allergies &amp; Intolerances: {{ selectedPatient.full_name }}</h3>
                    <table v-if="patientChart && patientChart.allergies && patientChart.allergies.length" style="margin-top:1rem">
                        <thead><tr><th>Allergen</th><th>Reaction</th><th>Severity</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="a in patientChart.allergies" :key="a.id">
                                <td><strong>{{ a.allergen }}</strong></td>
                                <td>{{ a.reaction || '—' }}</td>
                                <td>{{ a.severity }}</td>
                                <td><span class="badge badge-green">{{ a.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top:1rem">
                        <label style="font-weight:bold; display:block">Summary (synced to chart header)</label>
                        <div style="display:flex; gap:0.5rem; margin-top:0.5rem">
                            <input v-model="selectedPatient.allergies" style="padding:0.4rem 0.5rem; border-radius:4px; border:1px solid #cbd5e0; flex:1" placeholder="e.g. Penicillin, Peanuts">
                            <button class="btn btn-sm btn-primary" @click="savePatientAllergies(selectedPatient)">Save Summary</button>
                        </div>
                    </div>
                </div>

                <!-- Visit History Sub-tab -->
                <div v-if="patientTab==='history' && selectedPatient" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem">
                    <h3 style="color:#2d3748; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem">Visit History: {{ selectedPatient.full_name }}</h3>
                    <table style="margin-top:1rem">
                        <thead><tr><th>Date</th><th>Provider</th><th>Status</th><th>Billing</th></tr></thead>
                        <tbody>
                            <tr v-for="v in (patientChart && patientChart.visits) || []" :key="v.id">
                                <td>{{ formatDate(v.encounter_date) }}</td>
                                <td>{{ v.provider || '—' }}</td>
                                <td><span class="badge badge-green">{{ v.status }}</span></td>
                                <td><span class="badge badge-blue">{{ v.billing_sync_status || '—' }}</span></td>
                            </tr>
                            <tr v-if="!patientChart || !patientChart.visits || !patientChart.visits.length">
                                <td colspan="4" style="color:#718096">No visits on file.</td>
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

                <!-- Calendar Navigation Sub-tabs -->
                <div class="portal-nav" style="margin: 1rem 0; display:flex; gap:0.5rem; justify-content: space-between; align-items: center; flex-wrap:wrap">
                    <div style="display:flex; gap:0.5rem">
                        <button class="btn btn-sm" :class="apptTab==='calendar'?'btn-primary':''" @click="apptTab='calendar'"><i class="fas fa-calendar-alt" style="margin-right:4px"></i>Calendar Grid View</button>
                        <button class="btn btn-sm" :class="apptTab==='scheduled'?'btn-primary':''" @click="apptTab='scheduled'"><i class="fas fa-list" style="margin-right:4px"></i>Scheduled Appointments</button>
                        <button class="btn btn-sm" :class="apptTab==='requested'?'btn-primary':''" @click="apptTab='requested'"><i class="fas fa-envelope-open-text" style="margin-right:4px"></i>Patient Requests ({{ appointments.filter(a=>a.status==='requested').length }})</button>
                    </div>
                    
                    <!-- Calendar Legend (RichiBillings Statuses) -->
                    <div v-if="apptTab==='calendar'" style="display:flex; gap:0.25rem; flex-wrap:wrap; font-size:0.75rem">
                        <span class="badge" style="background:#48bb78; color:white">office_visit</span>
                        <span class="badge" style="background:#3182ce; color:white">telehealth</span>
                        <span class="badge" style="background:#ecc94b; color:#744210">requested</span>
                    </div>
                </div>

                <!-- Interactive Timeline Calendar Grid (RichiBillings Style) -->
                <div v-if="apptTab==='calendar'" class="panel" style="background:#fff; border:1px solid #cbd5e0; padding:1.5rem; overflow-x:auto">
                    <!-- Calendar Header Controls -->
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e2e8f0; padding-bottom:0.75rem; margin-bottom:1rem">
                        <div style="display:flex; align-items:center; gap:0.5rem">
                            <button class="btn btn-sm btn-icon" type="button" aria-label="Previous day" @click="prevDay()"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                            <button class="btn btn-sm btn-icon" type="button" aria-label="Next day" @click="nextDay()"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                            <button class="btn btn-sm" type="button" @click="goToToday()">Today</button>
                            <span style="font-weight:bold; font-size:1.1rem; color:#2d3748">{{ formatCalendarHeader() }}</span>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center">
                            <input type="date" v-model="calendarDateInput" @change="setDateFromInput" style="padding:0.3rem 0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.9rem">
                        </div>
                    </div>

                    <!-- Timeline Sheet Grid -->
                    <div style="min-width: 800px">
                        <!-- Hour Row Header -->
                        <div style="display:flex; background:#edf2f7; font-weight:bold; border-bottom:1px solid #cbd5e0">
                            <div style="width:150px; padding:0.5rem; border-right:1px solid #cbd5e0">Providers</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">8:00 AM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">9:00 AM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">10:00 AM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">11:00 AM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">12:00 PM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">1:00 PM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">2:00 PM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">3:00 PM</div>
                            <div style="flex:1; padding:0.5rem; border-right:1px solid #cbd5e0; text-align:center">4:00 PM</div>
                            <div style="flex:1; padding:0.5rem; text-align:center">5:00 PM</div>
                        </div>

                        <!-- Provider Row & Scheduled Events -->
                        <div v-for="pr in providers" :key="pr.id" style="display:flex; border-bottom:1px solid #e2e8f0; height:80px; position:relative; background:#fff">
                            <!-- Provider Name Label -->
                            <div style="width:150px; padding:0.5rem; border-right:1px solid #cbd5e0; display:flex; flex-direction:column; justify-content:center; background:#f7fafc; font-weight:bold; font-size:0.9rem">
                                <span>Dr. {{ pr.last_name }}</span>
                                <span style="font-weight:normal; font-size:0.75rem; color:#718096">{{ pr.specialty || 'Clinical Care' }}</span>
                            </div>

                            <!-- Hour columns placeholders -->
                            <div style="flex:10; display:flex; position:relative; height:100%">
                                <div @click="clickGridSlot(pr, 8)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 8:00 AM"></div>
                                <div @click="clickGridSlot(pr, 9)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 9:00 AM"></div>
                                <div @click="clickGridSlot(pr, 10)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 10:00 AM"></div>
                                <div @click="clickGridSlot(pr, 11)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 11:00 AM"></div>
                                <div style="flex:1; border-right:1px solid #f0f4f8; background:#edf2f7; display:flex; justify-content:center; align-items:center; color:#a0aec0; font-size:0.8rem">Lunch Break</div>
                                <div @click="clickGridSlot(pr, 13)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 1:00 PM"></div>
                                <div @click="clickGridSlot(pr, 14)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 2:00 PM"></div>
                                <div @click="clickGridSlot(pr, 15)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 3:00 PM"></div>
                                <div @click="clickGridSlot(pr, 16)" style="flex:1; border-right:1px solid #f0f4f8; cursor:pointer" title="Click to schedule at 4:00 PM"></div>
                                <div @click="clickGridSlot(pr, 17)" style="flex:1; cursor:pointer" title="Click to schedule at 5:00 PM"></div>

                                <!-- Dynamically Rendered Calendar Blocks -->
                                <div v-for="appt in getApptsForProviderAndDate(pr.id)" :key="appt.id"
                                     style="position:absolute; top:10px; height:55px; border-radius:4px; padding:0.4rem; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,0.1); cursor:pointer; display:flex; flex-direction:column; justify-content:space-between"
                                     :style="getApptStyle(appt)"
                                     @click.stop="openApptDetails(appt)">
                                    <div style="font-weight:bold; text-overflow:ellipsis; white-space:nowrap; overflow:hidden">
                                        {{ appt.patient ? appt.patient.first_name + ' ' + appt.patient.last_name : 'Patient Encounter' }}
                                    </div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.7rem; opacity:0.9">
                                        <span>{{ appt.appointment_type }}</span>
                                        <span>{{ appt.status }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #edf2f7; padding-bottom: 0.75rem; margin-bottom: 1.5rem; flex-wrap:wrap; gap:0.5rem">
                    <h2 style="margin: 0; color: #1e4d6b; display:flex; align-items:center; gap:0.5rem">
                        <span>🌐</span> Interoperability &amp; ONC Compliance Hub
                    </h2>
                    <span class="badge badge-green" style="font-weight:600">US Core v5.0.1 (FHIR R4)</span>
                </div>

                <!-- Sub View Tabs -->
                <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 1.5rem; padding-bottom: 0.5rem; overflow-x: auto; white-space: nowrap;">
                    <button :class="['btn btn-sm', interopSubView === 'fhir' ? 'btn-primary' : '']" @click="interopSubView = 'fhir'">📶 HIE &amp; FHIR Exchange</button>
                    <button :class="['btn btn-sm', interopSubView === 'ehi' ? 'btn-primary' : '']" @click="interopSubView = 'ehi'">📦 Computable EHI Export (b10)</button>
                    <button :class="['btn btn-sm', interopSubView === 'requests' ? 'btn-primary' : '']" @click="interopSubView = 'requests'">📝 Info Blocking Intake Log</button>
                    <button :class="['btn btn-sm', interopSubView === 'dsi' ? 'btn-primary' : '']" @click="interopSubView = 'dsi'">⚙️ Decision Support &amp; CQMs (b11)</button>
                </div>

                <!-- TAB 1: FHIR & HIE EXCHANGES -->
                <div v-if="interopSubView === 'fhir'">
                    <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:1.5rem; align-items:start">
                        <div>
                            <div class="panel">
                                <h3>Active HIE Network Connections</h3>
                                <table>
                                    <thead><tr><th>Network</th><th>Type</th><th>Status</th><th>Agreement</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <tr v-for="c in hieConnections" :key="c.id">
                                            <td><strong>{{ c.name }}</strong></td>
                                            <td>{{ c.network_type }}</td>
                                            <td><span class="badge">{{ c.status }}</span></td>
                                            <td>{{ c.agreement_signed_at ? formatDate(c.agreement_signed_at) : 'Pending' }}</td>
                                            <td>
                                                <div style="display:flex; gap:0.25rem">
                                                    <button class="btn btn-xs" @click="queryHie(c.id)">Query</button>
                                                    <button class="btn btn-xs btn-primary" @click="pushHieSummary(c.id)">Push</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="panel">
                                <h3>Recent FHIR &amp; HIE Transactions</h3>
                                <table>
                                    <thead><tr><th>Timestamp</th><th>Patient</th><th>Direction</th><th>Resource Type</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <tr v-for="x in hieExchanges" :key="x.id">
                                            <td>{{ formatDate(x.created_at) }}</td>
                                            <td>{{ x.patient ? x.patient.first_name + ' ' + x.patient.last_name : '—' }}</td>
                                            <td><span class="badge">{{ x.direction }}</span></td>
                                            <td><code>{{ x.resource_type }}</code></td>
                                            <td><span class="badge badge-green">{{ x.status }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- FHIR JSON Viewer -->
                        <div class="panel" style="background:#f7fafc">
                            <h3>FHIR Resource Visualizer</h3>
                            <p style="font-size:0.8rem; color:#718096; margin-bottom:0.75rem">Inspect live JSON representations of patients mapped to HL7 FHIR US Core profiles.</p>
                            <div class="form-group">
                                <label>Select Patient</label>
                                <select v-model="hiePatientId" @change="viewFhirPatientPreview(hiePatientId)" style="width:100%; padding:0.4rem">
                                    <option value="">-- Choose Patient --</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                </select>
                            </div>
                            <div v-if="fhirPatientPreview" style="margin-top:1rem">
                                <span class="badge badge-green" style="font-size:0.7rem; margin-bottom:0.5rem; display:inline-block">Live USCDI Core Profile Mapped</span>
                                <pre style="background:#1a202c; color:#a0aec0; padding:1rem; border-radius:6px; font-size:0.75rem; font-family:monospace; max-height:300px; overflow-y:auto">{{ JSON.stringify(fhirPatientPreview, null, 2) }}</pre>
                            </div>
                            <p v-else-if="hiePatientId" style="font-size:0.85rem; color:#718096; text-align:center; padding:1rem">Loading FHIR resource preview...</p>
                            <p v-else style="font-size:0.85rem; color:#718096; text-align:center; padding:1rem">Select a patient to preview resource</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: EHI EXPORT HUB (b10) -->
                <div v-if="interopSubView === 'ehi'">
                    <div class="panel" style="background:#ebf8ff; border:1px solid #bee3f8; margin-bottom:1.5rem">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem">
                            <div>
                                <h3 style="margin:0; color:#2b6cb0">Electronic Health Information (EHI) Export</h3>
                                <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#2b6cb0">Generate complete, computable clinical datasets in zip/json packages conforming to ONC §170.315(b)(10).</p>
                            </div>
                            <div style="display:flex; gap:0.5rem; align-items:center">
                                <select v-model="hiePatientId" style="padding:0.4rem">
                                    <option value="">Select Patient to Export</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                </select>
                                <button class="btn btn-primary" @click="generateEhiExport(hiePatientId)" :disabled="generatingEhi || !hiePatientId">
                                    {{ generatingEhi ? 'Generating...' : 'Trigger EHI Export' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Generated EHI Computable Packages</h3>
                        <table>
                            <thead><tr><th>Generated At</th><th>Patient</th><th>Requested By</th><th>Format</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <tr v-for="exp in interopExports" :key="exp.id">
                                    <td>{{ formatDate(exp.completed_at || exp.created_at) }}</td>
                                    <td><strong>{{ exp.patient ? exp.patient.first_name + ' ' + exp.patient.last_name : 'Bulk Export' }}</strong></td>
                                    <td>{{ exp.requested_by }}</td>
                                    <td><code>JSON (USCDI v3)</code></td>
                                    <td><span class="badge badge-green">{{ exp.status }}</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" @click="downloadEhiExport(exp)">
                                            <i class="fas fa-download" style="margin-right:4px"></i>Download JSON
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!interopExports.length"><td colspan="6" style="text-align:center;color:#718096">No EHI export packages generated yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: INFO BLOCKING COMPLIANCE LOG -->
                <div v-if="interopSubView === 'requests'">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
                        <p style="font-size:0.9rem; color:#4a5568; margin:0">
                            Log patient or third-party app requests for clinical data access to comply with 21st Century Cures Act Information Blocking rules.
                        </p>
                        <button class="btn btn-sm" @click="showAddInteropRequest = !showAddInteropRequest">Log Intake Request</button>
                    </div>

                    <!-- Log Intake Request form -->
                    <div v-if="showAddInteropRequest" class="panel" style="background:#f7fafc; margin-bottom:1.5rem">
                        <h3>Log EHI / API Intake Access Request</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Patient</label>
                                <select v-model="newInteropRequest.patient_id">
                                    <option value="">Select Patient</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.first_name }} {{ p.last_name }}</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Requestor Name</label><input v-model="newInteropRequest.requestor_name" type="text" placeholder="e.g. HealthApp LLC or Patient Name"></div>
                            <div class="form-group">
                                <label>Requestor Type</label>
                                <select v-model="newInteropRequest.requestor_type">
                                    <option value="patient">Patient</option>
                                    <option value="provider">Provider / Clinic</option>
                                    <option value="payer">Insurance Payer</option>
                                    <option value="third_party_app">Third-party App</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Access Method</label>
                                <select v-model="newInteropRequest.access_method">
                                    <option value="fhir_api">Standardized FHIR API</option>
                                    <option value="ehi_export">Computable EHI Export</option>
                                    <option value="patient_portal">Patient Portal Access</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select v-model="newInteropRequest.status">
                                    <option value="approved">Approved / Connected</option>
                                    <option value="pending">Under Review</option>
                                    <option value="denied">Denied / Blocked Exception</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="newInteropRequest.status === 'denied'">
                                <label>Blocking Exception Reason</label>
                                <select v-model="newInteropRequest.exception_reason">
                                    <option value="security">Security Exception</option>
                                    <option value="privacy">Privacy Exception</option>
                                    <option value="infeasibility">Infeasibility Exception</option>
                                    <option value="harm_prevention">Preventing Harm Exception</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Justification / Compliance Notes</label>
                            <textarea v-model="newInteropRequest.notes" rows="2" style="width:100%; font-family:inherit"></textarea>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="addInteropRequest">Save Request Log</button>
                    </div>

                    <!-- Requests log list -->
                    <table>
                        <thead>
                            <tr>
                                <th>Logged Date</th>
                                <th>Requestor</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Patient</th>
                                <th>Status</th>
                                <th>Adjudication / Exception Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="req in interopRequests" :key="req.id">
                                <td>{{ formatDate(req.created_at) }}</td>
                                <td><strong>{{ req.requestor_name }}</strong></td>
                                <td><span class="badge">{{ req.requestor_type }}</span></td>
                                <td><code>{{ req.access_method }}</code></td>
                                <td>{{ req.patient ? req.patient.first_name + ' ' + req.patient.last_name : 'All Patients' }}</td>
                                <td>
                                    <span class="badge" :class="{
                                        'badge-green': req.status === 'approved',
                                        'badge-yellow': req.status === 'pending',
                                        'badge-red': req.status === 'denied'
                                    }">{{ req.status }}</span>
                                </td>
                                <td style="font-size:0.8rem">
                                    <div v-if="req.status === 'denied'">
                                        <strong style="color:#c53030">Exception: {{ req.exception_reason }}</strong>
                                        <p style="margin:2px 0 0; color:#718096">{{ req.notes }}</p>
                                    </div>
                                    <div v-else-if="req.status === 'pending'" style="display:flex; gap:0.25rem">
                                        <button class="btn btn-xs btn-primary" @click="updateInteropRequestStatus(req, 'approved')">Approve</button>
                                        <button class="btn btn-xs" @click="updateInteropRequestStatus(req, 'denied', 'security')">Deny (Security)</button>
                                    </div>
                                    <span v-else>{{ req.notes || 'Access provisioned successfully.' }}</span>
                                </td>
                            </tr>
                            <tr v-if="!interopRequests.length"><td colspan="7" style="text-align:center;color:#718096">No compliance access requests logged.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- TAB 4: DECISION SUPPORT & QUALITY RULES (b11) -->
                <div v-if="interopSubView === 'dsi'">
                    <div style="display:grid; grid-template-columns: 1fr 1.2fr; gap:1.5rem">
                        <div class="panel">
                            <h3>Clinical Decision Support Interventions (DSI)</h3>
                            <p style="font-size:0.85rem; color:#4a5568">ONC §170.315(b)(11) requires transparency regarding clinical rule sources, developers, and funding.</p>
                            
                            <div style="background:#fff; border:1px solid #cbd5e0; border-radius:6px; padding:0.75rem; margin-top:1rem">
                                <h4 style="margin:0 0 0.5rem; color:#2b6cb0">⚡ Rule: Severe Hypertension Warning</h4>
                                <ul style="font-size:0.8rem; list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.25rem">
                                    <li><strong>Condition:</strong> BP Systolic &gt;= 160 or Diastolic &gt;= 100</li>
                                    <li><strong>Developer:</strong> American College of Cardiology</li>
                                    <li><strong>Funding Source:</strong> Federal Grant ACC-30012</li>
                                    <li><strong>Evidence/Standard:</strong> 2017 ACC/AHA Guideline for Prevention, Detection, Evaluation and Management of High Blood Pressure.</li>
                                </ul>
                            </div>

                            <div style="background:#fff; border:1px solid #cbd5e0; border-radius:6px; padding:0.75rem; margin-top:1rem">
                                <h4 style="margin:0 0 0.5rem; color:#2b6cb0">⚡ Rule: Pediatric Asthma Care Action Plan</h4>
                                <ul style="font-size:0.8rem; list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.25rem">
                                    <li><strong>Condition:</strong> Age &lt;= 12 and Diagnosis Asthma</li>
                                    <li><strong>Developer:</strong> National Heart, Lung, and Blood Institute (NHLBI)</li>
                                    <li><strong>Evidence/Standard:</strong> EPR-3 Guidelines for the Diagnosis and Management of Asthma.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Clinical Quality Measure (eCQM) Calculation Engine</h3>
                            <p style="font-size:0.85rem; color:#4a5568">Track practice compliance performance against CMS Quality Payment Program (MIPS/Quality) definitions.</p>
                            <table style="font-size:0.85rem; margin-top:1rem">
                                <thead><tr><th>Measure ID</th><th>CMS Quality Title</th><th>Status</th><th>Calculation Framework</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td><strong>CMS122v11</strong></td>
                                        <td>Diabetes: Hemoglobin A1c Poor Control</td>
                                        <td><span class="badge badge-green">eCQM Compliant</span></td>
                                        <td>Denominator: patients 18-75 with Diabetes. Numerator: HbA1c &gt; 9.0%.</td>
                                    </tr>
                                    <tr>
                                        <td><strong>CMS165v11</strong></td>
                                        <td>Controlling High Blood Pressure</td>
                                        <td><span class="badge badge-green">eCQM Compliant</span></td>
                                        <td>Denominator: patients 18-85 with Hypertension. Numerator: BP &lt; 140/90 mmHg.</td>
                                    </tr>
                                    <tr>
                                        <td><strong>CMS138v11</strong></td>
                                        <td>Preventive Care &amp; Screening: Tobacco Use</td>
                                        <td><span class="badge">Draft Calculation</span></td>
                                        <td>Screening and cessation intervention.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="view==='integrations'" class="card">
                <div class="row-between">
                    <h2>System Administration &amp; Integrations</h2>
                    <div>
                        <button class="btn" @click="testSurescripts" style="margin-right:0.25rem">Test Surescripts</button>
                        <button class="btn" @click="testLab">Test Lab</button>
                    </div>
                </div>

                <div class="portal-nav" style="margin:1rem 0; display:flex; gap:0.5rem; flex-wrap:wrap; background:#f7fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0">
                    <button class="btn btn-sm" :class="adminTab==='plan'?'btn-primary':''" @click="adminTab='plan'">Project Plan &amp; HIPAA Readiness</button>
                    <button class="btn btn-sm" :class="adminTab==='config'?'btn-primary':''" @click="adminTab='config'">Configure Core Lists</button>
                </div>

                <div v-if="adminTab==='plan'">
                    <div v-if="opsStatus" class="panel" style="margin-bottom:1rem">
                        <h3>Operations &amp; HIPAA Readiness</h3>
                        <div class="stats" style="margin:0.75rem 0">
                            <div class="stat"><div class="num">{{ opsStatus.queue.driver }}</div><div class="label">Queue Driver</div></div>
                            <div class="stat"><div class="num">{{ opsStatus.database.connected ? 'OK' : '—' }}</div><div class="label">Database</div></div>
                            <div class="stat"><div class="num">{{ opsStatus.mfa.enabled ? 'On' : 'Ready' }}</div><div class="label">MFA</div></div>
                            <div class="stat"><div class="num">{{ opsStatus.hipaa_controls.rbac_enforced ? 'Yes' : '—' }}</div><div class="label">RBAC Enforced</div></div>
                        </div>
                        <p style="font-size:0.875rem;color:#4a5568">{{ opsStatus.backup_dr.recommendation }}</p>
                    </div>
                    <div v-if="trainingModules.length" class="panel" style="margin-bottom:1rem">
                        <h3>Staff Training Modules</h3>
                        <table>
                            <thead><tr><th>Module</th><th>Audience</th><th>Duration</th><th>Topics</th></tr></thead>
                            <tbody>
                                <tr v-for="mod in trainingModules" :key="mod.id">
                                    <td><strong>{{ mod.title }}</strong></td>
                                    <td>{{ mod.audience }}</td>
                                    <td>{{ mod.duration_minutes }} min</td>
                                    <td>{{ mod.topics.join('; ') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="projectPlan" class="panel" style="margin-bottom:1rem">
                        <h3>{{ projectPlan.title }}</h3>
                        <div class="stats" style="margin:1rem 0">
                            <div class="stat"><div class="num">{{ projectPlan.metrics.functional_requirements }}</div><div class="label">Functional Reqs</div></div>
                            <div class="stat"><div class="num">{{ projectPlan.metrics.technical_requirements }}</div><div class="label">Technical Reqs</div></div>
                            <div class="stat"><div class="num">{{ projectPlan.metrics.compliance_controls }}</div><div class="label">Compliance</div></div>
                            <div class="stat"><div class="num">{{ projectPlan.completion.functional }}%</div><div class="label">MVP Functional</div></div>
                        </div>
                        <table>
                            <thead><tr><th>Phase</th><th>Start Week</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr v-for="phase in projectPlan.phases" :key="phase.name">
                                    <td>{{ phase.name }}</td>
                                    <td>{{ phase.start_week }}</td>
                                    <td><span class="badge" :class="phase.status==='complete'?'badge-green':(phase.status==='in_progress'?'badge-blue':'badge-yellow')">{{ phase.status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                        <h4 style="margin-top:1.25rem">Functional requirements</h4>
                        <table>
                            <thead><tr><th>ID</th><th>Requirement</th><th>Priority</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr v-for="req in projectPlan.functional_requirements" :key="req.id">
                                    <td>{{ req.id }}</td>
                                    <td>{{ req.name }}</td>
                                    <td>{{ req.priority }}</td>
                                    <td><span class="badge" :class="req.status==='complete'?'badge-green':(req.status==='partial'?'badge-blue':'badge-yellow')">{{ req.status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="integrationTest" class="toast" role="status">
                        <span class="toast__text">{{ integrationTest }}</span>
                        <button type="button" class="toast__close" @click="integrationTest = ''" aria-label="Dismiss notification">&times;</button>
                    </div>
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

                <div v-if="adminTab==='config'">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem">
                        <!-- Payer List configuration -->
                        <div class="panel">
                            <h3>Practice Payer List</h3>
                            <p style="font-size:0.8rem; color:#718096; margin-bottom:0.75rem">Manage insurance companies accepted by the practice.</p>
                            <div style="display:flex; gap:0.25rem; margin-bottom:1rem">
                                <input v-model="newCoreItem.payers" placeholder="e.g. Humana" style="flex:1; padding:0.35rem" @keyup.enter="addCoreItem('payers')">
                                <button class="btn btn-sm btn-primary" @click="addCoreItem('payers')">Add</button>
                            </div>
                            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem">
                                <li v-for="(payer, idx) in coreLists.payers" :key="payer" style="display:flex; justify-content:space-between; align-items:center; background:#f7fafc; padding:0.4rem 0.6rem; border-radius:4px; border:1px solid #e2e8f0">
                                    <span>{{ payer }}</span>
                                    <button class="btn btn-xs" @click="removeCoreItem('payers', idx)" style="background:#e53e3e; color:white; border:none; padding:0.15rem 0.35rem; border-radius:2px; cursor:pointer">&times;</button>
                                </li>
                            </ul>
                        </div>

                        <!-- Document Types Configuration -->
                        <div class="panel">
                            <h3>Document Types</h3>
                            <p style="font-size:0.8rem; color:#718096; margin-bottom:0.75rem">Configure categorizations for uploaded charts files.</p>
                            <div style="display:flex; gap:0.25rem; margin-bottom:1rem">
                                <input v-model="newCoreItem.docTypes" placeholder="e.g. Pathology Report" style="flex:1; padding:0.35rem" @keyup.enter="addCoreItem('docTypes')">
                                <button class="btn btn-sm btn-primary" @click="addCoreItem('docTypes')">Add</button>
                            </div>
                            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem">
                                <li v-for="(type, idx) in coreLists.docTypes" :key="type" style="display:flex; justify-content:space-between; align-items:center; background:#f7fafc; padding:0.4rem 0.6rem; border-radius:4px; border:1px solid #e2e8f0">
                                    <span>{{ type }}</span>
                                    <button class="btn btn-xs" @click="removeCoreItem('docTypes', idx)" style="background:#e53e3e; color:white; border:none; padding:0.15rem 0.35rem; border-radius:2px; cursor:pointer">&times;</button>
                                </li>
                            </ul>
                        </div>

                        <!-- Roles Configuration -->
                        <div class="panel">
                            <h3>User Roles &amp; Permissions</h3>
                            <p style="font-size:0.8rem; color:#718096; margin-bottom:0.75rem">Define job functions and operational titles.</p>
                            <div style="display:flex; gap:0.25rem; margin-bottom:1rem">
                                <input v-model="newCoreItem.roles" placeholder="e.g. Front Desk Staff" style="flex:1; padding:0.35rem" @keyup.enter="addCoreItem('roles')">
                                <button class="btn btn-sm btn-primary" @click="addCoreItem('roles')">Add</button>
                            </div>
                            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem">
                                <li v-for="(role, idx) in coreLists.roles" :key="role" style="display:flex; justify-content:space-between; align-items:center; background:#f7fafc; padding:0.4rem 0.6rem; border-radius:4px; border:1px solid #e2e8f0">
                                    <span>{{ role }}</span>
                                    <button class="btn btn-xs" @click="removeCoreItem('roles', idx)" style="background:#e53e3e; color:white; border:none; padding:0.15rem 0.35rem; border-radius:2px; cursor:pointer">&times;</button>
                                </li>
                            </ul>
                        </div>

                        <!-- Appointment Types Configuration -->
                        <div class="panel">
                            <h3>Appointment Visit Types</h3>
                            <p style="font-size:0.8rem; color:#718096; margin-bottom:0.75rem">Manage available clinical encounter classifications.</p>
                            <div style="display:flex; gap:0.25rem; margin-bottom:1rem">
                                <input v-model="newCoreItem.apptTypes" placeholder="e.g. Diagnostic Scan" style="flex:1; padding:0.35rem" @keyup.enter="addCoreItem('apptTypes')">
                                <button class="btn btn-sm btn-primary" @click="addCoreItem('apptTypes')">Add</button>
                            </div>
                            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem">
                                <li v-for="(type, idx) in coreLists.apptTypes" :key="type" style="display:flex; justify-content:space-between; align-items:center; background:#f7fafc; padding:0.4rem 0.6rem; border-radius:4px; border:1px solid #e2e8f0">
                                    <span>{{ type }}</span>
                                    <button class="btn btn-xs" @click="removeCoreItem('apptTypes', idx)" style="background:#e53e3e; color:white; border:none; padding:0.15rem 0.35rem; border-radius:2px; cursor:pointer">&times;</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clearinghouse view with sub-navigation tabs -->
            <div v-if="view==='billing-rcm'" class="card">
                <h2>Clearinghouse — Claims, ERA &amp; Remittance</h2>
                
                <div class="portal-nav" style="margin:1rem 0; display:flex; gap:0.5rem; flex-wrap:wrap; background:#f7fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0">
                    <button class="btn btn-sm" :class="billingTab==='claims'?'btn-primary':''" @click="billingTab='claims'">Claims Management</button>
                    <button class="btn btn-sm" :class="billingTab==='posting'?'btn-primary':''" @click="billingTab='posting'">Payment Posting</button>
                    <button class="btn btn-sm" :class="billingTab==='era'?'btn-primary':''" @click="billingTab='era'">ERA / EOB</button>
                    <button class="btn btn-sm" :class="billingTab==='denials'?'btn-primary':''" @click="billingTab='denials'">Denial Management</button>
                    <button class="btn btn-sm" :class="billingTab==='statements'?'btn-primary':''" @click="billingTab='statements'">Patient Statements</button>
                    <button class="btn btn-sm" :class="billingTab==='fee-schedule'?'btn-primary':''" @click="billingTab='fee-schedule'">Fee Schedules</button>
                </div>

                <div v-if="billingTab==='claims'" class="panel">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
                        <h3 style="margin:0">Claims Tracking &amp; Electronic Submissions</h3>
                        <span class="badge badge-blue">Availity EDI Gateway: CONNECTED 📶</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>CPT Code</th>
                                <th>Charge Amt</th>
                                <th>Scrubbing Status</th>
                                <th>EDI Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in encounters.filter(x=>x.billing_sync_status==='synced')" :key="e.id">
                                <td><strong>{{ e.patient ? e.patient.full_name : '—' }}</strong></td>
                                <td>{{ encounterPrimaryCpt(e) }}</td>
                                <td>{{ encounterChargeTotal(e) }}</td>
                                <td>
                                    <span v-if="claimScrubResults[e.id]==='scrubbing'" class="badge" style="background:#ecc94b; color:#744210">Scrubbing...</span>
                                    <span v-else-if="claimScrubResults[e.id]" class="badge badge-green">{{ claimScrubResults[e.id] }}</span>
                                    <span v-else class="badge" style="background:#e2e8f0; color:#4a5568">Ready to Scrub</span>
                                </td>
                                <td>
                                    <span v-if="claimStatuses[e.id]==='submitting'" class="badge" style="background:#3182ce; color:white">Transmitting...</span>
                                    <span v-else-if="claimStatuses[e.id]" class="badge badge-green">{{ claimStatuses[e.id] }}</span>
                                    <span v-else class="badge" style="background:#edf2f7; color:#718096">EDI Ready</span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.25rem">
                                        <button class="btn btn-xs" @click="scrubClaim(e.id)" style="padding:0.2rem 0.4rem; font-size:0.75rem">Scrub</button>
                                        <button class="btn btn-xs btn-primary" @click="submitClaimEDI(e.id)" :disabled="!claimScrubResults[e.id]" style="padding:0.2rem 0.4rem; font-size:0.75rem">Submit EDI</button>
                                        <button class="btn btn-xs btn-secondary" @click="openClaimForm(e, 'hcfa')" style="padding:0.2rem 0.4rem; font-size:0.75rem">HCFA</button>
                                        <button class="btn btn-xs btn-secondary" @click="openClaimForm(e, 'ub04')" style="padding:0.2rem 0.4rem; font-size:0.75rem">UB-04</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="billingTab==='posting'" class="panel">
                    <h3>Payment Posting Log</h3>
                    <table>
                        <thead><tr><th>Post Date</th><th>Patient</th><th>Method</th><th>Description</th><th>Posted Amt</th></tr></thead>
                        <tbody>
                            <tr><td>2026-06-24</td><td>Jane Doe</td><td>Credit Card</td><td>Copay Payment</td><td>$20.00</td></tr>
                            <tr><td>2026-06-24</td><td>John Smith</td><td>EFT Payer Check</td><td>Co-insurance posting</td><td>$110.00</td></tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="billingTab==='era'" class="panel">
                    <h3>Electronic Remittance Advice (ERA 835) Clearinghouse Sync</h3>
                    <p style="color:#4a5568; margin-bottom:1rem">Remittances fetched automatically from Availity/Change clearinghouses matching EFT deposits.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Check Date</th>
                                <th>Payer</th>
                                <th>EFT Check #</th>
                                <th>Paid Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="era in eras" :key="era.id">
                                <td>{{ era.date }}</td>
                                <td><strong>{{ era.payer }}</strong></td>
                                <td><code>{{ era.check_number }}</code></td>
                                <td><strong>${{ era.amount.toFixed(2) }}</strong></td>
                                <td>
                                    <span v-if="era.status==='posting'" class="badge" style="background:#ecc94b; color:#744210">Posting...</span>
                                    <span v-else-if="era.status==='posted'" class="badge badge-green">Auto-Posted ✅</span>
                                    <span v-else class="badge badge-blue">Pending</span>
                                </td>
                                <td>
                                    <button v-if="era.status==='pending'" class="btn btn-xs btn-primary" @click="autoPostERA(era)" style="padding:0.2rem 0.4rem; font-size:0.75rem">Process &amp; Auto-Post</button>
                                    <span v-else style="font-size:0.8rem; color:#718096">Completed</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="billingTab==='denials'" class="panel">
                    <h3>Clinical Denial Management &amp; AI Appeal Generator</h3>
                    <p style="color:#4a5568; margin-bottom:1rem">Track and generate appeal packets for claims denied during payer adjudication.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Carrier</th>
                                <th>Denial Code</th>
                                <th>Description</th>
                                <th>Denied Amt</th>
                                <th>Appeal Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in denials" :key="d.id">
                                <td><strong>{{ d.patient }}</strong></td>
                                <td>{{ d.payer }}</td>
                                <td><span class="badge badge-yellow">{{ d.code }}</span></td>
                                <td>{{ d.description }}</td>
                                <td><strong>${{ d.amount.toFixed(2) }}</strong></td>
                                <td>
                                    <span v-if="d.appeal_status==='generating'" class="badge" style="background:#ecc94b; color:#744210">Generating Appeal...</span>
                                    <span v-else-if="d.appeal_status!=='none'" class="badge badge-green">{{ d.appeal_status }}</span>
                                    <span v-else class="badge">Open Denial</span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-primary" @click="generateAIAppeal(d)" :disabled="d.appeal_status==='generating'" style="padding:0.2rem 0.4rem; font-size:0.75rem">Generate Appeal</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="billingTab==='statements'" class="panel">
                    <h3>Generate Patient Statements</h3>
                    <p style="color:#4a5568; margin-bottom:1rem">Print or transmit digital patient ledger statements for remaining account balances.</p>
                    <div style="display:flex; gap:1rem">
                        <button class="btn btn-sm btn-primary"><i class="fas fa-print" style="margin-right:6px"></i>Batch Print Statements</button>
                        <button class="btn btn-sm btn-secondary"><i class="fas fa-paper-plane" style="margin-right:6px"></i>Send Patient portal Statements</button>
                    </div>
                </div>

                <div v-if="billingTab==='fee-schedule'" class="panel">
                    <h3>Practice Fee Schedules</h3>
                    <table>
                        <thead><tr><th>CPT Code</th><th>Standard Fee</th><th>Allowed Medicare</th><th>Allowed UHC</th></tr></thead>
                        <tbody>
                            <tr><td>99213 (Outpatient Level 3)</td><td>$150.00</td><td>$110.00</td><td>$125.00</td></tr>
                            <tr><td>99214 (Outpatient Level 4)</td><td>$220.00</td><td>$160.00</td><td>$182.00</td></tr>
                            <tr><td>36415 (Venipuncture Draw)</td><td>$35.00</td><td>$12.00</td><td>$18.00</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Messages & Inbox Tab View -->
            <div v-if="view==='messages'" class="card mailbox-card">
                <div class="mailbox-header">
                    <div>
                        <h2 style="margin:0">Clinical Mailbox &amp; Patient Messages</h2>
                        <p class="mailbox-subtitle">Secure messages from the patient portal</p>
                    </div>
                    <span v-if="mailboxUnreadCount" class="badge badge-blue">{{ mailboxUnreadCount }} unread</span>
                </div>

                <div class="mailbox-layout">
                    <!-- Inbox list -->
                    <aside class="mailbox-inbox">
                        <div class="mailbox-inbox-toolbar">
                            <input v-model="mailboxSearch" type="search" placeholder="Search patients or subjects..." class="mailbox-search">
                        </div>
                        <div class="mailbox-thread-list">
                            <button
                                v-for="thread in filteredMailboxThreads"
                                :key="thread.id"
                                type="button"
                                class="mailbox-thread-item"
                                :class="{ active: selectedMailboxThreadId === thread.id, unread: thread.unread > 0 }"
                                @click="selectMailboxThread(thread.id)"
                            >
                                <div class="mailbox-thread-avatar">{{ mailboxInitials(thread.patientName) }}</div>
                                <div class="mailbox-thread-meta">
                                    <div class="mailbox-thread-top">
                                        <strong>{{ thread.patientName }}</strong>
                                        <span class="mailbox-thread-time">{{ formatMailboxTime(thread.updatedAt) }}</span>
                                    </div>
                                    <div class="mailbox-thread-subject">{{ thread.subject }}</div>
                                    <div class="mailbox-thread-preview">{{ mailboxPreview(thread) }}</div>
                                </div>
                                <span v-if="thread.unread" class="mailbox-unread-dot" aria-label="Unread"></span>
                            </button>
                            <p v-if="!filteredMailboxThreads.length" class="mailbox-empty">No messages match your search.</p>
                        </div>
                    </aside>

                    <!-- Thread panel -->
                    <section v-if="selectedMailboxThread" class="mailbox-thread-panel">
                        <header class="mailbox-thread-header">
                            <div class="mailbox-thread-avatar mailbox-thread-avatar--lg">{{ mailboxInitials(selectedMailboxThread.patientName) }}</div>
                            <div>
                                <h3 class="mailbox-thread-title">{{ selectedMailboxThread.subject }}</h3>
                                <p class="mailbox-thread-participant">{{ selectedMailboxThread.patientName }} · Patient Portal</p>
                            </div>
                        </header>

                        <div class="mailbox-messages">
                            <div
                                v-for="msg in selectedMailboxThread.messages"
                                :key="msg.id"
                                class="mailbox-message"
                                :class="msg.sender === 'staff' ? 'mailbox-message--out' : 'mailbox-message--in'"
                            >
                                <div class="mailbox-bubble">
                                    <div class="mailbox-bubble-head">
                                        <strong>{{ msg.author }}</strong>
                                        <span>{{ msg.role }}</span>
                                    </div>
                                    <p class="mailbox-bubble-body">{{ msg.body }}</p>
                                    <time class="mailbox-bubble-time">{{ formatMailboxTime(msg.sentAt) }}</time>
                                </div>
                            </div>
                        </div>

                        <footer class="mailbox-compose">
                            <textarea
                                v-model="mailboxReplyDraft"
                                rows="3"
                                placeholder="Type your reply to the patient..."
                                @keydown.ctrl.enter="sendMailboxReply"
                            ></textarea>
                            <div class="mailbox-compose-actions">
                                <span class="mailbox-compose-hint">Ctrl+Enter to send</span>
                                <button class="btn btn-sm btn-primary" :disabled="!mailboxReplyDraft.trim()" @click="sendMailboxReply">
                                    <i class="fas fa-paper-plane" style="margin-right:6px"></i>Send Reply
                                </button>
                            </div>
                        </footer>
                    </section>

                    <section v-else class="mailbox-thread-panel mailbox-thread-panel--empty">
                        <i class="fas fa-inbox" style="font-size:2rem; color:#a0aec0; margin-bottom:0.75rem"></i>
                        <p>Select a conversation from the inbox</p>
                    </section>
                </div>
            </div>

            <!-- Reports & Analytics Tab View -->
            <div v-if="view==='reports'" class="card">
                <h2>Reports &amp; Analytical Insights</h2>

                <div v-if="reportMetrics" class="stats" style="margin:1rem 0">
                    <div class="stat"><div class="num">{{ reportMetrics.patients }}</div><div class="label">Patients</div></div>
                    <div class="stat"><div class="num">{{ reportMetrics.encounters }}</div><div class="label">Encounters</div></div>
                    <div class="stat"><div class="num">{{ reportMetrics.billing_sync_rate }}%</div><div class="label">Billing Sync Rate</div></div>
                    <div class="stat"><div class="num">{{ reportMetrics.audit_events_30d }}</div><div class="label">Audit Events (30d)</div></div>
                </div>
                
                <div class="portal-nav" style="margin:1rem 0; display:flex; gap:0.5rem; background:#f7fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0">
                    <button class="btn btn-sm" :class="reportTab==='financial'?'btn-primary':''" @click="reportTab='financial'">Financial Reports</button>
                    <button class="btn btn-sm" :class="reportTab==='quality'?'btn-primary':''" @click="reportTab='quality'">Clinical Quality Measures</button>
                    <button class="btn btn-sm" :class="reportTab==='productivity'?'btn-primary':''" @click="reportTab='productivity'">Provider Productivity</button>
                    <button class="btn btn-sm" :class="reportTab==='custom'?'btn-primary':''" @click="reportTab='custom'">Audit Log</button>
                </div>

                <div v-if="reportTab==='financial'" class="panel">
                    <h3>Financial Performance Analytics &amp; A/R Aging</h3>
                    
                    <!-- Recommended Filters Card -->
                    <div style="background:#f7fafc; border:1px solid #cbd5e0; padding:1rem; border-radius:6px; margin-bottom:1.5rem">
                        <h4 style="margin:0 0 0.75rem 0; color:#2d3748">🔍 Filters</h4>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem">
                            <div class="form-group" style="margin:0">
                                <label style="font-size:0.8rem">Provider</label>
                                <select v-model="agingFilters.provider" style="width:100%; padding:0.3rem">
                                    <option value="all">All Providers</option>
                                    <option value="1">Dr. David Miller</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label style="font-size:0.8rem">Payer / Insurance</label>
                                <select v-model="agingFilters.payer" style="width:100%; padding:0.3rem">
                                    <option value="all">All Payers</option>
                                    <option value="uhc">UnitedHealthcare</option>
                                    <option value="medicare">Medicare (CMS)</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label style="font-size:0.8rem">Location</label>
                                <select v-model="agingFilters.location" style="width:100%; padding:0.3rem">
                                    <option value="all">All Facilities</option>
                                    <option value="1">Main Clinic</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label style="font-size:0.8rem">Claim Status Group</label>
                                <select v-model="agingFilters.statusGroup" style="width:100%; padding:0.3rem">
                                    <option value="all">All Claims</option>
                                    <option value="open">Open Claims</option>
                                    <option value="problem">Problem Claims</option>
                                    <option value="action">Action Required</option>
                                    <option value="closed">Closed Claims</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label style="font-size:0.8rem">Patient Name / Account</label>
                                <input v-model="agingFilters.patientName" placeholder="Search Patient..." style="width:100%; padding:0.3rem; border:1px solid #cbd5e0; border-radius:4px">
                            </div>
                        </div>
                    </div>

                    <!-- Aging by Service Date Buckets -->
                    <h3 style="color:#2d3748; margin-top:1.5rem; font-size:1.1rem; border-bottom:1px solid #edf2f7; padding-bottom:0.4rem"><i class="fas fa-calendar-day" style="color:#3182ce; margin-right:6px"></i>Aging by Service Date</h3>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; margin-top:0.75rem">
                        <div @click="agingType='service'; selectedAgingBucket='1'" style="flex:1; min-width:130px; background:#ebf8ff; border:1px solid #bee3f8; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='service' && selectedAgingBucket==='1' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #3182ce' : 'border-top: 4px solid #3182ce'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">0 - 30 Days</span>
                            <h2 style="margin:0.25rem 0">$1,020.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Service Date (Click details)</span>
                        </div>
                        <div @click="agingType='service'; selectedAgingBucket='2'" style="flex:1; min-width:130px; background:#fffaf0; border:1px solid #feebc8; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='service' && selectedAgingBucket==='2' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #dd6b20' : 'border-top: 4px solid #dd6b20'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">31 - 60 Days</span>
                            <h2 style="margin:0.25rem 0">$250.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Service Date (Click details)</span>
                        </div>
                        <div @click="agingType='service'; selectedAgingBucket='3'" style="flex:1; min-width:130px; background:#fff5f5; border:1px solid #fed7d7; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='service' && selectedAgingBucket==='3' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #e53e3e' : 'border-top: 4px solid #e53e3e'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">61 - 90 Days</span>
                            <h2 style="margin:0.25rem 0">$180.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Service Date (Click details)</span>
                        </div>
                        <div @click="agingType='service'; selectedAgingBucket='4'" style="flex:1; min-width:130px; background:#edf2f7; border:1px solid #e2e8f0; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='service' && selectedAgingBucket==='4' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #4a5568' : 'border-top: 4px solid #4a5568'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">91 - 120 Days</span>
                            <h2 style="margin:0.25rem 0">$0.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Service Date (Click details)</span>
                        </div>
                        <div @click="agingType='service'; selectedAgingBucket='5'" style="flex:1; min-width:130px; background:#1a202c; color:white; border:1px solid #2d3748; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='service' && selectedAgingBucket==='5' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #cbd5e0' : 'border-top: 4px solid #cbd5e0'">
                            <span style="font-size:0.8rem; color:#a0aec0; font-weight:bold">120+ Days</span>
                            <h2 style="margin:0.25rem 0; color:white">$150.00</h2>
                            <span style="font-size:0.75rem; color:#a0aec0">Service Date (Click details)</span>
                        </div>
                    </div>

                    <!-- Aging by Submission Date Buckets -->
                    <h3 style="color:#2d3748; margin-top:2rem; font-size:1.1rem; border-bottom:1px solid #edf2f7; padding-bottom:0.4rem"><i class="fas fa-paper-plane" style="color:#38a169; margin-right:6px"></i>Aging by Submission Date</h3>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; margin-top:0.75rem">
                        <div @click="agingType='submission'; selectedAgingBucket='1'" style="flex:1; min-width:130px; background:#e6fffa; border:1px solid #b2f5ea; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='submission' && selectedAgingBucket==='1' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #319795' : 'border-top: 4px solid #319795'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">0 - 30 Days</span>
                            <h2 style="margin:0.25rem 0">$920.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Submission Date (Click details)</span>
                        </div>
                        <div @click="agingType='submission'; selectedAgingBucket='2'" style="flex:1; min-width:130px; background:#fffaf0; border:1px solid #feebc8; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='submission' && selectedAgingBucket==='2' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #dd6b20' : 'border-top: 4px solid #dd6b20'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">31 - 60 Days</span>
                            <h2 style="margin:0.25rem 0">$310.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Submission Date (Click details)</span>
                        </div>
                        <div @click="agingType='submission'; selectedAgingBucket='3'" style="flex:1; min-width:130px; background:#fff5f5; border:1px solid #fed7d7; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='submission' && selectedAgingBucket==='3' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #e53e3e' : 'border-top: 4px solid #e53e3e'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">61 - 90 Days</span>
                            <h2 style="margin:0.25rem 0">$120.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Submission Date (Click details)</span>
                        </div>
                        <div @click="agingType='submission'; selectedAgingBucket='4'" style="flex:1; min-width:130px; background:#edf2f7; border:1px solid #e2e8f0; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='submission' && selectedAgingBucket==='4' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #4a5568' : 'border-top: 4px solid #4a5568'">
                            <span style="font-size:0.8rem; color:#4a5568; font-weight:bold">91 - 120 Days</span>
                            <h2 style="margin:0.25rem 0">$50.00</h2>
                            <span style="font-size:0.75rem; color:#718096">Submission Date (Click details)</span>
                        </div>
                        <div @click="agingType='submission'; selectedAgingBucket='5'" style="flex:1; min-width:130px; background:#1a202c; color:white; border:1px solid #2d3748; padding:1rem; border-radius:6px; cursor:pointer; transition: transform 0.2s" :style="agingType==='submission' && selectedAgingBucket==='5' ? 'transform: translateY(-4px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #cbd5e0' : 'border-top: 4px solid #cbd5e0'">
                            <span style="font-size:0.8rem; color:#a0aec0; font-weight:bold">120+ Days</span>
                            <h2 style="margin:0.25rem 0; color:white">$110.00</h2>
                            <span style="font-size:0.75rem; color:#a0aec0">Submission Date (Click details)</span>
                        </div>
                    </div>

                    <!-- Aging Reports Sub-navigation (Placed above the detailed table) -->
                    <div style="margin: 2rem 0 1rem 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; display:flex; justify-content:space-between; align-items:center">
                        <div style="display:flex; gap:0.5rem">
                            <button class="btn btn-sm" :class="agingType==='service'?'btn-primary':''" @click="agingType='service'">Aging by Service Date</button>
                            <button class="btn btn-sm" :class="agingType==='submission'?'btn-primary':''" @click="agingType='submission'">Aging by Submission Date</button>
                        </div>
                        <button v-if="selectedAgingBucket" class="btn btn-sm btn-secondary" @click="selectedAgingBucket=null" style="font-size:0.8rem">Clear Bucket Filter (Show All)</button>
                    </div>

                    <div class="panel" style="background:#fff">
                        <h4 style="margin-top:0">Detailed Aging Accounts Receivable List ({{ agingType === 'service' ? 'By Service Date' : 'By Submission Date' }}) <span v-if="selectedAgingBucket" class="badge badge-blue">Filtered: Bucket {{ selectedAgingBucket }}</span></h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Carrier</th>
                                    <th>Billed Amt</th>
                                    <th>Current Status</th>
                                    <th>Bucket Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="e in encounters.filter(x=>x.billing_sync_status==='synced' && (!selectedAgingBucket || selectedAgingBucket === '1'))" :key="e.id">
                                    <td>{{ e.patient ? e.patient.full_name : '—' }}</td>
                                    <td>{{ formatDate(e.encounter_date) }}</td>
                                    <td>UnitedHealthcare</td>
                                    <td>$150.00</td>
                                    <td><span class="badge badge-green">Submitted</span></td>
                                    <td><span class="badge">0-30 Days</span></td>
                                </tr>
                                <tr v-if="selectedAgingBucket && selectedAgingBucket !== '1'">
                                    <td colspan="6" style="text-align:center; color:#718096; padding:2rem">No claims in this bucket match the active filter criteria.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="reportTab==='quality'" class="panel">
                    <h3>CQM Quality Dashboard</h3>
                    <table v-if="qualityMetrics && qualityMetrics.measures">
                        <thead><tr><th>Measure</th><th>Name</th><th>Rate</th><th>Numerator</th><th>Denominator</th></tr></thead>
                        <tbody>
                            <tr v-for="m in qualityMetrics.measures" :key="m.id">
                                <td>{{ m.id }}</td>
                                <td>{{ m.name }}</td>
                                <td><strong>{{ m.rate }}%</strong></td>
                                <td>{{ m.numerator }}</td>
                                <td>{{ m.denominator }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else style="color:#718096">Loading quality measures…</p>
                </div>

                <div v-if="reportTab==='productivity'" class="panel">
                    <h3>Provider Productivity &amp; Charting Volume</h3>
                    <table v-if="productivityMetrics && productivityMetrics.providers">
                        <thead><tr><th>Provider</th><th>Specialty</th><th>Signed Encounters</th><th>Total Encounters</th><th>Appointments</th><th>Rx Written</th></tr></thead>
                        <tbody>
                            <tr v-for="p in productivityMetrics.providers" :key="p.provider_id">
                                <td><strong>{{ p.provider_name }}</strong></td>
                                <td>{{ p.specialty }}</td>
                                <td>{{ p.encounters_signed }}</td>
                                <td>{{ p.encounters_total }}</td>
                                <td>{{ p.appointments_completed }}</td>
                                <td>{{ p.prescriptions_written }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else style="color:#718096">Loading productivity data…</p>
                </div>

                <div v-if="reportTab==='custom'" class="panel">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem">
                        <h3 style="margin:0">PHI Audit Log (recent activity)</h3>
                        <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap">
                            <input v-model="auditFilters.event" @input="loadReports" placeholder="Filter by event/action..." style="padding:0.3rem 0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.85rem">
                            <input type="date" v-model="auditFilters.date_from" @change="loadReports" style="padding:0.25rem 0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.85rem">
                            <input type="date" v-model="auditFilters.date_to" @change="loadReports" style="padding:0.25rem 0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.85rem">
                            <button v-if="auditFilters.event || auditFilters.date_from || auditFilters.date_to" type="button" class="btn btn-sm btn-secondary" @click="auditFilters.event=''; auditFilters.date_from=''; auditFilters.date_to=''; loadReports()" style="padding:0.25rem 0.5rem; font-size:0.8rem">Clear</button>
                        </div>
                    </div>
                    <table v-if="auditLogs.length">
                        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Resource</th><th>IP</th></tr></thead>
                        <tbody>
                            <tr v-for="log in auditLogs" :key="log.id">
                                <td>{{ formatDate(log.created_at) }}</td>
                                <td>{{ log.user ? log.user.name : 'System' }}</td>
                                <td>{{ log.event }}</td>
                                <td>{{ log.auditable_type ? log.auditable_type.split('\\').pop() : '—' }} #{{ log.auditable_id || '—' }}</td>
                                <td>{{ log.ip_address || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else style="color:#718096">No audit events recorded yet.</p>
                </div>
            </div>
        </main>
        <!-- Detailed Appointment Settings Modal -->
        <div v-if="selectedApptDetail" class="modal-overlay" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.55); display:flex; justify-content:center; align-items:center; z-index:1000">
            <div class="modal-card" style="background:white; padding:2rem; border-radius:12px; max-width:650px; width:90%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); border-top: 6px solid #3182ce;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:1rem; margin-bottom:1.5rem">
                    <h2 style="margin:0; color:#2d3748; font-size:1.5rem;"><i class="fas fa-calendar-check" style="color:#3182ce; margin-right:8px"></i>Appointment Settings</h2>
                    <button class="btn btn-sm" @click="selectedApptDetail=null" style="background:#edf2f7; border:none; color:#4a5568; font-size:1rem; cursor:pointer; padding:0.25rem 0.5rem; border-radius:4px">&times;</button>
                </div>

                <!-- Patient & Quick info header -->
                <div style="background:#f7fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1rem; margin-bottom:1.5rem; display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem; font-size:0.9rem">
                    <div><strong>Patient:</strong> {{ selectedApptDetail.patient ? selectedApptDetail.patient.first_name + ' ' + selectedApptDetail.patient.last_name : '—' }}</div>
                    <div><strong>DOB:</strong> {{ selectedApptDetail.patient ? selectedApptDetail.patient.date_of_birth : '—' }}</div>
                    <div><strong>MRN:</strong> {{ selectedApptDetail.patient ? (selectedApptDetail.patient.mrn || 'N/A') : '—' }}</div>
                    <div><strong>Sync Status:</strong> <span class="badge badge-green">{{ selectedApptDetail.portal_sync_status || 'Synced' }}</span></div>
                </div>

                <div class="form-row" style="margin-bottom:1rem">
                    <div class="form-group" style="flex:1; min-width:200px">
                        <label>Provider</label>
                        <select v-model="selectedApptDetail.provider_id" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0">
                            <option v-for="pr in providers" :key="pr.id" :value="pr.id">Dr. {{ pr.last_name }} ({{ pr.specialty }})</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1; min-width:200px">
                        <label>Location</label>
                        <select v-model="selectedApptDetail.location_id" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0">
                            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:1rem">
                    <div class="form-group" style="flex:1; min-width:200px">
                        <label>Scheduled Date &amp; Time</label>
                        <input type="datetime-local" v-model="selectedApptDetail.scheduled_at" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0">
                    </div>
                    <div class="form-group" style="flex:1; min-width:200px">
                        <label>Duration</label>
                        <select v-model="selectedApptDetail.duration_minutes" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0">
                            <option :value="15">15 Minutes</option>
                            <option :value="30">30 Minutes</option>
                            <option :value="45">45 Minutes</option>
                            <option :value="60">60 Minutes</option>
                            <option :value="90">90 Minutes</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:1rem">
                    <div class="form-group" style="flex:1; min-width:200px">
                        <label>Visit Type</label>
                        <select v-model="selectedApptDetail.appointment_type" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0">
                            <option value="office_visit">Office Visit</option>
                            <option value="telehealth">Telehealth (Video Visit)</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1; min-width:200px">
                        <label>Appointment Status</label>
                        <select v-model="selectedApptDetail.status" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0">
                            <option value="scheduled">Scheduled</option>
                            <option value="checked_in">Checked In</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.5rem">
                    <label>Reason / Notes</label>
                    <textarea v-model="selectedApptDetail.notes" rows="2" style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-family:inherit"></textarea>
                </div>

                <!-- Realtime Insurance Eligibility Check Sub-panel -->
                <div style="background:#ebf8ff; border:1px solid #bee3f8; border-radius:8px; padding:1rem; margin-bottom:1.5rem">
                    <div style="display:flex; justify-content:space-between; align-items:center">
                        <span style="font-weight:bold; color:#2b6cb0"><i class="fas fa-shield-alt" style="margin-right:6px"></i>Carrier Realtime Eligibility Check</span>
                        <button class="btn btn-sm btn-primary" @click="checkEligibility" :disabled="isEligibilityChecking" style="padding:0.25rem 0.5rem; font-size:0.8rem">
                            <span v-if="isEligibilityChecking">Verifying...</span>
                            <span v-else>Run 270/271 Check</span>
                        </button>
                    </div>
                    <!-- Result display -->
                    <div v-if="eligibilityResult" style="margin-top:0.75rem; font-size:0.85rem; border-top:1px solid #bee3f8; padding-top:0.5rem; display:grid; grid-template-columns:1fr 1fr; gap:0.25rem">
                        <div><strong>Status:</strong> <span style="color:#2f855a; font-weight:bold">{{ eligibilityResult.status }}</span></div>
                        <div><strong>Payer:</strong> {{ eligibilityResult.payer }}</div>
                        <div><strong>Copay:</strong> {{ eligibilityResult.copay }}</div>
                        <div><strong>Deductible:</strong> {{ eligibilityResult.deductible }}</div>
                        <div style="grid-column: span 2; font-size:0.75rem; color:#718096; margin-top:0.25rem">Checked at: {{ eligibilityResult.verifiedAt }} via Availity Real-time Engine</div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e2e8f0; padding-top:1rem">
                    <div style="display:flex; gap:0.5rem">
                        <button class="btn btn-sm" @click="sendAppointmentReminder" style="background:#edf2f7; color:#4a5568"><i class="fas fa-bell" style="margin-right:4px"></i>Send Reminder</button>
                        <button v-if="selectedApptDetail.appointment_type==='telehealth' && selectedApptDetail.status==='scheduled'" class="btn btn-sm" style="background:#3182ce; color:white;" @click="selectedApptDetail=null; startTelehealthMeeting(selectedApptDetail)"><i class="fas fa-video" style="margin-right:4px"></i>Launch Video</button>
                        <button v-if="selectedApptDetail.status==='scheduled'" class="btn btn-sm btn-primary" @click="selectedApptDetail=null; checkInAppt(selectedApptDetail.id)"><i class="fas fa-check" style="margin-right:4px"></i>Check In</button>
                    </div>
                    <div style="display:flex; gap:0.5rem">
                        <button class="btn btn-sm" @click="selectedApptDetail=null" style="background:#edf2f7; color:#4a5568">Close</button>
                        <button class="btn btn-sm btn-primary" @click="updateApptDetails">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Claim Form Preview Modal (CMS-1500 / UB-04) -->
        <div v-if="claimFormLoading || claimFormPreview" class="modal-overlay" style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; justify-content:center; align-items:flex-start; z-index:1050; padding:1rem; overflow-y:auto">
            <div class="claim-form-modal">
                <div class="claim-form-toolbar">
                    <div>
                        <h3 style="margin:0; color:#1e4d6b; font-size:1.05rem">
                            <i class="fas fa-file-invoice" style="margin-right:6px"></i>
                            {{ claimFormPreview ? claimFormPreview.title : 'Generating claim form...' }}
                        </h3>
                        <p v-if="claimFormPreview" style="margin:0.25rem 0 0; font-size:0.72rem; color:#718096">{{ claimFormPreview.standard }}</p>
                    </div>
                    <div class="claim-form-modal-actions" style="display:flex; gap:0.5rem">
                        <button v-if="claimFormPreview && (claimFormPdfUrl || claimFormType === 'hcfa' || claimFormType === 'ub04')" class="btn btn-sm" :disabled="claimFormSaving" @click="regenerateClaimFormPdf">
                            <i class="fas fa-sync" style="margin-right:4px"></i>{{ claimFormSaving ? 'Applying…' : 'Apply edits' }}
                        </button>
                        <button v-if="claimFormPreview" class="btn btn-sm btn-primary" :disabled="claimFormSaving" @click="printClaimForm"><i class="fas fa-print" style="margin-right:4px"></i>Print</button>
                        <button class="btn btn-sm" @click="closeClaimForm">Close</button>
                    </div>
                </div>

                <div v-if="claimFormLoading" style="text-align:center; padding:3rem; color:#4a5568">
                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem; margin-bottom:0.75rem"></i>
                    <p style="margin:0">Rendering official PDF template…</p>
                    <p style="margin:0.5rem 0 0; font-size:0.75rem; color:#718096">First load may take 10–15 seconds.</p>
                </div>

                <template v-else-if="claimFormPreview">
                    <div class="claim-form-meta">
                        Claim <strong>{{ claimFormPreview.claim_number }}</strong>
                        &middot; Encounter {{ claimFormPreview.encounter_uuid }}
                        &middot; Generated {{ claimFormPreview.generated_at }}
                    </div>

                    <div class="claim-form-pdf-wrap">
                        <div
                            v-if="claimFormPreview && (claimFormPdfUrl || claimFormType === 'hcfa' || claimFormType === 'ub04')"
                            id="claim-form-pdf-pages"
                            class="claim-form-pages"
                        ></div>
                        <p v-else-if="claimFormPreview && !claimFormLoading" class="claim-form-error">{{ error || 'PDF could not be loaded.' }}</p>
                    </div>
                    <p class="claim-form-template-note">
                        Click any field on the form to edit. Use <strong>Apply edits</strong> to refresh the PDF, or <strong>Print</strong> to apply and print.
                    </p>
                </template>
            </div>
        </div>

        <!-- AI Appeal Letter Modal -->
        <div v-if="showAppealModal" class="modal-overlay" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.55); display:flex; justify-content:center; align-items:center; z-index:1060">
            <div class="modal-card" style="background:white; padding:2rem; border-radius:12px; max-width:600px; width:90%; box-shadow:0 20px 25px rgba(0,0,0,0.15)">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:1rem; margin-bottom:1.5rem">
                    <h3 style="margin:0; color:#2d3748"><i class="fas fa-file-signature" style="color:#38a169; margin-right:6px"></i>AI Ambient Appeal Letter</h3>
                    <button class="btn btn-sm" @click="showAppealModal=false" style="background:#edf2f7; border:none; padding:0.25rem 0.5rem; border-radius:4px; cursor:pointer">&times;</button>
                </div>
                <textarea v-model="generatedAppealLetter" rows="12" style="width:100%; padding:0.75rem; border-radius:6px; border:1px solid #cbd5e0; font-family:monospace; font-size:0.85rem; line-height:1.5; color:#2d3748; background:#f7fafc"></textarea>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem">
                    <button class="btn btn-sm" @click="showAppealModal=false">Close</button>
                    <button class="btn btn-sm btn-primary" @click="toast='Appeal Letter copied to clipboard'; showAppealModal=false">Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>
@endverbatim
</div>
<script src="/js/hcfa-acro-map.js"></script>
<script src="/js/hcfa-overlays.js"></script>
<script src="/js/ub04-overlays.js"></script>
<script src="/js/claim-form-viewer.js"></script>
<script src="/js/ehr-admin.js"></script>
</body>
</html>
