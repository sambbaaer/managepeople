/**
 * automation.js
 * JavaScript für Automation-Tab Interaktivität
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===== ACCORDION FUNKTIONALITÄT =====
    const accordionTriggers = document.querySelectorAll('.accordion-trigger');

    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const icon = this.querySelector('.accordion-icon');

            // Toggle Accordion
            if (content.classList.contains('active')) {
                content.classList.remove('active');
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('active');
                icon.style.transform = 'rotate(180deg)';
            }
        });

        // Erstes Accordion standardmässig öffnen
        if (accordionTriggers[0] === trigger) {
            trigger.click();
        }
    });


    // ===== GLOBAL TOGGLE =====
    const globalToggle = document.getElementById('global-toggle');
    if (globalToggle) {
        globalToggle.addEventListener('click', function () {
            const currentState = this.getAttribute('data-enabled') === '1';

            // Optimistic UI Update
            this.classList.toggle('active');
            const statusSpan = document.getElementById('global-status');
            statusSpan.textContent = currentState ? 'Inaktiv' : 'Aktiv';

            // AJAX Request
            fetch('index.php?page=automation_toggle_global', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.setAttribute('data-enabled', data.is_enabled ? '1' : '0');
                        showToast(data.message, 'success');
                    } else {
                        // Rollback bei Fehler
                        this.classList.toggle('active');
                        statusSpan.textContent = currentState ? 'Aktiv' : 'Inaktiv';
                        showToast(data.error || 'Fehler beim Ändern der Einstellung', 'error');
                    }
                })
                .catch(error => {
                    // Rollback bei Fehler
                    this.classList.toggle('active');
                    statusSpan.textContent = currentState ? 'Aktiv' : 'Inaktiv';
                    showToast('Netzwerkfehler', 'error');
                    console.error('Error:', error);
                });
        });
    }


    // ===== REGEL TOGGLE =====
    const ruleToggles = document.querySelectorAll('.rule-toggle');

    ruleToggles.forEach(toggle => {
        toggle.addEventListener('click', function () {
            const ruleId = this.getAttribute('data-rule-id');
            const currentState = this.classList.contains('active');

            // Optimistic UI Update
            this.classList.toggle('active');

            // AJAX Request
            const formData = new FormData();
            formData.append('rule_id', ruleId);

            fetch('index.php?page=automation_toggle_rule', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Statistik aktualisieren (optional)
                        updateStatistics();
                    } else {
                        // Rollback bei Fehler
                        this.classList.toggle('active');
                        showToast(data.error || 'Fehler beim Ändern der Regel', 'error');
                    }
                })
                .catch(error => {
                    // Rollback bei Fehler
                    this.classList.toggle('active');
                    showToast('Netzwerkfehler', 'error');
                    console.error('Error:', error);
                });
        });
    });


    // ===== INLINE EDIT - TAGE =====
    const daysInputs = document.querySelectorAll('.days-input');
    let debounceTimer;

    daysInputs.forEach(input => {
        input.addEventListener('input', function () {
            const ruleId = this.getAttribute('data-rule-id');
            const newValue = this.value;

            // Debounce (warten bis User fertig getippt hat)
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                updateRuleField(ruleId, 'days_offset', newValue, this);
            }, 800); // 800ms Verzögerung
        });

        // Live-Update des Textes "Tag" vs "Tagen"
        input.addEventListener('input', function () {
            const parent = this.closest('.flex.items-center.gap-2');
            const pluralSpan = parent.querySelector('span:last-child');
            if (pluralSpan) {
                pluralSpan.textContent = this.value == 1 ? 'Tag' : 'Tagen';
            }
        });
    });


    // ===== UPDATE REGEL FIELD (Generic) =====
    function updateRuleField(ruleId, field, value, inputElement) {
        const formData = new FormData();
        formData.append('rule_id', ruleId);
        formData.append('field', field);
        formData.append('value', value);

        // Visual Feedback
        if (inputElement) {
            inputElement.style.borderColor = '#4ECDC4';
        }

        fetch('index.php?page=automation_update_rule', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (inputElement) {
                        inputElement.style.borderColor = '#22c55e'; // Grün
                        setTimeout(() => {
                            inputElement.style.borderColor = '';
                        }, 1000);
                    }
                    showToast(data.message, 'success');
                } else {
                    if (inputElement) {
                        inputElement.style.borderColor = '#ef4444'; // Rot
                        setTimeout(() => {
                            inputElement.style.borderColor = '';
                        }, 2000);
                    }
                    showToast(data.error || 'Fehler beim Speichern', 'error');
                }
            })
            .catch(error => {
                if (inputElement) {
                    inputElement.style.borderColor = '#ef4444';
                    setTimeout(() => {
                        inputElement.style.borderColor = '';
                    }, 2000);
                }
                showToast('Netzwerkfehler', 'error');
                console.error('Error:', error);
            });
    }


    // ===== STATISTIK AKTUALISIEREN =====
    function updateStatistics() {
        // Zähle aktive Toggles
        const allToggles = document.querySelectorAll('.rule-toggle');
        const activeToggles = document.querySelectorAll('.rule-toggle.active');

        // Aktualisiere nur die Zahlen (ohne Reload)
        const activeCountEl = document.querySelector('.glass-card:nth-child(1) .text-3xl');
        if (activeCountEl) {
            activeCountEl.textContent = activeToggles.length;
        }
    }


    // ===== TOAST NOTIFICATIONS =====
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container') || createToastContainer();

        const toast = document.createElement('div');
        toast.className = `glass-card px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 transform transition-all duration-300 translate-x-full`;

        // Icon und Farbe basierend auf Typ
        let icon = 'info';
        let colorClass = 'border-l-4 border-blue-500';

        if (type === 'success') {
            icon = 'check-circle-2';
            colorClass = 'border-l-4 border-green-500';
        } else if (type === 'error') {
            icon = 'x-circle';
            colorClass = 'border-l-4 border-red-500';
        }

        toast.className += ' ' + colorClass;

        toast.innerHTML = `
            <i data-lucide="${icon}" class="w-5 h-5"></i>
            <span class="text-sm font-medium text-gray-800">${message}</span>
            <button class="ml-2 text-gray-400 hover:text-gray-600" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;

        container.appendChild(toast);

        // Lucide Icons initialisieren
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Slide in animation
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 10);

        // Auto-remove nach 4 Sekunden
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
        return container;
    }

    // ===== REGEL LÖSCHEN =====
    window.deleteRule = function (ruleId) {
        if (!confirm('Möchtest du diese Regel wirklich löschen?')) {
            return;
        }

        const formData = new FormData();
        formData.append('rule_id', ruleId);

        fetch('index.php?page=automation_delete_rule', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Element aus dem DOM entfernen
                    const ruleCard = document.querySelector(`.rule-toggle[data-rule-id="${ruleId}"]`).closest('.relative.group');
                    if (ruleCard) {
                        ruleCard.remove();
                        // Wenn keine Regeln mehr da sind, Reload um Empty-State zu zeigen
                        if (document.querySelectorAll('.rule-toggle').length === 0) {
                            location.reload();
                        }
                    }
                } else {
                    showToast(data.error || 'Fehler beim Löschen', 'error');
                }
            })
            .catch(error => {
                showToast('Netzwerkfehler', 'error');
                console.error('Error:', error);
            });
    };

    // ===== REGEL IMPORTIEREN =====
    window.importRule = function (input) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        const formData = new FormData();
        formData.append('file', file);

        // UI Feedback: Button Text "Lade..."
        // Button ist vor dem input
        const button = input.previousElementSibling;
        let originalText = '';
        if (button) {
            originalText = button.innerHTML;
            button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Importiere...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        fetch('index.php?page=automation_import_rule', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Regel erfolgreich importiert', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Fehler beim Importieren', 'error');
                    input.value = '';
                    if (button) button.innerHTML = originalText;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            })
            .catch(error => {
                showToast('Netzwerkfehler', 'error');
                console.error('Error:', error);
                input.value = '';
                if (button) button.innerHTML = originalText;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
    };

});
