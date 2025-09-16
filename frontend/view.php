<?php
/**
 * BOSTARTER - Visualizzazione Progetto
 *
 * Questa pagina mostra i dettagli completi di un progetto specifico,
 * inclusi finanziamenti, commenti, reward disponibili e informazioni
 * sul creatore. Permette agli utenti di finanziare il progetto e
 * lasciare commenti.
 *
 * Funzionalità implementate:
 * - Visualizzazione dettagliata progetto
 * - Lista finanziamenti ricevuti
 * - Sistema commenti e risposte
 * - Selezione reward per finanziamento
 * - Progress bar finanziamento
 * - Informazioni creatore
 *
 * Sicurezza:
 * - Validazione ID progetto
 * - Sanitizzazione input
 * - Protezione SQL injection
 * - Controllo permessi per operazioni
 *
 * @author BOSTARTER Development Team
 * @version 1.0
 * @since 2025
 */

// Avvia la sessione
session_start();

/**
 * Verifica se l'utente è autenticato
 * @return bool True se l'utente è loggato, false altrimenti
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Validazione e recupero ID progetto dalla URL
$project_id = $_GET["id"] ?? null;
$error = "";
$project = null;
$finanziamenti = [];
$redirect_to_home = false;

// Validazione ID progetto
if (!$project_id) {
    $error = "Nessun progetto specificato.";
    $redirect_to_home = true;
} elseif (!is_numeric($project_id) || $project_id <= 0) {
    $error = "ID progetto non valido. L'ID deve essere un numero positivo.";
    $redirect_to_home = true;
} else {
    $project_id = filter_var($project_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($project_id === false) {
        $error = "ID progetto non valido. Formato non corretto.";
        $redirect_to_home = true;
    } else {
        // Connessione al database e recupero dati progetto
        try {
            require_once "../backend/config/database.php";
            $db = Database::getInstance();

            // IMPORTANTE: Chiudi eventuali result set pendenti
            while ($db->query('SELECT 1')) {
                // Consuma eventuali result set pendenti
                break;
            }
            
            $query = "
                SELECT p.*, u.nickname as creatore_nickname,
                       p.stato as tipo_progetto,
                       p.data_limite as data_scadenza
                FROM progetti p
                LEFT JOIN utenti u ON p.creatore_id = u.id
                WHERE p.id = ?
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            if ($project) {
                // Calcola statistiche
                $query_stats = "
                    SELECT 
                        COALESCE(SUM(importo), 0) as totale_raccolto,
                        COUNT(DISTINCT utente_id) as numero_sostenitori,
                        COUNT(id) as numero_finanziamenti
                    FROM finanziamenti 
                    WHERE progetto_id = ?
                ";
                $stmt_stats = $conn->prepare($query_stats);
                $stmt_stats->execute([$project_id]);
                $stats = $stmt_stats->fetch();
                
                $project['totale_raccolto'] = $stats['totale_raccolto'] ?? 0;
                $project['numero_sostenitori'] = $stats['numero_sostenitori'] ?? 0;
                $project['numero_finanziamenti'] = $stats['numero_finanziamenti'] ?? 0;
                
                // Carica finanziamenti
                try {
                    $query_finanziamenti = "
                        SELECT f.*, u.nickname as finanziatore_nickname 
                        FROM finanziamenti f 
                        LEFT JOIN utenti u ON f.utente_id = u.id 
                        WHERE f.progetto_id = ? 
                        ORDER BY f.data_finanziamento DESC
                    ";
                    $stmt_fin = $db->prepare($query_finanziamenti);
                    $stmt_fin->execute([$project_id]);
                    $finanziamenti = $stmt_fin->fetchAll();
                } catch(Exception $e) {
                    $finanziamenti = [];
                }

                // Carica ricompense
                try {
                    $query_ricompense = "
                        SELECT id, nome as descrizione, importo_minimo,
                               NULL as disponibili_totali, NULL as disponibili_rimanenti
                        FROM rewards
                        WHERE progetto_id = ?
                        ORDER BY importo_minimo ASC
                    ";
                    $stmt_ric = $db->prepare($query_ricompense);
                    $stmt_ric->execute([$project_id]);
                    $ricompense = $stmt_ric->fetchAll();
                } catch(Exception $e) {
                    $ricompense = [];
                }
            } else {
                $error = "Progetto non trovato. Il progetto con ID $project_id potrebbe essere stato rimosso o non esistere.";
                $redirect_to_home = true;
            }
        } catch(Exception $e) {
            $error = "Errore nel caricamento del progetto: " . $e->getMessage();
        }
    }
}

$is_logged_in = isset($_SESSION["user_id"]);
$progress = $project && $project["budget_richiesto"] > 0 ? min(100, ($project["totale_raccolto"] / $project["budget_richiesto"]) * 100) : 0;
$days_left = $project && $project["data_limite"] ? max(0, floor((strtotime($project["data_limite"]) - time()) / (60 * 60 * 24))) : 0;
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project ? htmlspecialchars($project['titolo']) : 'Progetto'; ?> - BOSTARTER</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- <?php include __DIR__ . '/includes/navbar.php'; ?> -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="home.php">BOSTARTER</a>
        </div>
    </nav>

    <main class="container py-4">
        <div class="container py-4">
            <?php if ($error): ?>
            <div class="alert alert-danger animate-fade-up">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <?php if ($redirect_to_home): ?>
                <div class="mt-3">
                    <a href="home.php" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i>Torna alla Homepage
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- Header Progetto -->
            <div class="row mb-4">
                <div class="col-12">
                    <nav aria-label="breadcrumb" class="animate-fade-left">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="index.php#progetti">Progetti</a></li>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($project["titolo"]) ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="row">
                <!-- Contenuto principale -->
                <div class="col-lg-8">
                    <div class="card-bostarter mb-4 animate-fade-up">
                        <img src="images/project-placeholder.jpg" class="card-img-top project-image"
                            alt="<?= htmlspecialchars($project["titolo"]); ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h1 class="text-gradient-bostarter"><?= htmlspecialchars($project["titolo"]) ?></h1>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-user me-2"></i>
                                        di <strong><?= htmlspecialchars($project["creatore_nickname"]) ?></strong>
                                    </p>
                                </div>
                                <span class="badge badge-bostarter">
                                    <i
                                        class="fas fa-<?= ($project["tipo_progetto"] ?? 'software') === "hardware" ? "microchip" : "code" ?> me-1"></i>
                                    <?= ucfirst($project["tipo_progetto"] ?? 'software') ?>
                                </span>
                            </div>
                            <div class="mb-4">
                                <h3>Descrizione del progetto</h3>
                                <p class="lead"><?= nl2br(htmlspecialchars($project["descrizione"])) ?></p>
                            </div>
                            <?php if (!empty($project["categoria"])): ?>
                            <div class="mb-4">
                                <h3>Categoria</h3>
                                <div class="bg-light p-3 rounded">
                                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($project["categoria"]) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <!-- Timeline -->
                            <div class="mb-4">
                                <h3>Timeline del progetto</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-calendar-plus text-success me-3"></i>
                                            <div>
                                                <strong>Avvio:</strong><br>
                                                <small
                                                    class="text-muted"><?= date("d/m/Y", strtotime($project["data_inserimento"])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-flag-checkered text-warning me-3"></i>
                                            <div>
                                                <strong>Scadenza:</strong><br>
                                                <small class="text-muted">
                                                    <?= $project["data_scadenza"] ? date("d/m/Y", strtotime($project["data_scadenza"])) : "Non specificata" ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sostenitori -->
                    <?php if (!empty($finanziamenti)): ?>
                    <div class="card-bostarter animate-fade-up">
                        <div class="card-body">
                            <h3 class="mb-4">
                                <i class="fas fa-heart text-danger me-2"></i>
                                Sostenitori recenti
                            </h3>
                            <div class="row">
                                <?php foreach ($finanziamenti as $finanziamento): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 avatar-sm">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($finanziamento["finanziatore_nickname"]) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                €<?= number_format($finanziamento["importo"], 2, ',', '.') ?> -
                                                <?= date("d/m/Y", strtotime($finanziamento["data_finanziamento"])) ?>
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
                    <div class="card-bostarter sticky-top animate-fade-right sticky-top-bostarter">
                        <div class="card-body">
                            <!-- Progress -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Progresso</span>
                                    <span class="fw-bold"><?= number_format($progress, 1) ?>%</span>
                                </div>
                                <div class="progress-bostarter progress-thin">
                                    <div class="progress-bar progress-bar-bostarter"
                                        style="--progress: <?= $progress ?>%"></div>
                                </div>
                            </div>
                            <!-- Stats -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Raccolto</span>
                                    <span class="fw-bold">€<?= number_format($project["totale_raccolto"], 0, ',', '.') ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Obiettivo</span>
                                    <span class="fw-bold">€<?= number_format($project["budget_richiesto"], 0, ',', '.') ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Sostenitori</span>
                                    <span class="fw-bold"><?= $project["numero_sostenitori"] ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Giorni rimasti</span>
                                    <span class="fw-bold"><?= $days_left ?></span>
                                </div>
                            </div>
                            <!-- Azioni -->
                            <div class="d-grid gap-2">
                                <?php if ($project["stato"] === "aperto"): ?>
                                    <?php if ($is_logged_in): ?>
                                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#supportModal">
                                            <i class="fas fa-hand-holding-heart me-2"></i>Supporta il progetto
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Accedi per supportare
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-lock me-2"></i>Progetto chiuso
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-outline-secondary">
                                    <i class="far fa-bookmark me-2"></i>Salva tra i preferiti
                                </button>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <button class="btn btn-sm btn-outline-primary rounded-circle">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary rounded-circle">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary rounded-circle">
                                        <i class="fab fa-linkedin-in"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary rounded-circle">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <!-- Modal Bootstrap per supporto -->
    <?php if ($is_logged_in && $project && $project["stato"] === "aperto"): ?>
    <div class="modal fade modal-bostarter" id="supportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supporta il progetto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <form id="supportForm" action="finanziamento.php" method="POST">
                        <input type="hidden" name="progetto_id" value="<?= $project["id"] ?>">
                        <div class="mb-3">
                            <label for="importo" class="form-label">Importo (€)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" class="form-control" id="importo" name="importo" min="5" step="5" 
                                       value="50" required>
                            </div>
                            <div class="form-text">Importo minimo: €5</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Scegli una ricompensa (opzionale)</label>
                            <div class="list-group">
                                <?php if (!empty($ricompense)): ?>
                                    <?php foreach ($ricompense as $ricompensa): ?>
                                        <label class="list-group-item">
                                            <input class="form-check-input me-2" type="radio" name="ricompensa_id" 
                                                   value="<?= $ricompensa["id"] ?>" 
                                                   data-min-importo="<?= $ricompensa["importo_minimo"] ?>">
                                            <div>
                                                <div class="fw-bold">€<?= number_format($ricompensa["importo_minimo"], 0, ',', '.') ?>+</div>
                                                <div class="small"><?= htmlspecialchars($ricompensa["descrizione"]) ?></div>
                                                <small class="text-muted">
                                                    <?= $ricompensa["disponibili_rimanenti"] === null ? 'Illimitate' : $ricompensa["disponibili_rimanenti"] . ' su ' . $ricompensa["disponibili_totali"] . ' rimaste' ?>
                                                </small>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted text-center py-3">
                                        Nessuna ricompensa disponibile
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="messaggio" class="form-label">Messaggio di supporto (opzionale)</label>
                            <textarea class="form-control" id="messaggio" name="messaggio" rows="3" 
                                      placeholder="Scrivi un messaggio di incoraggiamento al creatore del progetto"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="anonimo" name="anonimo">
                            <label class="form-check-label" for="anonimo">
                                Supporto anonimo (il tuo nome non verrà mostrato pubblicamente)
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" form="supportForm" class="btn btn-primary">
                        <i class="fas fa-credit-card me-2"></i>Procedi al pagamento
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- <?php endif; ?>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/scripts.php'; ?> -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>