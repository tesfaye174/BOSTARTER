<?php
session_start();
require_once 'includes/init.php';

// Verifica autenticazione e ruolo admin
if (!isAuthenticated()) {
    header('Location: auth/login.php');
    exit();
}

$userType = getUserType();
if ($userType !== 'amministratore') {
    header('Location: home.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Recupera competenze
$competenze = [];
$error = null;

try {
    $response = file_get_contents("http://localhost/BOSTARTER/backend/api/competenze.php");
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success']) {
        $competenze = $data['data'];
    } else {
        $error = $data['error'] ?? 'Errore nel recupero competenze';
    }
} catch (Exception $e) {
    $error = 'Errore di connessione: ' . $e->getMessage();
}

include 'includes/head.php';
?>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-cogs"></i>
                            Gestione Competenze
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="fas fa-shield-alt"></i>
                            Accesso riservato agli amministratori
                            <span class="mx-2">•</span>
                            <i class="fas fa-info-circle"></i>
                            Gestisci le competenze disponibili nella piattaforma
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-bostarter-primary" data-bs-toggle="modal" data-bs-target="#addCompetenzaModal">
                            <i class="fas fa-plus"></i> Nuova Competenza
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <!-- Filtri -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="categoriaFilter" class="form-label">Filtra per Categoria</label>
                            <select class="form-select" id="categoriaFilter">
                                <option value="">Tutte le categorie</option>
                                <option value="programmazione">Programmazione</option>
                                <option value="design">Design</option>
                                <option value="marketing">Marketing</option>
                                <option value="business">Business</option>
                                <option value="hardware">Hardware</option>
                                <option value="generale">Generale</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchCompetenza" class="form-label">Cerca Competenza</label>
                            <input type="text" class="form-control" id="searchCompetenza" 
                                placeholder="Nome o descrizione...">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                <i class="fas fa-undo"></i> Reset Filtri
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista Competenze -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-list"></i>
                        Competenze Disponibili
                    </h5>
                    <span class="badge bg-bostarter-primary"><?php echo count($competenze); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($competenze)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessuna competenza configurata.</p>
                            <p class="text-muted">Aggiungi la prima competenza per iniziare!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="competenzeTable">
                                <thead class="table-bostarter">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrizione</th>
                                        <th>Categoria</th>
                                        <th>Stato</th>
                                        <th>Data Creazione</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($competenze as $competenza): ?>
                                        <tr data-categoria="<?php echo htmlspecialchars($competenza['categoria']); ?>"
                                            data-nome="<?php echo htmlspecialchars($competenza['nome']); ?>"
                                            data-descrizione="<?php echo htmlspecialchars($competenza['descrizione']); ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($competenza['nome']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="text-muted">
                                                    <?php echo htmlspecialchars(substr($competenza['descrizione'], 0, 100)); ?>
                                                    <?php if (strlen($competenza['descrizione']) > 100): ?>...<?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-bostarter-info">
                                                    <?php echo ucfirst(htmlspecialchars($competenza['categoria'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $competenza['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $competenza['is_active'] ? 'Attiva' : 'Disattivata'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($competenza['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editCompetenza(<?php echo $competenza['id']; ?>, '<?php echo htmlspecialchars($competenza['nome']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($competenza['is_active']): ?>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                            onclick="deactivateCompetenza(<?php echo $competenza['id']; ?>, '<?php echo htmlspecialchars($competenza['nome']); ?>')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Nuova Competenza -->
    <div class="modal fade" id="addCompetenzaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuova Competenza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCompetenzaForm">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Competenza *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                placeholder="es. JavaScript, UI/UX Design, Marketing Digitale" required>
                        </div>
                        <div class="mb-3">
                            <label for="descrizione" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="descrizione" name="descrizione" rows="3" 
                                placeholder="Breve descrizione della competenza..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="generale">Generale</option>
                                <option value="programmazione">Programmazione</option>
                                <option value="design">Design</option>
                                <option value="marketing">Marketing</option>
                                <option value="business">Business</option>
                                <option value="hardware">Hardware</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="codice_sicurezza" class="form-label">Codice di Sicurezza *</label>
                            <input type="password" class="form-control" id="codice_sicurezza" name="codice_sicurezza" 
                                placeholder="Inserisci il tuo codice di sicurezza" required>
                            <small class="form-text text-muted">
                                Codice richiesto per le operazioni amministrative
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-bostarter-primary" onclick="addCompetenza()">Crea Competenza</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifica Competenza -->
    <div class="modal fade" id="editCompetenzaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Competenza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCompetenzaForm">
                        <input type="hidden" id="edit_competenza_id" name="id">
                        <div class="mb-3">
                            <label for="edit_campo" class="form-label">Campo da Modificare</label>
                            <select class="form-select" id="edit_campo" name="campo" required>
                                <option value="">Seleziona campo...</option>
                                <option value="nome">Nome</option>
                                <option value="descrizione">Descrizione</option>
                                <option value="categoria">Categoria</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_valore" class="form-label">Nuovo Valore</label>
                            <input type="text" class="form-control" id="edit_valore" name="valore" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-bostarter-primary" onclick="saveCompetenzaEdit()">Salva</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
    
    <script>
        // Gestione form nuova competenza
        function addCompetenza() {
            const form = document.getElementById('addCompetenzaForm');
            const formData = new FormData(form);
            
            const data = {
                nome: formData.get('nome'),
                descrizione: formData.get('descrizione'),
                categoria: formData.get('categoria'),
                codice_sicurezza: formData.get('codice_sicurezza')
            };
            
            if (!data.nome || !data.codice_sicurezza) {
                showMessage('error', 'Compila tutti i campi obbligatori');
                return;
            }
            
            fetch('/BOSTARTER/backend/api/competenze.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Competenza aggiunta con successo!');
                    bootstrap.Modal.getInstance(document.getElementById('addCompetenzaModal')).hide();
                    form.reset();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'aggiunta competenza');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        }

        // Modifica competenza
        function editCompetenza(competenzaId, competenzaName) {
            document.getElementById('edit_competenza_id').value = competenzaId;
            document.getElementById('edit_campo').value = '';
            document.getElementById('edit_valore').value = '';
            
            new bootstrap.Modal(document.getElementById('editCompetenzaModal')).show();
        }

        // Salva modifica competenza
        function saveCompetenzaEdit() {
            const competenzaId = document.getElementById('edit_competenza_id').value;
            const campo = document.getElementById('edit_campo').value;
            const valore = document.getElementById('edit_valore').value;
            
            if (!campo || !valore) {
                showMessage('error', 'Compila tutti i campi');
                return;
            }
            
            fetch('/BOSTARTER/backend/api/competenze.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    id: competenzaId,
                    campo: campo,
                    valore: valore
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Competenza aggiornata con successo!');
                    bootstrap.Modal.getInstance(document.getElementById('editCompetenzaModal')).hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'aggiornamento competenza');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        }

        // Disattiva competenza
        function deactivateCompetenza(competenzaId, competenzaName) {
            if (!confirm(`Sei sicuro di voler disattivare la competenza "${competenzaName}"?`)) {
                return;
            }
            
            fetch(`/BOSTARTER/backend/api/competenze.php?id=${competenzaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Competenza disattivata con successo!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella disattivazione competenza');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        }

        // Filtri
        document.getElementById('categoriaFilter')?.addEventListener('change', filterCompetenze);
        document.getElementById('searchCompetenza')?.addEventListener('input', filterCompetenze);

        function filterCompetenze() {
            const categoria = document.getElementById('categoriaFilter').value.toLowerCase();
            const search = document.getElementById('searchCompetenza').value.toLowerCase();
            const rows = document.querySelectorAll('#competenzeTable tbody tr');
            
            rows.forEach(row => {
                const rowCategoria = row.dataset.categoria.toLowerCase();
                const rowNome = row.dataset.nome.toLowerCase();
                const rowDescrizione = row.dataset.descrizione.toLowerCase();
                
                const categoriaMatch = !categoria || rowCategoria === categoria;
                const searchMatch = !search || 
                    rowNome.includes(search) || 
                    rowDescrizione.includes(search);
                
                row.style.display = categoriaMatch && searchMatch ? '' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('categoriaFilter').value = '';
            document.getElementById('searchCompetenza').value = '';
            filterCompetenze();
        }

        // Utility functions
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function showMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html> 