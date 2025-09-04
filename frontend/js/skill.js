// Gestione interattiva delle competenze
document.addEventListener('DOMContentLoaded', function () {
    // Inizializzazione tooltip
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Gestione form aggiunta competenza
    const skillForm = document.getElementById('addSkillForm');
    if (skillForm) {
        skillForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('skill.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Competenza aggiunta con successo!');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast('error', data.message || 'Si è verificato un errore');
                    }
                })
                .catch(error => {
                    showToast('error', 'Si è verificato un errore nella comunicazione col server');
                });
        });
    }

    // Gestione rimozione competenza
    document.querySelectorAll('.remove-skill').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const skillId = this.dataset.skillId;
            const skillName = this.dataset.skillName;

            if (confirm(`Sei sicuro di voler rimuovere la competenza "${skillName}"?`)) {
                const formData = new FormData();
                formData.append('action', 'remove_skill');
                formData.append('competenza_id', skillId);
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                fetch('skill.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const skillCard = document.querySelector(`[data-skill-id="${skillId}"]`);
                            skillCard.classList.add('fade-out');
                            setTimeout(() => {
                                skillCard.remove();
                                showToast('success', 'Competenza rimossa con successo!');
                                if (document.querySelectorAll('.skill-card').length === 0) {
                                    showEmptyState();
                                }
                            }, 300);
                        } else {
                            showToast('error', data.message || 'Si è verificato un errore');
                        }
                    })
                    .catch(error => {
                        showToast('error', 'Si è verificato un errore nella comunicazione col server');
                    });
            }
        });
    });

    // Funzione per mostrare i toast
    function showToast(type, message) {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // Funzione per mostrare lo stato vuoto
    function showEmptyState() {
        const skillsContainer = document.querySelector('.skills-container');
        skillsContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                <h5>Non hai ancora aggiunto competenze!</h5>
                <p class="text-muted">Le competenze ti aiutano a trovare progetti più adatti al tuo profilo.</p>
            </div>
        `;
    }
});
