/**
 * dashboard.js
 * JavaScript für Dashboard ToDo Inline-Aktionen
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===== TASK ACTIONS (Complete, Delete, Reschedule) =====
    const taskActionButtons = document.querySelectorAll('.task-action');

    taskActionButtons.forEach(button => {
        button.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const action = this.getAttribute('data-action');
            const taskId = this.getAttribute('data-id');

            if (!taskId) return;

            switch (action) {
                case 'complete':
                    await completeTask(taskId);
                    break;
                case 'delete':
                    await deleteTask(taskId);
                    break;
                case 'reschedule':
                    await rescheduleTask(taskId);
                    break;
            }
        });
    });


    // ===== COMPLETE TASK =====
    async function completeTask(taskId) {
        try {
            const response = await fetch(`index.php?page=task_toggle&id=${taskId}`);

            if (response.ok) {
                // Reload page to reflect changes
                window.location.reload();
            } else {
                showToast('Fehler beim Erledigen der Aufgabe', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Netzwerkfehler', 'error');
        }
    }


    // ===== DELETE TASK =====
    async function deleteTask(taskId) {
        if (!confirm('Möchtest du diese Aufgabe wirklich löschen?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', taskId);

            const response = await fetch('index.php?page=task_delete', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Aufgabe gelöscht', 'success');
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(result.error || 'Fehler beim Löschen', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Netzwerkfehler', 'error');
        }
    }


    // ===== RESCHEDULE TASK =====
    async function rescheduleTask(taskId) {
        // Create simple prompt-based reschedule for now
        const newDate = prompt('Neues Fälligkeitsdatum (YYYY-MM-DD oder DD.MM.YYYY):');

        if (!newDate) return;

        // Parse date format (allow both YYYY-MM-DD and DD.MM.YYYY)
        let formattedDate = newDate;
        if (newDate.includes('.')) {
            const parts = newDate.split('.');
            if (parts.length === 3) {
                formattedDate = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
            }
        }

        try {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('new_date', formattedDate);

            const response = await fetch('index.php?page=task_reschedule', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Termin verschoben', 'success');
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(result.error || 'Fehler beim Verschieben', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Netzwerkfehler', 'error');
        }
    }


    // ===== TOAST NOTIFICATIONS =====
    function showToast(message, type = 'info') {
        // Check if toast container exists
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `bg-white shadow-lg rounded-lg px-4 py-3 flex items-center gap-3 transform transition-all duration-300 translate-x-full border-l-4`;

        // Color based on type
        if (type === 'success') {
            toast.classList.add('border-green-500');
        } else if (type === 'error') {
            toast.classList.add('border-red-500');
        } else {
            toast.classList.add('border-blue-500');
        }

        toast.innerHTML = `
            <span class="text-sm font-medium text-gray-800">${message}</span>
            <button class="ml-2 text-gray-400 hover:text-gray-600" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;

        container.appendChild(toast);

        // Reinitialize lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Slide in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 10);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

});
