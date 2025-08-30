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
                $stmt = $conn->prepare("SELECT id FROM skill_utente WHERE utente_id = ? AND competenza_id = ?");
                $stmt->execute([$_SESSION['user_id'], $competenza_id]);
                
                if ($stmt->fetch()) {
                    // Aggiorna livello
                    $stmt = $conn->prepare("UPDATE skill_utente SET livello = ? WHERE utente_id = ? AND competenza_id = ?");
                    $stmt->execute([$livello, $_SESSION['user_id'], $competenza_id]);
                    $_SESSION['flash_success'] = 'Livello competenza aggiornato!';
                } else {
                    // Inserisci nuova skill
                    $stmt = $conn->prepare("INSERT INTO skill_utente (utente_id, competenza_id, livello) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $competenza_id, $livello]);
                    $_SESSION['flash_success'] = 'Competenza aggiunta con successo!';
                }
            }
        } elseif ($_POST['action'] === 'remove_skill') {
            $competenza_id = intval($_POST['competenza_id']);
            $stmt = $conn->prepare("DELETE FROM skill_utente WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$_SESSION['user_id'], $competenza_id]);
            $_SESSION['flash_success'] = 'Competenza rimossa!';
        }
        
        header('Location: skill.php');
        exit;
    }
    
    // Carica le skill dell'utente
    $stmt = $conn->prepare("
        SELECT su.*, c.nome, c.descrizione 
        FROM skill_utente su 
        JOIN competenze c ON su.competenza_id = c.id 
        WHERE su.utente_id = ? 
        ORDER BY c.nome
    ");
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
<?php include __DIR__ . '/includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-tools"></i> Le Mie Competenze</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_skills)): ?>
                            <div class="alert alert-info">
                                <h5>Non hai ancora aggiunto competenze!</h5>
                                <p>Le competenze ti aiutano a trovare progetti più adatti al tuo profilo e aumentano la tua credibilità presso i creatori di progetti.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($user_skills as $skill): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-left-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title"><?= htmlspecialchars($skill['nome']) ?></h6>
                                                        <p class="card-text text-muted small">
                                                            <?= htmlspecialchars($skill['descrizione'] ?? '') ?>
                                                        </p>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Livello:</small>
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star <?= $i <= $skill['livello'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="action" value="remove_skill">
                                                        <input type="hidden" name="competenza_id" value="<?= $skill['competenza_id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                onclick="return confirm('Rimuovere questa competenza?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Aggiungi Competenza</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="add_skill">
                            
                            <div class="mb-3">
                                <label for="competenza_id" class="form-label">Competenza</label>
                                <select name="competenza_id" id="competenza_id" class="form-select" required>
                                    <option value="">Seleziona una competenza...</option>
                                    <?php foreach ($all_competenze as $competenza): ?>
                                        <?php if (!in_array($competenza['id'], $possessed_skills)): ?>
                                            <option value="<?= $competenza['id'] ?>">
                                                <?= htmlspecialchars($competenza['nome']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
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
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> Aggiungi
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> Info</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            Le competenze ti aiutano a:
                        </p>
                        <ul class="small text-muted">
                            <li>Candidarti per ruoli specifici nei progetti</li>
                            <li>Aumentare la tua credibilità</li>
                            <li>Essere trovato dai creatori di progetti</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>
