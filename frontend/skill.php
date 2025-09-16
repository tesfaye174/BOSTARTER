<?php
/**
 * BOSTARTER - Gestione Skill Curriculum Utente
 *
 * Pagina per la gestione delle competenze personali dell'utente.
 * Permette di aggiungere, modificare, visualizzare le proprie skill.
 *
 * Funzionalità:
 * - Visualizzazione skill attuali con livelli
 * - Aggiunta nuove competenze
 * - Modifica livelli esistenti
 * - Rimozione skill (se necessario)
 *
 * @author BOSTARTER Development Team
 * @version 2.0
 * @since 2025
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['tipo_utente'] ?? '';
$nickname = $_SESSION['nickname'] ?? '';

$message = '';
$error = '';

// Gestione form aggiunta skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_skill') {
        $competenza_id = intval($_POST['competenza_id'] ?? 0);
        $livello = intval($_POST['livello'] ?? 0);

        if ($competenza_id > 0 && $livello >= 0 && $livello <= 5) {
            try {
                require_once __DIR__ . '/../backend/config/database.php';
                $db = Database::getInstance();
                $conn = $db->getConnection();

                // Verifica se la competenza esiste
                $stmt = $conn->prepare("SELECT id FROM competenze WHERE id = ?");
                $stmt->execute([$competenza_id]);
                if ($stmt->fetch()) {
                    // Inserisci o aggiorna skill curriculum
                    $stmt = $conn->prepare("CALL inserisci_skill_curriculum(?, ?, ?)");
                    $stmt->execute([$user_id, $competenza_id, $livello]);

                    $message = 'Competenza aggiunta/modificata con successo!';

                    // Log dell'evento
                    require_once __DIR__ . '/../backend/services/MongoLogger.php';
                    BOSTARTER_Audit::logSkillAddition($user_id, [
                        'competenza_id' => $competenza_id,
                        'livello' => $livello
                    ]);
                } else {
                    $error = 'Competenza non valida.';
                }
            } catch (Exception $e) {
                $error = 'Errore nell\'aggiornamento delle skill: ' . $e->getMessage();
            }
        } else {
            $error = 'Dati non validi.';
        }
    } elseif ($_POST['action'] === 'remove_skill') {
        $competenza_id = intval($_POST['competenza_id'] ?? 0);

        try {
            require_once __DIR__ . '/../backend/config/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("DELETE FROM skill_curriculum WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $competenza_id]);

            $message = 'Competenza rimossa dal curriculum.';
        } catch (Exception $e) {
            $error = 'Errore nella rimozione della skill: ' . $e->getMessage();
        }
    }
}

// Carica skill attuali dell'utente
$skill_attuali = [];
$competenze_disponibili = [];

try {
    require_once __DIR__ . '/../backend/config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Carica skill curriculum attuale
    $stmt = $conn->prepare("
        SELECT sc.*, c.nome as competenza_nome
        FROM skill_curriculum sc
        JOIN competenze c ON sc.competenza_id = c.id
        WHERE sc.utente_id = ?
        ORDER BY c.nome
    ");
    $stmt->execute([$user_id]);
    $skill_attuali = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Carica tutte le competenze disponibili
    $stmt = $conn->query("SELECT id, nome FROM competenze ORDER BY nome");
    $competenze_disponibili = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = 'Errore nel caricamento dei dati: ' . $e->getMessage();
}

$page_title = 'Le Mie Competenze';

// Connessione al database
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Gestione richieste POST per modifiche competenze
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            $_SESSION['flash_error'] = 'Token CSRF non valido.';
            header('Location: skill.php');
            exit;
        }

        // Aggiunta nuova competenza
        if ($_POST['action'] === 'add_skill') {
            $competenza_id = intval($_POST['competenza_id']);
            $livello = intval($_POST['livello']);

            if ($livello < 1 || $livello > 5) {
                $_SESSION['flash_error'] = 'Il livello deve essere compreso tra 1 e 5.';
            } else {
                // Verifica se competenza già presente
                $stmt = $conn->prepare("SELECT id FROM utenti_competenze WHERE utente_id = ? AND competenza_id = ?");
                $stmt->execute([$_SESSION['user_id'], $competenza_id]);

                if ($stmt->fetch()) {
                    // Aggiorna livello esistente
                    $stmt = $conn->prepare("UPDATE utenti_competenze SET livello = ? WHERE utente_id = ? AND competenza_id = ?");
                    $stmt->execute([$livello, $_SESSION['user_id'], $competenza_id]);
                    $_SESSION['flash_success'] = 'Livello competenza aggiornato con successo!';
                } else {
                    // Inserisci nuova competenza
                    $stmt = $conn->prepare("INSERT INTO utenti_competenze (utente_id, competenza_id, livello) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $competenza_id, $livello]);
                    $_SESSION['flash_success'] = 'Competenza aggiunta al tuo profilo!';
                }
            }
        }

        // Rimozione competenza
        elseif ($_POST['action'] === 'remove_skill') {
            $competenza_id = intval($_POST['competenza_id']);
            $stmt = $conn->prepare("DELETE FROM utenti_competenze WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$_SESSION['user_id'], $competenza_id]);
            $_SESSION['flash_success'] = 'Competenza rimossa dal profilo.';
        }

        header('Location: skill.php');
        exit;
    }

    // Recupero competenze attuali dell'utente
    $stmt = $conn->prepare("
        SELECT uc.id, c.nome as competenza_nome, uc.livello, c.id as competenza_id
        FROM utenti_competenze uc
        JOIN competenze c ON uc.competenza_id = c.id
        WHERE uc.utente_id = ?
        ORDER BY c.nome
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recupero catalogo completo competenze
    $stmt = $conn->prepare("SELECT id, nome FROM competenze ORDER BY nome");
    $stmt->execute();
    $allSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Errore di connessione al database: ' . $e->getMessage();
    header('Location: home.php');
    exit;
}

// Inizializza token CSRF se non presente
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gestione messaggi flash
$message = '';
$error = '';

if (isset($_SESSION['flash_success'])) {
    $message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Includi header comune
require_once __DIR__.'/../backend/config/SecurityConfig.php';
require_once __DIR__.'/includes/head.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= htmlspecialchars($page_title) ?> - BOSTARTER</title>

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
        }

        body {
            padding-top: 76px;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .skill-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .skill-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .skill-star {
            color: #e5e7eb;
            font-size: 16px;
        }

        .skill-star.active {
            color: #fbbf24;
        }

        .level-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .text-gradient-primary {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-bostarter-primary {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            border: none;
            color: white;
        }

        .btn-bostarter-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #6d28d9);
            color: white;
        }

        .form-floating > label {
            color: #6b7280;
        }

        .empty-state {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Progetti</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="skill.php">Le Mie Skill</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="candidature.php">Candidature</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nickname'] ?? 'Utente') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dash.php">Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header con breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Le Mie Competenze</li>
            </ol>
        </nav>

        <!-- Messaggi di feedback -->
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

        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>

                <!-- Scheda informativa -->
                <div class="card mb-4 border-primary">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title text-primary">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    Sistema Competenze BOSTARTER
                                </h5>
                                <p class="card-text">
                                    Gestisci le tue competenze per aumentare la visibilità del tuo profilo
                                    e ricevere candidature per progetti più adatti alle tue capacità.
                                    Le competenze vengono utilizzate dal sistema di matching automatico.
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div>
                                        <i class="fas fa-cogs fa-3x text-primary mb-2"></i>
                                        <p class="text-muted small mb-0">Sistema di Matching</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Competenze esistenti -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Le Tue Competenze (<?php echo count($userSkills); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userSkills)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                            <h5 class="mb-3">Nessuna competenza aggiunta</h5>
                            <p class="text-muted mb-4">
                                Inizia ad aggiungere le tue competenze per migliorare il tuo profilo
                                e ricevere proposte per progetti adatti alle tue capacità.
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($userSkills as $skill): ?>
                            <div class="col-md-6">
                                <div class="card skill-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-1">
                                                <?php echo htmlspecialchars($skill['competenza_nome']); ?>
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="removeSkill(<?php echo $skill['competenza_id']; ?>, '<?php echo htmlspecialchars($skill['competenza_nome']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <div class="level-badge mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star skill-star <?php echo $i <= $skill['livello'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-2 small">
                                                <?php
                                                $livelli = ['Principiante', 'Base', 'Intermedio', 'Avanzato', 'Esperto'];
                                                echo $livelli[$skill['livello'] - 1];
                                                ?>
                                            </span>
                                        </div>

                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="action" value="add_skill">
                                            <input type="hidden" name="competenza_id" value="<?php echo $skill['competenza_id']; ?>">
                                            <select name="livello" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                <option value="1" <?php echo $skill['livello'] == 1 ? 'selected' : ''; ?>>Principiante</option>
                                                <option value="2" <?php echo $skill['livello'] == 2 ? 'selected' : ''; ?>>Base</option>
                                                <option value="3" <?php echo $skill['livello'] == 3 ? 'selected' : ''; ?>>Intermedio</option>
                                                <option value="4" <?php echo $skill['livello'] == 4 ? 'selected' : ''; ?>>Avanzato</option>
                                                <option value="5" <?php echo $skill['livello'] == 5 ? 'selected' : ''; ?>>Esperto</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Form aggiunta competenza -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 2rem;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            Aggiungi Competenza
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <input type="hidden" name="action" value="add_skill">

                            <div class="mb-3">
                                <label for="competenza_id" class="form-label">Competenza</label>
                                <select name="competenza_id" id="competenza_id" class="form-select" required>
                                    <option value="">Seleziona competenza...</option>
                                    <?php
                                    $possessedIds = array_column($userSkills, 'competenza_id');
                                    foreach ($allSkills as $skill):
                                        if (!in_array($skill['id'], $possessedIds)):
                                    ?>
                                    <option value="<?php echo $skill['id']; ?>">
                                        <?php echo htmlspecialchars($skill['nome']); ?>
                                    </option>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="livello" class="form-label">Livello</label>
                                <select name="livello" id="livello" class="form-select" required>
                                    <option value="">Seleziona livello...</option>
                                    <option value="1">⭐ Principiante</option>
                                    <option value="2">⭐⭐ Base</option>
                                    <option value="3">⭐⭐⭐ Intermedio</option>
                                    <option value="4">⭐⭐⭐⭐ Avanzato</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Esperto</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-bostarter-primary w-100">
                                <i class="fas fa-plus me-2"></i>
                                Aggiungi Competenza
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="text-muted small">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Vantaggi delle competenze:
                            </h6>
                            <ul class="mb-0 ps-3">
                                <li class="mb-1">Migliora la visibilità del profilo</li>
                                <li class="mb-1">Ricevi proposte mirate</li>
                                <li>Migliora il matching con i progetti</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal conferma rimozione -->
    <div class="modal fade" id="removeSkillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rimuovi Competenza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler rimuovere la competenza "<strong id="skillName"></strong>" dal tuo profilo?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <form method="POST" class="d-inline" id="removeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <input type="hidden" name="action" value="remove_skill">
                        <input type="hidden" name="competenza_id" id="removeSkillId">
                        <button type="submit" class="btn btn-danger">Rimuovi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removeSkill(skillId, skillName) {
            document.getElementById('skillName').textContent = skillName;
            document.getElementById('removeSkillId').value = skillId;
            new bootstrap.Modal(document.getElementById('removeSkillModal')).show();
        }
    </script>
</body>
</html>