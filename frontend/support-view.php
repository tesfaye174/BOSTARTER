<?php
/**
 * BOSTARTER - Supporta progetto
 * Pagina per supportare un progetto con finanziamento
 */

session_start();
require_once __DIR__ . "/../backend/config/database.php";
require_once __DIR__ . "/../backend/utils/Security.php";

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$security = Security::getInstance();
$security->setSecurityHeaders();

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    header('Location: home.php?error=project_not_found');
    exit;
}

try {
    $db = Database::getInstance();
    
    // Ottieni dati progetto con statistiche
    $stmt = $db->prepare("
        SELECT p.*, u.nome as creatore_nome, u.cognome as creatore_cognome,
               ps.totale_raccolto, ps.numero_sostenitori, ps.stato_finanziamento,
               ps.percentuale_completamento
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        JOIN progetti_statistiche ps ON p.id = ps.id
        WHERE p.id = ?
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header('Location: home.php?error=project_not_found');
        exit;
    }
    
    // Verifica che il progetto sia ancora aperto
    if ($project['stato'] !== 'aperto') {
        header('Location: view.php?id=' . $project_id . '&error=project_closed');
        exit;
    }
    
    // Verifica che non sia il proprio progetto
    if ($project['creatore_id'] == $_SESSION['user_id']) {
        header('Location: view.php?id=' . $project_id . '&error=cannot_support_own');
        exit;
    }
    
    // Ottieni rewards disponibili
    $stmt = $db->prepare("SELECT * FROM rewards WHERE progetto_id = ? ORDER BY importo_minimo ASC");
    $stmt->execute([$project_id]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gestione form di supporto
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Token di sicurezza non valido';
        } else {
            $importo = floatval($_POST['importo'] ?? 0);
            $reward_id = intval($_POST['reward_id'] ?? 0);
            $note = $security->sanitizeInput($_POST['note'] ?? '');
            
            // Validazione
            $errors = [];
            
            if ($importo <= 0) {
                $errors[] = 'L\'importo deve essere maggiore di zero';
            }
            
            if ($reward_id <= 0) {
                $errors[] = 'Devi selezionare una reward';
            }
            
            // Verifica che la reward esista per questo progetto
            $stmt = $db->prepare("SELECT * FROM rewards WHERE id = ? AND progetto_id = ?");
            $stmt->execute([$reward_id, $project_id]);
            $selectedReward = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$selectedReward) {
                $errors[] = 'Reward non valida';
            } elseif ($importo < $selectedReward['importo_minimo']) {
                $errors[] = 'L\'importo minimo per questa reward è €' . number_format($selectedReward['importo_minimo'], 2);
            }
            
            // Rate limiting per finanziamenti
            $rateLimitCheck = $security->checkRateLimit('support_' . $_SESSION['user_id'], 3, 600);
            if (!$rateLimitCheck['allowed']) {
                $errors[] = $rateLimitCheck['message'];
            }
            
            if (empty($errors)) {
                try {
                    $db->beginTransaction();
                    
                    // Inserisci finanziamento
                    $stmt = $db->prepare("
                        INSERT INTO finanziamenti (utente_id, progetto_id, reward_id, importo, note)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $_SESSION['user_id'], $project_id, $reward_id, $importo, $note
                    ]);
                    
                    $finanziamento_id = $db->lastInsertId();
                    
                    // Log evento
                    $security->logSecurityEvent('project_supported', [
                        'project_id' => $project_id,
                        'amount' => $importo,
                        'reward_id' => $reward_id
                    ]);
                    
                    $db->commit();
                    
                    // Redirect con messaggio di successo
                    header('Location: view.php?id=' . $project_id . '&success=support_added');
                    exit;
                    
                } catch (PDOException $e) {
                    $db->rollBack();
                    $error = 'Errore durante il supporto del progetto';
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
    
} catch (Exception $e) {
    $error = 'Errore durante il caricamento del progetto';
}

$csrf_token = $security->generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supporta <?= htmlspecialchars($project['nome']) ?> - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-heart me-2 text-danger"></i>
                            Supporta "<?= htmlspecialchars($project['nome']) ?>"
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="supportForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-4">
                                <label for="importo" class="form-label">Importo del supporto (€)</label>
                                <input type="number" class="form-control form-control-lg" 
                                       id="importo" name="importo" step="0.01" min="1" 
                                       placeholder="Es. 25.00" required>
                                <div class="form-text">
                                    L'importo minimo dipende dalla reward selezionata
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Seleziona una reward</label>
                                <div class="row">
                                    <?php if (empty($rewards)): ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Nessuna reward disponibile per questo progetto
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($rewards as $reward): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card reward-card" style="cursor: pointer;" 
                                                     onclick="selectReward(<?= $reward['id'] ?>, <?= $reward['importo_minimo'] ?>)">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="reward_id" value="<?= $reward['id'] ?>" 
                                                                   id="reward_<?= $reward['id'] ?>" required>
                                                            <label class="form-check-label" for="reward_<?= $reward['id'] ?>">
                                                                <strong><?= htmlspecialchars($reward['codice']) ?></strong>
                                                            </label>
                                                        </div>
                                                        <p class="card-text mt-2">
                                                            <?= htmlspecialchars($reward['descrizione']) ?>
                                                        </p>
                                                        <div class="text-muted">
                                                            <i class="fas fa-euro-sign me-1"></i>
                                                            Importo minimo: €<?= number_format($reward['importo_minimo'], 2) ?>
                                                        </div>
                                                        <?php if ($reward['foto']): ?>
                                                            <img src="<?= htmlspecialchars($reward['foto']) ?>" 
                                                                 class="img-fluid mt-2 rounded" style="max-height: 100px;">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="note" class="form-label">Note aggiuntive (opzionale)</label>
                                <textarea class="form-control" id="note" name="note" rows="3" 
                                          placeholder="Messaggio per il creatore del progetto..."></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Importante:</strong> I finanziamenti sono definitivi e non rimborsabili. 
                                Riceverai la reward selezionata solo se il progetto raggiunge il suo obiettivo.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view.php?id=<?= $project_id ?>" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Torna al progetto
                                </a>
                                <button type="submit" class="btn btn-success btn-lg" 
                                        <?= empty($rewards) ? 'disabled' : '' ?>>
                                    <i class="fas fa-heart me-2"></i>Supporta il progetto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Riepilogo progetto -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Riepilogo Progetto</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($project['nome']) ?></h6>
                        <p class="card-text text-muted">
                            di <?= htmlspecialchars($project['creatore_nome'] . ' ' . $project['creatore_cognome']) ?>
                        </p>
                        
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?= min($project['percentuale_completamento'], 100) ?>%">
                                <?= number_format($project['percentuale_completamento'], 1) ?>%
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0">€<?= number_format($project['totale_raccolto'], 0) ?></h4>
                                    <small class="text-muted">Raccolti</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success mb-0"><?= $project['numero_sostenitori'] ?></h4>
                                <small class="text-muted">Sostenitori</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <h5 class="text-primary">€<?= number_format($project['budget_richiesto'], 0) ?></h5>
                            <small class="text-muted">Obiettivo</small>
                        </div>
                        
                        <div class="text-center mt-3">
                            <?php
                            $giorni_rimasti = max(0, (strtotime($project['data_limite']) - time()) / (60 * 60 * 24));
                            ?>
                            <h5 class="<?= $giorni_rimasti <= 7 ? 'text-danger' : 'text-info' ?>">
                                <?= floor($giorni_rimasti) ?>
                            </h5>
                            <small class="text-muted">Giorni rimasti</small>
                        </div>
                    </div>
                </div>
                
                <!-- Supporti recenti -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="fas fa-users me-2"></i>Supporti Recenti</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $db->prepare("
                            SELECT f.importo, f.data_finanziamento, u.nickname, r.codice as reward_codice
                            FROM finanziamenti f
                            JOIN utenti u ON f.utente_id = u.id
                            JOIN rewards r ON f.reward_id = r.id
                            WHERE f.progetto_id = ?
                            ORDER BY f.data_finanziamento DESC
                            LIMIT 5
                        ");
                        $stmt->execute([$project_id]);
                        $recent_supports = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($recent_supports)): ?>
                            <p class="text-muted small">Nessun supporto ancora</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_supports as $support): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?= htmlspecialchars($support['nickname']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($support['reward_codice']) ?></small>
                                            </div>
                                            <div class="text-end">
                                                <strong>€<?= number_format($support['importo'], 0) ?></strong>
                                                <br><small class="text-muted">
                                                    <?= date('d/m', strtotime($support['data_finanziamento'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectReward(rewardId, minAmount) {
            // Seleziona il radio button
            document.getElementById('reward_' + rewardId).checked = true;
            
            // Aggiorna l'importo minimo
            const importoField = document.getElementById('importo');
            importoField.min = minAmount;
            
            if (parseFloat(importoField.value) < minAmount) {
                importoField.value = minAmount.toFixed(2);
            }
            
            // Evidenzia la carta selezionata
            document.querySelectorAll('.reward-card').forEach(card => {
                card.classList.remove('border-primary', 'bg-light');
            });
            
            event.currentTarget.classList.add('border-primary', 'bg-light');
        }
        
        // Validazione form
        document.getElementById('supportForm').addEventListener('submit', function(e) {
            const importo = parseFloat(document.getElementById('importo').value);
            const selectedReward = document.querySelector('input[name="reward_id"]:checked');
            
            if (!selectedReward) {
                e.preventDefault();
                alert('Seleziona una reward');
                return;
            }
            
            const minAmount = parseFloat(document.getElementById('importo').min);
            if (importo < minAmount) {
                e.preventDefault();
                alert('L\'importo minimo per questa reward è €' + minAmount.toFixed(2));
                return;
            }
            
            if (!confirm('Sei sicuro di voler supportare questo progetto con €' + importo.toFixed(2) + '?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
