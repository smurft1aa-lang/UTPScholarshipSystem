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
        'Additional Mathematics', 'Physics', 'Chemistry', 'Biology',
        'Pendidikan Islam', 'Pendidikan Moral', 'Prinsip Perakaunan', 'Ekonomi',
        'Perniagaan', 'Sains Komputer', 'Grafik Komunikasi Teknikal',
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
    window.addSubjectRow = function(prefillSubject = '', isCore = false) {
        var row = document.createElement('div');
        row.className = 'form-row';
        row.style.marginBottom = '16px';
        row.style.display = 'grid';
        row.style.gridTemplateColumns = '2fr 1fr 42px';
        row.style.gap = '12px';
        row.style.alignItems = 'flex-start';

        var subjCol = document.createElement('div');
        subjCol.className = 'form-group';
        subjCol.style.marginBottom = '0';

        if (isCore) {
            subjCol.innerHTML = '<label class="form-label">' + prefillSubject + '</label>' +
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
        gradeCol.className = 'form-group';
        gradeCol.style.marginBottom = '0';

        var gradeSelect = '<label class="form-label">Grade</label>' +
                          '<select name="grades[]" class="form-select" required>' +
                          '<option value="" disabled selected>Grade</option>';
        gradeList.forEach(function(g) {
            gradeSelect += '<option value="' + g + '">' + g + '</option>';
        });
        gradeSelect += '</select>';
        gradeCol.innerHTML = gradeSelect;

        var actionCol = document.createElement('div');
        actionCol.style.display = 'flex';
        actionCol.style.flexDirection = 'column';
        actionCol.style.justifyContent = 'flex-end';
        actionCol.style.height = '100%';
        
        if (!isCore) {
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-red btn-sm';
            removeBtn.textContent = 'X';
            removeBtn.style.padding = '8px 12px';
            removeBtn.style.height = '42px';
            removeBtn.style.marginTop = '26px'; // Align with select inputs
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
