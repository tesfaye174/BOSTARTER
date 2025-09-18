<?php
/**
 * Gestione finanziamenti BOSTARTER
 * Visualizza e gestisci contributi ai progetti
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

// Recupera ID progetto dalla query string
$progettoId = isset($_GET['progetto_id']) ? (int)$_GET['progetto_id'] : null;

// Recupera informazioni progetto e rewards
$progetto = null;
$rewards = [];
$error = null;

if ($progettoId) {
    try {
        // Recupera progetto
        $response = file_get_contents("http://localhost/BOSTARTER/backend/api/project.php?id=$progettoId");
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            $progetto = $data['data'];
            
            // Recupera rewards
            $response = file_get_contents("http://localhost/BOSTARTER/backend/api/rewards.php?progetto_id=$progettoId");
            $data = json_decode($response, true);
            
            if (isset($data['success']) && $data['success']) {
                $rewards = $data['data'];
            }
        } else {
            $error = 'Progetto non trovato';
        }
    } catch (Exception $e) {
        $error = 'Errore di connessione: ' . $e->getMessage();
    }
}

// Recupera finanziamenti dell'utente
$finanziamenti = [];
try {
    $response = file_get_contents("http://localhost/BOSTARTER/backend/api/finanziamenti.php");
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success']) {
        $finanziamenti = $data['data'];
    }
} catch (Exception $e) {
    // Ignora errori per finanziamenti
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
            <!-- Header Progetto -->
            <?php if ($progetto): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="fas fa-hand-holding-usd"></i>
                                Finanzia: <?php echo htmlspecialchars($progetto['nome']); ?>
                            </h1>
                            <p class="text-muted mb-0">
                                <i class="fas fa-user"></i>
                                Creato da: <?php echo htmlspecialchars($progetto['creatore_nickname'] ?? 'Utente'); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-calendar"></i>
                                Scade: <?php echo date('d/m/Y', strtotime($progetto['data_limite'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="view.php?id=<?php echo $progettoId; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Torna al Progetto
                            </a>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Progresso: €<?php echo number_format($progetto['budget_raccolto'], 2); ?> / €<?php echo number_format($progetto['budget_richiesto'], 2); ?></span>
                            <span><?php echo round(($progetto['budget_raccolto'] / $progetto['budget_richiesto']) * 100, 1); ?>%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-bostarter-success" role="progressbar" 
                                style="width: <?php echo min(100, ($progetto['budget_raccolto'] / $progetto['budget_richiesto']) * 100); ?>%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Nuovo Finanziamento -->
            <?php if ($progetto && $progetto['stato'] === 'aperto'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-plus"></i> Nuovo Finanziamento</h5>
                </div>
                <div class="card-body">
                    <form id="finanziamentoForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="importo" class="form-label">Importo (€) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control" id="importo" name="importo" 
                                            min="1" step="0.01" required>
                                    </div>
                                    <small class="form-text text-muted">
                                        Importo minimo: €1.00
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reward_id" class="form-label">Reward (Opzionale)</label>
                                    <select class="form-select" id="reward_id" name="reward_id">
                                        <option value="">Nessuna reward</option>
                                        <?php foreach ($rewards as $reward): ?>
                                            <option value="<?php echo $reward['id']; ?>" 
                                                data-importo-min="<?php echo $reward['importo_minimo']; ?>"
                                                data-disponibile="<?php echo $reward['quantita_disponibile']; ?>"
                                                data-utilizzata="<?php echo $reward['quantita_utilizzata']; ?>">
                                                <?php echo htmlspecialchars($reward['nome']); ?> 
                                                (€<?php echo $reward['importo_minimo']; ?>)
                                                <?php if ($reward['quantita_disponibile'] !== null): ?>
                                                    - <?php echo $reward['quantita_disponibile'] - $reward['quantita_utilizzata']; ?> disponibili
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="messaggio_supporto" class="form-label">Messaggio di Supporto</label>
                            <textarea class="form-control" id="messaggio_supporto" name="messaggio_supporto" rows="3" 
                                placeholder="Lascia un messaggio di supporto per il creatore del progetto..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-bostarter-primary btn-lg">
                            <i class="fas fa-heart"></i> Finanzia questo Progetto!
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista Finanziamenti -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-list"></i>
                        <?php if ($progetto): ?>
                            Finanziamenti per questo Progetto
                        <?php else: ?>
                            I Miei Finanziamenti
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-bostarter-primary"><?php echo count($finanziamenti); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($finanziamenti)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3"></i>
                            <p class="text-muted">
                                <?php if ($progetto): ?>
                                    Nessun finanziamento ancora per questo progetto. Sii il primo a finanziarlo!
                                <?php else: ?>
                                    Non hai ancora effettuato finanziamenti.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-bostarter">
                                    <tr>
                                        <th>Progetto</th>
                                        <th>Importo</th>
                                        <th>Data</th>
                                        <th>Stato</th>
                                        <th>Reward</th>
                                        <th>Messaggio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($finanziamenti as $finanziamento): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <i class="fas fa-project-diagram"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($finanziamento['progetto_nome']); ?></strong>
                                                        <?php if (isset($finanziamento['progetto_tipo'])): ?>
                                                            <br><small class="badge bg-bostarter-secondary">
                                                                <?php echo ucfirst($finanziamento['progetto_tipo']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    €<?php echo number_format($finanziamento['importo'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($finanziamento['data_finanziamento'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $stato = $finanziamento['stato_pagamento'];
                                                $statoClass = [
                                                    'pending' => 'bg-warning',
                                                    'completed' => 'bg-success',
                                                    'failed' => 'bg-danger',
                                                    'refunded' => 'bg-secondary'
                                                ];
                                                $statoText = [
                                                    'pending' => 'In Attesa',
                                                    'completed' => 'Completato',
                                                    'failed' => 'Fallito',
                                                    'refunded' => 'Rimborsato'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $statoClass[$stato] ?? 'bg-secondary'; ?>">
                                                    <?php echo $statoText[$stato] ?? 'Sconosciuto'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (isset($finanziamento['reward_nome'])): ?>
                                                    <span class="badge bg-bostarter-info">
                                                        <?php echo htmlspecialchars($finanziamento['reward_nome']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($finanziamento['messaggio_supporto']) && $finanziamento['messaggio_supporto']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="showMessage('<?php echo htmlspecialchars($finanziamento['messaggio_supporto']); ?>')">
                                                        <i class="fas fa-comment"></i> Visualizza
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
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

    <!-- Modal Messaggio Supporto -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Messaggio di Supporto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageModalBody">
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
        // Gestione form finanziamento
        document.getElementById('finanziamentoForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                progetto_id: <?php echo $progettoId; ?>,
                importo: parseFloat(formData.get('importo')),
                reward_id: formData.get('reward_id') || null,
                messaggio_supporto: formData.get('messaggio_supporto') || null
            };
            
            // Validazioni
            if (data.importo < 1) {
                showMessage('error', 'L\'importo deve essere almeno €1.00');
                return;
            }
            
            const selectedReward = document.getElementById('reward_id').selectedOptions[0];
            if (selectedReward && selectedReward.value) {
                const importoMin = parseFloat(selectedReward.dataset.importoMin);
                if (data.importo < importoMin) {
                    showMessage('error', `L'importo deve essere almeno €${importoMin.toFixed(2)} per questa reward`);
                    return;
                }
                
                const disponibile = parseInt(selectedReward.dataset.disponibile);
                const utilizzata = parseInt(selectedReward.dataset.utilizzata);
                if (disponibile !== null && (disponibile - utilizzata) <= 0) {
                    showMessage('error', 'Questa reward non è più disponibile');
                    return;
                }
            }
            
            // Conferma finanziamento
            if (!confirm(`Sei sicuro di voler finanziare questo progetto con €${data.importo.toFixed(2)}?`)) {
                return;
            }
            
            fetch('/BOSTARTER/backend/api/finanziamenti.php', {
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
                    showMessage('success', data.message || 'Finanziamento completato con successo!');
                    this.reset();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showMessage('error', data.error || 'Errore nel completamento finanziamento');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        });

        // Aggiorna importo minimo quando si seleziona una reward
        document.getElementById('reward_id')?.addEventListener('change', function() {
            const selectedOption = this.selectedOptions[0];
            const importoInput = document.getElementById('importo');
            
            if (selectedOption && selectedOption.value) {
                const importoMin = parseFloat(selectedOption.dataset.importoMin);
                importoInput.min = importoMin;
                importoInput.placeholder = `Minimo €${importoMin.toFixed(2)}`;
                
                // Aggiorna importo se è troppo basso
                if (parseFloat(importoInput.value) < importoMin) {
                    importoInput.value = importoMin;
                }
            } else {
                importoInput.min = 1;
                importoInput.placeholder = 'Minimo €1.00';
            }
        });

        // Mostra messaggio supporto
        function showMessage(messageText) {
            document.getElementById('messageModalBody').textContent = messageText;
            new bootstrap.Modal(document.getElementById('messageModal')).show();
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