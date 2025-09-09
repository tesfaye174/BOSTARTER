<?php
/**
 * BOSTARTER - Gestione Skill Utente
 * Pagina per gestire le competenze dell'utente
 */
require_once __DIR__ . '/includes/init.php';

// Verifica login
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

$page_title = 'Le Mie Competenze';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Gestione POST per aggiungere/rimuovere skill
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            $_SESSION['flash_error'] = 'Token CSRF non valido.';
            header('Location: skill.php');
            exit;
        }
        
        if ($_POST['action'] === 'add_skill') {
            $competenza_id = intval($_POST['competenza_id']);
            $livello = intval($_POST['livello']);
            
            if ($livello < 1 || $livello > 5) {
                $_SESSION['flash_error'] = 'Il livello deve essere compreso tra 1 e 5.';
            } else {
                // Controlla se già presente
                $stmt = $conn->prepare("SELECT id FROM utenti_competenze WHERE utente_id = ? AND competenza_id = ?");
                $stmt->execute([$_SESSION['user_id'], $competenza_id]);
                
                if ($stmt->fetch()) {
                    // Aggiorna livello
                    $stmt = $conn->prepare("UPDATE utenti_competenze SET livello = ? WHERE utente_id = ? AND competenza_id = ?");
                    $stmt->execute([$livello, $_SESSION['user_id'], $competenza_id]);
                    $_SESSION['flash_success'] = 'Livello competenza aggiornato!';
                } else {
                    // Inserisci nuova skill
                    $stmt = $conn->prepare("INSERT INTO utenti_competenze (utente_id, competenza_id, livello) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $competenza_id, $livello]);
                    $_SESSION['flash_success'] = 'Competenza aggiunta con successo!';
                }
            }
        } elseif ($_POST['action'] === 'remove_skill') {
            $competenza_id = intval($_POST['competenza_id']);
            $stmt = $conn->prepare("DELETE FROM utenti_competenze WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$_SESSION['user_id'], $competenza_id]);
            $_SESSION['flash_success'] = 'Competenza rimossa!';
        }
        
        header('Location: skill.php');
        exit;
    }
    
    // Carica le skill dell'utente
    $stmt = $conn->prepare(
        "SELECT uc.*, c.nome, c.descrizione 
        FROM utenti_competenze uc 
        JOIN competenze c ON uc.competenza_id = c.id 
        WHERE uc.utente_id = ? 
        ORDER BY c.nome"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user_skills = $stmt->fetchAll();
    
    // Carica tutte le competenze disponibili
    $stmt = $conn->query("SELECT * FROM competenze ORDER BY nome");
    $all_competenze = $stmt->fetchAll();
    
    // Crea array delle competenze già possedute
    $possessed_skills = array_column($user_skills, 'competenza_id');
    
} catch (Exception $e) {
    error_log('Errore skill.php: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Si è verificato un errore. Riprova più tardi.';
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <?php $page_title = 'Le Mie Competenze'; include __DIR__ . '/includes/head.php'; ?>
    <link href="css/skill.css" rel="stylesheet">
    <meta name="csrf-token" content="<?= generate_csrf_token() ?>">
</head>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-6 fw-bold text-gradient-primary mb-2">
                    <i class="fas fa-tools me-2"></i>Le Mie Competenze
                </h1>
                <p class="text-muted lead">Gestisci il tuo profilo professionale aggiungendo e aggiornando le tue
                    competenze</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8 order-2 order-lg-1">
                <div class="card content-card">
                    <div class="card-body p-4">
                        <div class="skills-container">
                            <?php if (empty($user_skills)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                <h4 class="mb-3">Inizia ad aggiungere le tue competenze</h4>
                                <p class="text-muted mb-4">Le competenze ti aiutano a trovare progetti più adatti al tuo
                                    profilo e aumentano la tua visibilità.</p>
                                <button class="btn btn-primary btn-lg"
                                    onclick="document.getElementById('competenza_id').focus()">
                                    <i class="fas fa-plus me-2"></i>Aggiungi la prima competenza
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($user_skills as $skill): ?>
                                <div class="col-md-6" data-skill-id="<?= $skill['competenza_id'] ?>">
                                    <div class="skill-card card h-100">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h5 class="card-title mb-0">
                                                    <?= htmlspecialchars($skill['nome']) ?>
                                                </h5>
                                                <button class="btn btn-link text-danger p-0 skill-remove remove-skill"
                                                    data-skill-id="<?= $skill['competenza_id'] ?>"
                                                    data-skill-name="<?= htmlspecialchars($skill['nome']) ?>"
                                                    data-bs-toggle="tooltip" title="Rimuovi competenza">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>

                                            <p class="card-text text-muted small mb-3">
                                                <?= htmlspecialchars($skill['descrizione'] ?? '') ?>
                                            </p>

                                            <div class="level-badge">
                                                <span class="skill-level">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i
                                                        class="fas fa-star skill-star <?= $i <= $skill['livello'] ? 'active' : '' ?>"></i>
                                                    <?php endfor; ?>
                                                </span>
                                                <span class="ms-2">
                                                    <?php
                                                            $livelli = ['Principiante', 'Base', 'Intermedio', 'Avanzato', 'Esperto'];
                                                            echo $livelli[$skill['livello'] - 1];
                                                            ?>
                                                </span>
                                            </div>
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

            <div class="col-lg-4 order-1 order-lg-2">
                <div class="card content-card sticky-top" style="top: 2rem;">
                    <div class="card-header border-0 bg-transparent pt-4">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Aggiungi Competenza</h5>
                    </div>
                    <div class="card-body">
                        <form id="addSkillForm" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="add_skill">

                            <div class="mb-4">
                                <label for="competenza_id" class="form-label">Competenza</label>
                                <select name="competenza_id" id="competenza_id" class="form-select" required>
                                    <option value="">Seleziona una competenza...</option>
                                    <?php foreach ($all_competenze as $competenza): ?>
                                    <?php if (!in_array($competenza['id'], $possessed_skills)): ?>
                                    <option value="<?= $competenza['id'] ?>"
                                        data-description="<?= htmlspecialchars($competenza['descrizione'] ?? '') ?>">
                                        <?= htmlspecialchars($competenza['nome']) ?>
                                    </option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Seleziona una competenza</div>
                            </div>

                            <div class="mb-4">
                                <label for="livello" class="form-label">Livello di Competenza</label>
                                <select name="livello" id="livello" class="form-select" required>
                                    <option value="">Seleziona il tuo livello...</option>
                                    <option value="1">⭐ Principiante - Conoscenza base</option>
                                    <option value="2">⭐⭐ Base - Applicazione pratica</option>
                                    <option value="3">⭐⭐⭐ Intermedio - Buona padronanza</option>
                                    <option value="4">⭐⭐⭐⭐ Avanzato - Esperienza significativa</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Esperto - Padronanza completa</option>
                                </select>
                                <div class="invalid-feedback">Seleziona il tuo livello</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-plus me-2"></i>Aggiungi Competenza
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="small text-muted">
                            <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Perché aggiungere competenze?
                            </h6>
                            <ul class="mb-0 ps-3">
                                <li class="mb-2">Aumenta la visibilità del tuo profilo</li>
                                <li class="mb-2">Trova progetti adatti alle tue capacità</li>
                                <li>Dimostra la tua esperienza ai creatori</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <?php include __DIR__ . '/includes/scripts.php'; ?>
    <script src="js/skill.js"></script>
    </div>
    </div>
    </div>
    </div>
    </div>

    <?php include __DIR__ . '/includes/scripts.php'; ?>
</body>

</html>