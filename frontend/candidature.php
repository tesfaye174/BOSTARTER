<?php
session_start();
require_once 'includes/init.php';

// Verifica autenticazione
if (!isAuthenticated()) {
    header('Location: auth/login.php');
    exit();
}

$userType = getUserType();
$userId = $_SESSION['user_id'];
$isCreator = ($userType === 'creatore');
$isAdmin = ($userType === 'amministratore');

// Recupera candidature
$candidature = [];
$error = null;

try {
    if (isset($_GET['profilo_id'])) {
        // Candidature per un profilo specifico (solo creatore/admin del progetto)
        $profiloId = (int)$_GET['profilo_id'];
        $response = file_get_contents("http://localhost/BOSTARTER/backend/api/candidature.php?profilo_id=$profiloId");
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            $candidature = $data['data'];
        } else {
            $error = $data['error'] ?? 'Errore nel recupero candidature';
        }
    } else {
        // Candidature dell'utente corrente
        $response = file_get_contents("http://localhost/BOSTARTER/backend/api/candidature.php");
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            $candidature = $data['data'];
                    } else {
            $error = $data['error'] ?? 'Errore nel recupero candidature';
        }
    }
} catch (Exception $e) {
    $error = 'Errore di connessione: ' . $e->getMessage();
}

// Recupera progetti software per candidature
$progettiSoftware = [];
try {
    $response = file_get_contents("http://localhost/BOSTARTER/backend/api/project.php?tipo=software&stato=aperto");
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success']) {
        $progettiSoftware = $data['data']['projects'] ?? $data['data'];
    }
} catch (Exception $e) {
    // Ignora errori per progetti software
}

include 'includes/head.php';
?>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-users"></i>
                    <?php if (isset($_GET['profilo_id'])): ?>
                    Candidature per Profilo
                    <?php else: ?>
                    Le Mie Candidature
                    <?php endif; ?>
                </h1>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Form Nuova Candidatura -->
                <?php if (!isset($_GET['profilo_id']) && !$isCreator): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Nuova Candidatura</h5>
                    </div>
                    <div class="card-body">
                        <form id="candidaturaForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="progetto_id" class="form-label">Progetto Software *</label>
                                        <select class="form-select" id="progetto_id" name="progetto_id" required>
                                            <option value="">Seleziona progetto...</option>
                                            <?php foreach ($progettiSoftware as $progetto): ?>
                                            <option value="<?php echo $progetto['id']; ?>">
                                                <?php echo htmlspecialchars($progetto['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="profilo_id" class="form-label">Profilo Richiesto *</label>
                                        <select class="form-select" id="profilo_id" name="profilo_id" required disabled>
                                            <option value="">Prima seleziona un progetto...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="motivazione" class="form-label">Motivazione *</label>
                                <textarea class="form-control" id="motivazione" name="motivazione" rows="4"
                                    placeholder="Spiega perché sei la persona giusta per questo profilo..."
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-bostarter-primary">
                                <i class="fas fa-paper-plane"></i> Invia Candidatura
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista Candidature -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <i class="fas fa-list"></i>
                            <?php if (isset($_GET['profilo_id'])): ?>
                            Candidature Ricevute
                            <?php else: ?>
                            Le Mie Candidature
                            <?php endif; ?>
                        </h5>
                        <span class="badge bg-bostarter-primary"><?php echo count($candidature); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($candidature)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">
                                <?php if (isset($_GET['profilo_id'])): ?>
                                Nessuna candidatura ricevuta per questo profilo.
                                <?php else: ?>
                                Non hai ancora inviato candidature.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-bostarter">
                                    <tr>
                                        <th>Utente</th>
                                        <th>Progetto</th>
                                        <th>Profilo</th>
                                        <th>Data</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidature as $candidatura): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($candidatura['nickname'] ?? $candidatura['utente_nickname']); ?></strong>
                                                    <?php if (isset($candidatura['nome'])): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars($candidatura['nome'] . ' ' . $candidatura['cognome']); ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($candidatura['progetto_nome']); ?></strong>
                                            <?php if (isset($candidatura['progetto_tipo'])): ?>
                                            <br><small class="badge bg-bostarter-secondary">
                                                <?php echo ucfirst($candidatura['progetto_tipo']); ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-bostarter-info">
                                                <?php echo htmlspecialchars($candidatura['profilo_nome']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($candidatura['data_candidatura'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                                    $stato = $candidatura['stato'] ?? 'in_valutazione';
                                                    $statoClass = [
                                                        'in_valutazione' => 'bg-warning',
                                                        'accettata' => 'bg-success',
                                                        'rifiutata' => 'bg-danger'
                                                    ];
                                                    $statoText = [
                                                        'in_valutazione' => 'In Valutazione',
                                                        'accettata' => 'Accettata',
                                                        'rifiutata' => 'Rifiutata'
                                                    ];
                                                    ?>
                                            <span class="badge <?php echo $statoClass[$stato]; ?>">
                                                <?php echo $statoText[$stato]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                    onclick="viewCandidatura(<?php echo $candidatura['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <?php if ($isCreator || $isAdmin): ?>
                                                <?php if ($stato === 'in_valutazione'): ?>
                                                <button type="button" class="btn btn-outline-success"
                                                    onclick="updateCandidaturaStatus(<?php echo $candidatura['id']; ?>, 'accettata')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="updateCandidaturaStatus(<?php echo $candidatura['id']; ?>, 'rifiutata')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (!$isCreator || $candidatura['utente_id'] == $userId): ?>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="deleteCandidatura(<?php echo $candidatura['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dettaglio Candidatura -->
    <div class="modal fade" id="candidaturaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dettaglio Candidatura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="candidaturaModalBody">
                    <!-- Contenuto caricato dinamicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>

    <script>
    // Gestione form candidatura
    document.getElementById('candidaturaForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            profilo_id: formData.get('profilo_id'),
            motivazione: formData.get('motivazione')
        };

        fetch('/BOSTARTER/backend/api/candidature.php', {
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
                    showMessage('success', data.message || 'Candidatura inviata con successo!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'invio candidatura');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    });

    // Carica profili quando si seleziona un progetto
    document.getElementById('progetto_id')?.addEventListener('change', function() {
        const progettoId = this.value;
        const profiloSelect = document.getElementById('profilo_id');

        if (!progettoId) {
            profiloSelect.disabled = true;
            profiloSelect.innerHTML = '<option value="">Prima seleziona un progetto...</option>';
            return;
        }

        // Carica profili del progetto
        fetch(`/BOSTARTER/backend/api/project.php?id=${progettoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const progetto = data.data;
                    if (progetto.profili_richiesti) {
                        profiloSelect.innerHTML = '<option value="">Seleziona profilo...</option>';
                        progetto.profili_richiesti.forEach(profilo => {
                            const option = document.createElement('option');
                            option.value = profilo.id;
                            option.textContent = profilo.nome;
                            profiloSelect.appendChild(option);
                        });
                        profiloSelect.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // Visualizza dettaglio candidatura
    function viewCandidatura(candidaturaId) {
        fetch(`/BOSTARTER/backend/api/candidature.php?id=${candidaturaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const candidatura = data.data;
                    document.getElementById('candidaturaModalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informazioni Utente</h6>
                                <p><strong>Nickname:</strong> ${candidatura.nickname}</p>
                                <p><strong>Nome:</strong> ${candidatura.nome} ${candidatura.cognome}</p>
                                <p><strong>Email:</strong> ${candidatura.email || 'Non disponibile'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Informazioni Candidatura</h6>
                                <p><strong>Progetto:</strong> ${candidatura.progetto_nome}</p>
                                <p><strong>Profilo:</strong> ${candidatura.profilo_nome}</p>
                                <p><strong>Data:</strong> ${new Date(candidatura.data_candidatura).toLocaleDateString('it-IT')}</p>
                                <p><strong>Stato:</strong> 
                                    <span class="badge bg-${getStatusColor(candidatura.stato)}">
                                        ${getStatusText(candidatura.stato)}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6>Motivazione</h6>
                            <div class="border rounded p-3 bg-light">
                                ${candidatura.motivazione}
                            </div>
                        </div>
                    `;

                    new bootstrap.Modal(document.getElementById('candidaturaModal')).show();
                } else {
                    showMessage('error', data.error || 'Errore nel caricamento candidatura');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    }

    // Aggiorna stato candidatura
    function updateCandidaturaStatus(candidaturaId, stato) {
        if (!confirm(`Sei sicuro di voler ${stato === 'accettata' ? 'accettare' : 'rifiutare'} questa candidatura?`)) {
            return;
        }

        fetch('/BOSTARTER/backend/api/candidature.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    candidatura_id: candidaturaId,
                    stato: stato
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Stato candidatura aggiornato');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'aggiornamento');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    }

    // Cancella candidatura
    function deleteCandidatura(candidaturaId) {
        if (!confirm('Sei sicuro di voler cancellare questa candidatura?')) {
            return;
        }

        fetch(`/BOSTARTER/backend/api/candidature.php?id=${candidaturaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Candidatura cancellata');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella cancellazione');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    }

    // Utility functions
    function getStatusColor(stato) {
        const colors = {
            'in_valutazione' => 'warning',
            'accettata' => 'success',
            'rifiutata' => 'danger'
        };
        return colors[stato] || 'secondary';
    }

    function getStatusText(stato) {
        const texts = {
            'in_valutazione' => 'In Valutazione',
            'accettata' => 'Accettata',
            'rifiutata' => 'Rifiutata'
        };
        return texts[stato] || 'Sconosciuto';
    }

    function getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function showMessage(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show`;
        alert.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

        document.querySelector('.container').insertBefore(alert, document.querySelector('.row'));

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    </script>
</body>

</html>