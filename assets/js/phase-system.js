/**
 * assets/js/phase-system.js
 * Zentrales JS für das Phasen-Modalsystem
 */

let currentPhaseFormat = 'full_date';

/**
 * Öffnet das Phasen-Modal
 * @param {string} contactId 
 * @param {string} status 
 * @param {string} phaseName 
 * @param {string} dateLabel 
 * @param {string} dateFormat 
 */
function openPhaseModal(contactId, status, phaseName, dateLabel, dateFormat) {
    const modal = document.getElementById('phaseModal');
    const form = document.getElementById('phaseForm');

    if (!modal || !form) return;

    modal.classList.remove('hidden');
    document.getElementById('phaseModalTitle').textContent = 'Phase: ' + phaseName;
    document.getElementById('phaseModalLabel').textContent = dateLabel || 'Wähle das Datum';
    document.getElementById('phaseModalStatus').value = status;
    document.getElementById('phaseInput').value = phaseName;

    // Set form action dynamically
    form.action = 'index.php?page=contact_update&id=' + contactId;

    currentPhaseFormat = dateFormat;

    // Picker anzeigen basierend auf Format
    if (dateFormat === 'month_year') {
        document.getElementById('monthYearPicker').classList.remove('hidden');
        document.getElementById('fullDatePicker').classList.add('hidden');
        // Aktuellen Monat vorauswählen
        document.getElementById('phaseMonth').value = String(new Date().getMonth() + 1).padStart(2, '0');
    } else {
        document.getElementById('monthYearPicker').classList.add('hidden');
        document.getElementById('fullDatePicker').classList.remove('hidden');
    }

    // Lucide Icons initialisieren
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Öffnet das Workflow-Modal (Status & Phase) via AJAX
 * @param {string} contactId 
 */
function openWorkflowModal(contactId) {
    const modal = document.getElementById('workflowModal');
    const content = document.getElementById('workflow-modal-content');

    if (!modal || !content) return;

    modal.classList.remove('hidden');

    // Loading State
    content.innerHTML = `
        <div class="p-12 text-center text-gray-500">
            <div class="animate-spin inline-block w-8 h-8 border-4 border-current border-t-transparent text-secondary rounded-full mb-4" role="status"></div>
            <p>Lade Workflow...</p>
        </div>
    `;

    // Fetch UI via AJAX
    fetch(`index.php?page=contact_workflow_ui&id=${contactId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            // Re-initialize Lucide Icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Handle Form Submissions within the modal (to keep it interactive or just let it redirect)
            // For now, standard POST redirects are fine as they refresh the list/detail anyway.
            // But we need to make sure 'openPhaseModal' works from within the modal.
        })
        .catch(error => {
            console.error('Error loading workflow UI:', error);
            content.innerHTML = '<div class="p-12 text-center text-red-500">Fehler beim Laden.</div>';
        });
}

function closePhaseModal() {
    const modal = document.getElementById('phaseModal');
    if (modal) modal.classList.add('hidden');
}

function closeWorkflowModal() {
    const modal = document.getElementById('workflowModal');
    if (modal) modal.classList.add('hidden');
}

// Global Event Listener Initialization
document.addEventListener('DOMContentLoaded', function () {
    const phaseForm = document.getElementById('phaseForm');
    const phaseModal = document.getElementById('phaseModal');
    const workflowModal = document.getElementById('workflowModal');

    if (phaseForm) {
        phaseForm.addEventListener('submit', function (e) {
            let finalDate = '';

            if (currentPhaseFormat === 'month_year') {
                const month = document.getElementById('phaseMonth').value;
                const year = document.getElementById('phaseYear').value;
                finalDate = year + '-' + month + '-01';
            } else {
                finalDate = document.getElementById('phaseFullDate').value;
            }

            document.getElementById('phaseDateFinal').value = finalDate;
        });
    }

    if (phaseModal) {
        phaseModal.addEventListener('click', function (e) {
            if (e.target === this) closePhaseModal();
        });
    }

    if (workflowModal) {
        workflowModal.addEventListener('click', function (e) {
            if (e.target === this) closeWorkflowModal();
        });
    }

    // Escape-Taste zum Schliessen
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePhaseModal();
            closeWorkflowModal();
        }
    });
});
