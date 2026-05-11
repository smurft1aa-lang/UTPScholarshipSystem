/**
 * UTP System - Client-side JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Password strength validation
    const passwordInputs = document.querySelectorAll('input[data-validate="password"]');
    passwordInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            validatePasswordStrength(this);
        });
    });

    // Confirm password match
    const confirmPw = document.getElementById('confirm_password');
    if (confirmPw) {
        confirmPw.addEventListener('input', function() {
            const pw = document.getElementById('password');
            const err = document.getElementById('confirm_password_error');
            if (pw && err) {
                if (this.value && this.value !== pw.value) {
                    err.textContent = 'Passwords do not match.';
                } else {
                    err.textContent = '';
                }
            }
        });
    }

    // Form submission validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    // Modal handling
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-modal-target');
            openModal(targetId);
        });
    });

    const modalClosers = document.querySelectorAll('[data-modal-close]');
    modalClosers.forEach(function(closer) {
        closer.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.closest('.modal-overlay').id;
            closeModal(targetId);
        });
    });

    const printBtns = document.querySelectorAll('[data-action="print"]');
    printBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    });
});

function validatePasswordStrength(input) {
    var value = input.value;
    var indicator = document.getElementById('password_strength');
    if (!indicator) return;

    var strength = 0;
    if (value.length >= 8) strength++;
    if (/[A-Z]/.test(value)) strength++;
    if (/[a-z]/.test(value)) strength++;
    if (/[0-9]/.test(value)) strength++;
    if (/[^A-Za-z0-9]/.test(value)) strength++;

    var labels = ['', 'Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    var colors = ['', '#dc3545', '#dc3545', '#e6a817', '#22a867', '#22a867'];

    if (value.length === 0) {
        indicator.textContent = '';
        indicator.style.color = '';
    } else {
        indicator.textContent = labels[strength] || '';
        indicator.style.color = colors[strength] || '';
    }
}

function validateForm(form) {
    var valid = true;
    var inputs = form.querySelectorAll('[required]');

    inputs.forEach(function(input) {
        var errId = input.id + '_error';
        var errEl = document.getElementById(errId);
        if (!errEl) return;

        if (!input.value.trim()) {
            errEl.textContent = 'This field is required.';
            valid = false;
        } else {
            errEl.textContent = '';
        }
    });

    // Email validation
    var emailInput = form.querySelector('input[type="email"]');
    if (emailInput && emailInput.value) {
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        var emailErr = document.getElementById(emailInput.id + '_error');
        if (!emailPattern.test(emailInput.value) && emailErr) {
            emailErr.textContent = 'Please enter a valid email address.';
            valid = false;
        }
    }

    // Password confirmation
    var pw = form.querySelector('#password');
    var cpw = form.querySelector('#confirm_password');
    if (pw && cpw && cpw.value !== pw.value) {
        var cpwErr = document.getElementById('confirm_password_error');
        if (cpwErr) cpwErr.textContent = 'Passwords do not match.';
        valid = false;
    }

    return valid;
}

// Modal handling
function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// Dynamic grade inputs for qualification types
// Dynamic grade inputs for qualification types
function updateGradeInputs(qualType) {
    var container = document.getElementById('grade_inputs');
    if (!container) return;

    var coreSubjects = {};
    coreSubjects['SPM'] = ['Bahasa Melayu', 'English', 'Mathematics', 'Sejarah'];
    coreSubjects['O-Level'] = ['English Language', 'Mathematics'];
    coreSubjects['IGCSE'] = ['English Language', 'Mathematics'];

    var optionalSubjects = [
        'Additional Mathematics', 'Physics', 'Chemistry', 'Biology', 'Science',
        'Pendidikan Islam', 'Pendidikan Moral', 'Prinsip Perakaunan', 'Ekonomi',
        'Perniagaan', 'Sains Komputer', 'Grafik Komunikasi Teknikal', 'Pendidikan Seni Visual', 'Reka Cipta',
        'Other Subject', 'Other Subject I', 'Other Subject II', 'Other Subject III', 'Other Subject IV',
        'Other Non-Language Subject', 'Other Non-Language Subject I', 'Other Non-Language Subject II', 'Other Non-Language Subject III', 'Other Non-Language Subject IV'
    ];

    var grades = {};
    grades['SPM'] = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'E', 'G'];
    grades['O-Level'] = ['A*', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'U'];
    grades['IGCSE'] = ['A*', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'U'];

    var subjectList = coreSubjects[qualType] || [];
    var gradeList = grades[qualType] || [];

    // Clear and build UI
    container.innerHTML = '<div id="subject_rows"></div>';
    var rowsContainer = document.getElementById('subject_rows');

    // Create a function to add a row
    window.addSubjectRow = function(prefillSubject, isCore) {
        prefillSubject = prefillSubject || '';
        isCore = isCore || false;

        var row = document.createElement('div');
        row.className = 'form-row';
        row.style.cssText = 'margin-bottom:12px; display:grid; grid-template-columns:2fr 1fr 42px; gap:12px; align-items:end;';

        var subjCol = document.createElement('div');

        if (isCore) {
            subjCol.innerHTML = '<label class="form-label" style="margin-bottom:0; padding:10px 0; font-weight:600;">' + prefillSubject + '</label>' +
                                '<input type="hidden" name="subjects[]" value="' + prefillSubject + '">';
        } else {
            var subjSelect = '<label class="form-label">Subject</label>' +
                             '<select name="subjects[]" class="form-select" required>' +
                             '<option value="" disabled' + (prefillSubject === '' ? ' selected' : '') + '>Select Subject</option>';
            optionalSubjects.forEach(function(s) {
                subjSelect += '<option value="' + s + '"' + (prefillSubject === s ? ' selected' : '') + '>' + s + '</option>';
            });
            subjSelect += '</select>';
            subjCol.innerHTML = subjSelect;
        }

        var gradeCol = document.createElement('div');

        var gradeSelect = '<label class="form-label">Grade</label>' +
                          '<select name="grades[]" class="form-select" required>' +
                          '<option value="" disabled selected>Grade</option>';
        gradeList.forEach(function(g) {
            gradeSelect += '<option value="' + g + '">' + g + '</option>';
        });
        gradeSelect += '</select>';
        gradeCol.innerHTML = gradeSelect;

        var actionCol = document.createElement('div');
        actionCol.style.cssText = 'display:flex; align-items:center; justify-content:center; height:42px;';

        if (!isCore) {
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-red btn-sm';
            removeBtn.textContent = 'X';
            removeBtn.style.cssText = 'padding:8px 12px; height:42px; width:42px; display:flex; align-items:center; justify-content:center;';
            removeBtn.addEventListener('click', function() {
                row.remove();
            });
            actionCol.appendChild(removeBtn);
        }

        row.appendChild(subjCol);
        row.appendChild(gradeCol);
        row.appendChild(actionCol);
        rowsContainer.appendChild(row);
    };

    // Add core subjects first
    subjectList.forEach(function(s) {
        window.addSubjectRow(s, true);
    });

    // Add 3 blank optional subjects for convenience
    for(var i=0; i<3; i++) {
        window.addSubjectRow();
    }

    // Add button
    var addBtn = document.createElement('button');
    addBtn.type = 'button';
    addBtn.className = 'btn btn-outline btn-sm';
    addBtn.textContent = '+ Add Another Subject';
    addBtn.style.marginTop = '10px';
    addBtn.addEventListener('click', function() {
        window.addSubjectRow();
    });

    container.appendChild(addBtn);

    var step2 = document.getElementById('step2');
    if (step2) step2.classList.remove('hidden');

    updateSteps(2);
}

function updateSteps(activeStep) {
    var steps = document.querySelectorAll('.step');
    var lines = document.querySelectorAll('.step-line');

    steps.forEach(function(step, index) {
        step.classList.remove('active', 'completed');
        if (index + 1 < activeStep) step.classList.add('completed');
        if (index + 1 === activeStep) step.classList.add('active');
    });

    lines.forEach(function(line, index) {
        line.classList.remove('completed');
        if (index + 1 < activeStep) line.classList.add('completed');
    });
}

// ── Toast Notification System ──

/**
 * Show a toast notification.
 * @param {string} message - The message to display
 * @param {'success'|'error'|'warning'|'info'} type - Type of toast
 * @param {number} duration - Duration in milliseconds (default 4000)
 */
function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration || 4000;

    // Create container if it doesn't exist
    var container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var icons = { success: '✓', error: '✕', warning: '!', info: 'i' };

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (icons[type] ? type : 'info');
    toast.style.setProperty('--toast-duration', (duration / 1000) + 's');

    // Build toast content using safe DOM APIs (no innerHTML — prevents XSS)
    var iconSpan = document.createElement('span');
    iconSpan.className = 'toast-icon';
    iconSpan.textContent = icons[type] || 'i';

    var msgSpan = document.createElement('span');
    msgSpan.className = 'toast-message';
    msgSpan.textContent = message;

    var closeBtn = document.createElement('button');
    closeBtn.className = 'toast-close';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.innerHTML = '&times;';

    var progressDiv = document.createElement('div');
    progressDiv.className = 'toast-progress';

    toast.appendChild(iconSpan);
    toast.appendChild(msgSpan);
    toast.appendChild(closeBtn);
    toast.appendChild(progressDiv);

    container.appendChild(toast);

    // Close button
    closeBtn.addEventListener('click', function() {
        removeToast(toast);
    });

    // Auto dismiss
    setTimeout(function() {
        removeToast(toast);
    }, duration);
}

function removeToast(toast) {
    if (toast.classList.contains('removing')) return;
    toast.classList.add('removing');
    setTimeout(function() {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 300);
}

// ── Loading States ──

/**
 * Set a button to loading state
 * @param {HTMLElement} btn - The button element
 */
function setButtonLoading(btn) {
    btn.classList.add('loading');
    btn.disabled = true;
}

/**
 * Remove loading state from a button
 * @param {HTMLElement} btn - The button element
 */
function clearButtonLoading(btn) {
    btn.classList.remove('loading');
    btn.disabled = false;
}

// Auto-show toasts from PHP flash messages
document.addEventListener('DOMContentLoaded', function() {
    var flashElements = document.querySelectorAll('[data-toast]');
    flashElements.forEach(function(el) {
        var msg = el.getAttribute('data-toast-message') || el.textContent.trim();
        var type = el.getAttribute('data-toast') || 'info';
        if (msg) showToast(msg, type);
        el.style.display = 'none';
    });

    // Add loading state to forms on submit
    var submitForms = document.querySelectorAll('form:not([data-no-loading])');
    submitForms.forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"], .btn-submit');
            if (btn) setButtonLoading(btn);
        });
    });
});

