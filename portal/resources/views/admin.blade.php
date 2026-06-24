<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocRev Patient Portal</title>
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
    <div v-if="!token" class="portal-login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <img src="/img/logo.png" alt="DocRev" class="docrev-brand-logo">
                <p class="docrev-brand-product">Patient Portal</p>
            </div>
            <div class="form-group"><label>Email</label><input v-model="loginForm.email" type="email"></div>
            <div class="form-group"><label>Password</label><input v-model="loginForm.password" type="password"></div>
            <button class="btn btn-primary" style="width:100%" @click="login">Sign In</button>
            <p v-if="error" class="error">{{ error }}</p>
        </div>
    </div>
    <div v-else class="wrap">
        <div class="portal-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <img src="/img/logo.png" alt="DocRev" class="docrev-brand-logo">
                <span class="docrev-brand-product">Patient Portal</span>
            </div>
            <button class="btn btn-sm logout" @click="logout">Logout</button>
        </div>
        <p v-if="toast" class="toast">{{ toast }}</p>
        
        <div class="portal-nav" style="margin: 1.5rem 0; display:flex; gap:0.75rem; flex-wrap:wrap;">
            <button class="btn" :class="view==='dashboard'?'btn-primary':''" @click="view='dashboard'">Dashboard</button>
            <button class="btn" :class="view==='appointments'?'btn-primary':''" @click="view='appointments'">Appointments</button>
            <button class="btn" :class="view==='medications'?'btn-primary':''" @click="view='medications'">My Medications</button>
            <button class="btn" :class="view==='forms'?'btn-primary':''" @click="view='forms'">My Forms &amp; Consents ({{ forms.filter(f=>f.status==='pending').length }})</button>
            <button class="btn" :class="view==='statements'?'btn-primary':''" @click="view='statements'">Statements & Bills</button>
        </div>

        <div v-if="view==='dashboard'">
            <div class="card">
                <h2>Welcome, {{ patient.first_name }} {{ patient.last_name }}</h2>
                <p style="color:#4a5568; margin-top:0.5rem;">Manage your appointments, view statements, check your medication history, or make a payment safely from the menu above.</p>
            </div>
            <div class="stats" style="margin-top:1.5rem;">
                <div class="stat" style="cursor:pointer" @click="view='appointments'"><div class="num">{{ appointments.length }}</div><div class="label">Total Appointments</div></div>
                <div class="stat" style="cursor:pointer" @click="view='medications'"><div class="num">{{ medications.length }}</div><div class="label">Medications</div></div>
                <div class="stat" style="cursor:pointer" @click="view='statements'"><div class="num">{{ statements.filter(s=>s.balance_due>0).length }}</div><div class="label">Unpaid Bills</div></div>
            </div>
        </div>

        <div v-if="view==='appointments'">
            <!-- Patient Telehealth Video Meeting Room -->
            <div v-if="isMeetingActive" class="panel" style="background:#1a202c; color:white; border-radius:8px; padding:1.5rem; margin-bottom:1.5rem; position:relative; box-shadow: 0 10px 25px rgba(0,0,0,0.3)">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #4a5568; padding-bottom:0.75rem">
                    <div style="display:flex; align-items:center; gap:0.5rem">
                        <span style="color:#e53e3e; font-size:1.5rem; animation: pulse 1.5s infinite">🔴</span>
                        <h3 style="margin:0; color:white">Telehealth Consultation: {{ meetingAppointment ? meetingAppointment.provider_name : '' }}</h3>
                    </div>
                    <div style="display:flex; align-items:center; gap:1rem">
                        <span style="background:#2d3748; padding:0.25rem 0.75rem; border-radius:4px; font-family:monospace; font-size:0.9rem">{{ meetingTimer }}</span>
                        <span class="badge" style="background:#38a169; color:white">HD Quality 📶</span>
                    </div>
                </div>

                <!-- Video Grid Mockup -->
                <div style="display:flex; gap:1rem; height:320px; margin-bottom:1rem">
                    <!-- Provider Remote Stream -->
                    <div style="flex:1; background:#2d3748; border-radius:6px; overflow:hidden; position:relative; display:flex; justify-content:center; align-items:center; border:2px solid #4a5568">
                        <div v-if="isProviderCameraOn" style="text-align:center">
                            <span style="font-size:4rem">👨‍⚕️</span>
                            <p style="margin-top:0.5rem; color:#cbd5e0; font-size:0.9rem">Doctor - Video Feed</p>
                        </div>
                        <div v-else style="text-align:center; color:#a0aec0">
                            <span style="font-size:3rem">🔇</span>
                            <p style="margin-top:0.5rem; font-size:0.9rem">Doctor camera is off</p>
                        </div>
                        <span style="position:absolute; bottom:0.5rem; left:0.5rem; background:rgba(0,0,0,0.6); padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem">Doctor (Remote)</span>
                    </div>
                    <!-- Patient Local Stream -->
                    <div style="flex:1; background:#2d3748; border-radius:6px; overflow:hidden; position:relative; display:flex; justify-content:center; align-items:center; border:2px solid #3182ce">
                        <div v-if="isLocalCameraOn" style="text-align:center">
                            <span style="font-size:4rem">🤒</span>
                            <p style="margin-top:0.5rem; color:#cbd5e0; font-size:0.9rem">You - Preview</p>
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
                            <div v-for="msg in meetingMessages" :key="msg.time" :style="{textAlign: msg.sender==='Patient'?'right':'left'}">
                                <span style="color:#a0aec0; font-size:0.7rem">{{ msg.sender }} - {{ msg.time }}</span>
                                <div :style="{background: msg.sender==='Patient'?'#3182ce':'#4a5568', padding: '0.4rem 0.6rem', borderRadius: '6px', display: 'inline-block', maxWidth: '85%', marginTop: '0.1rem', wordBreak: 'break-word', color: 'white'}">
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
                    <button class="btn btn-sm" style="background:#e53e3e; border:none; color:white" @click="endTelehealthMeeting">🔴 Leave Room</button>
                </div>
            </div>

            <!-- Interactive Timeline Calendar Grid (Patient View) -->
            <div class="card" style="margin-bottom:1.5rem; overflow-x:auto">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e2e8f0; padding-bottom:0.75rem; margin-bottom:1rem">
                    <div style="display:flex; align-items:center; gap:0.5rem">
                        <button class="btn btn-sm" style="background:#edf2f7" @click="prevDay()"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-sm" style="background:#edf2f7" @click="nextDay()"><i class="fas fa-chevron-right"></i></button>
                        <button class="btn btn-sm" style="background:#edf2f7" @click="goToToday()">Today</button>
                        <span style="font-weight:bold; font-size:1.1rem; color:#2d3748">@{{ formatCalendarHeader() }}</span>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center">
                        <input type="date" v-model="calendarDateInput" @change="setDateFromInput" style="padding:0.3rem 0.5rem; border-radius:6px; border:1px solid #cbd5e0; font-size:0.9rem">
                        <span class="badge" style="background:#48bb78; color:white">office_visit</span>
                        <span class="badge" style="background:#3182ce; color:white">telehealth</span>
                    </div>
                </div>

                <div style="min-width:700px">
                    <!-- Hours Grid Headers -->
                    <div style="display:flex; background:#edf2f7; font-weight:bold; border-bottom:1px solid #cbd5e0">
                        <div style="width:150px; padding:0.5rem; border-right:1px solid #cbd5e0">Timeline</div>
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

                    <!-- Agenda Schedule Row -->
                    <div style="display:flex; border-bottom:1px solid #e2e8f0; height:80px; position:relative; background:#fff">
                        <div style="width:150px; padding:0.5rem; border-right:1px solid #cbd5e0; display:flex; flex-direction:column; justify-content:center; background:#f7fafc; font-weight:bold; font-size:0.9rem">
                            <span>My Agenda</span>
                            <span style="font-weight:normal; font-size:0.75rem; color:#718096">Scheduled Visits</span>
                        </div>

                        <div style="flex:10; display:flex; position:relative; height:100%">
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8; background:#edf2f7; display:flex; justify-content:center; align-items:center; color:#a0aec0; font-size:0.8rem">Rest Period</div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1; border-right:1px solid #f0f4f8"></div>
                            <div style="flex:1;"></div>

                            <!-- Calendar Appts blocks -->
                            <div v-for="appt in getApptsForDate()" :key="appt.id"
                                 style="position:absolute; top:10px; height:55px; border-radius:4px; padding:0.4rem; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,0.1); cursor:pointer; display:flex; flex-direction:column; justify-content:space-between"
                                 :style="getApptStyle(appt)">
                                <div style="font-weight:bold; text-overflow:ellipsis; white-space:nowrap; overflow:hidden">
                                    {{ appt.provider_name }}
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.7rem; opacity:0.9">
                                    <span>{{ appt.appointment_type }}</span>
                                    <button v-if="appt.appointment_type==='telehealth' && appt.status==='scheduled'" @click="startTelehealthMeeting(appt)" style="background:#38a169; border:none; color:white; font-size:0.65rem; padding:0.1rem 0.3rem; border-radius:2px; cursor:pointer">Join Room</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:1.5rem">
                <h2>Request a New Appointment</h2>
                <div class="form-row" style="margin-top:1rem">
                    <div class="form-group" style="flex:1; min-width:250px">
                        <label>Provider / Doctor</label>
                        <select v-model="apptForm.provider_id" style="width:100%; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e0;">
                            <option value="">-- Select a Doctor --</option>
                            <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }} ({{ p.specialty || 'General Clinical' }})</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1; min-width:250px">
                        <label>Preferred Date &amp; Time</label>
                        <input type="datetime-local" v-model="apptForm.scheduled_at" style="width:100%; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e0;">
                    </div>
                </div>
                <div class="form-group" style="margin-top:1rem">
                    <label>Reason for Visit / Special Notes</label>
                    <textarea v-model="apptForm.notes" rows="3" placeholder="Explain your symptoms or request detail" style="width:100%; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e0; font-family:inherit;"></textarea>
                </div>
                <button class="btn btn-primary" style="margin-top:1rem;" @click="submitRequest">Submit Appointment Request</button>
            </div>
        </div>

        <div v-if="view==='medications'" class="card">
            <h2>My Prescriptions &amp; Medications</h2>
            <table v-if="medications.length">
                <thead><tr><th>Medication</th><th>Instructions (Sig)</th><th>Prescriber</th><th>Pharmacy</th><th>Status</th></tr></thead>
                <tbody>
                    <tr v-for="m in medications" :key="m.id">
                        <td><strong>{{ m.drug_name }}</strong><br><span style="font-size:0.8rem; color:#718096">NDC: {{ m.ndc }}</span></td>
                        <td>{{ m.sig }}</td>
                        <td>{{ m.provider_name }}</td>
                        <td>{{ m.pharmacy_name }}</td>
                        <td><span class="badge" :class="m.status==='sent'?'badge-green':'badge-blue'">{{ m.status }}</span></td>
                    </tr>
                </tbody>
            </table>
            <p v-else style="color:#718096">No prescriptions found.</p>
        </div>

        <div v-if="view==='statements'" class="card">
            <h2>Statements & Balances</h2>
            <table v-if="statements.length">
                <thead><tr><th>Date</th><th>Total</th><th>Balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="s in statements" :key="s.id">
                        <td>{{ s.statement_date }}</td>
                        <td>${{ s.total_amount }}</td>
                        <td>${{ s.balance_due }}</td>
                        <td><span class="badge" :class="s.status==='paid'?'badge-green':'badge-blue'">{{ s.status }}</span></td>
                        <td>
                            <button v-if="s.balance_due > 0" class="btn btn-sm btn-primary" @click="payStatement(s)">Pay Balance</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else style="color:#718096">No statements found.</p>
        </div>

        <div v-if="view==='forms'" class="card">
            <h2>My Consent Forms &amp; Intake Screeners</h2>
            <p style="font-size:0.875rem; color:#4a5568; margin-bottom:1rem">Forms sent on demand by your care team for review and signature.</p>
            <table v-if="forms.length">
                <thead><tr><th>Form Name</th><th>Status</th><th>Signed/Received Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <tr v-for="f in forms" :key="f.id">
                        <td><strong>{{ f.form_name }}</strong></td>
                        <td><span class="badge" :class="f.status==='signed'?'badge-green':'badge-blue'">{{ f.status }}</span></td>
                        <td>{{ formatDate(f.signed_at || f.created_at) }}</td>
                        <td>
                            <button v-if="f.status==='pending'" class="btn btn-sm btn-primary" @click="openSignForm(f)">Fill &amp; Sign</button>
                            <span v-else>Signed: <code>{{ f.signature_name }}</code></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else style="color:#718096">No pending or completed forms on file.</p>
        </div>

        <!-- Digital Signature Modal -->
        <div v-if="showSignModal && selectedForm" class="modal-overlay" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:1000">
            <div class="modal-card" style="background:white; padding:2rem; border-radius:8px; max-width:600px; width:90%; box-shadow:0 10px 15px rgba(0,0,0,0.1)">
                <h2>Sign: {{ selectedForm.form_name }}</h2>
                <div style="background:#f7fafc; padding:1rem; border-radius:4px; max-height:250px; overflow-y:auto; border:1px solid #e2e8f0; margin-top:1rem; font-size:0.9rem; line-height:1.5; color:#2d3748">
                    {{ selectedForm.form_content }}
                </div>
                <div style="margin-top:1.5rem">
                    <label style="font-weight:bold; display:block; font-size:0.9rem">Type your full legal name to sign digitally</label>
                    <input v-model="signatureName" style="width:100%; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e0; margin-top:0.25rem" placeholder="Jane Doe">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem">
                    <button class="btn btn-sm" @click="showSignModal=false">Cancel</button>
                    <button class="btn btn-sm btn-primary" @click="submitSignature">Agree &amp; Sign</button>
                </div>
            </div>
        </div>
    </div>
@endverbatim
</div>
<script src="/js/portal-admin.js"></script>
</body>
</html>
