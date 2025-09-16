<?php
/**
 * Dashboard Amministratore BOSTARTER
 *
 * Pannello di controllo completo per amministratori:
 * - Statistiche sistema (utenti, progetti, finanziamenti)
 * - Gestione utenti (attivazione/disattivazione)
 * - Approvazione progetti
 * - Aggiunta competenze rapide
 * 
 */

// Sessione obbligatoria per admin
session_start();

// Verifica accesso amministratore
function isAdmin() {
    return isset($_SESSION["tipo_utente"]) && $_SESSION["tipo_utente"] === "amministratore";
}

if (!isAdmin()) {
    header("Location: ../home.php?error=access_denied");
    exit;
}

$page_title = 'Dashboard Amministratore - BOSTARTER';
require_once __DIR__.'/../includes/head.php';
?>

<?php
// Inizializza variabili
$message = '';
$error = '';
$stats = [
    'total_users' => 0,
    'total_creators' => 0,
    'total_projects' => 0,
    'total_funds' => 0
];
$recent_users = [];
$pending_projects = [];

// Includi dipendenze e recupera dati
try {
    require_once __DIR__ . "/../../backend/config/database.php";

    $db = Database::getInstance();
    $conn = $db;

    // Debug: verifica connessione
    echo "<!-- Debug: Database connesso -->\n";

    // IMPORTANTE: Chiudi eventuali result set pendenti
    while ($conn->query('SELECT 1')) {
        // Consuma eventuali result set pendenti
        break;
    }

    // Statistiche generali
    $stmt = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM utenti WHERE is_active = TRUE) as total_users,
            (SELECT COUNT(*) FROM utenti WHERE tipo_utente = 'creatore' AND is_active = TRUE) as total_creators,
            (SELECT COUNT(*) FROM progetti WHERE is_active = TRUE) as total_projects,
            (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE stato_pagamento = 'completed') as total_funds
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?? $stats;

    // Debug: mostra statistiche
    echo "<!-- Debug stats: " . json_encode($stats) . " -->\n";

    // Utenti recenti
    $stmt = $conn->prepare("
        SELECT id, nickname, email, tipo_utente, data_registrazione, is_active
        FROM utenti
        WHERE is_active = TRUE
        ORDER BY data_registrazione DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

    // Progetti in attesa di approvazione (tutti i progetti attivi per ora)
    $stmt = $conn->prepare("
        SELECT p.id, p.titolo as titolo, p.descrizione as descrizione_breve,
               u.nickname as creatore, p.data_inserimento as data_creazione,
               p.tipo_progetto
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE p.is_active = TRUE
        ORDER BY p.data_inserimento DESC
        LIMIT 5
    ");
    $stmt->execute();
    $pending_projects = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

} catch (Exception $e) {
    error_log('Errore inizializzazione dashboard admin: ' . $e->getMessage());
    $error = 'Errore nel caricamento dei dati del sistema: ' . $e->getMessage();
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'approve_project') {
            $project_id = intval($_POST['project_id']);
            // Per ora solo aggiorna lo stato
            $stmt = $conn->prepare("UPDATE progetti SET stato = 'pubblicato' WHERE id = ?");
            $stmt->execute([$project_id]);
            $message = 'Progetto approvato con successo!';

        } elseif ($action === 'reject_project') {
            $project_id = intval($_POST['project_id']);
            $stmt = $conn->prepare("UPDATE progetti SET stato = 'chiuso' WHERE id = ?");
            $stmt->execute([$project_id]);
            $message = 'Progetto rifiutato.';

        } elseif ($action === 'toggle_user_status') {
            $user_id = intval($_POST['user_id']);
            $current_status = $_POST['current_status'] === '1' ? 0 : 1;

            $stmt = $conn->prepare("UPDATE utenti SET is_active = ? WHERE id = ?");
            $stmt->execute([$current_status, $user_id]);

            $message = $current_status ? 'Utente riattivato.' : 'Utente disattivato.';

        } elseif ($action === 'add_skill') {
            $skill_name = trim($_POST['skill_name']);
            $skill_description = trim($_POST['skill_description']);

            if (!empty($skill_name)) {
                // Per ora inseriamo una competenza semplice
                $stmt = $conn->prepare("INSERT INTO competenze (nome, descrizione) VALUES (?, ?) ON DUPLICATE KEY UPDATE descrizione = VALUES(descrizione)");
                $stmt->execute([$skill_name, $skill_description]);
                $message = 'Competenza aggiunta con successo!';
            }
        }

        header("Location: dashboard.php?message=" . urlencode($message));
        exit;

    } catch (Exception $e) {
        $error = 'Errore nell\'esecuzione dell\'azione: ' . $e->getMessage();
    }
}

// Messaggi dalla URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bostarter-primary: #2563eb;
            --bostarter-secondary: #7c3aed;
            --bostarter-success: #059669;
            --bostarter-warning: #d97706;
            --bostarter-danger: #dc2626;
            --bostarter-info: #0891b2;
            --bostarter-dark: #1f2937;
        }

        body {
            padding-top: 76px;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .table th {
            background-color: var(--bostarter-primary);
            color: white;
            border: none;
        }

        .badge-admin {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
        }

        .badge-creator {
            background-color: var(--bostarter-warning);
        }

        .badge-user {
            background-color: var(--bostarter-info);
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            border: none;
            color: white;
        }

        .btn-admin:hover {
            background: linear-gradient(135deg, #1d4ed8, #6d28d9);
            color: white;
        }

        .sidebar {
            position: fixed;
            top: 76px;
            left: 0;
            height: calc(100vh - 76px);
            width: 250px;
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 1rem;
            overflow-y: auto;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .sidebar-link {
            display: block;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background-color: var(--bostarter-primary);
            color: white;
        }

        .sidebar-link i {
            margin-right: 0.5rem;
            width: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER Admin
            </a>

            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-shield me-2"></i>
                    <?= htmlspecialchars($_SESSION['nickname'] ?? 'Admin') ?>
                </span>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <h6 class="sidebar-title mb-3">
            <i class="fas fa-tachometer-alt me-2"></i>Pannello Admin
        </h6>

        <a href="dashboard.php" class="sidebar-link active">
            <i class="fas fa-home"></i>Dashboard
        </a>

        <a href="competenze.php" class="sidebar-link">
            <i class="fas fa-tools"></i>Competenze
        </a>

        <a href="add_skill.php" class="sidebar-link">
            <i class="fas fa-plus-circle"></i>Aggiungi Skill
        </a>

        <hr class="my-3">

        <a href="../home.php" class="sidebar-link">
            <i class="fas fa-globe"></i>Sito Pubblico
        </a>

        <a href="../statistiche.php" class="sidebar-link">
            <i class="fas fa-chart-bar"></i>Statistiche Pubbliche
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Amministratore
                </h1>
                <p class="text-muted mb-0">Gestisci il sistema BOSTARTER</p>
            </div>
            <div class="text-end">
                <small class="text-muted">Ultimo aggiornamento</small>
                <br>
                <strong><?php echo date('d/m/Y H:i'); ?></strong>
            </div>
        </div>

        <!-- Messaggi -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistiche Generali -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto mb-3" style="background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="card-title mb-1"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                        <p class="card-text text-muted small">Utenti Totali</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto mb-3" style="background-color: var(--bostarter-warning);">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="card-title mb-1"><?php echo number_format($stats['total_creators'] ?? 0); ?></h3>
                        <p class="card-text text-muted small">Creatori</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto mb-3" style="background-color: var(--bostarter-success);">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h3 class="card-title mb-1"><?php echo number_format($stats['total_projects'] ?? 0); ?></h3>
                        <p class="card-text text-muted small">Progetti Totali</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto mb-3" style="background-color: var(--bostarter-info);">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h3 class="card-title mb-1">€<?php echo number_format($stats['total_funds'] ?? 0, 0, ',', '.'); ?></h3>
                        <p class="card-text text-muted small">Finanziamenti</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione Progetti in Attesa -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Progetti in Attesa di Approvazione
                            <span class="badge bg-warning ms-2"><?php echo count($pending_projects); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_projects)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">Tutti i progetti sono stati revisionati!</h5>
                            <p class="text-muted">Nessun progetto in attesa di approvazione.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Progetto</th>
                                        <th>Creatore</th>
                                        <th>Tipo</th>
                                        <th>Data Creazione</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_projects as $project): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['titolo']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($project['descrizione_breve'], 0, 60)); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['creatore']); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo ucfirst($project['tipo_progetto']); ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($project['data_creazione'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="approve_project">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approvare questo progetto?')">
                                                        <i class="fas fa-check"></i> Approva
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject_project">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Rifiutare questo progetto?')">
                                                        <i class="fas fa-times"></i> Rifiuta
                                                    </button>
                                                </form>
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
            </div>
        </div>

        <!-- Utenti Recenti -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            Utenti Recenti
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nickname</th>
                                        <th>Email</th>
                                        <th>Tipo</th>
                                        <th>Data Registrazione</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nickname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['tipo_utente']; ?>">
                                                <?php echo ucfirst($user['tipo_utente']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['data_registrazione'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $user['is_active'] ? 'Attivo' : 'Disattivato'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $user['is_active'] ? '1' : '0'; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                                        onclick="return confirm('<?php echo $user['is_active'] ? 'Disattivare' : 'Riattivare'; ?> questo utente?')">
                                                    <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Azioni Rapide -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Azioni Rapide
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="competenze.php" class="btn btn-admin">
                                <i class="fas fa-tools me-2"></i>Gestisci Competenze
                            </a>
                            <a href="add_skill.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Aggiungi Skill
                            </a>
                            <a href="../statistiche.php" class="btn btn-outline-info">
                                <i class="fas fa-chart-bar me-2"></i>Visualizza Statistiche
                            </a>
                        </div>

                        <hr class="my-3">

                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="add_skill">
                            <h6>Aggiungi Competenza Rapida</h6>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="skill_name" placeholder="Nome competenza" required>
                            </div>
                            <div class="mb-2">
                                <textarea class="form-control form-control-sm" name="skill_description" rows="2" placeholder="Descrizione (opzionale)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-plus me-1"></i>Aggiungi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    // Bootstrap JS
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
