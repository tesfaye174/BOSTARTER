<?php
session_start();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/app_config.php';

// Funzioni di utilità
function isAdmin() {
    return ($_SESSION['user_type'] ?? '') === 'amministratore';
}

function isAuthenticated() {
    return isset($_SESSION["user_id"]);
}

// Verifica autenticazione e ruolo admin
if (!isAuthenticated() || !isAdmin()) {
    header('Location: /BOSTARTER/frontend/auth/login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_competenza') {
            $nome = trim($_POST['nome']);
            if (!empty($nome)) {
                $stmt = $conn->prepare("SELECT id FROM competenze WHERE nome = ?");
                $stmt->execute([$nome]);
                
                if (!$stmt->fetch()) {
                    $stmt = $conn->prepare("INSERT INTO competenze (nome) VALUES (?)");
                    $stmt->execute([$nome]);
                    $success_message = "Competenza aggiunta con successo!";
                } else {
                    $error_message = "Competenza già esistente";
                }
            } else {
                $error_message = "Il nome della competenza non può essere vuoto";
            }
        } elseif ($_POST['action'] === 'delete_competenza') {
            $competenza_id = intval($_POST['competenza_id']);
            
            // Verifica che non sia utilizzata
            $stmt = $conn->prepare("SELECT COUNT(*) as uso FROM utenti_competenze WHERE competenza_id = ?");
            $stmt->execute([$competenza_id]);
            $uso = $stmt->fetch()['uso'];
            
            if ($uso == 0) {
                $stmt = $conn->prepare("DELETE FROM competenze WHERE id = ?");
                $stmt->execute([$competenza_id]);
                $success_message = "Competenza eliminata con successo!";
            } else {
                $error_message = "Impossibile eliminare: competenza utilizzata da $uso utenti";
            }
        }
    }
}

// Carica tutte le competenze con statistiche di utilizzo
$stmt = $conn->query("
    SELECT 
        c.id, c.nome,
        COUNT(uc.utente_id) as utenti_utilizzatori,
        COUNT(sp.profilo_id) as profili_richiesti
    FROM competenze c
    LEFT JOIN utenti_competenze uc ON c.id = uc.competenza_id
    LEFT JOIN skill_profili sp ON c.id = sp.competenza_id
    GROUP BY c.id, c.nome
    ORDER BY c.nome
");
$competenze = $stmt->fetchAll();

// Statistiche generali
$stmt = $conn->query("SELECT COUNT(*) as totale FROM competenze");
$stats_competenze = $stmt->fetch()['totale'];

$stmt = $conn->query("SELECT COUNT(*) as totale FROM utenti WHERE tipo_utente = 'AMMINISTRATORE'");
$stats_admin = $stmt->fetch()['totale'];

$stmt = $conn->query("SELECT COUNT(*) as totale FROM utenti WHERE tipo_utente = 'CREATORE'");
$stats_creatori = $stmt->fetch()['totale'];

$stmt = $conn->query("SELECT COUNT(*) as totale FROM utenti");
$stats_totale_utenti = $stmt->fetch()['totale'];
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <?php $page_title = 'Pannello Amministratore'; include __DIR__ . '/../includes/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h3><i class="fas fa-user-shield"></i> Pannello Amministratore</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>

                        <!-- Statistiche generali -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h5><?= $stats_totale_utenti ?></h5>
                                        <small>Utenti Totali</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5><?= $stats_creatori ?></h5>
                                        <small>Creatori</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5><?= $stats_admin ?></h5>
                                        <small>Amministratori</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h5><?= $stats_competenze ?></h5>
                                        <small>Competenze</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form aggiunta competenza -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Aggiungi Nuova Competenza</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="add_competenza">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" name="nome" class="form-control"
                                                placeholder="Nome della competenza (es. JavaScript, PHP, AI, Machine Learning...)"
                                                required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus"></i> Aggiungi Competenza
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Lista competenze -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Gestione Competenze (<?= count($competenze) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($competenze)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Nessuna competenza ancora inserita.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome Competenza</th>
                                                <th>Utenti che la possiedono</th>
                                                <th>Profili che la richiedono</th>
                                                <th>Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($competenze as $comp): ?>
                                            <tr>
                                                <td><?= $comp['id'] ?></td>
                                                <td><strong><?= htmlspecialchars($comp['nome']) ?></strong></td>
                                                <td>
                                                    <span
                                                        class="badge bg-primary"><?= $comp['utenti_utilizzatori'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $comp['profili_richiesti'] ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($comp['utenti_utilizzatori'] == 0 && $comp['profili_richiesti'] == 0): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token"
                                                            value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="delete_competenza">
                                                        <input type="hidden" name="competenza_id"
                                                            value="<?= $comp['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Sei sicuro di voler eliminare questa competenza?')">
                                                            <i class="fas fa-trash"></i> Elimina
                                                        </button>
                                                    </form>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning">In uso</span>
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

                        <!-- Competenze più utilizzate -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Top Competenze Più Richieste</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $conn->query("
                    SELECT 
                        c.nome,
                        COUNT(uc.utente_id) as utenti_con_skill,
                        COUNT(sp.profilo_id) as profili_richiedenti,
                        (COUNT(uc.utente_id) + COUNT(sp.profilo_id)) as popolarita_totale
                    FROM competenze c
                    LEFT JOIN utenti_competenze uc ON c.id = uc.competenza_id
                    LEFT JOIN skill_profili sp ON c.id = sp.competenza_id
                    GROUP BY c.id, c.nome
                    HAVING popolarita_totale > 0
                    ORDER BY popolarita_totale DESC
                    LIMIT 10
                ");
                                $top_competenze = $stmt->fetchAll();
                                ?>

                                <?php if (empty($top_competenze)): ?>
                                <div class="alert alert-info">Nessuna competenza ancora utilizzata.</div>
                                <?php else: ?>
                                <div class="row">
                                    <?php foreach ($top_competenze as $i => $comp): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><?= ($i+1) ?>.
                                                <strong><?= htmlspecialchars($comp['nome']) ?></strong></span>
                                            <span class="badge bg-info"><?= $comp['profili_richiedenti'] ?>
                                                profili</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="../home.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Torna alla Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>