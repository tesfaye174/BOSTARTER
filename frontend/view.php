<?php
/**
 * BOSTARTER - Visualizzazione Progetto Migliorata
 *
 * Pagina completa per visualizzare i dettagli di un progetto con:
 * - Gestione errori elegante e user-friendly
 * - Design responsive moderno
 * - Sistema commenti interattivo
 * - Gestione immagini progetti
 * - Navbar completa
 * - Sicurezza avanzata
 */

// Avvia la sessione sicura
session_start();

// Configurazione errori per debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Verifica se l'utente è autenticato
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Genera token CSRF sicuro
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sanitizza output HTML
 */
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formatta data in italiano
 */
function format_date($date) {
    if (!$date) return 'Non specificata';
    return date('d/m/Y', strtotime($date));
}

/**
 * Calcola giorni rimanenti
 */
function calculate_days_left($deadline) {
    if (!$deadline) return 0;
    $now = time();
    $deadline_time = strtotime($deadline);
    $diff = $deadline_time - $now;
    return max(0, ceil($diff / (60 * 60 * 24)));
}

// Inizializza variabili
$project_id = $_GET['id'] ?? null;
$error = '';
$warning = '';
$project = null;
$finanziamenti = [];
$ricompense = [];
$commenti = [];
$redirect_to_home = false;
$can_edit = false;

// Validazione ID progetto
if (!$project_id) {
    $error = 'ID progetto mancante nell\'URL.';
    $redirect_to_home = true;
} elseif (!is_numeric($project_id) || $project_id <= 0) {
    $error = 'ID progetto non valido. Deve essere un numero positivo.';
    $redirect_to_home = true;
} else {
    $project_id = filter_var($project_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($project_id === false) {
        $error = 'ID progetto non valido. Formato non corretto.';
        $redirect_to_home = true;
    }
}

// Carica dati se ID valido
if (!$error) {
    try {
        require_once '../backend/config/database.php';
        $db = Database::getInstance();

        // Query progetto principale
        $query = "
            SELECT p.*,
                   u.nickname as creatore_nickname,
                   u.nome as creatore_nome,
                   u.cognome as creatore_cognome,
                   p.stato as tipo_progetto,
                   p.data_limite as data_scadenza,
                   p.data_inserimento,
                   p.creatore_id
            FROM progetti p
            LEFT JOIN utenti u ON p.creatore_id = u.id
            WHERE p.id = ?
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($project) {
            // Verifica se utente può modificare il progetto
            $can_edit = isLoggedIn() && $_SESSION['user_id'] == $project['creatore_id'];

            // Carica statistiche finanziarie
            $query_stats = "
                SELECT
                    COALESCE(SUM(importo), 0) as totale_raccolto,
                    COUNT(DISTINCT utente_id) as numero_sostenitori,
                    COUNT(id) as numero_finanziamenti
                FROM finanziamenti
                WHERE progetto_id = ?
            ";
            $stmt_stats = $db->prepare($query_stats);
            $stmt_stats->execute([$project_id]);
            $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

            $project['totale_raccolto'] = $stats['totale_raccolto'] ?? 0;
            $project['numero_sostenitori'] = $stats['numero_sostenitori'] ?? 0;
            $project['numero_finanziamenti'] = $stats['numero_finanziamenti'] ?? 0;

            // Carica finanziamenti
            $query_finanziamenti = "
                SELECT f.*,
                       u.nickname as finanziatore_nickname,
                       r.nome as reward_nome
                FROM finanziamenti f
                LEFT JOIN utenti u ON f.utente_id = u.id
                LEFT JOIN rewards r ON f.reward_id = r.id
                WHERE f.progetto_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT 10
            ";
            $stmt_fin = $db->prepare($query_finanziamenti);
            $stmt_fin->execute([$project_id]);
            $finanziamenti = $stmt_fin->fetchAll(PDO::FETCH_ASSOC);

            // Carica ricompense
            $query_ricompense = "
                SELECT id, nome, descrizione, importo_minimo,
                       quantita_disponibile, quantita_rimanente
                FROM rewards
                WHERE progetto_id = ?
                ORDER BY importo_minimo ASC
            ";
            $stmt_ric = $db->prepare($query_ricompense);
            $stmt_ric->execute([$project_id]);
            $ricompense = $stmt_ric->fetchAll(PDO::FETCH_ASSOC);

            // Carica commenti
            $query_commenti = "
                SELECT c.*,
                       u.nickname as autore_nickname,
                       r.testo as risposta_testo,
                       r.data_creazione as risposta_data
                FROM commenti c
                LEFT JOIN utenti u ON c.utente_id = u.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                WHERE c.progetto_id = ?
                ORDER BY c.data_creazione DESC
            ";
            $stmt_comm = $db->prepare($query_commenti);
            $stmt_comm->execute([$project_id]);
            $commenti = $stmt_comm->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $error = "Progetto non trovato. Il progetto potrebbe essere stato rimosso.";
            $redirect_to_home = true;
        }
    } catch(Exception $e) {
        $error = "Errore nel caricamento del progetto: " . sanitize_output($e->getMessage());
        error_log("Errore view.php: " . $e->getMessage());
    }
}

// Calcoli per il template
$is_logged_in = isLoggedIn();
$progress = $project && $project['budget_richiesto'] > 0 ?
    min(100, ($project['totale_raccolto'] / $project['budget_richiesto']) * 100) : 0;
$days_left = $project ? calculate_days_left($project['data_limite']) : 0;
$csrf_token = generate_csrf_token();

// Titolo pagina per header moderno
$page_title = ($project ? sanitize_output($project['titolo']) : 'Progetto') . ' - BOSTARTER';

// Includi header moderno
require_once 'includes/head.php';

// Includi navbar moderno
require_once 'includes/navbar.php';
?>

<body>
    <!-- Redirect se necessario -->
    <?php if ($redirect_to_home): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'home.php';
        }, 3000);
    </script>
    <?php endif; ?>

    <!-- Error Display -->
    <?php if (!empty($error)): ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="alert alert-danger border-0 shadow-sm animate-fade-up" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                        <div>
                            <h5 class="alert-heading mb-2">Errore nel caricamento</h5>
                            <p class="mb-0"><?php echo sanitize_output($error); ?></p>
                            <hr>
                            <p class="mb-0">Sarai reindirizzato alla home page tra 3 secondi...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="animate-fade-up">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-<?php
                                switch($project['stato']) {
                                    case 'attivo': echo 'success'; break;
                                    case 'in_attesa': echo 'warning'; break;
                                    case 'completato': echo 'info'; break;
                                    default: echo 'secondary';
                                }
                            ?> me-2">
                                <i class="fas fa-circle me-1"></i><?php echo sanitize_output($project['stato']); ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Pubblicato il <?php echo format_date($project['data_inserimento']); ?>
                            </small>
                        </div>

                        <h1 class="hero-title mb-3"><?php echo sanitize_output($project['titolo']); ?></h1>
                        <p class="hero-subtitle mb-4"><?php echo sanitize_output($project['descrizione']); ?></p>

                        <div class="row g-3 mb-4">
                            <div class="col-auto">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted d-block">Creato da</small>
                                        <strong><?php echo sanitize_output($project['creatore_nickname']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tag fa-2x text-info me-3"></i>
                                    <div>
                                        <small class="text-muted d-block">Categoria</small>
                                        <strong><?php echo sanitize_output($project['tipo_progetto']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-lg-8">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h5 class="mb-0">Progresso Raccolta</h5>
                                                <span class="badge bg-primary"><?php echo number_format($progress, 1); ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="text-center p-3 bg-light rounded">
                                                    <div class="h4 mb-1 text-success fw-bold">
                                                        €<?php echo number_format($project['totale_raccolto'], 0); ?>
                                                    </div>
                                                    <small class="text-muted">Raccolto</small>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="text-center p-3 bg-light rounded">
                                                    <div class="h4 mb-1 text-primary fw-bold">
                                                        €<?php echo number_format($project['budget_richiesto'], 0); ?>
                                                    </div>
                                                    <small class="text-muted">Obiettivo</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 text-center">
                                        <?php if ($project['stato'] === 'attivo' && $days_left > 0): ?>
                                        <div class="p-3 bg-warning bg-opacity-10 rounded">
                                            <div class="h2 mb-1 text-warning fw-bold"><?php echo $days_left; ?></div>
                                            <small class="text-muted">Giorni rimanenti</small>
                                        </div>
                                        <?php elseif ($project['stato'] === 'completato'): ?>
                                        <div class="p-3 bg-success bg-opacity-10 rounded">
                                            <div class="h2 mb-1 text-success fw-bold">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <small class="text-muted">Progetto completato</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-lg border-0 animate-fade-up">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-hand-holding-heart me-2 text-danger"></i>
                                Sostieni questo progetto
                            </h5>

                            <?php if ($is_logged_in): ?>
                            <form method="POST" action="process_financement.php" class="mb-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="progetto_id" value="<?php echo $project_id; ?>">

                                <div class="mb-3">
                                    <label for="importo" class="form-label fw-semibold">Importo (€)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control form-control-lg border-0 shadow-sm"
                                               id="importo" name="importo" placeholder="50" min="1" required>
                                    </div>
                                </div>

                                <?php if (!empty($ricompense)): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Ricompensa (opzionale)</label>
                                    <select name="ricompensa_id" class="form-select border-0 shadow-sm">
                                        <option value="">Nessuna ricompensa</option>
                                        <?php foreach ($ricompense as $ricompensa): ?>
                                        <option value="<?php echo $ricompensa['id']; ?>">
                                            €<?php echo $ricompensa['importo_minimo']; ?> - <?php echo sanitize_output($ricompensa['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                                    <i class="fas fa-heart me-2"></i>Finanzia Progetto
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="text-center">
                                <p class="text-muted mb-3">Accedi per supportare questo progetto</p>
                                <a href="auth/login.php" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Accedi
                                </a>
                                <a href="auth/signup.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Registrati
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Share buttons -->
                            <hr>
                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Condividi questo progetto</small>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-secondary btn-sm" onclick="shareOnFacebook()">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="shareOnTwitter()">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Details -->
    <div class="container-fluid py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Project Description -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Dettagli del Progetto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="project-description">
                                <?php echo nl2br(sanitize_output($project['descrizione_completa'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Rewards Section -->
                    <?php if (!empty($ricompense)): ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-gift me-2 text-warning"></i>Ricompense
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($ricompense as $ricompensa): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm reward-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-1"><?php echo sanitize_output($ricompensa['nome']); ?></h6>
                                                <span class="badge bg-primary">€<?php echo $ricompensa['importo_minimo']; ?></span>
                                            </div>
                                            <p class="card-text text-muted small mb-2">
                                                <?php echo sanitize_output($ricompensa['descrizione']); ?>
                                            </p>
                                            <small class="text-muted">
                                                Disponibili: <?php echo $ricompensa['quantita_rimanente']; ?>/<?php echo $ricompensa['quantita_disponibile']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Comments Section -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-comments me-2 text-info"></i>Commenti
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($is_logged_in): ?>
                            <!-- Add Comment Form -->
                            <form method="POST" action="process_comment.php" class="mb-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="progetto_id" value="<?php echo $project_id; ?>">

                                <div class="mb-3">
                                    <label for="commento" class="form-label fw-semibold">Lascia un commento</label>
                                    <textarea class="form-control border-0 shadow-sm" id="commento" name="testo"
                                              rows="3" placeholder="Condividi i tuoi pensieri..." required></textarea>
                                </div>

                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Pubblica Commento
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info border-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <a href="auth/login.php" class="alert-link">Accedi</a> per lasciare un commento.
                            </div>
                            <?php endif; ?>

                            <!-- Comments List -->
                            <?php if (!empty($commenti)): ?>
                            <div class="comments-list">
                                <?php foreach ($commenti as $commento): ?>
                                <div class="comment-item border-bottom pb-3 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong><?php echo sanitize_output($commento['autore_nickname']); ?></strong>
                                                <small class="text-muted">
                                                    <?php echo format_date($commento['data_creazione']); ?>
                                                </small>
                                            </div>
                                            <p class="mb-2"><?php echo nl2br(sanitize_output($commento['testo'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nessun commento ancora. Sii il primo a commentare!</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Project Stats Sidebar -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-bar me-2 text-success"></i>Statistiche
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="h5 mb-1 text-primary"><?php echo count($finanziamenti); ?></div>
                                        <small class="text-muted">Finanziatori</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="h5 mb-1 text-info"><?php echo count($commenti); ?></div>
                                        <small class="text-muted">Commenti</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Supporters -->
                    <?php if (!empty($finanziamenti)): ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-0">
                                <i class="fas fa-heart me-2 text-danger"></i>Ultimi Sostenitori
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach (array_slice($finanziamenti, 0, 5) as $finanziamento): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                     style="width: 30px; height: 30px; font-size: 12px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="fw-semibold"><?php echo sanitize_output($finanziamento['nickname']); ?></small>
                                    <br>
                                    <small class="text-muted">€<?php echo number_format($finanziamento['importo'], 2); ?></small>
                                </div>
                                <small class="text-muted"><?php echo format_date($finanziamento['data_finanziamento']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTopBtn" title="Torna in cima">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- JavaScript per funzionalità avanzate -->
    <script>
        // Share functions
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }

        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent("Scopri questo progetto: <?php echo sanitize_output($project['titolo'] ?? ''); ?>");
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'toast toast-bostarter show align-items-center text-white bg-success border-0 position-fixed top-0 end-0 p-3';
                toast.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="fas fa-check me-2"></i>Link copiato negli appunti!</div></div>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            });
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling to anchor links
            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
    </script>

    <!-- JavaScript Ottimizzato -->
    <script src="assets/js/bostarter-optimized.min.js"></script>
</body>
</html>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project ? htmlspecialchars($project['titolo']) : 'Progetto'; ?> - BOSTARTER</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS Avanzato -->
    <style>
        :root {
            --bostarter-primary: #2563eb;
            --bostarter-secondary: #7c3aed;
            --bostarter-success: #059669;
            --bostarter-warning: #d97706;
            --bostarter-danger: #dc2626;
            --bostarter-info: #0891b2;
            --bostarter-light: #f8fafc;
            --bostarter-dark: #1e293b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --border-radius-sm: 8px;
            --border-radius-md: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 24px;
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        /* Dark Mode Variables */
        [data-bs-theme="dark"] {
            --bostarter-primary: #3b82f6;
            --bostarter-secondary: #8b5cf6;
            --bostarter-light: #1e293b;
            --bostarter-dark: #0f172a;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6);
        }

        [data-bs-theme="dark"] body {
            background-color: var(--bostarter-dark);
            color: #e2e8f0;
        }

        [data-bs-theme="dark"] .card-bostarter {
            background-color: var(--bostarter-light);
            border: 1px solid #334155;
        }

        [data-bs-theme="dark"] .stats-card {
            background-color: var(--bostarter-light);
            border: 1px solid #334155;
        }

        /* Enhanced Body */
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            transition: background-color var(--transition-normal), color var(--transition-normal);
        }

        [data-bs-theme="dark"] body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        /* Enhanced Navbar */
        .navbar-bostarter {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all var(--transition-normal);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            transition: transform var(--transition-fast);
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            font-weight: 500;
            transition: all var(--transition-normal);
            position: relative;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 0;
            height: 2px;
            background: white;
            transition: all var(--transition-fast);
            transform: translateX(-50%);
        }

        .nav-link:hover::before {
            width: 100%;
        }

        /* Dark Mode Toggle */
        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            width: 50px;
            height: 24px;
            position: relative;
            cursor: pointer;
            transition: all var(--transition-normal);
            overflow: hidden;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .theme-toggle::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .theme-toggle.dark::before {
            content: '🌙';
            left: 28px;
        }

        .theme-toggle.light::before {
            content: '☀️';
            left: 2px;
        }

        /* Enhanced Cards */
        .card-bostarter {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .card-bostarter:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .card-header-bostarter {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            color: white;
            border: none;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .card-header-bostarter::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced Progress Bar */
        .progress-bostarter {
            height: 10px;
            border-radius: 5px;
            background: linear-gradient(90deg, #e2e8f0, #cbd5e1);
            overflow: hidden;
            position: relative;
        }

        .progress-bar-bostarter {
            background: linear-gradient(90deg, var(--bostarter-success), var(--bostarter-primary), var(--bostarter-secondary));
            background-size: 200% 100%;
            animation: progressShine 2s ease-in-out infinite;
            border-radius: 5px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .progress-bar-bostarter::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: progressGlow 2s ease-in-out infinite;
        }

        @keyframes progressShine {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes progressGlow {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 200%; }
        }

        /* Enhanced Buttons */
        .btn-bostarter {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            border: none;
            color: white;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: var(--border-radius-lg);
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .btn-bostarter::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left var(--transition-normal);
        }

        .btn-bostarter:hover::before {
            left: 100%;
        }

        .btn-bostarter:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-xl);
            color: white;
        }

        .btn-bostarter:active {
            transform: translateY(-1px) scale(1.02);
        }

        /* Enhanced Stats Cards */
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        [data-bs-theme="dark"] .stats-card {
            background: var(--bostarter-light);
            border: 1px solid #334155;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--bostarter-primary), var(--bostarter-secondary));
            transform: scaleX(0);
            transition: transform var(--transition-normal);
        }

        .stats-card:hover::before {
            transform: scaleX(1);
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--bostarter-primary);
            margin-bottom: 0.5rem;
            transition: all var(--transition-normal);
        }

        .stats-card:hover .stats-number {
            transform: scale(1.1);
        }

        /* Enhanced Image Handling */
        .project-image-container {
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius-lg);
        }

        .project-image {
            width: 100%;
            height: 450px;
            object-fit: cover;
            transition: all var(--transition-slow);
        }

        .project-image:hover {
            transform: scale(1.05);
        }

        .project-image-placeholder {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Enhanced Comments */
        .comment-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            border: 1px solid transparent;
        }

        [data-bs-theme="dark"] .comment-card {
            background: var(--bostarter-light);
            border-color: #334155;
        }

        .comment-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--bostarter-primary);
        }

        .comment-author {
            font-weight: 600;
            color: var(--bostarter-primary);
            margin-bottom: 0.5rem;
            transition: color var(--transition-fast);
        }

        .comment-card:hover .comment-author {
            color: var(--bostarter-secondary);
        }

        /* Enhanced Modal */
        .modal-bostarter .modal-content {
            border-radius: var(--border-radius-xl);
            border: none;
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .modal-bostarter .modal-header {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            color: white;
            border-radius: var(--border-radius-xl) var(--border-radius-xl) 0 0;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .modal-bostarter .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }

        /* Enhanced Reward Cards */
        .reward-card {
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            transition: all var(--transition-normal);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .reward-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: left var(--transition-normal);
        }

        .reward-card:hover::before {
            left: 100%;
        }

        .reward-card:hover {
            border-color: var(--bostarter-primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .reward-card.selected {
            border-color: var(--bostarter-success);
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(5, 150, 105, 0.05));
            transform: translateY(-2px);
        }

        /* Enhanced Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-up {
            animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-fade-left {
            animation: fadeInLeft 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-fade-right {
            animation: fadeInRight 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-bounce-in {
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        /* Stagger animations */
        .animate-stagger-1 { animation-delay: 0.1s; }
        .animate-stagger-2 { animation-delay: 0.2s; }
        .animate-stagger-3 { animation-delay: 0.3s; }
        .animate-stagger-4 { animation-delay: 0.4s; }
        .animate-stagger-5 { animation-delay: 0.5s; }

        /* Loading States */
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: var(--border-radius-md);
        }

        [data-bs-theme="dark"] .loading-shimmer {
            background: linear-gradient(90deg, #334155 25%, #475569 50%, #334155 75%);
            background-size: 200% 100%;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: var(--border-radius-md);
        }

        .skeleton-text {
            height: 1rem;
            margin-bottom: 0.5rem;
        }

        .skeleton-title {
            height: 2rem;
            margin-bottom: 1rem;
        }

        .skeleton-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        /* Enhanced Error States */
        .error-card {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #fca5a5;
            border-radius: var(--border-radius-lg);
            padding: 3rem;
            text-align: center;
            color: #dc2626;
            position: relative;
            overflow: hidden;
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounceIn 0.8s ease-out;
        }

        /* Enhanced Success States */
        .success-message {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #6ee7b7;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: #059669;
            animation: fadeInUp 0.6s ease-out;
        }

        /* Toast Notifications */
        .toast-bostarter {
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .toast-success {
            background: linear-gradient(135deg, var(--bostarter-success), #10b981);
            color: white;
        }

        .toast-error {
            background: linear-gradient(135deg, var(--bostarter-danger), #ef4444);
            color: white;
        }

        .toast-info {
            background: linear-gradient(135deg, var(--bostarter-info), #06b6d4);
            color: white;
        }

        /* Enhanced Tooltips */
        .tooltip-inner {
            background: var(--bostarter-dark);
            color: white;
            border-radius: var(--border-radius-md);
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            box-shadow: var(--shadow-lg);
        }

        .tooltip-arrow::before {
            border-top-color: var(--bostarter-dark);
        }

        /* Accessibility Improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus States */
        .btn:focus,
        .form-control:focus,
        .card-bostarter:focus {
            outline: 2px solid var(--bostarter-primary);
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print Styles */
        @media print {
            .navbar,
            .btn,
            .modal,
            .theme-toggle {
                display: none !important;
            }

            .card-bostarter {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Performance Optimizations */
        .will-change-transform {
            will-change: transform;
        }

        .gpu-accelerated {
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.25rem;
            }

            .project-image {
                height: 280px;
            }

            .stats-card {
                margin-bottom: 1.5rem;
                padding: 1.5rem;
            }

            .stats-number {
                font-size: 2rem;
            }

            .card-bostarter {
                margin-bottom: 2rem;
            }

            .modal-dialog {
                margin: 1rem;
            }

            .reward-card {
                margin-bottom: 1rem;
            }
        }

        /* High DPI Displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .project-image {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Safe Area for Mobile Devices */
        @supports (padding: max(0px)) {
            .container {
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
            }

            .navbar {
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
            }
        }
    </style>
</head>

<body>
    <!-- Navbar Migliorata -->
    <nav class="navbar navbar-expand-lg navbar-bostarter navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Torna alla homepage">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progetti.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Esplora tutti i progetti">
                            <i class="fas fa-lightbulb me-1"></i>Progetti
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="skill.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Gestisci le tue competenze">
                            <i class="fas fa-brain me-1"></i>Le Mie Skill
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="candidature.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Le tue candidature">
                            <i class="fas fa-user-check me-1"></i>Candidature
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="crea_progetto.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Crea un nuovo progetto">
                            <i class="fas fa-plus-circle me-1"></i>Crea Progetto
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav d-flex align-items-center">
                    <!-- Dark Mode Toggle -->
                    <li class="nav-item me-2">
                        <button class="theme-toggle light" id="themeToggle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Cambia tema">
                            <span class="sr-only">Toggle theme</span>
                        </button>
                    </li>

                    <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-menu" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo sanitize_output($_SESSION['nickname'] ?? 'Utente'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            <li><a class="dropdown-item" href="profilo.php">
                                <i class="fas fa-user me-2"></i>Profilo
                            </a></li>
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Accedi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php">
                            <i class="fas fa-user-plus me-1"></i>Registrati
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-4">
        <?php if ($error): ?>
        <!-- Error Display Migliorato -->
        <div class="error-card animate-fade-up">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4 class="mb-3">Ops! Qualcosa è andato storto</h4>
            <p class="mb-4"><?php echo sanitize_output($error); ?></p>
            <?php if ($redirect_to_home): ?>
            <div class="d-flex gap-2 justify-content-center">
                <a href="home.php" class="btn btn-bostarter">
                    <i class="fas fa-home me-2"></i>Torna alla Homepage
                </a>
                <button onclick="history.back()" class="btn btn-outline-bostarter">
                    <i class="fas fa-arrow-left me-2"></i>Indietro
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4 animate-fade-left">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="home.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="progetti.php">Progetti</a>
                </li>
                <li class="breadcrumb-item active"><?php echo sanitize_output($project['titolo']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Contenuto Principale -->
            <div class="col-lg-8">
                <!-- Header Progetto -->
                <div class="card-bostarter mb-4 animate-fade-up">
                    <!-- Immagine Progetto con fallback -->
                    <div class="project-image-container position-relative">
                        <?php if (!empty($project['immagine']) && file_exists('../backend/uploads/' . $project['immagine'])): ?>
                            <img src="../backend/uploads/<?php echo sanitize_output($project['immagine']); ?>"
                                 class="project-image gpu-accelerated" alt="<?php echo sanitize_output($project['titolo']); ?>">
                        <?php else: ?>
                            <div class="project-image project-image-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Badge Tipo Progetto con tooltip -->
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge badge-bostarter"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="left"
                                  title="<?php echo $project['tipo_progetto'] === 'hardware' ? 'Progetto Hardware' : 'Progetto Software'; ?>">
                                <i class="fas fa-<?php echo $project['tipo_progetto'] === 'hardware' ? 'microchip' : 'code'; ?> me-1"></i>
                                <?php echo ucfirst(sanitize_output($project['tipo_progetto'])); ?>
                            </span>
                        </div>

                        <!-- Badge Stato Progetto -->
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="badge <?php echo $project['stato'] === 'aperto' ? 'bg-success' : 'bg-secondary'; ?> fs-6 px-3 py-2">
                                <i class="fas fa-<?php echo $project['stato'] === 'aperto' ? 'play-circle' : 'pause-circle'; ?> me-1"></i>
                                <?php echo ucfirst(sanitize_output($project['stato'])); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h1 class="h2 mb-2"><?php echo sanitize_output($project['titolo']); ?></h1>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user me-2"></i>
                                    Creato da <strong><?php echo sanitize_output($project['creatore_nickname']); ?></strong>
                                </p>
                                <?php if (!empty($project['categoria'])): ?>
                                <span class="badge bg-light text-dark"
                                      data-bs-toggle="tooltip"
                                      data-bs-placement="top"
                                      title="Categoria del progetto">
                                    <i class="fas fa-tag me-1"></i><?php echo sanitize_output($project['categoria']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($can_edit): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                        data-bs-toggle="tooltip" data-bs-placement="left" title="Gestisci progetto">
                                    <i class="fas fa-cog me-1"></i>Gestisci
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                    <li><a class="dropdown-item" href="edit_progetto.php?id=<?php echo $project_id; ?>">
                                        <i class="fas fa-edit me-2"></i>Modifica Progetto
                                    </a></li>
                                    <li><a class="dropdown-item" href="rewards.php?id=<?php echo $project_id; ?>">
                                        <i class="fas fa-gift me-2"></i>Gestisci Ricompense
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmCloseProject()">
                                        <i class="fas fa-times me-2"></i>Chiudi Progetto
                                    </a></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Descrizione con espandibilità -->
                        <div class="mb-4">
                            <h4 class="mb-3">
                                <i class="fas fa-align-left me-2"></i>Descrizione del Progetto
                            </h4>
                            <div class="bg-light p-4 rounded position-relative">
                                <p class="mb-0 lead description-text"><?php echo nl2br(sanitize_output($project['descrizione'])); ?></p>
                                <button class="btn btn-sm btn-outline-primary position-absolute bottom-0 end-0 m-2" id="expandDescription"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Espandi descrizione"
                                        style="display: none;">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Timeline con animazioni -->
                        <div class="mb-4">
                            <h4 class="mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>Timeline del Progetto
                            </h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="bg-light p-3 rounded text-center animate-stagger-1">
                                        <i class="fas fa-calendar-plus text-success fa-2x mb-2"></i>
                                        <h6 class="mb-1">Data Creazione</h6>
                                        <p class="mb-0 text-muted"><?php echo format_date($project['data_inserimento']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="bg-light p-3 rounded text-center animate-stagger-2">
                                        <i class="fas fa-flag-checkered text-warning fa-2x mb-2"></i>
                                        <h6 class="mb-1">Scadenza</h6>
                                        <p class="mb-0 text-muted"><?php echo format_date($project['data_limite']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commenti -->
                <?php if (!empty($commenti)): ?>
                <div class="card-bostarter animate-fade-up">
                    <div class="card-header-bostarter">
                        <h4 class="mb-0">
                            <i class="fas fa-comments me-2"></i>Commenti (<?php echo count($commenti); ?>)
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($commenti as $commento): ?>
                        <div class="comment-card">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="comment-author">
                                        <?php echo sanitize_output($commento['autore_nickname']); ?>
                                    </div>
                                    <div class="comment-date">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo format_date($commento['data_creazione']); ?>
                                    </div>
                                    <div class="comment-content">
                                        <?php echo nl2br(sanitize_output($commento['testo'])); ?>
                                    </div>

                                    <?php if (!empty($commento['risposta_testo'])): ?>
                                    <div class="bg-light p-3 rounded mt-3 border-start border-primary border-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-reply text-primary me-2"></i>
                                            <strong class="text-primary">Risposta del Creatore</strong>
                                            <small class="text-muted ms-auto">
                                                <?php echo format_date($commento['risposta_data']); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(sanitize_output($commento['risposta_testo'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sostenitori Recenti -->
                <?php if (!empty($finanziamenti)): ?>
                <div class="card-bostarter animate-fade-up">
                    <div class="card-header-bostarter">
                        <h4 class="mb-0">
                            <i class="fas fa-heart me-2"></i>Sostenitori Recenti
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach (array_slice($finanziamenti, 0, 6) as $finanziamento): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?php echo sanitize_output($finanziamento['finanziatore_nickname'] ?: 'Anonimo'); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            €<?php echo number_format($finanziamento['importo'], 2, ',', '.'); ?> -
                                            <?php echo format_date($finanziamento['data_finanziamento']); ?>
                                            <?php if ($finanziamento['reward_nome']): ?>
                                            <br><span class="badge bg-primary"><?php echo sanitize_output($finanziamento['reward_nome']); ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    <!-- Statistiche -->
                    <div class="card-bostarter mb-4 animate-fade-right">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-chart-line me-2"></i>Statistiche Progetto
                            </h5>

                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Progresso Raccolta</span>
                                    <span class="fw-bold"><?php echo number_format($progress, 1); ?>%</span>
                                </div>
                                <div class="progress-bostarter">
                                    <div class="progress-bar progress-bar-bostarter" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number">€<?php echo number_format($project['totale_raccolto'], 0, ',', '.'); ?></div>
                                        <div class="stats-label">Raccolto</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number">€<?php echo number_format($project['budget_richiesto'], 0, ',', '.'); ?></div>
                                        <div class="stats-label">Obiettivo</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number"><?php echo $project['numero_sostenitori']; ?></div>
                                        <div class="stats-label">Sostenitori</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number"><?php echo $days_left; ?></div>
                                        <div class="stats-label">Giorni Restanti</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Azioni -->
                            <div class="d-grid gap-2">
                                <?php if ($project['stato'] === 'aperto'): ?>
                                    <?php if ($is_logged_in): ?>
                                        <button class="btn btn-bostarter btn-lg" data-bs-toggle="modal" data-bs-target="#supportModal">
                                            <i class="fas fa-hand-holding-heart me-2"></i>Supporta il Progetto
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-bostarter btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Accedi per Supportare
                                        </a>
                                    <?php endif; ?>

                                    <button class="btn btn-outline-bostarter" onclick="shareProject()">
                                        <i class="fas fa-share-alt me-2"></i>Condividi
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-lock me-2"></i>Progetto Chiuso
                                    </button>
                                <?php endif; ?>

                                <button class="btn btn-outline-bostarter" onclick="toggleFavorite()">
                                    <i class="far fa-bookmark me-2"></i>Salva nei Preferiti
                                </button>
                            </div>

                            <!-- Condividi -->
                            <div class="mt-3 text-center">
                                <small class="text-muted">Condividi questo progetto</small>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="shareOnFacebook()">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info rounded-circle" onclick="shareOnTwitter()">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="shareOnWhatsApp()">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-dark rounded-circle" onclick="copyLink()">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Creatore Info -->
                    <div class="card-bostarter animate-fade-right">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <h6 class="mb-1"><?php echo sanitize_output($project['creatore_nome'] . ' ' . $project['creatore_cognome']); ?></h6>
                            <p class="text-muted mb-3">@<?php echo sanitize_output($project['creatore_nickname']); ?></p>
                            <button class="btn btn-outline-bostarter btn-sm">
                                <i class="fas fa-envelope me-1"></i>Contatta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal Supporto -->
    <?php if ($is_logged_in && $project && $project['stato'] === 'aperto'): ?>
    <div class="modal fade modal-bostarter" id="supportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-hand-holding-heart me-2"></i>Supporta "<?php echo sanitize_output($project['titolo']); ?>"
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="supportForm" action="process_finanziamento.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="progetto_id" value="<?php echo $project_id; ?>">

                        <div class="mb-4">
                            <label for="importo" class="form-label fw-bold">Importo del Supporto (€)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">€</span>
                                <input type="number" class="form-control" id="importo" name="importo" min="5" step="5" value="50" required>
                            </div>
                            <div class="form-text">Importo minimo: €5</div>
                        </div>

                        <?php if (!empty($ricompense)): ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Scegli una Ricompensa (Opzionale)</label>
                            <div class="row">
                                <?php foreach ($ricompense as $ricompensa): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="reward-card border rounded p-3 h-100" data-min-importo="<?php echo $ricompensa['importo_minimo']; ?>">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="ricompensa_id"
                                                   value="<?php echo $ricompensa['id']; ?>"
                                                   id="reward_<?php echo $ricompensa['id']; ?>">
                                            <label class="form-check-label w-100" for="reward_<?php echo $ricompensa['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <strong>€<?php echo number_format($ricompensa['importo_minimo'], 0, ',', '.'); ?>+</strong>
                                                    <?php if ($ricompensa['quantita_rimanente'] !== null): ?>
                                                    <small class="text-muted">
                                                        <?php echo $ricompensa['quantita_rimanente']; ?>/<?php echo $ricompensa['quantita_disponibile']; ?> rimasti
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                                <h6 class="mb-1"><?php echo sanitize_output($ricompensa['nome']); ?></h6>
                                                <p class="small text-muted mb-0"><?php echo sanitize_output($ricompensa['descrizione']); ?></p>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="messaggio" class="form-label fw-bold">Messaggio di Supporto (Opzionale)</label>
                            <textarea class="form-control" id="messaggio" name="messaggio" rows="3"
                                      placeholder="Scrivi un messaggio di incoraggiamento al creatore del progetto"></textarea>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="anonimo" name="anonimo">
                                <label class="form-check-label" for="anonimo">
                                    <i class="fas fa-user-secret me-2"></i>Supporto anonimo (il tuo nome non verrà mostrato pubblicamente)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annulla
                    </button>
                    <button type="submit" form="supportForm" class="btn btn-bostarter">
                        <i class="fas fa-credit-card me-2"></i>Procedi al Pagamento
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript Avanzato -->
    <script>
        // ==========================================
        // CONFIGURAZIONE INIZIALE
        // ==========================================

        // Inizializza quando il DOM è pronto
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            initializeTooltips();
            initializeAnimations();
            initializeDescriptionToggle();
            initializeRewardValidation();
            initializeKeyboardShortcuts();
            initializePerformanceOptimizations();
        });

        // ==========================================
        // DARK MODE SYSTEM
        // ==========================================

        function initializeTheme() {
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;

            // Carica tema salvato
            const savedTheme = localStorage.getItem('bostarter-theme') || 'light';
            html.setAttribute('data-bs-theme', savedTheme);

            if (savedTheme === 'dark') {
                themeToggle.classList.remove('light');
                themeToggle.classList.add('dark');
            }

            // Event listener per toggle
            themeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                html.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('bostarter-theme', newTheme);

                // Aggiorna classe toggle
                themeToggle.classList.toggle('light');
                themeToggle.classList.toggle('dark');

                // Notifica utente
                showToast(
                    newTheme === 'dark' ? '🌙 Modalità scura attivata' : '☀️ Modalità chiara attivata',
                    'info',
                    2000
                );
            });
        }

        // ==========================================
        // TOOLTIPS E ACCESSIBILITÀ
        // ==========================================

        function initializeTooltips() {
            // Inizializza tutti i tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    delay: { show: 300, hide: 100 },
                    html: true,
                    container: 'body'
                });
            });
        }

        // ==========================================
        // ANIMAZIONI AVANZATE
        // ==========================================

        function initializeAnimations() {
            // Intersection Observer per animazioni on-scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Osserva elementi da animare
            document.querySelectorAll('.animate-fade-up, .animate-fade-left, .animate-fade-right').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                observer.observe(el);
            });

            // Animazione progress bar
            const progressBar = document.querySelector('.progress-bar-bostarter');
            if (progressBar) {
                const progressValue = progressBar.style.width;
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = progressValue;
                }, 500);
            }
        }

        // ==========================================
        // DESCRIZIONE ESPANDIBILE
        // ==========================================

        function initializeDescriptionToggle() {
            const descriptionText = document.querySelector('.description-text');
            const expandButton = document.getElementById('expandDescription');

            if (descriptionText && expandButton) {
                // Controlla se la descrizione è troppo lunga
                if (descriptionText.scrollHeight > descriptionText.clientHeight) {
                    expandButton.style.display = 'block';

                    expandButton.addEventListener('click', function() {
                        const isExpanded = descriptionText.classList.contains('expanded');

                        if (isExpanded) {
                            descriptionText.classList.remove('expanded');
                            expandButton.innerHTML = '<i class="fas fa-chevron-down"></i>';
                            expandButton.setAttribute('data-bs-original-title', 'Espandi descrizione');
                        } else {
                            descriptionText.classList.add('expanded');
                            expandButton.innerHTML = '<i class="fas fa-chevron-up"></i>';
                            expandButton.setAttribute('data-bs-original-title', 'Comprimi descrizione');
                        }

                        // Aggiorna tooltip
                        const tooltip = bootstrap.Tooltip.getInstance(expandButton);
                        if (tooltip) {
                            tooltip.dispose();
                            new bootstrap.Tooltip(expandButton);
                        }
                    });
                }
            }
        }

        // ==========================================
        // VALIDAZIONE RICOMPENSE
        // ==========================================

        function initializeRewardValidation() {
            const importoInput = document.getElementById('importo');
            const rewardCards = document.querySelectorAll('.reward-card');

            if (importoInput) {
                // Debounced input handler
                let timeout;
                importoInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        const importo = parseFloat(this.value) || 0;
                        updateRewardAvailability(importo, rewardCards);
                    }, 150);
                });

                // Inizializza stato iniziale
                const initialImporto = parseFloat(importoInput.value) || 0;
                updateRewardAvailability(initialImporto, rewardCards);
            }
        }

        function updateRewardAvailability(importo, rewardCards) {
            rewardCards.forEach(card => {
                const minImporto = parseFloat(card.dataset.minImporto);
                const radioInput = card.querySelector('input[type="radio"]');

                if (importo >= minImporto) {
                    card.classList.remove('opacity-50');
                    radioInput.disabled = false;
                    card.style.cursor = 'pointer';
                } else {
                    card.classList.add('opacity-50');
                    radioInput.disabled = true;
                    radioInput.checked = false;
                    card.style.cursor = 'not-allowed';
                }
            });
        }

        // ==========================================
        // SHORTCUTS TASTIERA
        // ==========================================

        function initializeKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // ESC per chiudere modal
                if (e.key === 'Escape') {
                    const modal = document.querySelector('.modal.show');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }
                }

                // Ctrl/Cmd + K per focus search (se presente)
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    // Implementare focus search se presente
                }

                // T per toggle tema
                if (e.key === 't' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    document.getElementById('themeToggle').click();
                }
            });
        }

        // ==========================================
        // OTTIMIZZAZIONI PERFORMANCE
        // ==========================================

        function initializePerformanceOptimizations() {
            // Lazy loading per immagini (se necessario)
            const images = document.querySelectorAll('img[data-src]');
            if (images.length > 0) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('loading-shimmer');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                images.forEach(img => {
                    img.classList.add('loading-shimmer');
                    imageObserver.observe(img);
                });
            }

            // Debounced scroll handler per animazioni
            let scrollTimeout;
            window.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    // Logica scroll se necessaria
                }, 16); // ~60fps
            });
        }

        // ==========================================
        // UTILITÀ E HELPER FUNCTIONS
        // ==========================================

        // Sistema di toast notifications
        function showToast(message, type = 'info', duration = 3000) {
            // Rimuovi toast esistenti
            const existingToasts = document.querySelectorAll('.toast-bostarter');
            existingToasts.forEach(toast => toast.remove());

            // Crea nuovo toast
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.innerHTML = `
                <div class="toast toast-bostarter align-items-center text-white border-0 show"
                     role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${getIconForType(type)} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            // Applica classe colore
            const toast = toastContainer.querySelector('.toast');
            toast.classList.add(`toast-${type}`);

            // Aggiungi al DOM
            document.body.appendChild(toastContainer);

            // Inizializza e mostra
            const bsToast = new bootstrap.Toast(toast, {
                delay: duration
            });
            bsToast.show();

            // Rimuovi dal DOM dopo la chiusura
            toast.addEventListener('hidden.bs.toast', () => {
                toastContainer.remove();
            });
        }

        function getIconForType(type) {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-triangle',
                warning: 'exclamation-circle',
                info: 'info-circle'
            };
            return icons[type] || 'info-circle';
        }

        // Utility per debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Utility per throttle
        function throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }

        // ==========================================
        // FUNZIONI ESISTENTI MIGLIORATE
        // ==========================================

        // Funzioni di condivisione migliorate
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);

            try {
                window.open(
                    `https://www.facebook.com/sharer/sharer.php?u=${url}&t=${title}`,
                    'facebook-share',
                    'width=600,height=400,scrollbars=yes,resizable=yes'
                );
                showToast('Post condiviso su Facebook!', 'success');
            } catch (e) {
                showToast('Errore nella condivisione', 'error');
            }
        }

        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(`Scopri questo progetto: ${document.title}`);

            try {
                window.open(
                    `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
                    'twitter-share',
                    'width=600,height=400,scrollbars=yes,resizable=yes'
                );
                showToast('Tweet creato!', 'success');
            } catch (e) {
                showToast('Errore nella condivisione', 'error');
            }
        }

        function shareOnWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(`Scopri questo progetto: ${document.title} ${url}`);

            try {
                window.open(
                    `https://wa.me/?text=${text}`,
                    'whatsapp-share'
                );
                showToast('Messaggio WhatsApp creato!', 'success');
            } catch (e) {
                showToast('Errore nella condivisione', 'error');
            }
        }

        async function copyLink() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                showToast('Link copiato negli appunti!', 'success');
            } catch (err) {
                // Fallback per browser vecchi
                const textArea = document.createElement('textarea');
                textArea.value = window.location.href;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Link copiato negli appunti!', 'success');
            }
        }

        function shareProject() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: 'Scopri questo progetto su BOSTARTER',
                    url: window.location.href
                }).then(() => {
                    showToast('Contenuto condiviso!', 'success');
                }).catch(() => {
                    copyLink();
                });
            } else {
                copyLink();
            }
        }

        // Toggle preferiti migliorato
        function toggleFavorite() {
            const btn = event.target.closest('button');
            const icon = btn.querySelector('i');

            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.innerHTML = '<i class="fas fa-bookmark me-2"></i>Rimosso dai Preferiti';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-bostarter');

                // Simula chiamata AJAX
                setTimeout(() => {
                    showToast('Aggiunto ai preferiti!', 'success');
                }, 300);
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.innerHTML = '<i class="far fa-bookmark me-2"></i>Salva nei Preferiti';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-bostarter');

                // Simula chiamata AJAX
                setTimeout(() => {
                    showToast('Rimosso dai preferiti!', 'info');
                }, 300);
            }
        }

        // Conferma chiusura progetto migliorata
        function confirmCloseProject() {
            // Crea modal di conferma personalizzata
            const confirmModal = document.createElement('div');
            confirmModal.className = 'modal fade modal-bostarter';
            confirmModal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Conferma Chiusura Progetto
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                                <h6>Sei sicuro di voler chiudere questo progetto?</h6>
                                <p class="text-muted mb-0">
                                    Questa azione non può essere annullata. Gli utenti non potranno più supportare il progetto.
                                </p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-arrow-left me-2"></i>Annulla
                            </button>
                            <button type="button" class="btn btn-danger" onclick="closeProject()">
                                <i class="fas fa-times me-2"></i>Chiudi Progetto
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(confirmModal);
            const modal = new bootstrap.Modal(confirmModal);
            modal.show();

            // Rimuovi modal dal DOM quando si chiude
            confirmModal.addEventListener('hidden.bs.modal', () => {
                confirmModal.remove();
            });
        }

        function closeProject() {
            // Simula chiusura progetto
            showToast('Progetto chiuso con successo!', 'success');

            // Chiudi modal
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            }

            // Ricarica pagina dopo un delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }

        // ==========================================
        // GESTIONE ERRORI E LOGGING
        // ==========================================

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            showToast('Si è verificato un errore. Riprova.', 'error');
        });

        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
            showToast('Si è verificato un errore. Riprova.', 'error');
        });

        // Performance monitoring
        if ('performance' in window && 'PerformanceObserver' in window) {
            try {
                // Monitora caricamento immagini
                const imageObserver = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (entry.duration > 1000) {
                            console.warn('Immagine lenta:', entry.name, entry.duration + 'ms');
                        }
                    });
                });
                imageObserver.observe({ entryTypes: ['resource'] });
            } catch (e) {
                console.log('Performance monitoring non supportato');
            }
        }
    </script>
</body>
</html>