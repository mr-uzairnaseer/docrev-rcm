<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('admin.title') }}</title>
    <link rel="icon" href="/img/logo.png" type="image/png">
    <link rel="icon" href="/img/logo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/img/logo.png">
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    @include('partials.admin-styles', ['accent' => config('admin.theme')])
</head>
<body>
<div id="app">
@verbatim
    <div v-if="!token" class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <img src="/img/logo.png" alt="DocRev" class="docrev-brand-logo">
                <p class="docrev-brand-product">Clearinghouse</p>
            </div>
            <div class="form-group"><label>Email</label><input v-model="loginForm.email" type="email"></div>
            <div class="form-group"><label>Password</label><input v-model="loginForm.password" type="password"></div>
            <button class="btn btn-primary" style="width:100%" @click="login">Sign In</button>
            <p v-if="error" class="error">{{ error }}</p>
        </div>
    </div>
    <div v-else class="layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="/img/logo-white.png" alt="DocRev" class="docrev-brand-logo">
                <span class="docrev-brand-product">Clearinghouse</span>
            </div>
            <nav>
                <a :class="{active: view==='dashboard'}" @click="setView('dashboard')">Dashboard</a>
                <a :class="{active: view==='eligibility'}" @click="setView('eligibility')">Eligibility</a>
                <a :class="{active: view==='charges'}" @click="setView('charges')">Charges</a>
                <a :class="{active: view==='claims'}" @click="setView('claims')">Claims</a>
                <a :class="{active: view==='eras'}" @click="setView('eras')">ERA / Payments</a>
                <a :class="{active: view==='denials'}" @click="setView('denials')">Denials</a>
                <a :class="{active: view==='qa'}" @click="setView('qa')">QA Tracker</a>
                <a :class="{active: view==='cms'}" @click="setView('cms')">CMS Reference</a>
                <a :class="{active: view==='setup'}" @click="setView('setup')">Setup</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" @click.prevent="logout" class="logout">Logout</a>
            </div>
        </aside>
        <main class="main">
            <div v-if="toast" class="toast" role="status">
                <span class="toast__text">{{ toast }}</span>
                <button type="button" class="toast__close" @click="toast = ''" aria-label="Dismiss notification">&times;</button>
            </div>

            <div v-if="view==='dashboard'">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem">
                    <h2 style="margin:0; color:#1e4d6b">RCM Summary</h2>
                    <span style="font-size:0.75rem; color:#718096">
                        <span style="display:inline-block; width:8px; height:8px; background:#48bb78; border-radius:50%; margin-right:4px"></span>
                        Live sync{{ dashLastUpdated ? ' · ' + formatDate(dashLastUpdated) : '' }}
                    </span>
                </div>
                <div class="stats">
                    <div class="stat"><div class="num">${{ dash.ar ? dash.ar.patient_responsibility : '0' }}</div><div class="label">Patient A/R</div></div>
                    <div class="stat"><div class="num">${{ dash.claims ? dash.claims.total_paid : '0' }}</div><div class="label">Payer Paid</div></div>
                    <div class="stat"><div class="num">{{ dash.claims ? dash.claims.submitted : 0 }}</div><div class="label">Submitted</div></div>
                    <div class="stat"><div class="num">{{ dash.denials ? dash.denials.open : 0 }}</div><div class="label">Open Denials</div></div>
                </div>
                <div class="card">
                    <h2>Clearinghouse Summary</h2>
                    <table>
                        <tbody>
                            <tr><td>Ready charges</td><td>{{ dash.charges ? dash.charges.ready : 0 }}</td></tr>
                            <tr><td>Claims paid</td><td>{{ dash.claims ? dash.claims.paid : 0 }}</td></tr>
                            <tr><td>Claims denied</td><td>{{ dash.claims ? dash.claims.denied : 0 }}</td></tr>
                            <tr><td>Patient payments received</td><td>${{ dash.ar ? dash.ar.patient_payments_received : '0' }}</td></tr>
                            <tr><td>ERAs posted</td><td>{{ dash.eras ? dash.eras.total : 0 }} (${{ dash.eras ? dash.eras.total_posted : '0' }})</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="card" style="margin-top:1.5rem">
                    <h2>Accounts Receivable (A/R) Aging Analytics</h2>
                    <p style="font-size:0.875rem; color:#4a5568; margin-bottom:1rem">Outstanding claims and patient responsibilities segmented by days since billing.</p>
                    <div class="stats" style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap">
                        <div class="stat" style="flex:1; min-width:140px; background:#f7fafc; border-bottom: 4px solid #48bb78">
                            <div class="num" style="color:#2f855a">${{ dash.aging ? dash.aging['0_30'] : '0.00' }}</div>
                            <div class="label">0 - 30 Days</div>
                            <div style="background:#e2e8f0; height:8px; border-radius:4px; margin-top:0.5rem; overflow:hidden">
                                <div :style="{ width: agingBarPercent('0_30') + '%' }" style="background:#48bb78; height:100%"></div>
                            </div>
                        </div>
                        <div class="stat" style="flex:1; min-width:140px; background:#f7fafc; border-bottom: 4px solid #ecc94b">
                            <div class="num" style="color:#b7791f">${{ dash.aging ? dash.aging['31_60'] : '0.00' }}</div>
                            <div class="label">31 - 60 Days</div>
                            <div style="background:#e2e8f0; height:8px; border-radius:4px; margin-top:0.5rem; overflow:hidden">
                                <div :style="{ width: agingBarPercent('31_60') + '%' }" style="background:#ecc94b; height:100%"></div>
                            </div>
                        </div>
                        <div class="stat" style="flex:1; min-width:140px; background:#f7fafc; border-bottom: 4px solid #ed8936">
                            <div class="num" style="color:#dd6b20">${{ dash.aging ? dash.aging['61_90'] : '0.00' }}</div>
                            <div class="label">61 - 90 Days</div>
                            <div style="background:#e2e8f0; height:8px; border-radius:4px; margin-top:0.5rem; overflow:hidden">
                                <div :style="{ width: agingBarPercent('61_90') + '%' }" style="background:#ed8936; height:100%"></div>
                            </div>
                        </div>
                        <div class="stat" style="flex:1; min-width:140px; background:#f7fafc; border-bottom: 4px solid #f56565">
                            <div class="num" style="color:#c53030">${{ dash.aging ? dash.aging['91_plus'] : '0.00' }}</div>
                            <div class="label">90+ Days (Critical)</div>
                            <div style="background:#e2e8f0; height:8px; border-radius:4px; margin-top:0.5rem; overflow:hidden">
                                <div :style="{ width: agingBarPercent('91_plus') + '%' }" style="background:#f56565; height:100%"></div>
                            </div>
                        </div>
                    </div>
                    <p v-if="dash.aging" style="font-size:0.75rem; color:#718096; margin-top:0.75rem">Total outstanding A/R: <strong>${{ dash.aging.total || '0.00' }}</strong></p>
                </div>
            </div>

            <div v-if="view==='charges'" class="card">
                <h2>Charges</h2>
                <table>
                    <thead><tr><th>Patient</th><th>Date</th><th>CPT</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr v-for="c in charges" :key="c.id">
                            <td>{{ patientName(c.patient_id) }}</td>
                            <td>{{ c.service_date }}</td>
                            <td>{{ c.cpt_code || '—' }}</td>
                            <td>${{ c.charge_amount }}</td>
                            <td><span class="badge">{{ c.status }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='claims'" class="card">
                <div class="row-between">
                    <h2>Claims</h2>
                    <div>
                        <button class="btn" @click="exportClaims">Export CSV</button>
                        <button class="btn btn-primary" @click="showBuildClaim = !showBuildClaim">Build Claim</button>
                    </div>
                </div>

                <div v-if="showBuildClaim" class="panel">
                    <h3>Build from Ready Charges</h3>
                    <div class="form-row">
                        <div class="form-group"><label>Patient ID</label><input v-model="buildForm.patient_id" type="number"></div>
                        <div class="form-group"><label>Payer ID</label><input v-model="buildForm.payer_id" type="number"></div>
                        <div class="form-group"><label>ICD-10 (comma)</label><input v-model="buildForm.icd10" placeholder="Z00.00"></div>
                        <div class="form-group"><label>Place of Service</label>
                            <select v-model="buildForm.place_of_service">
                                <option value="11">11 — Office</option>
                                <option value="02">02 — Telehealth</option>
                                <option value="10">10 — Telehealth (patient home)</option>
                                <option value="20">20 — Urgent Care</option>
                                <option value="21">21 — Inpatient Hospital</option>
                                <option value="22">22 — Outpatient Hospital</option>
                                <option value="23">23 — ER</option>
                                <option value="31">31 — Skilled Nursing</option>
                                <option value="32">32 — Nursing Facility</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Charge IDs (comma)</label>
                        <input v-model="buildForm.charge_ids" :placeholder="readyChargeIds">
                    </div>
                    <button class="btn btn-primary" @click="buildClaim">Create Draft Claim</button>
                </div>

                <div class="panel">
                    <h3>Export Filters</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status</label>
                            <select v-model="exportForm.status">
                                <option value="">All statuses</option>
                                <option value="draft">Draft</option>
                                <option value="ready">Ready</option>
                                <option value="submitted">Submitted</option>
                                <option value="paid">Paid</option>
                                <option value="denied">Denied</option>
                                <option value="partial">Partial</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Service From</label><input v-model="exportForm.from" type="date"></div>
                        <div class="form-group"><label>Service To</label><input v-model="exportForm.to" type="date"></div>
                    </div>
                </div>

                <table>
                    <thead><tr><th>Claim #</th><th>Patient</th><th>Total</th><th>Status</th><th>Paid</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr v-for="cl in claims" :key="cl.id">
                            <td>{{ cl.claim_number }}<span v-if="cl.frequency_code==='7'" class="badge badge-yellow" style="margin-left:0.35rem">COR</span></td>
                            <td>{{ cl.patient ? cl.patient.first_name + ' ' + cl.patient.last_name : '—' }}</td>
                            <td>${{ cl.total_charge_amount }}</td>
                            <td><span class="badge">{{ cl.status }}</span></td>
                            <td>{{ cl.paid_amount ? '$' + cl.paid_amount : '—' }}</td>
                            <td class="actions">
                                <button v-if="cl.status==='draft'" class="btn btn-sm" @click="markReady(cl.id)">Scrub & EDI</button>
                                <button v-if="cl.status==='ready'" class="btn btn-sm btn-primary" @click="submitClaim(cl.id)">Submit</button>
                                <button v-if="cl.status==='submitted'" class="btn btn-sm btn-primary" @click="simulateEra(cl.id)">Simulate ERA</button>
                                <button v-if="cl.status==='submitted'" class="btn btn-sm" @click="simulateDenial(cl.id)">Simulate Denial</button>
                                <button v-if="cl.status==='denied' || cl.status==='rejected'" class="btn btn-sm btn-primary" @click="correctAndResubmit(cl.id)">Correct & Resubmit</button>
                                <button v-if="cl.status==='denied' || cl.status==='rejected'" class="btn btn-sm" @click="createCorrected(cl.id)">Correct (Draft)</button>
                                <button v-if="cl.has_edi" class="btn btn-sm" @click="viewEdi(cl.id)">View EDI</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="ediPreview" class="panel">
                    <h3>EDI 837P Professional Claim Preview (HIPAA 5010)</h3>
                    <pre class="edi-box">{{ ediPreview }}</pre>
                </div>
            </div>

            <div v-if="view==='eras'" class="card">
                <h2>ERA / Payment Posting</h2>
                <div class="panel">
                    <h3>Import ERA 835</h3>
                    <div class="form-group">
                        <label>Paste EDI 835 content</label>
                        <textarea v-model="eraImport" rows="6" style="width:100%;font-family:monospace;font-size:0.8rem"></textarea>
                    </div>
                    <button class="btn btn-primary" @click="importEra">Post ERA</button>
                </div>
                
                <div class="panel">
                    <h3>Payer ERA Remittances</h3>
                    <table>
                        <thead><tr><th>Trace #</th><th>Claims</th><th>Matched</th><th>Total Paid</th><th>Status</th><th>Posted</th></tr></thead>
                        <tbody>
                            <tr v-for="era in eras" :key="era.id">
                                <td>{{ era.trace_number }}</td>
                                <td>{{ era.claim_count }}</td>
                                <td>{{ era.matched_count }}</td>
                                <td>${{ era.total_payment_amount }}</td>
                                <td><span class="badge">{{ era.status }}</span></td>
                                <td>{{ formatDate(era.posted_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="panel" style="margin-top:2rem">
                    <h3>Patient Portal Self-Pay Receipts</h3>
                    <p style="font-size:0.875rem; color:#4a5568; margin-bottom:1rem">Real-time payments processed online by patients via the Patient self-service Portal.</p>
                    <table v-if="patientPayments.length">
                        <thead><tr><th>Payment Date</th><th>Patient</th><th>Claim Reference</th><th>Amount</th><th>Method</th><th>Reference #</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="p in patientPayments" :key="p.id">
                                <td>{{ formatDate(p.paid_at || p.created_at) }}</td>
                                <td>{{ p.patient ? (p.patient.first_name + ' ' + p.patient.last_name) : '#' + p.patient_id }}</td>
                                <td>{{ p.claim ? p.claim.claim_number : '—' }}</td>
                                <td><strong style="color:#2f855a">${{ p.amount }}</strong></td>
                                <td><span class="badge badge-green">{{ p.payment_method }}</span></td>
                                <td><code>{{ p.reference_number || 'n/a' }}</code></td>
                                <td><span class="badge">{{ p.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else style="color:#718096; margin-top:1rem">No patient self-pay records found.</p>
                </div>
            </div>

            <div v-if="view==='eligibility'" class="card">
                <h2>Eligibility (270/271)</h2>
                <div class="panel">
                    <h3>Check Coverage</h3>
                    <div class="form-row">
                        <div class="form-group"><label>Patient ID</label><input v-model="eligForm.patient_id" type="number"></div>
                        <div class="form-group"><label>Payer ID</label><input v-model="eligForm.payer_id" type="number"></div>
                        <div class="form-group"><label>Service Date</label><input v-model="eligForm.service_date" type="date"></div>
                        <div class="form-group"><label>Member ID</label><input v-model="eligForm.member_id" placeholder="optional"></div>
                    </div>
                    <button class="btn btn-primary" @click="checkEligibility">Run Eligibility Check</button>
                </div>
                <div class="panel filter-bar">
                    <div class="form-group">
                        <label>Filter by status</label>
                        <select v-model="eligFilters.coverage_status" @change="refresh">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="unknown">Unknown</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Patient ID</label>
                        <input v-model="eligFilters.patient_id" @keyup.enter="refresh" placeholder="optional">
                    </div>
                    <button class="btn btn-sm" @click="refresh">Apply Filters</button>
                </div>
                <table>
                    <thead><tr><th>Patient</th><th>Payer</th><th>Status</th><th>Plan</th><th>Copay</th><th>Checked</th></tr></thead>
                    <tbody>
                        <tr v-for="inq in eligibility" :key="inq.id">
                            <td>{{ inq.patient ? inq.patient.first_name + ' ' + inq.patient.last_name : '#' + inq.patient_id }}</td>
                            <td>{{ inq.payer ? inq.payer.name : '#' + inq.payer_id }}</td>
                            <td><span class="badge">{{ inq.coverage_status }}</span></td>
                            <td>{{ inq.plan_name || '—' }}</td>
                            <td>{{ inq.copay_amount ? '$' + inq.copay_amount : '—' }}</td>
                            <td>{{ formatDate(inq.checked_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='denials'" class="card">
                <h2>Claim Denials</h2>
                <p v-if="!denials.length" style="color:#718096">No denials on file.</p>
                <div v-else style="display:flex; gap:1.5rem; flex-wrap:wrap">
                    <div style="flex:2; min-width:350px">
                        <table>
                            <thead><tr><th>Claim #</th><th>Code</th><th>Reason</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <tr v-for="d in denials" :key="d.id">
                                    <td>{{ d.claim ? d.claim.claim_number : '—' }}</td>
                                    <td>{{ d.reason_code || '—' }}</td>
                                    <td>{{ d.reason_description || '—' }}</td>
                                    <td>${{ d.denied_amount }}</td>
                                    <td><span class="badge">{{ d.status }}</span></td>
                                    <td>
                                        <button v-if="d.status==='open'" class="btn btn-sm btn-primary" @click="correctFromDenial(d)" style="margin-right:0.25rem">Correct</button>
                                        <button v-if="d.status==='open'" class="btn btn-sm" @click="openAppealScribe(d)">Appeal Scribe</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Appeal Scribe Generated Letter Drawer -->
                    <div v-if="activeDenialForAppeal" style="flex:1.2; min-width:300px; background:#f7fafc; padding:1.5rem; border-radius:8px; border:1px solid #cbd5e0">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #cbd5e0; padding-bottom:0.5rem">
                            <h3 style="margin:0; color:#2b6cb0">✍️ Claim Appeal Letter Scribe</h3>
                            <button class="btn btn-sm" @click="activeDenialForAppeal=null" style="padding:0.2rem 0.5rem">Close</button>
                        </div>
                        <div class="form-group">
                            <label style="font-weight:bold">Select Reason Template</label>
                            <select v-model="appealTemplateType" @change="generateAppealLetterText" style="width:100%; padding:0.4rem; border-radius:4px; border:1px solid #cbd5e0; margin-top:0.25rem">
                                <option value="medical_necessity">Medical Necessity Appeal</option>
                                <option value="timely_filing">Timely Filing Limit Appeal</option>
                                <option value="incorrect_modifier">Incorrect Modifier / Coding Appeal</option>
                            </select>
                        </div>
                        
                        <div style="margin-top:1rem">
                            <label style="font-weight:bold; display:block">Generated Appeal Document</label>
                            <textarea v-model="appealLetterText" rows="12" style="width:100%; padding:0.5rem; border-radius:4px; border:1px solid #cbd5e0; font-family:monospace; font-size:0.85rem; margin-top:0.25rem; line-height:1.4"></textarea>
                        </div>
                        
                        <div style="display:flex; gap:0.5rem; margin-top:1rem">
                            <button class="btn btn-sm btn-primary" @click="submitScribeAppeal" style="flex:1">Submit Appeal</button>
                            <button class="btn btn-sm" @click="copyAppealLetterText" style="background:#edf2f7">Copy</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="view==='qa'" class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem">
                    <div>
                        <h2 style="margin:0">Clearinghouse QA Test Tracker</h2>
                        <p style="margin:0.35rem 0 0; font-size:0.875rem; color:#4a5568">Revenue Cycle Management · EHR/EMR Integration · USA Clearinghouse Ready</p>
                    </div>
                    <button class="btn btn-sm" @click="refresh">Refresh</button>
                </div>
                <div v-if="qaTracker && qaTracker.summary" class="stats" style="margin-bottom:1rem">
                    <div class="stat"><div class="num">{{ qaTracker.summary.total }}</div><div class="label">Total Tests</div></div>
                    <div class="stat"><div class="num" style="color:#2f855a">{{ qaTracker.summary.pass }}</div><div class="label">Pass</div></div>
                    <div class="stat"><div class="num" style="color:#c53030">{{ qaTracker.summary.fail }}</div><div class="label">Fail</div></div>
                    <div class="stat"><div class="num">{{ qaTracker.summary.untested }}</div><div class="label">Untested</div></div>
                </div>
                <p v-if="qaTracker" style="font-size:0.75rem; color:#718096; margin-bottom:1rem">
                    Driver: <strong>{{ qaTracker.clearinghouse_driver }}</strong>
                    <span v-if="qaTracker.last_submission_at"> · Last 837 submission: {{ formatDate(qaTracker.last_submission_at) }}</span>
                    · Updated {{ formatDate(qaTracker.generated_at) }}
                </p>
                <table v-if="qaTracker && qaTracker.tests">
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Module</th>
                            <th>Scenario / Step</th>
                            <th>Expected Result</th>
                            <th>Clearinghouse</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in qaTracker.tests" :key="t.id">
                            <td><strong>{{ t.id }}</strong></td>
                            <td>{{ t.module }}</td>
                            <td style="max-width:200px">{{ t.scenario }}</td>
                            <td style="max-width:220px; font-size:0.85rem">{{ t.expected }}</td>
                            <td style="font-size:0.8rem">{{ t.clearinghouse }}</td>
                            <td>{{ t.priority }}</td>
                            <td><span class="badge" :class="qaStatusClass(t.status)">{{ t.status }}</span></td>
                            <td style="font-size:0.8rem; max-width:240px">{{ t.notes }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="panel" style="margin-top:2rem">
                    <h3>EDI Transaction Set Reference — HIPAA 5010</h3>
                    <p style="font-size:0.8rem; color:#718096">Standard ANSI ASC X12N EDI transactions used in US healthcare RCM workflows.</p>
                    <table style="font-size:0.8rem">
                        <thead><tr><th>Transaction</th><th>Name</th><th>Direction</th><th>RCM Stage</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>837P</td><td>Professional Claim</td><td>Provider → Payer</td><td>Claim Submission</td><td>CMS-1500 equivalent outpatient claims</td></tr>
                            <tr><td>837I</td><td>Institutional Claim</td><td>Provider → Payer</td><td>Claim Submission</td><td>UB-04 equivalent facility claims</td></tr>
                            <tr><td>835</td><td>Electronic Remittance</td><td>Payer → Provider</td><td>Payment Posting</td><td>ERA / EOB payment data</td></tr>
                            <tr><td>270/271</td><td>Eligibility</td><td>Provider ↔ Payer</td><td>Registration</td><td>Real-time benefits verification</td></tr>
                            <tr><td>276/277</td><td>Claim Status</td><td>Provider ↔ Payer</td><td>AR Follow-Up</td><td>Status inquiry and response</td></tr>
                            <tr><td>999</td><td>Functional Ack</td><td>Payer ↔ Provider</td><td>All Stages</td><td>Syntactic acceptance/rejection</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="view==='cms'" class="card">
                <div class="row-between">
                    <h2>CMS Reference Data</h2>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                        <button class="btn btn-sm" @click="showCmsImport = !showCmsImport">Import Options</button>
                        <button class="btn btn-sm" @click="exportCmsTab">Export CSV</button>
                        <button class="btn btn-sm" @click="resetCmsFilters">Reset Filters</button>
                        <button class="btn btn-sm btn-primary" @click="importCmsData">Re-import CMS Data</button>
                    </div>
                </div>
                <p style="font-size:0.875rem;color:#4a5568;margin-bottom:1rem">
                    Official CMS public reference: payers, MACs, ICD-10, HCPCS, modifiers, CARC/RARC, type of bill, revenue codes, POS, and taxonomy.
                </p>
                <p v-if="cmsLoading" class="panel" style="color:#4a5568;font-size:0.875rem;margin-bottom:1rem">Loading CMS reference data…</p>
                <div v-if="showCmsImport" class="panel cms-import-panel" style="margin-bottom:1rem">
                    <div class="filter-bar">
                        <label class="checkbox-inline"><input type="checkbox" v-model="cmsImportOptions.fresh"> Replace existing data</label>
                        <label class="checkbox-inline"><input type="checkbox" v-model="cmsImportOptions.download"> Download latest from CMS/NUCC</label>
                        <div class="form-group form-group--multi">
                            <label>Datasets only (leave empty for all)</label>
                            <select v-model="cmsImportOptions.only" multiple size="5">
                                <option v-for="d in (cmsSummary && cmsSummary.datasets ? cmsSummary.datasets : [])" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div v-if="cmsSummary" class="stats" style="margin-bottom:1rem">
                    <div class="stat"><div class="num">{{ cmsSummary.states }}</div><div class="label">States</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.macs }}</div><div class="label">MACs</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.payers }}</div><div class="label">Payers</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.icd10_codes }}</div><div class="label">ICD-10</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.hcpcs_codes }}</div><div class="label">HCPCS</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.modifiers }}</div><div class="label">Modifiers</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.claim_adjustment_codes }}</div><div class="label">CARC</div></div>
                    <div class="stat"><div class="num">{{ cmsSummary.revenue_codes }}</div><div class="label">Revenue</div></div>
                </div>
                <div class="panel filter-bar">
                    <div class="form-group form-group--wide">
                        <label>CMS Tab</label>
                        <select v-model="cmsTab" @change="loadCmsTab">
                            <option value="payers">Payers</option>
                            <option value="medicare-advantage">Medicare Advantage</option>
                            <option value="qhp">QHP Issuers</option>
                            <option value="states">States</option>
                            <option value="macs">MACs</option>
                            <option value="icd10">ICD-10</option>
                            <option value="hcpcs">HCPCS</option>
                            <option value="modifiers">Modifiers</option>
                            <option value="carc">CARC (Adjustments)</option>
                            <option value="rarc">RARC (Remarks)</option>
                            <option value="tob">Type of Bill</option>
                            <option value="revenue">Revenue Codes</option>
                            <option value="pos">Place of Service</option>
                            <option value="taxonomy">Taxonomy</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Per page</label>
                        <select v-model="cmsFilters.per_page" @change="loadCmsTab">
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='payers'" class="form-group form-group--wide">
                        <label>Program</label>
                        <select v-model="cmsFilters.program" @change="loadCmsTab">
                            <option value="">All programs</option>
                            <option value="medicare">Medicare</option>
                            <option value="medicare_advantage">Medicare Advantage</option>
                            <option value="medicaid">Medicaid</option>
                            <option value="chip">CHIP</option>
                            <option value="marketplace">Marketplace</option>
                            <option value="commercial">Commercial</option>
                            <option value="workers_comp">Workers Comp</option>
                            <option value="tricare">TRICARE</option>
                            <option value="va">VA</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='payers'" class="form-group">
                        <label>Ownership</label>
                        <select v-model="cmsFilters.ownership" @change="loadCmsTab">
                            <option value="">All</option>
                            <option value="public">Public / Government</option>
                            <option value="private">Private</option>
                            <option value="nonprofit">Nonprofit</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='payers' || cmsTab==='macs' || cmsTab==='qhp'" class="form-group form-group--state">
                        <label>State</label>
                        <select v-model="cmsFilters.state" @change="loadCmsTab">
                            <option value="">All states</option>
                            <option v-for="s in cmsStateOptions" :key="s.id" :value="s.code">{{ s.code }} — {{ s.name }}</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='macs'" class="form-group">
                        <label>MAC Type</label>
                        <select v-model="cmsFilters.mac_type" @change="loadCmsTab">
                            <option value="">All types</option>
                            <option value="ab_mac">A/B MAC</option>
                            <option value="dme_mac">DME MAC</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='medicare-advantage'" class="form-group">
                        <label>Part D</label>
                        <select v-model="cmsFilters.part_d" @change="loadCmsTab">
                            <option value="">All</option>
                            <option value="yes">Offers Part D</option>
                            <option value="no">No Part D</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='hcpcs' || cmsTab==='revenue'" class="form-group form-group--wide">
                        <label>Category</label>
                        <input v-model="cmsFilters.category" @keyup.enter="loadCmsTab" placeholder="e.g. Pharmacy">
                    </div>
                    <div v-if="cmsTab==='modifiers'" class="form-group">
                        <label>Level</label>
                        <select v-model="cmsFilters.level" @change="loadCmsTab">
                            <option value="">All</option>
                            <option value="cpt">CPT</option>
                            <option value="hcpcs">HCPCS</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='carc'" class="form-group form-group--wide">
                        <label>Group</label>
                        <select v-model="cmsFilters.group_code" @change="loadCmsTab">
                            <option value="">All</option>
                            <option value="CO">CO — Contractual</option>
                            <option value="OA">OA — Other Adjustment</option>
                            <option value="PI">PI — Payer Initiated</option>
                            <option value="PR">PR — Patient Responsibility</option>
                        </select>
                    </div>
                    <div v-if="cmsTab==='icd10'" class="form-group form-group--wide">
                        <label>Billable</label>
                        <select v-model="cmsFilters.billable" @change="loadCmsTab">
                            <option value="">All</option>
                            <option value="yes">Billable only</option>
                            <option value="no">Header/category only</option>
                        </select>
                    </div>
                    <div v-if="cmsTabHasSearch" class="form-group form-group--search">
                        <label>Search</label>
                        <input v-model="cmsFilters.q" @keyup.enter="loadCmsTab" placeholder="Name, code, payer ID">
                    </div>
                    <button class="btn btn-primary btn-sm" @click="loadCmsTab">Apply</button>
                </div>
                <p v-if="cmsPagination" style="font-size:0.8rem;color:#5a7184;margin-top:0.75rem">
                    Showing {{ cmsPagination.from || 0 }}–{{ cmsPagination.to || 0 }} of {{ cmsPagination.total }} (page {{ cmsPagination.current_page }} / {{ cmsPagination.last_page }})
                </p>

                <div v-if="cmsStateDetail" class="detail-panel">
                    <div class="row-between">
                        <h3>{{ cmsStateDetail.name }} ({{ cmsStateDetail.code }})</h3>
                        <button class="btn btn-sm" @click="closeStateDetail">Close</button>
                    </div>
                    <p style="font-size:0.875rem;margin:0.5rem 0">Region: {{ cmsStateDetail.region ? cmsStateDetail.region.name : '—' }} · FIPS: {{ cmsStateDetail.fips_code || '—' }}</p>
                    <p style="font-size:0.875rem;margin-bottom:0.5rem"><strong>MACs:</strong> {{ cmsStateDetail.macs ? cmsStateDetail.macs.map(m => m.jurisdiction_code).join(', ') : '—' }}</p>
                    <p style="font-size:0.875rem"><strong>Reference payers in state:</strong> {{ cmsStateDetail.payers ? cmsStateDetail.payers.length : 0 }}</p>
                </div>

                <table v-if="cmsTab==='payers'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Name</th><th>Program</th><th>Ownership</th><th>State</th><th>Electronic ID</th><th>MAC / Plan</th></tr></thead>
                    <tbody>
                        <tr v-for="p in cmsPayers" :key="p.id">
                            <td>{{ p.code }}</td>
                            <td>{{ p.name }}</td>
                            <td><span class="badge">{{ p.program }}</span></td>
                            <td>{{ p.ownership }}</td>
                            <td>{{ p.state ? p.state.code : '—' }}</td>
                            <td>{{ p.electronic_payer_id || '—' }}</td>
                            <td>{{ p.mac ? p.mac.jurisdiction_code : (p.plan_type || '—') }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='states'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Name</th><th>CMS Region</th><th>Type</th><th>FIPS</th><th>MACs</th></tr></thead>
                    <tbody>
                        <tr v-for="s in cmsStates" :key="s.id" style="cursor:pointer" @click="openStateDetail(s.code)">
                            <td>{{ s.code }}</td>
                            <td>{{ s.name }}</td>
                            <td>{{ s.region ? s.region.name : '—' }}</td>
                            <td>{{ s.jurisdiction_type }}</td>
                            <td>{{ s.fips_code || '—' }}</td>
                            <td>{{ s.macs_count != null ? s.macs_count : (s.macs ? s.macs.length : 0) }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='macs'" style="margin-top:1rem">
                    <thead><tr><th>Contract #</th><th>Name</th><th>Type</th><th>Jurisdiction</th><th>States Covered</th><th>Phone</th></tr></thead>
                    <tbody>
                        <tr v-for="m in cmsMacs" :key="m.id">
                            <td>{{ m.contract_number }}</td>
                            <td>{{ m.name }}</td>
                            <td>{{ m.mac_type }}</td>
                            <td>{{ m.jurisdiction_code }}</td>
                            <td>{{ m.states && m.states.length ? m.states.map(x => x.code).join(', ') : '—' }}</td>
                            <td>{{ m.phone || '—' }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='medicare-advantage'" style="margin-top:1rem">
                    <thead><tr><th>Contract</th><th>Marketing Name</th><th>Organization</th><th>Parent</th><th>Plan Type</th><th>Enrollment</th><th>Part D</th></tr></thead>
                    <tbody>
                        <tr v-for="c in cmsMaContracts" :key="c.id">
                            <td>{{ c.contract_number }}</td>
                            <td>{{ c.marketing_name || '—' }}</td>
                            <td>{{ c.organization_name }}</td>
                            <td>{{ c.parent_organization || '—' }}</td>
                            <td>{{ c.plan_type || '—' }}</td>
                            <td>{{ c.total_enrollment || '—' }}</td>
                            <td>{{ c.offers_part_d ? 'Yes' : 'No' }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='qhp'" style="margin-top:1rem">
                    <thead><tr><th>Issuer ID</th><th>Name</th><th>State</th><th>Market</th><th>Ownership</th></tr></thead>
                    <tbody>
                        <tr v-for="i in cmsQhpIssuers" :key="i.id">
                            <td>{{ i.issuer_id }}</td>
                            <td>{{ i.issuer_name }}</td>
                            <td>{{ i.state ? i.state.code : '—' }}</td>
                            <td>{{ i.market_type }}</td>
                            <td>{{ i.ownership }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='icd10'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Description</th><th>Billable</th></tr></thead>
                    <tbody>
                        <tr v-for="c in cmsIcd10" :key="c.id">
                            <td>{{ c.code }}</td>
                            <td>{{ c.description }}</td>
                            <td>{{ c.is_billable ? 'Yes' : 'No' }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='modifiers'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Description</th><th>Level</th></tr></thead>
                    <tbody>
                        <tr v-for="m in cmsModifiers" :key="m.id">
                            <td>{{ m.code }}</td>
                            <td>{{ m.description }}</td>
                            <td>{{ m.level }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='carc'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Group</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr v-for="c in cmsCarc" :key="c.id">
                            <td>{{ c.code }}</td>
                            <td>{{ c.group_code || '—' }}</td>
                            <td style="max-width:520px">{{ c.description }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='rarc'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr v-for="r in cmsRarc" :key="r.id">
                            <td>{{ r.code }}</td>
                            <td style="max-width:620px">{{ r.description }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='tob'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Description</th><th>Facility</th><th>Care Type</th></tr></thead>
                    <tbody>
                        <tr v-for="t in cmsTob" :key="t.id">
                            <td>{{ t.code }}</td>
                            <td>{{ t.description }}</td>
                            <td>{{ t.facility_type || '—' }}</td>
                            <td>{{ t.care_type || '—' }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='revenue'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Description</th><th>Category</th></tr></thead>
                    <tbody>
                        <tr v-for="r in cmsRevenue" :key="r.id">
                            <td>{{ r.code }}</td>
                            <td>{{ r.description }}</td>
                            <td>{{ r.category || '—' }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='hcpcs'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Short Description</th><th>Category</th><th>Long Description</th></tr></thead>
                    <tbody>
                        <tr v-for="h in cmsHcpcs" :key="h.id">
                            <td>{{ h.code }}</td>
                            <td>{{ h.short_description }}</td>
                            <td>{{ h.category }}</td>
                            <td style="max-width:420px">{{ h.long_description }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='pos'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Name</th><th>Definition</th></tr></thead>
                    <tbody>
                        <tr v-for="c in cmsPos" :key="c.id">
                            <td>{{ c.code }}</td>
                            <td>{{ c.name }}</td>
                            <td style="max-width:520px">{{ c.definition }}</td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="cmsTab==='taxonomy'" style="margin-top:1rem">
                    <thead><tr><th>Code</th><th>Classification</th><th>Specialization</th><th>Definition</th></tr></thead>
                    <tbody>
                        <tr v-for="t in cmsTaxonomy" :key="t.id">
                            <td>{{ t.code }}</td>
                            <td>{{ t.classification }}</td>
                            <td>{{ t.specialization || '—' }}</td>
                            <td>{{ t.definition }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="view==='setup'" class="card">
                <div class="row-between">
                    <h2>Integration Setup</h2>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                        <button class="btn btn-sm" @click="syncPayerIds">Sync Payer IDs from CMS</button>
                        <button class="btn btn-primary btn-sm" @click="testClearinghouse">Test Clearinghouse</button>
                        <button class="btn btn-primary btn-sm" @click="testEligibility">Test Eligibility</button>
                    </div>
                </div>
                <p v-if="onboardingGuide" style="font-size:0.9rem;color:#4a5568;margin-bottom:1rem">{{ onboardingGuide.intro }}</p>
                <div v-if="clearinghouseTest" class="toast" role="status">
                    <span class="toast__text">{{ clearinghouseTest }}</span>
                    <button type="button" class="toast__close" @click="clearinghouseTest = ''" aria-label="Dismiss notification">&times;</button>
                </div>
                <div v-if="eligibilityTest" class="toast" role="status">
                    <span class="toast__text">{{ eligibilityTest }}</span>
                    <button type="button" class="toast__close" @click="eligibilityTest = ''" aria-label="Dismiss notification">&times;</button>
                </div>
                <p v-if="setupLoading" class="panel" style="color:#4a5568;font-size:0.875rem">Loading integration checklist…</p>
                <div v-if="requirements && requirements.all_ready_for_production && !setupReadyDismissed" class="toast" role="status">
                    <span class="toast__text">All integration checks passing for current configuration.</span>
                    <button type="button" class="toast__close" @click="setupReadyDismissed = true" aria-label="Dismiss notification">&times;</button>
                </div>

                <div v-if="orgProfile" class="panel">
                    <h3>Your Organization</h3>
                    <p style="font-size:0.85rem;color:#5a7184;margin:0 0 0.75rem">Name, organization NPI, billing address, and rendering providers import from NPPES. Only EIN is manual.</p>
                    <p style="font-size:0.875rem;margin:0.35rem 0"><strong>{{ orgProfile.name }}</strong> · Org NPI: {{ orgProfile.npi || '—' }} · EIN: {{ orgProfile.tax_id || '—' }}</p>
                    <p v-if="orgProfile.address" style="font-size:0.875rem;color:#4a5568">{{ orgProfile.address.line1 }}, {{ orgProfile.address.city }}, {{ orgProfile.address.state }} {{ orgProfile.address.zip }}</p>
                    <div v-if="orgProfile.checks" class="driver-chips" style="margin-top:0.5rem">
                        <span class="driver-chip" :style="orgProfile.checks.has_org_npi ? '' : 'border-color:#c05621'">Org NPI: {{ orgProfile.checks.has_org_npi ? '✓' : 'missing' }}</span>
                        <span class="driver-chip" :style="orgProfile.checks.has_tax_id ? '' : 'border-color:#c05621'">EIN: {{ orgProfile.checks.has_tax_id ? '✓' : 'manual only' }}</span>
                        <span class="driver-chip" :style="orgProfile.checks.has_billing_address ? '' : 'border-color:#c05621'">Address: {{ orgProfile.checks.has_billing_address ? '✓' : 'missing' }}</span>
                        <span class="driver-chip" :style="orgProfile.checks.has_rendering_providers ? '' : 'border-color:#c05621'">Providers: {{ orgProfile.checks.has_rendering_providers ? orgProfile.providers.length : 'none' }}</span>
                        <span class="driver-chip">Payers w/ ID: {{ orgProfile.checks.payers_with_electronic_id }}/{{ orgProfile.checks.total_payers }}</span>
                    </div>
                    <div class="filter-bar" style="margin-top:0.75rem">
                        <div class="form-group form-group--wide">
                            <label>Federal Tax ID (EIN) — not in NPPES</label>
                            <input v-model="orgTaxId" placeholder="12-3456789">
                        </div>
                        <button class="btn btn-sm btn-primary" @click="saveTaxId">Save EIN</button>
                    </div>
                    <div class="filter-bar" style="margin-top:0.75rem">
                        <div class="form-group form-group--wide">
                            <label>NPPES NPI Lookup</label>
                            <input v-model="npiLookup" maxlength="10" placeholder="Type-2 org or Type-1 provider NPI">
                        </div>
                        <button class="btn btn-sm" @click="lookupNpi">Lookup</button>
                        <button v-if="npiResult && npiResult.apply_as === 'organization'" class="btn btn-sm btn-primary" @click="applyNppes('organization')">Apply to Organization</button>
                        <button v-if="npiResult && npiResult.apply_as === 'rendering_provider'" class="btn btn-sm btn-primary" @click="applyNppes('rendering_provider')">Add Rendering Provider</button>
                        <button v-if="npiResult && npiResult.apply_as === 'organization'" class="btn btn-sm" @click="applyNppes('rendering_provider')">Add as Provider instead</button>
                        <button v-if="npiResult && npiResult.apply_as === 'rendering_provider'" class="btn btn-sm" @click="applyNppes('organization')">Apply as Org instead</button>
                    </div>
                    <div v-if="npiResult" class="detail-panel" style="margin-top:0.75rem">
                        <strong>{{ npiResult.name }}</strong> ({{ npiResult.enumeration_type }}) — {{ npiResult.status }}<br>
                        <span style="font-size:0.875rem">{{ npiResult.taxonomy || '—' }} · {{ npiResult.primary_practice_address || '—' }}</span>
                        <span v-if="npiResult.phone" style="display:block;font-size:0.875rem">{{ npiResult.phone }}</span>
                    </div>
                    <div style="margin-top:0.75rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
                        <button class="btn btn-sm" @click="discoverProviders" :disabled="!orgProfile.name">Discover Type-1 Providers at Practice</button>
                        <span v-if="npiProviderCandidates" style="font-size:0.85rem;color:#5a7184">{{ npiProviderCandidates.candidates.length }} found for “{{ npiProviderCandidates.organization_name }}”<span v-if="npiProviderCandidates.state"> in {{ npiProviderCandidates.state }}</span></span>
                    </div>
                    <div v-if="npiProviderCandidates && npiProviderCandidates.candidates.length" style="margin-top:0.75rem">
                        <table>
                            <thead><tr><th></th><th>Provider</th><th>NPI</th><th>Taxonomy</th><th>Address</th></tr></thead>
                            <tbody>
                                <tr v-for="c in npiProviderCandidates.candidates" :key="c.npi">
                                    <td><input type="checkbox" :checked="selectedProviderNpis.includes(c.npi)" @change="toggleProviderNpi(c.npi)"></td>
                                    <td>{{ c.name }}</td>
                                    <td>{{ c.npi }}</td>
                                    <td>{{ c.taxonomy || '—' }}</td>
                                    <td style="font-size:0.8rem">{{ c.primary_practice_address || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="btn btn-sm btn-primary" style="margin-top:0.5rem" @click="applySelectedProviders">Add Selected from NPPES</button>
                    </div>
                    <table v-if="orgProfile.providers && orgProfile.providers.length" style="margin-top:0.75rem">
                        <thead><tr><th>Rendering Provider</th><th>NPI</th><th>Credentials</th></tr></thead>
                        <tbody>
                            <tr v-for="pr in orgProfile.providers" :key="pr.id">
                                <td>{{ pr.first_name }} {{ pr.last_name }}</td>
                                <td>{{ pr.npi }}</td>
                                <td>{{ pr.credentials || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="referenceData" class="panel">
                    <h3>Auto-loaded Reference Data</h3>
                    <div class="driver-chips">
                        <span class="driver-chip">POS codes: {{ referenceData.place_of_service_codes }}</span>
                        <span class="driver-chip">CMS payers w/ electronic ID: {{ referenceData.reference_payers_with_electronic_id }}</span>
                    </div>
                </div>

                <div v-if="onboardingGuide && onboardingGuide.sections" class="panel">
                    <h3>What You Need &amp; How to Get It</h3>
                    <div v-for="(section, key) in onboardingGuide.sections" :key="key" style="margin-top:1rem">
                        <h4 style="font-size:0.95rem;color:#1e4d6b;margin-bottom:0.35rem">{{ section.title }}</h4>
                        <p v-if="section.subtitle" style="font-size:0.85rem;color:#5a7184;margin-bottom:0.5rem">{{ section.subtitle }}</p>
                        <table v-if="section.items && section.items.length" class="guide-table">
                            <thead><tr><th>What</th><th>Why</th><th>How to get it</th><th>Env vars</th></tr></thead>
                            <tbody>
                                <tr v-for="(item, idx) in section.items" :key="key + '-' + idx">
                                    <td>{{ item.what }}</td>
                                    <td>{{ item.why || '—' }}</td>
                                    <td>
                                        {{ item.how || '—' }}
                                        <a v-if="item.link" :href="item.link" target="_blank" rel="noopener" style="display:block;font-size:0.78rem">Open source ↗</a>
                                    </td>
                                    <td>{{ item.env_vars ? item.env_vars.join(', ') : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="section.action" style="font-size:0.85rem;margin-top:0.5rem"><strong>Action:</strong> {{ section.action }}</p>
                    </div>
                </div>

                <div v-if="posReference && posReference.common && posReference.common.length" class="panel">
                    <h3>Place of Service (CMS — {{ posReference.total }} loaded)</h3>
                    <table>
                        <thead><tr><th>Code</th><th>Name</th></tr></thead>
                        <tbody>
                            <tr v-for="c in posReference.common.slice(0, 12)" :key="c.code">
                                <td>{{ c.code }}</td>
                                <td>{{ c.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payerDirectory && payerDirectory.rows && payerDirectory.rows.length" class="panel">
                    <h3>Payer Electronic IDs (CMS — {{ payerDirectory.total_with_electronic_id }} available)</h3>
                    <table>
                        <thead><tr><th>Electronic ID</th><th>Name</th><th>Program</th><th>State</th></tr></thead>
                        <tbody>
                            <tr v-for="p in payerDirectory.rows.slice(0, 15)" :key="p.code">
                                <td>{{ p.electronic_payer_id }}</td>
                                <td>{{ p.name }}</td>
                                <td>{{ p.program }}</td>
                                <td>{{ p.state || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="requirements && requirements.drivers" class="panel">
                    <h3>Active Drivers</h3>
                    <div class="driver-chips">
                        <span class="driver-chip">Clearinghouse: {{ requirements.drivers.clearinghouse }}</span>
                        <span class="driver-chip">Eligibility: {{ requirements.drivers.eligibility }}</span>
                        <span class="driver-chip">DB: {{ requirements.drivers.database }}</span>
                        <span class="driver-chip">Queue: {{ requirements.drivers.queue }}</span>
                        <span class="driver-chip">Env: {{ requirements.drivers.app_env }}</span>
                    </div>
                </div>

                <div v-if="features" class="panel">
                    <div class="row-between">
                        <h3>Workspace &amp; Feature Options</h3>
                        <div style="display:flex;gap:0.5rem">
                            <button class="btn btn-sm" @click="applyDefaultPrefs">Reset defaults</button>
                            <button class="btn btn-sm btn-primary" @click="savePrefs">Save preferences</button>
                        </div>
                    </div>
                    <div class="filter-bar" style="margin-top:0.75rem">
                        <div class="form-group form-group--wide">
                            <label>Default CMS tab</label>
                            <select v-model="prefs.default_cms_tab">
                                <option value="payers">Payers</option>
                                <option value="icd10">ICD-10</option>
                                <option value="hcpcs">HCPCS</option>
                                <option value="modifiers">Modifiers</option>
                                <option value="states">States</option>
                                <option value="macs">MACs</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Default per page</label>
                            <select v-model="prefs.default_per_page">
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Default POS (claims)</label>
                            <select v-model="prefs.default_place_of_service">
                                <option value="11">11 — Office</option>
                                <option value="02">02 — Telehealth</option>
                                <option value="22">22 — Outpatient</option>
                                <option value="23">23 — ER</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>CMS export limit</label>
                            <select v-model="prefs.cms_export_max_rows">
                                <option value="500">500 rows</option>
                                <option value="1000">1,000 rows</option>
                                <option value="2000">2,000 rows</option>
                                <option value="5000">5,000 rows</option>
                            </select>
                        </div>
                        <label class="checkbox-inline"><input type="checkbox" v-model="prefs.eligibility_auto_refresh"> Auto-refresh eligibility after check</label>
                    </div>
                    <ul v-if="features.modules" style="margin-top:1rem;padding-left:1.25rem;font-size:0.875rem">
                        <li v-for="m in features.modules" :key="m.id">{{ m.label }} — {{ m.enabled ? 'enabled' : 'disabled' }}</li>
                    </ul>
                </div>

                <div v-for="(section, key) in (requirements && requirements.sections) || {}" :key="key" class="panel">
                    <div class="row-between">
                        <h3>{{ section.label }}</h3>
                        <span class="badge">{{ section.ready ? 'Ready' : 'Needs setup' }}</span>
                    </div>
                    <p v-if="section.note" style="font-size:0.875rem;color:#4a5568;margin:0.5rem 0">{{ section.note }}</p>
                    <p v-if="section.missing && section.missing.length" style="color:#c05621;font-size:0.875rem">
                        Missing: {{ section.missing.join(', ') }}
                    </p>
                    <ul v-if="section.you_provide && section.you_provide.length" style="margin-top:0.5rem;padding-left:1.25rem;font-size:0.875rem">
                        <li v-for="item in section.you_provide" :key="item">{{ item }}</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
@endverbatim
</div>
<script src="/js/billing-admin.js"></script>
</body>
</html>
