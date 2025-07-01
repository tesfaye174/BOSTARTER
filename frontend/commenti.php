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
if (!isset($_GET['progetto_id']) || !is_numeric($_GET['progetto_id'])) {
    header("Location: home.php");
    exit();
}

$progetto_id = intval($_GET['progetto_id']);

// Carica informazioni progetto
$stmt = $conn->prepare("
    SELECT p.nome, p.creatore_id, u.nickname as creatore_nickname
    FROM progetti p 
    JOIN utenti u ON p.creatore_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$progetto_id]);
$progetto = $stmt->fetch();

if (!$progetto) {
    header("Location: home.php");
    exit();
}

$is_creatore = ($user_id == $progetto['creatore_id']);

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_comment') {
            $testo = trim($_POST['testo']);
            if (!empty($testo)) {
                $stmt = $conn->prepare("
                    INSERT INTO commenti (utente_id, progetto_id, testo, data_commento) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$user_id, $progetto_id, $testo]);
                $success_message = "Commento aggiunto con successo!";
            } else {
                $error_message = "Il testo del commento non può essere vuoto";
            }
        } elseif ($_POST['action'] === 'add_response' && $is_creatore) {
            $commento_id = intval($_POST['commento_id']);
            $testo_risposta = trim($_POST['testo_risposta']);
            
            if (!empty($testo_risposta)) {
                // Verifica che il commento esista e non abbia già una risposta
                $stmt = $conn->prepare("
                    SELECT c.id FROM commenti c 
                    LEFT JOIN risposte_commenti rc ON c.id = rc.commento_id 
                    WHERE c.id = ? AND c.progetto_id = ? AND rc.id IS NULL
                ");
                $stmt->execute([$commento_id, $progetto_id]);
                
                if ($stmt->fetch()) {
                    $stmt = $conn->prepare("
                        INSERT INTO risposte_commenti (commento_id, testo_risposta, data_risposta) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$commento_id, $testo_risposta]);
                    $success_message = "Risposta aggiunta con successo!";
                } else {
                    $error_message = "Commento non trovato o già risposto";
                }
            } else {
                $error_message = "Il testo della risposta non può essere vuoto";
            }
        }
    }
}

// Carica commenti con risposte
$stmt = $conn->prepare("
    SELECT 
        c.id, c.testo, c.data_commento,
        u.nickname as utente_nickname,
        rc.testo_risposta, rc.data_risposta
    FROM commenti c
    JOIN utenti u ON c.utente_id = u.id
    LEFT JOIN risposte_commenti rc ON c.id = rc.commento_id
    WHERE c.progetto_id = ?
    ORDER BY c.data_commento DESC
");
$stmt->execute([$progetto_id]);
$commenti = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commenti - <?= htmlspecialchars($progetto['nome']) ?> - BOSTARTER</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments"></i> Commenti - <?= htmlspecialchars($progetto['nome']) ?></h3>
                        <small class="text-muted">Progetto di <?= htmlspecialchars($progetto['creatore_nickname']) ?></small>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        
                        <!-- Form nuovo commento -->
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="add_comment">
                            <div class="mb-3">
                                <label for="testo" class="form-label">Scrivi un commento</label>
                                <textarea name="testo" id="testo" class="form-control" rows="3" 
                                          placeholder="Condividi la tua opinione su questo progetto..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Pubblica Commento
                            </button>
                        </form>
                        
                        <!-- Lista commenti -->
                        <h5>Tutti i commenti (<?= count($commenti) ?>):</h5>
                        <?php if (empty($commenti)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Nessun commento ancora. Sii il primo a commentare!
                            </div>
                        <?php else: ?>
                            <?php foreach ($commenti as $commento): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($commento['utente_nickname']) ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($commento['data_commento'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars($commento['testo'])) ?></p>
                                        
                                        <!-- Risposta del creatore (se presente) -->
                                        <?php if ($commento['testo_risposta']): ?>
                                            <div class="alert alert-light mt-3">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-reply"></i> Risposta del creatore
                                                    <small class="text-muted">
                                                        - <?= date('d/m/Y H:i', strtotime($commento['data_risposta'])) ?>
                                                    </small>
                                                </h6>
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($commento['testo_risposta'])) ?></p>
                                            </div>
                                        <?php elseif ($is_creatore): ?>
                                            <!-- Form per rispondere (solo per il creatore) -->
                                            <div class="mt-3">
                                                <button class="btn btn-sm btn-outline-primary" type="button" 
                                                        data-bs-toggle="collapse" data-bs-target="#risposta-<?= $commento['id'] ?>" 
                                                        aria-expanded="false">
                                                    <i class="fas fa-reply"></i> Rispondi
                                                </button>
                                                <div class="collapse mt-2" id="risposta-<?= $commento['id'] ?>">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="add_response">
                                                        <input type="hidden" name="commento_id" value="<?= $commento['id'] ?>">
                                                        <div class="mb-2">
                                                            <textarea name="testo_risposta" class="form-control" rows="2" 
                                                                      placeholder="Scrivi la tua risposta..." required></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-paper-plane"></i> Pubblica Risposta
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-secondary" 
                                                                data-bs-toggle="collapse" data-bs-target="#risposta-<?= $commento['id'] ?>">
                                                            Annulla
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
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
