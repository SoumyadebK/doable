<?php
require_once('../global/config.php');
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automations - Custom Schedule Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #1e293b;
        }

        /* Sidebar styling */
        .sidebar-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e9eef3;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .sidebar-section {
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            padding-left: 0.5rem;
        }

        .sidebar-card .nav-link {
            color: #334155;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        .sidebar-card .nav-link i,
        .sidebar-card .nav-link .dot-icon {
            font-size: 1.1rem;
            color: #5b6e8c;
        }

        .sidebar-card .nav-link.active {
            background-color: #ecfdf5;
            color: #10b981 !important;
            font-weight: 600;
        }

        .sidebar-card .nav-link.active i {
            color: #10b981;
        }

        /* main panel */
        .main-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e9eef3;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .form-label-custom {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }

        .form-control-custom,
        .form-select-custom {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            padding: 0.6rem 0.9rem;
            background-color: #fefefe;
            transition: 0.2s;
        }

        .form-control-custom:focus,
        .form-select-custom:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background-color: #fff;
        }

        .form-control-inline,
        .form-select-inline {
            display: inline-block;
            width: auto;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            font-size: 0.85rem;
            padding: 0.3rem 0.9rem;
            background: #f9fafb;
        }

        input.form-control-inline[type="number"] {
            width: 75px;
        }

        .btn-pill-outline {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.4rem 1.2rem;
            transition: all 0.2s;
        }

        .btn-pill-outline:hover {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .btn-save-automation {
            background-color: #10b981;
            border: none;
            color: white;
            border-radius: 40px;
            font-weight: 600;
            padding: 0.7rem;
        }

        .btn-save-automation:hover {
            background-color: #059669;
        }

        .custom-switch .form-check-input {
            width: 2.3em;
            height: 1.25em;
            background-color: #cbd5e1;
            border-color: transparent;
            cursor: pointer;
        }

        .custom-switch .form-check-input:checked {
            background-color: #10b981;
        }

        .custom-switch .form-check-input:focus,
        .custom-checkbox .form-check-input:focus,
        .custom-radio .form-check-input:focus {
            box-shadow: none;
        }

        .custom-checkbox .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .custom-radio .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .variable-badge {
            background-color: #eef2ff;
            border-radius: 20px;
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin: 0 2px;
            color: #1e40af;
        }

        .btn-variable-token {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            font-size: 0.7rem;
            padding: 0.25rem 0.9rem;
        }

        .btn-variable-token:hover {
            background: #f1f5f9;
        }

        .custom-schedule-matrix {
            background: #fefefe;
            border-radius: 20px;
            padding: 0.5rem 0.25rem;
        }

        .reminder-row {
            transition: 0.1s;
        }

        .delete-reminder-btn {
            color: #94a3b8;
            font-size: 1rem;
        }

        .delete-reminder-btn:hover {
            color: #dc2626;
        }

        .extra-small {
            font-size: 0.7rem;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: #e2e8f0;
        }

        .accordion-button:not(.collapsed) {
            background-color: #fafcff;
            color: #0f172a;
        }

        .editable-content-area {
            min-height: 85px;
            outline: none;
        }

        @media (max-width: 768px) {
            .custom-schedule-matrix .row {
                flex-wrap: wrap;
                margin-bottom: 12px;
            }
        }

        /* Make checkboxes visible */
        .form-check-input[type="checkbox"] {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0;
            cursor: pointer;
            border: 1.5px solid #cbd5e1;
            background-color: white;
        }

        .form-check-input[type="checkbox"]:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .form-check-input[type="checkbox"]:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            border-color: #10b981;
        }

        /* Ensure the flex layout doesn't hide the checkboxes */
        .d-flex.align-items-center.gap-2 {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-3 dashboard-container">
        <div class="row g-4">
            <!-- Left Sidebar (similar style) -->
            <div class="col-12 col-md-4 col-xl-3">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main content -->
            <div class="col-12 col-md-8 col-lg-9">
                <div class="main-card p-4">
                    <div class="main-header border-bottom pb-3 mb-4 d-flex align-items-center gap-2">
                        <a href="#" class="text-dark text-decoration-none"><i class="bi bi-arrow-left fs-5 me-1"></i></a>
                        <h2 class="h5 mb-0 fw-semibold">Automations</h2>
                    </div>

                    <form id="automationForm">
                        <!-- Title & toggle -->
                        <div class="form-section row align-items-end mb-4">
                            <div class="col">
                                <label class="form-label-custom">Title</label>
                                <input type="text" class="form-control form-control-custom bg-light" value="Trial Class Follow Up" id="automationTitle">
                            </div>
                            <div class="col-auto ps-0 pb-2">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="automationToggle" checked>
                                    <label class="form-check-label text-dark small fw-medium" for="automationToggle">On</label>
                                </div>
                            </div>
                        </div>

                        <!-- Triggers and conditions (static demo) -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom">When this happens</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-sm-6"><select class="form-select form-select-custom bg-light">
                                        <option>Customer completes a class</option>
                                    </select></div>
                                <div class="col-12 col-sm-6"><select class="form-select form-select-custom bg-light">
                                        <option>Trial class</option>
                                    </select></div>
                            </div>
                            <button type="button" class="btn btn-pill-outline mt-1">Add a trigger</button>
                        </div>
                        <div class="form-section mb-4">
                            <label class="form-label-custom">Only if</label>
                            <div class="row mb-2">
                                <div class="col-12 col-sm-6"><select class="form-select form-select-custom bg-light">
                                        <option>Customer has not purchased a contract</option>
                                    </select></div>
                            </div>
                            <button type="button" class="btn btn-pill-outline mt-1">Add a condition</button>
                        </div>
                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Start first reminder</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center" value="3" id="startReminderValue">
                                <select class="form-select form-select-inline bg-light" id="startReminderUnit">
                                    <option>Days</option>
                                    <option>Hours</option>
                                </select>
                            </div>
                            <span class="text-muted extra-small">If trigger and conditions are not met, nothing happens</span>
                        </div>
                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Send up to</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center" value="5" id="maxReminders">
                                <span class="text-dark small fw-medium">reminders</span>
                            </div>
                            <span class="text-muted extra-small">Stops immediately once conditions are no longer met</span>
                        </div>

                        <!-- Schedule radio buttons + custom matrix area -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Schedule</label>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="scheduleRadio" id="radioSimple" value="simple">
                                    <label class="form-check-label text-dark small fw-medium" for="radioSimple">Simple</label>
                                </div>
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="scheduleRadio" id="radioCustom" value="custom" checked>
                                    <label class="form-check-label text-dark small fw-medium" for="radioCustom">Custom</label>
                                </div>
                            </div>

                            <!-- CUSTOM SCHEDULE MATRIX (initially visible because custom selected) -->
                            <div id="customScheduleContainer" class="custom-schedule-matrix ms-0 ms-md-3 mt-3">
                                <div class="row text-muted extra-small fw-semibold mb-2 g-2 align-items-center">
                                    <div class="col-1">Reminder</div>
                                    <div class="col-1 text-center">Send</div>
                                    <div class="col-8">Timing</div>
                                    <div class="col-1"></div>
                                </div>
                                <div id="remindersList"></div>
                                <button type="button" id="addReminderBtn" class="btn btn-pill-outline mt-3">+ Add another reminder</button>
                            </div>
                        </div>

                        <!-- Who gets notified -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Who gets notified</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="checkbox" id="checkServiceProvider" checked style="width: 20px; height: 20px;">
                                <label class="text-dark small" for="checkServiceProvider">Service provider</label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" id="checkStudioManager" checked style="width: 20px; height: 20px;">
                                <label class="text-dark small" for="checkStudioManager">Studio manager</label>
                            </div>
                        </div>

                        <!-- Message Templates Accordion (fully working variable buttons and editable) -->
                        <div class="form-section mb-3">
                            <label class="form-label-custom mb-1">Message templates</label>
                            <p class="text-muted extra-small mb-2">Optionally provide example language. This will only appear in the To Do list item for the assigned team members.</p>
                        </div>
                        <div class="accordion custom-accordion mb-4" id="messagesAccordion">
                            <!-- dynamic follow-ups will be generated from maxReminders, but to keep full interaction we prebuild 5 follow-ups with variable insertion -->
                        </div>

                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-save-automation w-100 py-2 fw-semibold mb-3">Save Automation</button>
                            <button type="button" id="deleteAutomationBtn" class="btn btn-link text-danger text-decoration-none small d-block mx-auto">Delete Automation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ---------- CUSTOM SCHEDULE STATE ----------
        let reminders = [{
                id: Date.now() + 1,
                enabled: true,
                value: 3,
                unit: "Days"
            },
            {
                id: Date.now() + 2,
                enabled: true,
                value: 5,
                unit: "Days"
            },
            {
                id: Date.now() + 3,
                enabled: true,
                value: 3,
                unit: "Days"
            },
            {
                id: Date.now() + 4,
                enabled: true,
                value: 5,
                unit: "Days"
            },
            {
                id: Date.now() + 5,
                enabled: true,
                value: 3,
                unit: "Days"
            }
        ];

        // Helper: render custom schedule rows
        function renderReminders() {
            const container = document.getElementById('remindersList');
            if (!container) return;
            container.innerHTML = '';
            reminders.forEach((rem, idx) => {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'row align-items-center g-2 mb-3 reminder-row';
                rowDiv.setAttribute('data-id', rem.id);
                rowDiv.innerHTML = `
                <div class="col-1 text-dark small fw-medium ps-2">${idx+1}</div>
                <div class="col-1 d-flex justify-content-center">
                    <div class="m-0 p-0">
                        <input class="reminder-enabled" type="checkbox" ${rem.enabled ? 'checked' : ''}>
                    </div>
                </div>
                <div class="col-2 d-flex align-items-center gap-2 flex-wrap">
                    <input type="number" class="form-control form-control-inline bg-light text-center reminder-value" value="${rem.value}" style="width:75px">
                    <select class="form-select form-select-inline bg-light reminder-unit" style="width:80px">
                        <option value="Days" ${rem.unit === 'Days' ? 'selected' : ''}>Days</option>
                        <option value="Hours" ${rem.unit === 'Hours' ? 'selected' : ''}>Hours</option>
                        <option value="Weeks" ${rem.unit === 'Weeks' ? 'selected' : ''}>Weeks</option>
                    </select>
                </div>
                <div class="col-1 text-center">
                    <button type="button" class="btn btn-link p-0 text-muted delete-reminder-btn"><i class="bi bi-trash3"></i></button>
                </div>
            `;
                // attach event to delete button
                const delBtn = rowDiv.querySelector('.delete-reminder-btn');
                delBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (reminders.length <= 1) {
                        alert("At least one reminder is required.");
                        return;
                    }
                    reminders = reminders.filter(r => r.id !== rem.id);
                    renderReminders();
                });
                // attach change events to update state
                const enableChk = rowDiv.querySelector('.reminder-enabled');
                const valueInp = rowDiv.querySelector('.reminder-value');
                const unitSel = rowDiv.querySelector('.reminder-unit');
                enableChk.addEventListener('change', (e) => {
                    rem.enabled = e.target.checked;
                });
                valueInp.addEventListener('change', (e) => {
                    rem.value = parseInt(e.target.value) || 0;
                });
                unitSel.addEventListener('change', (e) => {
                    rem.unit = e.target.value;
                });
                container.appendChild(rowDiv);
            });
        }

        // add reminder button
        function addNewReminder() {
            const newId = Date.now();
            reminders.push({
                id: newId,
                enabled: true,
                value: 1,
                unit: "Days"
            });
            renderReminders();
        }

        // handle schedule radio toggle: show/hide custom matrix and also optionally hide simple interval UI
        // in the design, we already have simple interval above? but specification: when custom selected, custom section appears.
        // The simple schedule part (start reminder + send up to) remains but the custom matrix shows fully interactive.
        // Also the "Simple" radio will hide the custom matrix and simple schedule will be used.
        const radioSimple = document.getElementById('radioSimple');
        const radioCustom = document.getElementById('radioCustom');
        const customContainerDiv = document.getElementById('customScheduleContainer');

        function toggleScheduleDisplay() {
            if (radioCustom.checked) {
                if (customContainerDiv) customContainerDiv.style.display = 'block';
            } else {
                if (customContainerDiv) customContainerDiv.style.display = 'none';
            }
        }

        if (radioSimple && radioCustom) {
            radioSimple.addEventListener('change', toggleScheduleDisplay);
            radioCustom.addEventListener('change', toggleScheduleDisplay);
            toggleScheduleDisplay(); // initial
        }

        // Attach add reminder button listener after DOM ready
        document.getElementById('addReminderBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            addNewReminder();
        });

        // ========== MESSAGE ACCORDION DYNAMIC GENERATION (fully functional variable insertion) ==========
        // We'll generate 5 Follow-up accordion items matching the maxReminders but also keep editability.
        // For full variable buttons, each accordion body will contain editable region + variable chips.
        function buildAccordionItems(count) {
            const accordionContainer = document.getElementById('messagesAccordion');
            if (!accordionContainer) return;
            accordionContainer.innerHTML = '';
            const sampleTexts = [
                'Hi <span class="variable-badge" contenteditable="false">Student Name</span> this is <span class="variable-badge" contenteditable="false">Service Provider Name</span> at <span class="variable-badge" contenteditable="false">Location</span>. How are you? I was wondering if you\'d be interested in signing up for our winter class.',
                'Just following up again! <span class="variable-badge" contenteditable="false">Student Name</span>, we have limited spots. Let me know if you have any questions.',
                'Hello <span class="variable-badge" contenteditable="false">Student Name</span>, hope you enjoyed the trial! Feel free to reply to <span class="variable-badge" contenteditable="false">Service Provider Name</span>.',
                'Reminder: special offer ends soon at <span class="variable-badge" contenteditable="false">Location</span>. Don\'t miss out!',
                'Last call! <span class="variable-badge" contenteditable="false">Student Name</span>, we\'d love to see you in our upcoming sessions.'
            ];
            for (let i = 1; i <= count; i++) {
                const accordionItem = document.createElement('div');
                accordionItem.className = 'accordion-item mb-2 border rounded-3 overflow-hidden';
                const headerId = `headingFollow${i}`;
                const collapseId = `collapseFollow${i}`;
                const expanded = (i === 1);
                accordionItem.innerHTML = `
                <h2 class="accordion-header" id="${headerId}">
                    <button class="accordion-button ${expanded ? '' : 'collapsed'} fs-6 text-dark fw-medium py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${expanded}" aria-controls="${collapseId}">
                        Follow up ${i}
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse ${expanded ? 'show' : ''}" aria-labelledby="${headerId}" data-bs-parent="#messagesAccordion">
                    <div class="accordion-body p-3 pt-1">
                        <div class="textarea-container p-2 border rounded-2 mb-2 bg-white">
                            <div class="editable-content-area" contenteditable="true" data-msg-index="${i}">
                                ${sampleTexts[(i-1) % sampleTexts.length]}
                            </div>
                        </div>
                        <div class="variables-section">
                            <span class="text-muted extra-small d-block mb-1">Insert Variables</span>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-variable-token var-btn" data-var="Student Name">Student Name</button>
                                <button type="button" class="btn btn-variable-token var-btn" data-var="Location">Location</button>
                                <button type="button" class="btn btn-variable-token var-btn" data-var="Service Provider Name">Service Provider Name</button>
                                <button type="button" class="btn btn-variable-token var-btn" data-var="Corporation Name">Corporation Name</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                accordionContainer.appendChild(accordionItem);
            }
            // attach variable insertion logic for all variable buttons after rendering
            document.querySelectorAll('.var-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const varName = btn.getAttribute('data-var');
                    // find the parent accordion-body -> editable area
                    const accordBody = btn.closest('.accordion-body');
                    if (accordBody) {
                        const editableDiv = accordBody.querySelector('.editable-content-area');
                        if (editableDiv) {
                            const variableSpan = document.createElement('span');
                            variableSpan.className = 'variable-badge';
                            variableSpan.setAttribute('contenteditable', 'false');
                            variableSpan.innerText = varName;
                            // insert at cursor position (simplified: append, but better: focus & insert)
                            editableDiv.focus();
                            const selection = window.getSelection();
                            const range = selection.getRangeAt(0);
                            range.deleteContents();
                            range.insertNode(variableSpan);
                            range.collapse(false);
                            // add a space after
                            const spaceNode = document.createTextNode('\u00A0');
                            range.insertNode(spaceNode);
                            range.collapse(false);
                            selection.removeAllRanges();
                            selection.addRange(range);
                            editableDiv.dispatchEvent(new Event('input'));
                        }
                    }
                });
            });
            // ensure existing variable badges are non-editable (they already are)
        }

        // Sync number of follow-ups with maxReminders input (live)
        const maxRemindersInput = document.getElementById('maxReminders');

        function updateAccordionCount() {
            let maxVal = parseInt(maxRemindersInput.value);
            if (isNaN(maxVal) || maxVal < 1) maxVal = 1;
            if (maxVal > 20) maxVal = 20;
            buildAccordionItems(maxVal);
        }
        if (maxRemindersInput) {
            maxRemindersInput.addEventListener('change', updateAccordionCount);
            // also on load generate initial 5 according to current value (5)
            updateAccordionCount();
        } else {
            buildAccordionItems(5);
        }

        // also sync the "Start first reminder" simple fields and "send up to" can be used for demo but custom matrix overrides.
        // functionality for insert variable from tokens works for all accordions.

        // Delete automation button alert simulation
        const deleteBtn = document.getElementById('deleteAutomationBtn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this automation? This action cannot be undone.')) {
                    alert('Automation deleted (demo)');
                    // optional reset form
                    document.getElementById('automationForm').reset();
                    reminders = [{
                        id: Date.now(),
                        enabled: true,
                        value: 1,
                        unit: "Days"
                    }];
                    renderReminders();
                    updateAccordionCount();
                }
            });
        }

        // Save automation: collect data
        const saveBtn = document.querySelector('.btn-save-automation');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const title = document.getElementById('automationTitle')?.value || '';
                const isActive = document.getElementById('automationToggle')?.checked;
                const scheduleType = radioCustom.checked ? 'custom' : 'simple';
                let customRemindersData = [];
                if (scheduleType === 'custom') {
                    customRemindersData = reminders.map(r => ({
                        enabled: r.enabled,
                        value: r.value,
                        unit: r.unit
                    }));
                }
                const notified = {
                    serviceProvider: document.getElementById('checkServiceProvider')?.checked,
                    studioManager: document.getElementById('checkStudioManager')?.checked
                };
                // extract message templates from each follow-up accordion
                const messages = [];
                const accordItems = document.querySelectorAll('#messagesAccordion .accordion-item');
                accordItems.forEach((item, idx) => {
                    const editableDiv = item.querySelector('.editable-content-area');
                    if (editableDiv) {
                        // capture inner HTML with variable spans
                        messages.push(editableDiv.innerHTML);
                    } else {
                        messages.push('');
                    }
                });
                console.log('Saved Automation:', {
                    title,
                    isActive,
                    scheduleType,
                    customRemindersData,
                    notified,
                    messages,
                    startReminderValue: document.getElementById('startReminderValue')?.value,
                    maxReminders: maxRemindersInput?.value
                });
                alert('Automation saved successfully! (demo)');
            });
        }

        // also ensure delete individual reminder rows works fully and the "Add another reminder" pushes new row.
        // for the "Simple" mode: optionally hide custom matrix, but we already handle that.
        // Also make sure that when custom radio is selected, the custom schedule matrix is fully interactive, and delete row & add row, checkbox toggles all work.
        // fix initial render of reminders after DOM ready
        renderReminders();

        // small edge: when maxReminders changes, we also want to make sure variable buttons inside newly built accordion still attach events (handled inside buildAccordionItems)
        // Additional for any dynamic follow-ups: we reassign variable clicks in buildAccordionItems. Also reinit on any change.
        // support for "editable-content-area" to maintain variable badges; badges are non-editable.
        // set up event delegation for variable buttons re-init after accordion dynamic? Already inside building function.
        // plus for delete reminder rows confirmation
        // Also final sync on load: ensure custom schedule is visible
        window.addEventListener('load', () => {
            renderReminders();
            toggleScheduleDisplay();
            // ensure all current delete buttons working
        });
    </script>
</body>

</html>