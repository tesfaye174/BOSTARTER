<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/FrontendSecurity.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/app_config.php';

// Simple admin check
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo_utente'] ?? '') !== 'amministratore') {
    header('Location: ../auth/login.php');
    exit();
}
$db = Database::getInstance();
$conn = $db->getConnection();
$error = '';
$success = '';
$csrf_token = generate_csrf_token();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    if (!FrontendSecurity::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token di sicurezza non valido. Riprova.';
        FrontendSecurity::logSecurityEvent('csrf_token_invalid', ['action' => 'add_skill']);
    } else {
        if (!FrontendSecurity::checkRateLimit('add_skill', 3, 300)) {
            $error = 'Troppi tentativi. Riprova tra 5 minuti.';
            FrontendSecurity::logSecurityEvent('rate_limit_exceeded', ['action' => 'add_skill']);
        } else {
            $nome = FrontendSecurity::sanitizeInput($_POST['nome'] ?? '');
            $descrizione = FrontendSecurity::sanitizeInput($_POST['descrizione'] ?? '');
            $categoria = FrontendSecurity::sanitizeInput($_POST['categoria'] ?? '');
            $validationRules = [
                'nome' => [
                    'required' => true,
                    'min_length' => 2,
                    'max_length' => 50,
                    'pattern' => '/^[a-zA-Z0-9\s\-\+\#\.]+$/',
                    'error_messages' => [
                        'required' => 'Il nome della competenza è obbligatorio',
                        'min_length' => 'Il nome deve essere di almeno 2 caratteri',
                        'max_length' => 'Il nome non può superare i 50 caratteri',
                        'pattern' => 'Il nome contiene caratteri non validi'
                    ]
                ],
                'descrizione' => [
                    'required' => true,
                    'min_length' => 10,
                    'max_length' => 500,
                    'error_messages' => [
                        'required' => 'La descrizione è obbligatoria',
                        'min_length' => 'La descrizione deve essere di almeno 10 caratteri',
                        'max_length' => 'La descrizione non può superare i 500 caratteri'
                    ]
                ],
                'categoria' => [
                    'required' => true,
                    'in_array' => ['Programming', 'Design', 'Marketing', 'Business', 'Other'],
                    'error_messages' => [
                        'required' => 'La categoria è obbligatoria',
                        'in_array' => 'Categoria non valida'
                    ]
                ]
            ];
            $validationErrors = [];
            foreach ($validationRules as $field => $rules) {
                $value = $$field ?? '';
                if ($rules['required'] && empty($value)) {
                    $validationErrors[$field] = $rules['error_messages']['required'];
                    continue;
                }
                if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                    $validationErrors[$field] = $rules['error_messages']['min_length'];
                }
                if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    $validationErrors[$field] = $rules['error_messages']['max_length'];
                }
                if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                    $validationErrors[$field] = $rules['error_messages']['pattern'];
                }
                if (isset($rules['in_array']) && !in_array($value, $rules['in_array'])) {
                    $validationErrors[$field] = $rules['error_messages']['in_array'];
                }
            }
            if (empty($validationErrors)) {
                try {
                    $conn->beginTransaction();
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM competenze WHERE LOWER(nome) = LOWER(?) AND categoria = ?");
                    $stmt->execute([strtolower($nome), $categoria]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Una competenza con questo nome già esiste in questa categoria");
                    }
                    $stmt = $conn->prepare("
                        INSERT INTO competenze (nome, descrizione, categoria, data_creazione)
                        VALUES (?, ?, ?, NOW())
                    ");
                    if ($stmt->execute([$nome, $descrizione, $categoria])) {
                        // Log azione nel database tradizionale
                        error_log("Admin {$_SESSION['user_id']} ha aggiunto la competenza: $nome");
                        $conn->commit();
                        $success = "Competenza aggiunta con successo!";
                        unset($nome, $descrizione, $categoria);
                    } else {
                        throw new Exception("Errore durante l'inserimento della competenza");
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = $e->getMessage();
                    error_log("Errore aggiunta competenza: " . $e->getMessage());
                }
            } else {
                $error = implode('<br>', $validationErrors);
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    if (!FrontendSecurity::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token di sicurezza non valido. Riprova.';
        FrontendSecurity::logSecurityEvent('csrf_token_invalid', ['action' => 'delete_skill']);
    } else {
        if (!FrontendSecurity::checkRateLimit('delete_skill', 3, 300)) {
            $error = 'Troppi tentativi. Riprova tra 5 minuti.';
            FrontendSecurity::logSecurityEvent('rate_limit_exceeded', ['action' => 'delete_skill']);
        } else {
            try {
                $skill_id = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
                if (!$skill_id) {
                    throw new Exception('ID competenza non valido');
                }
                $conn->beginTransaction();
                $stmt = $conn->prepare(
                    SELECT COUNT(*) 
                    FROM utenti_competenze 
                    WHERE id_competenza = ?
                );
                $stmt->execute([$skill_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Non � possibile eliminare questa competenza perch� � utilizzata da alcuni utenti');
                }
                $stmt = $conn->prepare(
                    DELETE FROM competenze 
                    WHERE id = ? 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM progetti_competenze 
                        WHERE id_competenza = competenze.id
                    )
                );
                if ($stmt->execute([$skill_id])) {
                    if ($stmt->rowCount() > 0) {
                        error_log("Admin {$_SESSION['user_id']} ha eliminato la competenza ID: $skill_id");
                        $conn->commit();
                        $success = "Competenza eliminata con successo!";
                    } else {
                        throw new Exception('Non è possibile eliminare questa competenza perché è utilizzata in alcuni progetti');
                    }
                } else {
                    throw new Exception("Errore durante l'eliminazione della competenza");
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $error = $e->getMessage();
                error_log("Errore eliminazione competenza: " . $e->getMessage());
            }
        }
    }
}
$stmt = $conn->prepare(
    SELECT 
        c.id,
        c.nome,
        c.descrizione,
        c.categoria,
        c.data_creazione,
        COUNT(DISTINCT uc.id_utente) as num_utenti,
        COUNT(DISTINCT pc.id_progetto) as num_progetti
    FROM competenze c
    LEFT JOIN utenti_competenze uc ON c.id = uc.id_competenza
    LEFT JOIN progetti_competenze pc ON c.id = pc.id_competenza
    GROUP BY c.id, c.nome, c.descrizione, c.categoria
    ORDER BY c.categoria, c.nome
);
$stmt->execute();
$competenze = $stmt->fetchAll(PDO::FETCH_ASSOC);
$competenze_per_categoria = [];
foreach ($competenze as $competenza) {
    $competenze_per_categoria[$competenza['categoria']][] = $competenza;
}
$stmt = $conn->prepare(
    SELECT 
        COUNT(*) as total_skills,
        COUNT(DISTINCT categoria) as total_categories,
        COUNT(DISTINCT srp.profilo_id) as used_in_projects
    FROM competenze c
    LEFT JOIN skill_richieste_profilo srp ON c.id = srp.competenza_id
);
$stmt->execute();
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <?php $page_title = 'Gestione Competenze'; include __DIR__ . '/../includes/head.php'; ?>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0">Gestione Competenze</h1>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        <!-- Form aggiunta competenza -->
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="add">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nome" name="nome" required
                                            pattern="[a-zA-Z0-9\s\-\+\#\.]{2,50}"
                                            value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>">
                                        <label for="nome">Nome Competenza</label>
                                        <div class="invalid-feedback">
                                            Nome non valido (2-50 caratteri, lettere, numeri e simboli base)
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select" id="categoria" name="categoria" required>
                                            <option value="" disabled selected>Seleziona categoria</option>
                                            <option value="Programming">Programming</option>
                                            <option value="Design">Design</option>
                                            <option value="Marketing">Marketing</option>
                                            <option value="Business">Business</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <label for="categoria">Categoria</label>
                                        <div class="invalid-feedback">
                                            Seleziona una categoria valida
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary h-100 w-100">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Aggiungi Competenza
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="descrizione" name="descrizione"
                                            class="textarea-large" required minlength="10"
                                            maxlength="500"><?php echo isset($descrizione) ? htmlspecialchars($descrizione) : ''; ?></textarea>
                                        <label for="descrizione">Descrizione</label>
                                        <div class="invalid-feedback">
                                            La descrizione deve essere tra 10 e 500 caratteri
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <!-- Lista competenze -->
                        <div class="mt-5">
                            <h2 class="h5 mb-4">Competenze Esistenti</h2>
                            <?php foreach ($competenze_per_categoria as $categoria => $competenze_categoria): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h3 class="h6 mb-0"><?php echo htmlspecialchars($categoria); ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                        <?php foreach ($competenze_categoria as $competenza): ?>
                                        <div class="col">
                                            <div class="card h-100 skill-card">
                                                <div class="card-body">
                                                    <h4
                                                        class="h6 card-title d-flex justify-content-between align-items-center">
                                                        <?php echo htmlspecialchars($competenza['nome']); ?>
                                                        <form method="POST" class="d-inline"
                                                            onsubmit="return confirm('Sei sicuro di voler eliminare questa competenza?');">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="skill_id"
                                                                value="<?php echo $competenza['id']; ?>">
                                                            <button type="submit"
                                                                class="btn btn-link text-danger p-0 delete-btn"
                                                                title="Elimina competenza">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </h4>
                                                    <p class="card-text small mb-2">
                                                        <?php echo htmlspecialchars($competenza['descrizione']); ?>
                                                    </p>
                                                    <div class="d-flex gap-2">
                                                        <span class="badge bg-primary stats-badge">
                                                            <i class="fas fa-users me-1"></i>
                                                            <?php echo $competenza['num_utenti']; ?> utenti
                                                        </span>
                                                        <span class="badge bg-info stats-badge">
                                                            <i class="fas fa-project-diagram me-1"></i>
                                                            <?php echo $competenza['num_progetti']; ?> progetti
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-footer bg-transparent">
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        Aggiunta il
                                                        <?php echo date('d/m/Y', strtotime($competenza['data_creazione'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/scripts.php'; ?>
    <script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    })();
    </script>
</body>

</html>