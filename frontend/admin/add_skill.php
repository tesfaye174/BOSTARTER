<?php
/**
 * Pagina per la gestione delle competenze (solo per amministratori)
 */

session_start();
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: /frontend/auth/login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$mongoLogger = new MongoLogger();

// Verifica che l'utente sia amministratore
$stmt = $conn->prepare("SELECT tipo_utente FROM utenti WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['tipo_utente'] !== 'admin') {
    header('Location: /frontend/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Gestione aggiunta nuova competenza
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $nome = trim($_POST['nome'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $security_code = trim($_POST['security_code'] ?? '');
    
    // Validazioni
    if (empty($nome)) {
        $error = 'Il nome della competenza è obbligatorio.';
    } elseif (strlen($nome) < 2) {
        $error = 'Il nome deve contenere almeno 2 caratteri.';
    } elseif (strlen($nome) > 100) {
        $error = 'Il nome non può superare i 100 caratteri.';
    } elseif (empty($categoria)) {
        $error = 'La categoria è obbligatoria.';
    } elseif (empty($security_code)) {
        $error = 'Il codice di sicurezza è obbligatorio.';
    } elseif ($security_code !== 'ADMIN2024') {
        $error = 'Codice di sicurezza non valido.';
        
        // Log tentativo di accesso non autorizzato
        $mongoLogger->logActivity($_SESSION['user_id'], 'admin_security_code_failed', [
            'attempted_code' => $security_code,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } else {
        try {
            // Verifica se la competenza esiste già
            $stmt = $conn->prepare("SELECT id FROM competenze WHERE LOWER(nome) = LOWER(?)");
            $stmt->execute([$nome]);
            
            if ($stmt->fetch()) {
                $error = 'Una competenza con questo nome esiste già.';
            } else {
                // Inserisci la nuova competenza
                $stmt = $conn->prepare("
                    INSERT INTO competenze (nome, descrizione, categoria)
                    VALUES (?, ?, ?)
                ");
                
                $stmt->execute([$nome, $descrizione, $categoria]);
                  // Log MongoDB
                $mongoLogger->logActivity($_SESSION['user_id'], 'admin_add_skill', [
                    'skill_name' => $nome,
                    'category' => $categoria,
                    'security_code_verified' => true
                ]);
                
                $success = 'Competenza aggiunta con successo!';
                
                // Reset form
                $_POST = [];
            }
        } catch (Exception $e) {
            $error = 'Errore durante l\'inserimento: ' . $e->getMessage();
            
            $mongoLogger->logActivity($_SESSION['user_id'], 'admin_add_skill_error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Gestione eliminazione competenza
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $skill_id = intval($_POST['skill_id'] ?? 0);
    
    if ($skill_id) {
        try {
            // Verifica se la competenza è utilizzata in progetti
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM profili_software ps 
                WHERE ps.id IN (
                    SELECT DISTINCT profilo_id 
                    FROM competenze_richieste 
                    WHERE competenza_id = ?
                )
            ");
            $stmt->execute([$skill_id]);
            $usage = $stmt->fetch();
            
            if ($usage['count'] > 0) {
                $error = 'Impossibile eliminare: la competenza è utilizzata in ' . $usage['count'] . ' progetti.';
            } else {
                // Elimina la competenza
                $stmt = $conn->prepare("DELETE FROM competenze WHERE id = ?");
                $stmt->execute([$skill_id]);
                
                $mongoLogger->logActivity($_SESSION['user_id'], 'admin_delete_skill', [
                    'skill_id' => $skill_id
                ]);
                
                $success = 'Competenza eliminata con successo!';
            }
        } catch (Exception $e) {
            $error = 'Errore durante l\'eliminazione: ' . $e->getMessage();
        }
    }
}

// Recupera tutte le competenze con statistiche di utilizzo
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.nome,
        c.descrizione,
        c.categoria,
        c.data_creazione,
        COUNT(DISTINCT cr.profilo_id) as progetti_count
    FROM competenze c
    LEFT JOIN competenze_richieste cr ON c.id = cr.competenza_id
    LEFT JOIN profili_software ps ON cr.profilo_id = ps.id
    GROUP BY c.id, c.nome, c.descrizione, c.categoria, c.data_creazione
    ORDER BY c.categoria, c.nome
");

$stmt->execute();
$competenze = $stmt->fetchAll();

// Raggruppa per categoria
$categories = [];
foreach ($competenze as $competenza) {
    $categories[$competenza['categoria']][] = $competenza;
}

// Statistiche generali
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_skills,
        COUNT(DISTINCT categoria) as total_categories,
        COUNT(DISTINCT cr.profilo_id) as used_in_projects
    FROM competenze c
    LEFT JOIN competenze_richieste cr ON c.id = cr.competenza_id
");
$stmt->execute();
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Competenze - Admin - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/frontend/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/frontend/">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
                <span class="badge bg-light text-danger ms-2">ADMIN</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/frontend/dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="/frontend/auth/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/frontend/">Home</a></li>
                <li class="breadcrumb-item"><a href="/frontend/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Gestione Competenze</li>
            </ol>
        </nav>

        <!-- Header con Statistiche -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h1 class="h3 mb-3">
                            <i class="fas fa-cogs me-2"></i>
                            Gestione Competenze
                        </h1>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tools fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['total_skills']; ?></h4>
                                        <small>Competenze Totali</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tags fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['total_categories']; ?></h4>
                                        <small>Categorie</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-project-diagram fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['used_in_projects']; ?></h4>
                                        <small>Progetti Attivi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form Aggiunta Competenza -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            Aggiungi Competenza
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="addSkillForm">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">
                                    Nome Competenza <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="nome" 
                                    name="nome"
                                    maxlength="100"
                                    value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="categoria" class="form-label">
                                    Categoria <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Seleziona categoria</option>
                                    <option value="Programming" <?php echo ($_POST['categoria'] ?? '') == 'Programming' ? 'selected' : ''; ?>>Programming</option>
                                    <option value="Web Development" <?php echo ($_POST['categoria'] ?? '') == 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
                                    <option value="Mobile Development" <?php echo ($_POST['categoria'] ?? '') == 'Mobile Development' ? 'selected' : ''; ?>>Mobile Development</option>
                                    <option value="Database" <?php echo ($_POST['categoria'] ?? '') == 'Database' ? 'selected' : ''; ?>>Database</option>
                                    <option value="DevOps" <?php echo ($_POST['categoria'] ?? '') == 'DevOps' ? 'selected' : ''; ?>>DevOps</option>
                                    <option value="Design" <?php echo ($_POST['categoria'] ?? '') == 'Design' ? 'selected' : ''; ?>>Design</option>
                                    <option value="Testing" <?php echo ($_POST['categoria'] ?? '') == 'Testing' ? 'selected' : ''; ?>>Testing</option>
                                    <option value="Security" <?php echo ($_POST['categoria'] ?? '') == 'Security' ? 'selected' : ''; ?>>Security</option>
                                    <option value="AI/ML" <?php echo ($_POST['categoria'] ?? '') == 'AI/ML' ? 'selected' : ''; ?>>AI/ML</option>
                                    <option value="Other" <?php echo ($_POST['categoria'] ?? '') == 'Other' ? 'selected' : ''; ?>>Altro</option>
                                </select>
                            </div>                            <div class="mb-3">
                                <label for="descrizione" class="form-label">Descrizione</label>
                                <textarea 
                                    class="form-control" 
                                    id="descrizione" 
                                    name="descrizione"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Descrizione opzionale della competenza"
                                ><?php echo htmlspecialchars($_POST['descrizione'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <span id="descCharCount">0</span>/500 caratteri
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="security_code" class="form-label">
                                    Codice di Sicurezza <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="security_code" 
                                    name="security_code"
                                    maxlength="20"
                                    placeholder="Inserisci il codice di sicurezza amministrativo"
                                    required
                                >
                                <div class="form-text">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Richiesto per confermare l'aggiunta di nuove competenze
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>Aggiungi Competenza
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista Competenze -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Competenze Esistenti
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($competenze)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-tools fa-3x mb-3"></i>
                                <p>Nessuna competenza trovata.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($categories as $categoria => $skills): ?>
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2">
                                        <i class="fas fa-tag me-2"></i>
                                        <?php echo htmlspecialchars($categoria); ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($skills); ?></span>
                                    </h6>
                                    
                                    <div class="row">
                                        <?php foreach ($skills as $skill): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-start border-3 border-primary">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($skill['nome']); ?></h6>
                                                                <?php if ($skill['descrizione']): ?>
                                                                    <p class="text-muted small mb-1">
                                                                        <?php echo htmlspecialchars($skill['descrizione']); ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                                <div class="d-flex align-items-center text-muted small">
                                                                    <i class="fas fa-project-diagram me-1"></i>
                                                                    <?php echo $skill['progetti_count']; ?> progetti
                                                                    <span class="mx-2">•</span>
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    <?php echo date('d/m/Y', strtotime($skill['data_creazione'])); ?>
                                                                </div>
                                                            </div>
                                                            <div class="ms-2">
                                                                <?php if ($skill['progetti_count'] == 0): ?>
                                                                    <button 
                                                                        class="btn btn-sm btn-outline-danger"
                                                                        onclick="deleteSkill(<?php echo $skill['id']; ?>, '<?php echo htmlspecialchars($skill['nome']); ?>')"
                                                                        title="Elimina competenza"
                                                                    >
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success">In uso</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form nascosto per eliminazione -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="skill_id" id="deleteSkillId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const descrizioneInput = document.getElementById('descrizione');
            const descCharCount = document.getElementById('descCharCount');

            // Conteggio caratteri descrizione
            function updateDescCharCount() {
                const count = descrizioneInput.value.length;
                descCharCount.textContent = count;
                
                if (count > 450) {
                    descCharCount.className = 'text-warning fw-bold';
                } else {
                    descCharCount.className = '';
                }
            }

            descrizioneInput.addEventListener('input', updateDescCharCount);
            updateDescCharCount();
        });

        function deleteSkill(skillId, skillName) {
            if (confirm(`Sei sicuro di voler eliminare la competenza "${skillName}"?\n\nQuesta azione non può essere annullata.`)) {
                document.getElementById('deleteSkillId').value = skillId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
