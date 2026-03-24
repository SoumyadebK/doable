<!DOCTYPE html>
<html lang="en">


<style>
    html,
    body {
        height: 100%;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1a1a1a;
    }

    .content {
        flex: 1;
    }

    .footer {
        text-align: center;
        padding: 10px 0;
        font-size: 12px;
        color: #828080;
    }

    .wizard {
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    /* ── Sidebar ── */
    .sidebar {
        width: 160px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        padding-top: 6px;
        position: sticky;
        top: 2rem;
    }

    .side-step {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        cursor: pointer;
        position: relative;
    }

    .side-line {
        position: absolute;
        left: 15px;
        top: 32px;
        width: 2px;
        background: #d0d7de;
        bottom: 0;
        z-index: 0;
    }

    .side-line.done {
        background: #39B54A;
    }

    .dot {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 1.5px solid #d0d7de;
        background: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        transition: all .2s;
        color: #666;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .dot.active {
        background: #39B54A;
        border-color: #39B54A;
        color: #fff;
    }

    .dot.done {
        background: #39B54A;
        border-color: #39B54A;
        color: #fff;
    }

    .side-label {
        font-size: 12px;
        color: #666;
        line-height: 1.3;
        padding-top: 7px;
        padding-bottom: 28px;
    }

    .side-label.active {
        color: #39B54A;
        font-weight: 600;
    }

    .side-label.done {
        color: #39B54A;
    }

    /* ── Main panel ── */
    .main {
        flex: 1;
        min-width: 0;
    }

    .panel {
        background: #fff;
        border: 1px solid #d0d7de;
        border-radius: 12px;
        padding: 1.75rem;
    }

    .panel-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 4px;
        color: #111;
    }

    .panel-sub {
        font-size: 13px;
        color: #666;
        margin-bottom: 1.5rem;
    }

    /* ── Record accordion ── */
    .record-block {
        border: 1px solid #d0d7de;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .record-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.65rem 1rem;
        background: #f5f6f8;
        cursor: pointer;
        user-select: none;
        border-bottom: 1px solid #d0d7de;
    }

    .record-label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chevron {
        font-size: 11px;
        color: #888;
        transition: transform .2s;
        display: inline-block;
    }

    .chevron.open {
        transform: rotate(180deg);
    }

    .record-body {
        padding: 1rem;
        background: #fff;
        display: none;
    }

    .record-body.open {
        display: block;
    }

    .remove-btn {
        font-size: 12px;
        color: #a32d2d;
        background: none;
        border: 1px solid #f09595;
        border-radius: 6px;
        padding: 3px 10px;
        cursor: pointer;
    }

    .remove-btn:hover {
        background: #fdecea;
    }

    /* ── Fields ── */
    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .field-group.full {
        grid-column: 1 / -1;
    }

    label {
        font-size: 12px;
        color: #555;
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    .req {
        color: #e24b4a;
        margin-left: 2px;
    }

    input[type=text],
    input[type=email],
    input[type=tel],
    input[type=number],
    select,
    textarea {
        width: 100%;
        font-size: 14px;
        padding: 8px 11px;
        border: 1.5px solid #b0b8c4;
        border-radius: 7px;
        background: #fff;
        color: #1a1a1a;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        font-family: inherit;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #39B54A;
        box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.14);
    }

    input:hover,
    select:hover,
    textarea:hover {
        border-color: #39B54A;
    }

    input::placeholder,
    textarea::placeholder {
        color: #aaa;
        font-size: 13px;
    }

    textarea {
        resize: vertical;
        min-height: 64px;
    }

    select {
        cursor: pointer;
    }

    .add-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: none;
        border: 1.5px dashed #39B54A;
        border-radius: 8px;
        padding: 9px 14px;
        font-size: 13px;
        color: #39B54A;
        cursor: pointer;
        width: 100%;
        justify-content: center;
        margin-top: 6px;
        transition: background .15s;
        font-family: inherit;
    }

    .add-btn:hover {
        background: #e6f1fb;
    }

    /* ── Nav ── */
    .nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.25rem;
    }

    .btn {
        padding: 9px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid #d0d7de;
        background: #f0f2f5;
        color: #333;
        transition: background .15s;
        font-family: inherit;
    }

    .btn:hover {
        background: #e2e6ea;
    }

    .btn.primary {
        background: #39B54A;
        border-color: #39B54A;
        color: #fff;
    }

    .btn.primary:hover {
        background: #0c447c;
    }

    /* ── Checkboxes ── */
    .checkrow {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkrow input[type=checkbox] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #39B54A;
    }

    .checkrow label {
        font-size: 14px;
        color: #333;
        cursor: pointer;
        font-weight: 400;
    }

    /* ── Package section divider ── */
    .pkg-divider {
        font-size: 11px;
        font-weight: 700;
        color: #666;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        border-bottom: 1px solid #e0e4ea;
        padding-bottom: 5px;
        margin: 1rem 0 0.25rem;
        grid-column: 1 / -1;
    }

    /* ── Success ── */
    .success-panel {
        text-align: center;
        padding: 3rem 1.5rem;
    }

    .success-icon {
        font-size: 48px;
        margin-bottom: 1rem;
        color: #39B54A;
    }


    .nav-new {
        margin: 0 auto;
        width: 100%;
        box-shadow: 1px 7px 8px 0px rgba(0, 0, 0, 0.47);
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 9;
    }
</style>

<body>
    <nav class="container-fluid d-flex justify-content-between align-items-center py-3 px-5 nav-new">
        <div class="fw-bold fs-4" style="padding: 5px;">
            <a href="index.php">
                <img width="150" src="demo1/images/doable_logo.png" />
            </a>
        </div>
    </nav>

    <!-- HERO -->
    <main class="content">
        <section class="text-center" style="margin-top: 40px;">
            <div class="container">
                <div class="wizard">
                    <div class="sidebar" id="sidebar"></div>
                    <div class="main">
                        <div id="panels"></div>
                        <div class="nav" id="nav">
                            <button class="btn" id="backBtn" onclick="navigate(-1)">← Back</button>
                            <span id="stepCounter" style="font-size:13px;color:#666"></span>
                            <button class="btn primary" id="nextBtn" onclick="navigate(1)">Next →</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">© <?= date('Y'); ?> Doable LLC</footer>
</body>






<script>
    const steps = [{
            id: 'corp',
            label: 'Corporation'
        },
        {
            id: 'location',
            label: 'Locations'
        },
        {
            id: 'users',
            label: 'Users'
        },
        {
            id: 'services',
            label: 'Services'
        },
        {
            id: 'packages',
            label: 'Packages'
        }
    ];

    let current = 0;
    const counters = {
        location: 0,
        user: 0,
        service: 0,
        package: 0
    };

    function toggleRecord(uid) {
        const body = document.getElementById('body-' + uid);
        const chev = document.getElementById('chev-' + uid);
        const open = body.classList.contains('open');
        body.classList.toggle('open', !open);
        chev.classList.toggle('open', !open);
    }

    function locationFields(i) {
        return `<div class="field-grid">
    <div class="field-group full"><label>Location Name / Business Name / DBA <span class="req">*</span></label><input type="text" placeholder="e.g. Main Street Branch"></div>
    <div class="field-group"><label>City <span class="req">*</span></label><input type="text" placeholder="City"></div>
    <div class="field-group"><label>State <span class="req">*</span></label><input type="text" placeholder="State"></div>
    <div class="field-group"><label>ZIP Code <span class="req">*</span></label><input type="text" placeholder="ZIP"></div>
    <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" placeholder="location@email.com"></div>
    <div class="field-group"><label>Time Zone <span class="req">*</span></label>
      <select><option value="">Select time zone</option><option>Eastern Time (ET)</option><option>Central Time (CT)</option><option>Mountain Time (MT)</option><option>Pacific Time (PT)</option></select>
    </div>
    <div class="field-group"><label>Operational Hours <span class="req">*</span></label><input type="text" placeholder="e.g. Mon–Fri 9am–6pm"></div>
    <div class="field-group full"><label>Credit Card <span class="req">*</span></label><input type="text" placeholder="Card on file for this location"></div>
  </div>`;
    }

    function userFields(i) {
        return `<div class="field-grid">
    <div class="field-group"><label>Name <span class="req">*</span></label><input type="text" placeholder="Full name"></div>
    <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" placeholder="user@email.com"></div>
    <div class="field-group"><label>Login <span class="req">*</span></label><input type="text" placeholder="Username or login ID"></div>
    <div class="field-group"><label>Role <span class="req">*</span></label>
      <select><option value="">Select role</option><option>Admin</option><option>Employee</option><option>Contractor</option><option>Manager</option></select>
    </div>
    <div class="field-group"><label>Service Hours <span class="req">*</span></label><input type="text" placeholder="e.g. Mon–Fri 9am–5pm"></div>
    <div class="field-group" style="justify-content:flex-end;padding-top:20px">
      <div class="checkrow"><input type="checkbox" id="cal-${i}"><label for="cal-${i}">Appear in Calendar</label></div>
    </div>
  </div>`;
    }

    function serviceFields(i) {
        return `<div class="field-grid">
    <div class="field-group"><label>Service Name <span class="req">*</span></label><input type="text" placeholder="e.g. Initial Consultation"></div>
    <div class="field-group"><label>Code <span class="req">*</span></label><input type="text" placeholder="e.g. SVC-001"></div>
    <div class="field-group"><label>Price <span class="req">*</span></label><input type="text" placeholder="$0.00"></div>
    <div class="field-group"><label>Service Class <span class="req">*</span></label><input type="text" placeholder="Class"></div>
    <div class="field-group full"><label>Description <span class="req">*</span></label><textarea placeholder="Brief description of this service"></textarea></div>
    <div class="field-group"><label>Sort Number <span class="req">*</span></label><input type="number" placeholder="1"></div>
    <div class="field-group" style="padding-top:4px">
      <div style="display:flex;flex-direction:column;gap:8px">
        <div class="checkrow"><input type="checkbox" id="chargeable-${i}"><label for="chargeable-${i}">Chargeable to client account</label></div>
        <div class="checkrow"><input type="checkbox" id="group-${i}"><label for="group-${i}">Is this a group service?</label></div>
        <div class="checkrow"><input type="checkbox" id="calcount-${i}"><label for="calcount-${i}">Show in calendar count</label></div>
      </div>
    </div>
  </div>`;
    }

    function packageFields(i) {
        return `<div class="field-grid">
    <div class="pkg-divider">Package Details</div>
    <div class="field-group"><label>Package Name <span class="req">*</span></label><input type="text" placeholder="e.g. Starter Pack"></div>
    <div class="field-group"><label>Package Code <span class="req">*</span></label><input type="text" placeholder="e.g. PKG-001"></div>
    <div class="field-group"><label>Price <span class="req">*</span></label><input type="text" placeholder="$0.00"></div>
    <div class="field-group"><label>Billing Cycle <span class="req">*</span></label>
      <select><option value="">Select</option><option>One-time</option><option>Weekly</option><option>Monthly</option><option>Annually</option></select>
    </div>
    <div class="field-group full"><label>Description <span class="req">*</span></label><textarea placeholder="What's included in this package?"></textarea></div>
    <div class="pkg-divider">Services Included</div>
    <div class="field-group full"><label>Services <span class="req">*</span></label><input type="text" placeholder="e.g. SVC-001, SVC-002 (comma-separated codes)"></div>
    <div class="field-group"><label>Session / Visit Limit</label><input type="number" placeholder="Leave blank for unlimited"></div>
    <div class="field-group"><label>Expiry (days)</label><input type="number" placeholder="Leave blank if none"></div>
    <div class="pkg-divider">Options</div>
    <div class="field-group"><label>Sort Number <span class="req">*</span></label><input type="number" placeholder="1"></div>
    <div class="field-group" style="padding-top:4px">
      <div style="display:flex;flex-direction:column;gap:8px">
        <div class="checkrow"><input type="checkbox" id="pkg-active-${i}"><label for="pkg-active-${i}">Active / available for purchase</label></div>
        <div class="checkrow"><input type="checkbox" id="pkg-client-${i}"><label for="pkg-client-${i}">Chargeable to client account</label></div>
      </div>
    </div>
  </div>`;
    }

    function addRecord(type, openBody) {
        counters[type]++;
        const i = counters[type];
        const list = document.getElementById(type + '-list');
        const labels = {
            location: 'Location',
            user: 'User / Employee',
            service: 'Service',
            package: 'Package'
        };
        const fieldsFn = {
            location: locationFields,
            user: userFields,
            service: serviceFields,
            package: packageFields
        } [type];
        const uid = type + '-' + i;
        const div = document.createElement('div');
        div.className = 'record-block';
        div.id = uid;
        div.innerHTML = `
    <div class="record-header" onclick="toggleRecord('${uid}')">
      <span class="record-label">
        <span class="chevron ${openBody ? 'open' : ''}" id="chev-${uid}">▼</span>
        ${labels[type]} #${i}
      </span>
      ${i > 1 ? `<button class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
    </div>
    <div class="record-body ${openBody ? 'open' : ''}" id="body-${uid}">${fieldsFn(i)}</div>`;
        list.appendChild(div);
    }

    function removeRecord(uid) {
        const el = document.getElementById(uid);
        if (el) el.remove();
    }

    const panelHTML = {
        corp: `<div class="panel-title">Corporation / Entity</div>
    <div class="panel-sub">Basic information about your corporation or business entity.</div>
    <div class="field-grid">
      <div class="field-group full"><label>Corporation / Entity Name <span class="req">*</span></label><input type="text" placeholder="e.g. Acme Corp — or owner's name if no formal entity"></div>
      <div class="field-group full"><label>Credit Card <span class="req">*</span></label><input type="text" placeholder="Card on file"></div>
    </div>`,
        location: `<div class="panel-title">Locations</div>
    <div class="panel-sub">Add one or more business locations. Click a header to expand or collapse.</div>
    <div id="location-list"></div>
    <button class="add-btn" onclick="addRecord('location', true)">+ Add another location</button>`,
        users: `<div class="panel-title">Users / Employees / Contractors</div>
    <div class="panel-sub">Add all team members who will use the system.</div>
    <div id="user-list"></div>
    <button class="add-btn" onclick="addRecord('user', true)">+ Add another user</button>`,
        services: `<div class="panel-title">Services</div>
    <div class="panel-sub">Define the services your business offers. A default scheduling code is applied automatically.</div>
    <div id="service-list"></div>
    <button class="add-btn" onclick="addRecord('service', true)">+ Add another service</button>`,
        packages: `<div class="panel-title">Packages</div>
    <div class="panel-sub">Bundle services into packages for client purchase or subscription.</div>
    <div id="package-list"></div>
    <button class="add-btn" onclick="addRecord('package', true)">+ Add another package</button>`
    };

    function renderSidebar() {
        document.getElementById('sidebar').innerHTML = steps.map((s, idx) => {
            const dotCls = idx < current ? 'done' : idx === current ? 'active' : '';
            const labelCls = idx < current ? 'done' : idx === current ? 'active' : '';
            const sym = idx < current ? '✓' : idx + 1;
            const lineDone = idx < current ? 'done' : '';
            return `<div class="side-step" onclick="goTo(${idx})">
      ${idx < steps.length - 1 ? `<div class="side-line ${lineDone}"></div>` : ''}
      <div class="dot ${dotCls}">${sym}</div>
      <span class="side-label ${labelCls}">${s.label}</span>
    </div>`;
        }).join('');
    }

    function renderPanel() {
        const id = steps[current].id;
        document.getElementById('panels').innerHTML = `<div class="panel">${panelHTML[id]}</div>`;
        if (id === 'location' && counters.location === 0) addRecord('location', true);
        if (id === 'users' && counters.user === 0) addRecord('user', true);
        if (id === 'services' && counters.service === 0) addRecord('service', true);
        if (id === 'packages' && counters.package === 0) addRecord('package', true);
        document.getElementById('backBtn').style.visibility = current === 0 ? 'hidden' : 'visible';
        document.getElementById('nextBtn').textContent = current === steps.length - 1 ? 'Submit ✓' : 'Next →';
        document.getElementById('stepCounter').textContent = `Step ${current + 1} of ${steps.length}`;
    }

    function navigate(dir) {
        if (dir === 1 && current === steps.length - 1) {
            document.getElementById('panels').innerHTML = `
      <div class="panel success-panel">
        <div class="success-icon">✓</div>
        <div class="panel-title">Setup complete!</div>
        <p style="margin-top:0.5rem;font-size:14px;color:#666">All sections have been filled in. Your team can now review and submit to the system.</p>
      </div>`;
            document.getElementById('nav').style.display = 'none';
            current = steps.length;
            renderSidebar();
            return;
        }
        current = Math.max(0, Math.min(steps.length - 1, current + dir));
        renderSidebar();
        renderPanel();
    }

    function goTo(idx) {
        current = idx;
        renderSidebar();
        renderPanel();
    }

    renderSidebar();
    renderPanel();
</script>


</html>