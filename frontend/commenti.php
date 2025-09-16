<?php
session_start();

// Funzioni di utilità
function isAuthenticated() {
    return isset($_SESSION["user_id"]);
}

function getUserType() {
    return $_SESSION['user_type'] ?? '';
}

// Verifica autenticazione
if (!isAuthenticated()) {
    header('Location: auth/login.php');
    exit();
}

$userType = getUserType();
$userId = $_SESSION['user_id'];
$isCreator = ($userType === 'creatore');
$isAdmin = ($userType === 'amministratore');

// Recupera ID progetto dalla query string
$progettoId = isset($_GET['progetto_id']) ? (int)$_GET['progetto_id'] : null;

if (!$progettoId) {
    header('Location: home.php');
    exit();
}

// Recupera informazioni progetto
$progetto = null;
$error = null;

try {
    $response = file_get_contents("http://localhost/BOSTARTER/backend/api/project.php?id=$progettoId");
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success']) {
        $progetto = $data['data'];
    } else {
        $error = 'Progetto non trovato';
    }
} catch (Exception $e) {
    $error = 'Errore di connessione: ' . $e->getMessage();
}

// Recupera commenti
$commenti = [];
if (!$error) {
    try {
        $response = file_get_contents("http://localhost/BOSTARTER/backend/api/commenti.php?progetto_id=$progettoId");
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            $commenti = $data['data'];
        } else {
            $error = $data['error'] ?? 'Errore nel recupero commenti';
        }
    } catch (Exception $e) {
        $error = 'Errore nel recupero commenti: ' . $e->getMessage();
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
            <!-- Header Progetto -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="fas fa-comments"></i>
                                Commenti: <?php echo htmlspecialchars($progetto['nome']); ?>
                            </h1>
                            <p class="text-muted mb-0">
                                <i class="fas fa-user"></i>
                                Creato da: <?php echo htmlspecialchars($progetto['creatore_nickname'] ?? 'Utente'); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($progetto['data_inserimento'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="view.php?id=<?php echo $progettoId; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Torna al Progetto
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Nuovo Commento -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-plus"></i> Aggiungi Commento</h5>
                </div>
                <div class="card-body">
                    <form id="commentoForm">
                        <div class="mb-3">
                            <label for="testo" class="form-label">Il tuo commento *</label>
                            <textarea class="form-control" id="testo" name="testo" rows="4" 
                                placeholder="Condividi la tua opinione su questo progetto..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-bostarter-primary">
                            <i class="fas fa-paper-plane"></i> Pubblica Commento
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista Commenti -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-comments"></i>
                        Commenti (<?php echo count($commenti); ?>)
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="refreshCommenti()">
                            <i class="fas fa-sync-alt"></i> Aggiorna
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($commenti)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessun commento ancora. Sii il primo a commentare!</p>
                        </div>
                    <?php else: ?>
                        <div id="commentiContainer">
                            <?php foreach ($commenti as $commento): ?>
                                <div class="commento-item mb-4" data-commento-id="<?php echo $commento['id']; ?>">
                                    <div class="d-flex">
                                        <div class="avatar-sm me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($commento['nickname']); ?></strong>
                                                    <small class="text-muted ms-2">
                                                        <?php echo date('d/m/Y H:i', strtotime($commento['data_commento'])); ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($commento['utente_id'] == $userId || $isAdmin): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="editCommento(<?php echo $commento['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                            onclick="deleteCommento(<?php echo $commento['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="commento-testo">
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($commento['testo'])); ?></p>
                                            </div>
                                            
                                            <div class="commento-edit" style="display: none;">
                                                <textarea class="form-control mb-2" rows="3"><?php echo htmlspecialchars($commento['testo']); ?></textarea>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-success btn-sm" onclick="saveCommento(<?php echo $commento['id']; ?>)">
                                                        <i class="fas fa-save"></i> Salva
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEditCommento(<?php echo $commento['id']; ?>)">
                                                        <i class="fas fa-times"></i> Annulla
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Risposta del creatore -->
                                            <?php if (isset($commento['risposta_testo']) && $commento['risposta_testo']): ?>
                                                <div class="risposta-creatore mt-3 p-3 bg-light border-start border-primary border-4">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <strong class="text-primary">
                                                                <i class="fas fa-reply"></i>
                                                                <?php echo htmlspecialchars($commento['risposta_creatore_nickname'] ?? 'Creatore'); ?>
                                                            </strong>
                                                            <small class="text-muted ms-2">
                                                                <?php echo date('d/m/Y H:i', strtotime($commento['data_risposta'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($commento['risposta_testo'])); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Form risposta (solo per creatore/admin) -->
                                            <?php if (($isCreator && $progetto['creatore_id'] == $userId) || $isAdmin): ?>
                                                <?php if (!isset($commento['risposta_testo'])): ?>
                                                    <div class="risposta-form mt-3" style="display: none;">
                                                        <form class="rispostaForm" data-commento-id="<?php echo $commento['id']; ?>">
                                                            <div class="input-group">
                                                                <textarea class="form-control" rows="2" 
                                                                    placeholder="Rispondi a questo commento..." required></textarea>
                                                                <button type="submit" class="btn btn-bostarter-primary">
                                                                    <i class="fas fa-reply"></i> Rispondi
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" 
                                                        onclick="toggleRispostaForm(<?php echo $commento['id']; ?>)">
                                                        <i class="fas fa-reply"></i> Rispondi
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/scripts.php'; ?>
    
    <script>
        // Gestione form commento
        document.getElementById('commentoForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                progetto_id: <?php echo $progettoId; ?>,
                testo: formData.get('testo')
            };
            
            fetch('/BOSTARTER/backend/api/commenti.php', {
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
                    showMessage('success', data.message || 'Commento pubblicato con successo!');
                    this.reset();
                    setTimeout(() => refreshCommenti(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella pubblicazione commento');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        });

        // Gestione form risposta
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('rispostaForm')) {
                e.preventDefault();
                
                const commentoId = e.target.dataset.commentoId;
                const testo = e.target.querySelector('textarea').value;
                
                fetch('/BOSTARTER/backend/api/commenti.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCSRFToken()
                    },
                    body: JSON.stringify({
                        commento_id: commentoId,
                        testo: testo
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message || 'Risposta pubblicata con successo!');
                        setTimeout(() => refreshCommenti(), 1500);
                    } else {
                        showMessage('error', data.error || 'Errore nella pubblicazione risposta');
                    }
                })
                .catch(error => {
                    showMessage('error', 'Errore di connessione');
                    console.error('Error:', error);
                });
            }
        });

        // Modifica commento
        function editCommento(commentoId) {
            const commentoItem = document.querySelector(`[data-commento-id="${commentoId}"]`);
            const commentoTesto = commentoItem.querySelector('.commento-testo');
            const commentoEdit = commentoItem.querySelector('.commento-edit');
            
            commentoTesto.style.display = 'none';
            commentoEdit.style.display = 'block';
        }

        // Salva commento modificato
        function saveCommento(commentoId) {
            const commentoItem = document.querySelector(`[data-commento-id="${commentoId}"]`);
            const textarea = commentoItem.querySelector('.commento-edit textarea');
            const nuovoTesto = textarea.value;
            
            fetch('/BOSTARTER/backend/api/commenti.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    commento_id: commentoId,
                    testo: nuovoTesto
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Commento aggiornato con successo!');
                    setTimeout(() => refreshCommenti(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'aggiornamento commento');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        }

        // Annulla modifica commento
        function cancelEditCommento(commentoId) {
            const commentoItem = document.querySelector(`[data-commento-id="${commentoId}"]`);
            const commentoTesto = commentoItem.querySelector('.commento-testo');
            const commentoEdit = commentoItem.querySelector('.commento-edit');
            
            commentoTesto.style.display = 'block';
            commentoEdit.style.display = 'none';
        }

        // Cancella commento
        function deleteCommento(commentoId) {
            if (!confirm('Sei sicuro di voler cancellare questo commento?')) {
                return;
            }
            
            fetch(`/BOSTARTER/backend/api/commenti.php?id=${commentoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Commento cancellato con successo!');
                    setTimeout(() => refreshCommenti(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella cancellazione commento');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
        }

        // Toggle form risposta
        function toggleRispostaForm(commentoId) {
            const rispostaForm = document.querySelector(`[data-commento-id="${commentoId}"] .risposta-form`);
            rispostaForm.style.display = rispostaForm.style.display === 'none' ? 'block' : 'none';
        }

        // Aggiorna commenti
        function refreshCommenti() {
            window.location.reload();
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
