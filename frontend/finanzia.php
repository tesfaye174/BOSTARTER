<?php
session_start();
require_once __DIR__ . "/../backend/config/database.php";

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Verifica che sia stato passato un ID progetto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$progetto_id = intval($_GET['id']);

// Carica dati del progetto
$stmt = $conn->prepare("
    SELECT p.*, u.nickname as creatore_nickname
    FROM progetti p 
    JOIN utenti u ON p.creatore_id = u.id 
    WHERE p.id = ? AND p.stato = 'aperto'
");
$stmt->execute([$progetto_id]);
$progetto = $stmt->fetch();

if (!$progetto) {
    header("Location: home.php");
    exit();
}

// Carica rewards disponibili
$stmt = $conn->prepare("SELECT * FROM rewards WHERE progetto_id = ? ORDER BY id");
$stmt->execute([$progetto_id]);
$rewards = $stmt->fetchAll();

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importo = floatval($_POST['importo']);
    $reward_id = intval($_POST['reward_id']);
    
    if ($importo > 0 && $reward_id > 0) {
        try {
            $conn->beginTransaction();
            
            // Inserisci finanziamento
            $stmt = $conn->prepare("
                INSERT INTO finanziamenti (utente_id, progetto_id, importo, data_finanziamento, reward_id) 
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$user_id, $progetto_id, $importo, $reward_id]);
            
            // Aggiorna budget raccolto del progetto
            $stmt = $conn->prepare("
                UPDATE progetti 
                SET budget_raccolto = budget_raccolto + ? 
                WHERE id = ?
            ");
            $stmt->execute([$importo, $progetto_id]);
            
            // Verifica se il progetto ha raggiunto il budget
            $stmt = $conn->prepare("SELECT budget_raccolto, budget_richiesto FROM progetti WHERE id = ?");
            $stmt->execute([$progetto_id]);
            $budget_info = $stmt->fetch();
            
            if ($budget_info['budget_raccolto'] >= $budget_info['budget_richiesto']) {
                // Chiudi il progetto
                $stmt = $conn->prepare("UPDATE progetti SET stato = 'chiuso' WHERE id = ?");
                $stmt->execute([$progetto_id]);
            }
            
            $conn->commit();
            $success_message = "Finanziamento effettuato con successo! Grazie per il tuo supporto!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Errore durante il finanziamento: " . $e->getMessage();
        }
    } else {
        $error_message = "Importo e reward devono essere selezionati correttamente";
    }
}

// Calcola statistiche progetto
$percentuale_completamento = ($progetto['budget_raccolto'] / $progetto['budget_richiesto']) * 100;
$giorni_rimanenti = ceil((strtotime($progetto['data_limite']) - time()) / (60 * 60 * 24));
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzia Progetto - BOSTARTER</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-heart"></i> Finanzia il Progetto</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        
                        <!-- Informazioni progetto -->
                        <div class="mb-4">
                            <h4><?= htmlspecialchars($progetto['nome']) ?></h4>
                            <p class="text-muted">di <strong><?= htmlspecialchars($progetto['creatore_nickname']) ?></strong></p>
                            <p><?= htmlspecialchars($progetto['descrizione']) ?></p>
                            
                            <!-- Progress bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span><strong>€<?= number_format($progetto['budget_raccolto'], 2) ?></strong> raccolti</span>
                                    <span>Obiettivo: <strong>€<?= number_format($progetto['budget_richiesto'], 2) ?></strong></span>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= min(100, $percentuale_completamento) ?>%"
                                         aria-valuenow="<?= $percentuale_completamento ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?= round($percentuale_completamento, 1) ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= $giorni_rimanenti > 0 ? "$giorni_rimanenti giorni rimanenti" : "Scaduto" ?>
                                </small>
                            </div>
                        </div>
                        
                        <?php if ($giorni_rimanenti > 0): ?>
                            <!-- Form finanziamento -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="importo" class="form-label">Importo del finanziamento (€)</label>
                                    <input type="number" name="importo" id="importo" class="form-control" 
                                           min="1" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reward_id" class="form-label">Scegli la tua ricompensa</label>
                                    <select name="reward_id" id="reward_id" class="form-select" required>
                                        <option value="">Seleziona una ricompensa...</option>
                                        <?php foreach ($rewards as $reward): ?>
                                            <option value="<?= $reward['id'] ?>">
                                                <?= htmlspecialchars($reward['codice']) ?> - <?= htmlspecialchars($reward['descrizione']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-heart"></i> Finanzia Ora
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Questo progetto è scaduto e non può più ricevere finanziamenti.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rewards disponibili -->
                        <div class="mt-4">
                            <h5>Ricompense disponibili:</h5>
                            <?php if (empty($rewards)): ?>
                                <div class="alert alert-info">
                                    Nessuna ricompensa disponibile per questo progetto.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($rewards as $reward): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?= htmlspecialchars($reward['codice']) ?></h6>
                                                    <p class="card-text"><?= htmlspecialchars($reward['descrizione']) ?></p>
                                                    <?php if ($reward['foto']): ?>
                                                        <img src="<?= htmlspecialchars($reward['foto']) ?>" 
                                                             class="img-fluid rounded" alt="Reward">
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4">
                            <a href="view.php?id=<?= $progetto_id ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Torna al Progetto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
