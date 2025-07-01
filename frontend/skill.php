<?php
session_start();
require_once __DIR__ . "/../backend/config/database.php";
require_once __DIR__ . "/../backend/services/MongoLogger.php";

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$logger = \BOSTARTER\Utils\MongoLoggerSingleton::getInstance();

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_skill') {
            $competenza_id = $_POST['competenza_id'];
            $livello = intval($_POST['livello']);
            
            // Verifica che il livello sia valido (0-5)
            if ($livello >= 0 && $livello <= 5) {
                // Ottieni nome competenza per il log
                $stmt = $conn->prepare("SELECT nome FROM competenze WHERE id = ?");
                $stmt->execute([$competenza_id]);
                $competenza_nome = $stmt->fetchColumn();
                
                // Inserisci o aggiorna skill
                $stmt = $conn->prepare("
                    INSERT INTO skill_utenti (utente_id, competenza_id, livello) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE livello = ?
                ");
                $stmt->execute([$user_id, $competenza_id, $livello, $livello]);
                
                // Log evento
                $logger->logSkillUpdate($user_id, $competenza_nome, $livello);
                
                $success_message = "Skill aggiunta/aggiornata con successo!";
            } else {
                $error_message = "Il livello deve essere compreso tra 0 e 5";
            }
        } elseif ($_POST['action'] === 'remove_skill') {
            $competenza_id = $_POST['competenza_id'];
            
            // Ottieni nome competenza per il log
            $stmt = $conn->prepare("SELECT nome FROM competenze WHERE id = ?");
            $stmt->execute([$competenza_id]);
            $competenza_nome = $stmt->fetchColumn();
            
            $stmt = $conn->prepare("DELETE FROM skill_utenti WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $competenza_id]);
            
            // Log evento
            $logger->logSkillUpdate($user_id, $competenza_nome . " (rimossa)", 0);
            
            $success_message = "Skill rimossa con successo!";
        }
    }
}

// Carica competenze disponibili
$stmt = $conn->query("SELECT id, nome FROM competenze ORDER BY nome");
$competenze = $stmt->fetchAll();

// Carica skill attuali dell'utente
$stmt = $conn->prepare("
    SELECT su.competenza_id, su.livello, c.nome 
    FROM skill_utenti su 
    JOIN competenze c ON su.competenza_id = c.id 
    WHERE su.utente_id = ? 
    ORDER BY c.nome
");
$stmt->execute([$user_id]);
$skill_utenti = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mie Skill - BOSTARTER</title>
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
                        <h3><i class="fas fa-skills"></i> Le Mie Skill di Curriculum</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        
                        <!-- Form aggiunta skill -->
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="add_skill">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="competenza_id" class="form-label">Competenza</label>
                                    <select name="competenza_id" id="competenza_id" class="form-select" required>
                                        <option value="">Seleziona competenza...</option>
                                        <?php foreach ($competenze as $comp): ?>
                                            <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="livello" class="form-label">Livello (0-5)</label>
                                    <select name="livello" id="livello" class="form-select" required>
                                        <option value="">Livello...</option>
                                        <option value="0">0 - Nessuna esperienza</option>
                                        <option value="1">1 - Principiante</option>
                                        <option value="2">2 - Base</option>
                                        <option value="3">3 - Intermedio</option>
                                        <option value="4">4 - Avanzato</option>
                                        <option value="5">5 - Esperto</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary form-control">
                                        <i class="fas fa-plus"></i> Aggiungi
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Lista skill attuali -->
                        <h5>Le Tue Skill Attuali:</h5>
                        <?php if (empty($skill_utenti)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Non hai ancora inserito nessuna skill. 
                                Aggiungi le tue competenze per candidarti ai progetti!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Competenza</th>
                                            <th>Livello</th>
                                            <th>Descrizione</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($skill_utenti as $skill): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($skill['nome']) ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?= $skill['livello'] >= 4 ? 'success' : ($skill['livello'] >= 2 ? 'warning' : 'secondary') ?>">
                                                        <?= $skill['livello'] ?>/5
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $descrizioni = [
                                                        0 => 'Nessuna esperienza',
                                                        1 => 'Principiante',
                                                        2 => 'Base',
                                                        3 => 'Intermedio',
                                                        4 => 'Avanzato',
                                                        5 => 'Esperto'
                                                    ];
                                                    echo $descrizioni[$skill['livello']] ?? 'N/D';
                                                    ?>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="remove_skill">
                                                        <input type="hidden" name="competenza_id" value="<?= $skill['competenza_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Sei sicuro di voler rimuovere questa skill?')">
                                                            <i class="fas fa-trash"></i> Rimuovi
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="home.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Torna alla Dashboard
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
