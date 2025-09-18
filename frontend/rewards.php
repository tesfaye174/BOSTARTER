<?php
/**
 * Sistema ricompense BOSTARTER
 * Gestione rewards e benefici progetti
 */

// Include funzioni comuni
require_once 'includes/functions.php';

session_start();

// Controllo autenticazione
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Dati utente dalla sessione
$userType = $_SESSION['tipo_utente'] ?? '';
$userId = $_SESSION['user_id'];
$isCreator = ($userType === 'creatore');
$isAdmin = ($userType === 'amministratore');

if (!$isCreator && !$isAdmin) {
    header('Location: home.php');
    exit();
}

// Recupera ID progetto dalla query string
$progettoId = isset($_GET['progetto_id']) ? (int)$_GET['progetto_id'] : null;

// Recupera informazioni progetto
$progetto = null;
$rewards = [];
$error = null;

if ($progettoId) {
    try {
        // Verifica che l'utente sia creatore/admin del progetto
        $response = file_get_contents("http://localhost/BOSTARTER/backend/api/project.php?id=$progettoId");
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            $progetto = $data['data'];
            
            // Verifica permessi
            if ($progetto['creatore_id'] != $userId && !$isAdmin) {
                $error = 'Accesso negato: non sei il creatore di questo progetto';
            } else {
                // Recupera rewards
                $response = file_get_contents("http://localhost/BOSTARTER/backend/api/rewards.php?progetto_id=$progettoId");
                $data = json_decode($response, true);
                
                if (isset($data['success']) && $data['success']) {
                    $rewards = $data['data'];
                }
            }
        } else {
            $error = 'Progetto non trovato';
        }
    } catch (Exception $e) {
        $error = 'Errore di connessione: ' . $e->getMessage();
    }
}

// Recupera progetti dell'utente (se creatore)
$mieiProgetti = [];
if ($isCreator && !$progettoId) {
    try {
        $response = file_get_contents("http://localhost/BOSTARTER/backend/api/project.php?creatore_id=$userId");
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            $mieiProgetti = $data['data']['projects'] ?? $data['data'];
        }
    } catch (Exception $e) {
        // Ignora errori per progetti
    }
}

include 'includes/head.php';
?>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
                <a href="home.php" class="btn btn-sm btn-outline-danger ms-3">Torna alla Home</a>
            </div>
        <?php else: ?>
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="fas fa-gift"></i>
                                <?php if ($progetto): ?>
                                    Rewards per: <?php echo htmlspecialchars($progetto['nome']); ?>
                                <?php else: ?>
                                    Gestione Rewards
                                <?php endif; ?>
                            </h1>
                            <p class="text-muted mb-0">
                                <?php if ($progetto): ?>
                                    <i class="fas fa-user"></i>
                                    Creato da: <?php echo htmlspecialchars($progetto['creatore_nickname'] ?? 'Utente'); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-calendar"></i>
                                    Scade: <?php echo date('d/m/Y', strtotime($progetto['data_limite'])); ?>
                                <?php else: ?>
                                    <i class="fas fa-info-circle"></i>
                                    Seleziona un progetto per gestire le sue rewards
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($progetto): ?>
                                <a href="view.php?id=<?php echo $progettoId; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left"></i> Torna al Progetto
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selezione Progetto (se non specificato) -->
            <?php if (!$progettoId && $isCreator): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> I Miei Progetti</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($mieiProgetti)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Non hai ancora creato progetti.</p>
                            <a href="new.php" class="btn btn-bostarter-primary">
                                <i class="fas fa-plus"></i> Crea il Primo Progetto
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($mieiProgetti as $progetto): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($progetto['nome']); ?></h6>
                                            <p class="card-text text-muted small">
                                                <?php echo substr(htmlspecialchars($progetto['descrizione']), 0, 100); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php echo $progetto['stato'] === 'aperto' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($progetto['stato']); ?>
                                                </span>
                                                <a href="?progetto_id=<?php echo $progetto['id']; ?>" class="btn btn-sm btn-bostarter-primary">
                                                    <i class="fas fa-gift"></i> Gestisci Rewards
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gestione Rewards -->
            <?php if ($progetto): ?>
            <!-- Form Nuova Reward -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-plus"></i> Nuova Reward</h5>
                </div>
                <div class="card-body">
                    <form id="rewardForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codice" class="form-label">Codice Univoco *</label>
                                    <input type="text" class="form-control" id="codice" name="codice" 
                                        placeholder="es. REWARD001" required>
                                    <small class="form-text text-muted">
                                        Codice univoco per identificare la reward
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Reward *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                        placeholder="es. Versione Beta" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descrizione" class="form-label">Descrizione *</label>
                            <textarea class="form-control" id="descrizione" name="descrizione" rows="3" 
                                placeholder="Descrivi cosa riceverà il finanziatore..." required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="importo_minimo" class="form-label">Importo Minimo (€) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control" id="importo_minimo" name="importo_minimo" 
                                            min="1" step="0.01" required>
                                    </div>
                                    <small class="form-text text-muted">
                                        Importo minimo per ricevere questa reward
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantita" class="form-label">Quantità Disponibile</label>
                                    <input type="number" class="form-control" id="quantita" name="quantita" 
                                        min="1" placeholder="Illimitata se vuoto">
                                    <small class="form-text text-muted">
                                        Lascia vuoto per reward illimitate
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-bostarter-primary">
                            <i class="fas fa-plus"></i> Crea Reward
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista Rewards -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-list"></i>
                        Rewards del Progetto
                    </h5>
                    <span class="badge bg-bostarter-primary"><?php echo count($rewards); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($rewards)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessuna reward creata per questo progetto.</p>
                            <p class="text-muted">Crea la tua prima reward per incentivare i finanziamenti!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-bostarter">
                                    <tr>
                                        <th>Codice</th>
                                        <th>Nome</th>
                                        <th>Descrizione</th>
                                        <th>Importo Min.</th>
                                        <th>Disponibilità</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rewards as $reward): ?>
                                        <tr>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded">
                                                    <?php echo htmlspecialchars($reward['codice']); ?>
                                                </code>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reward['nome']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="text-muted">
                                                    <?php echo htmlspecialchars(substr($reward['descrizione'], 0, 50)); ?>
                                                    <?php if (strlen($reward['descrizione']) > 50): ?>...<?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    €<?php echo number_format($reward['importo_minimo'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if ($reward['quantita_disponibile'] !== null): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo $reward['quantita_disponibile'] - $reward['quantita_utilizzata']; ?> disponibili
                                                    </span>
                                                    <br><small class="text-muted">
                                                        <?php echo $reward['quantita_utilizzata']; ?> utilizzate
                                                    </small>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Illimitata</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $reward['stato'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $reward['stato'] ? 'Attiva' : 'Disattivata'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editReward(<?php echo $reward['id']; ?>, '<?php echo htmlspecialchars($reward['nome']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteReward(<?php echo $reward['id']; ?>, '<?php echo htmlspecialchars($reward['nome']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
        <?php endif; ?>
    </div>

    <!-- Modal Modifica Reward -->
    <div class="modal fade" id="editRewardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Reward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editRewardForm">
                        <input type="hidden" id="edit_reward_id" name="id">
                        <div class="mb-3">
                            <label for="edit_campo" class="form-label">Campo da Modificare</label>
                            <select class="form-select" id="edit_campo" name="campo" required>
                                <option value="">Seleziona campo...</option>
                                <option value="nome">Nome</option>
                                <option value="descrizione">Descrizione</option>
                                <option value="importo_minimo">Importo Minimo</option>
                                <option value="quantita_disponibile">Quantità Disponibile</option>
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
                    <button type="button" class="btn btn-bostarter-primary" onclick="saveRewardEdit()">Salva</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
    
    <script>
        // Gestione form reward
        document.getElementById('rewardForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                progetto_id: <?php echo $progettoId ?? 'null'; ?>,
                codice: formData.get('codice'),
                nome: formData.get('nome'),
                descrizione: formData.get('descrizione'),
                importo_minimo: parseFloat(formData.get('importo_minimo')),
                quantita: formData.get('quantita') || null
            };
            
            // Validazioni
            if (data.importo_minimo < 1) {
                showMessage('error', 'L\'importo minimo deve essere almeno €1.00');
                return;
            }
            
            if (data.quantita && parseInt(data.quantita) < 1) {
                showMessage('error', 'La quantità deve essere almeno 1');
                return;
            }
            
            fetch('/BOSTARTER/backend/api/rewards.php', {
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
                    showMessage('success', data.message || 'Reward creata con successo!');
                    this.reset();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella creazione reward');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        });

        // Modifica reward
        function editReward(rewardId, rewardName) {
            document.getElementById('edit_reward_id').value = rewardId;
            document.getElementById('edit_campo').value = '';
            document.getElementById('edit_valore').value = '';
            
            new bootstrap.Modal(document.getElementById('editRewardModal')).show();
        }

        // Salva modifica reward
        function saveRewardEdit() {
            const rewardId = document.getElementById('edit_reward_id').value;
            const campo = document.getElementById('edit_campo').value;
            const valore = document.getElementById('edit_valore').value;
            
            if (!campo || !valore) {
                showMessage('error', 'Compila tutti i campi');
                return;
            }
            
            // Validazioni specifiche
            if (campo === 'importo_minimo' && parseFloat(valore) < 1) {
                showMessage('error', 'L\'importo minimo deve essere almeno €1.00');
                return;
            }
            
            if (campo === 'quantita_disponibile' && parseInt(valore) < 0) {
                showMessage('error', 'La quantità non può essere negativa');
                return;
            }
            
            fetch('/BOSTARTER/backend/api/rewards.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    id: rewardId,
                    campo: campo,
                    valore: valore
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Reward aggiornata con successo!');
                    bootstrap.Modal.getInstance(document.getElementById('editRewardModal')).hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'aggiornamento reward');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        }

        // Cancella reward
        function deleteReward(rewardId, rewardName) {
            if (!confirm(`Sei sicuro di voler cancellare la reward "${rewardName}"?`)) {
                return;
            }
            
            fetch(`/BOSTARTER/backend/api/rewards.php?id=${rewardId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Reward cancellata con successo!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella cancellazione reward');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
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