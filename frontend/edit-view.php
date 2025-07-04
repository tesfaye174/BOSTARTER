<?php
/**
 * BOSTARTER - Modifica progetto
 * Pagina per modificare un progetto esistente
 */

session_start();
require_once __DIR__ . "/../backend/config/database.php";
require_once __DIR__ . "/../backend/utils/Security.php";

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$security = Security::getInstance();
$security->setSecurityHeaders();

// Verifica che sia un creatore o amministratore
if (!$security->checkUserPermission('creatore')) {
    header('Location: home.php?error=permission_denied');
    exit;
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    header('Location: dash.php?error=project_not_found');
    exit;
}

try {
    $db = Database::getInstance();
    
    // Ottieni dati progetto
    $stmt = $db->prepare("
        SELECT p.*, u.nome as creatore_nome, u.cognome as creatore_cognome
        FROM progetti p
        JOIN utenti u ON p.creatore_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header('Location: dash.php?error=project_not_found');
        exit;
    }
    
    // Verifica proprietario
    if (!$security->checkResourceOwner($project['creatore_id'])) {
        header('Location: dash.php?error=permission_denied');
        exit;
    }
    
    // Gestione form di modifica
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica CSRF
        if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Token di sicurezza non valido';
        } else {
            $nome = $security->sanitizeInput($_POST['nome'] ?? '');
            $descrizione = $security->sanitizeInput($_POST['descrizione'] ?? '');
            $budget_richiesto = floatval($_POST['budget_richiesto'] ?? 0);
            $data_limite = $_POST['data_limite'] ?? '';
            
            // Validazione
            $errors = [];
            
            if (empty($nome)) {
                $errors[] = 'Il nome del progetto è obbligatorio';
            }
            
            if (empty($descrizione)) {
                $errors[] = 'La descrizione è obbligatoria';
            }
            
            if ($budget_richiesto <= 0) {
                $errors[] = 'Il budget deve essere maggiore di zero';
            }
            
            if (empty($data_limite) || strtotime($data_limite) <= time()) {
                $errors[] = 'La data limite deve essere futura';
            }
            
            if (empty($errors)) {
                try {
                    // Aggiorna progetto
                    $stmt = $db->prepare("
                        UPDATE progetti 
                        SET nome = ?, descrizione = ?, budget_richiesto = ?, data_limite = ?
                        WHERE id = ? AND creatore_id = ?
                    ");
                    
                    $stmt->execute([
                        $nome, $descrizione, $budget_richiesto, 
                        $data_limite, $project_id, $_SESSION['user_id']
                    ]);
                    
                    $success = 'Progetto aggiornato con successo';
                    
                    // Ricarica i dati
                    $stmt = $db->prepare("
                        SELECT p.*, u.nome as creatore_nome, u.cognome as creatore_cognome
                        FROM progetti p
                        JOIN utenti u ON p.creatore_id = u.id
                        WHERE p.id = ?
                    ");
                    $stmt->execute([$project_id]);
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $error = 'Esiste già un progetto con questo nome';
                    } else {
                        $error = 'Errore durante l\'aggiornamento del progetto';
                    }
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
    
} catch (Exception $e) {
    $error = 'Errore durante il caricamento del progetto';
}

$csrf_token = $security->generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Progetto - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit me-2"></i>Modifica Progetto</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $success ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="editProjectForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Progetto</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?= htmlspecialchars($project['nome']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descrizione" class="form-label">Descrizione</label>
                                <textarea class="form-control" id="descrizione" name="descrizione" 
                                          rows="5" required><?= htmlspecialchars($project['descrizione']) ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_richiesto" class="form-label">Budget Richiesto (€)</label>
                                        <input type="number" class="form-control" id="budget_richiesto" 
                                               name="budget_richiesto" step="0.01" min="1" 
                                               value="<?= number_format($project['budget_richiesto'], 2, '.', '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="data_limite" class="form-label">Data Limite</label>
                                        <input type="date" class="form-control" id="data_limite" 
                                               name="data_limite" value="<?= $project['data_limite'] ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nota:</strong> Non è possibile modificare il tipo di progetto (<?= ucfirst($project['tipo']) ?>) 
                                dopo la creazione.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view.php?id=<?= $project_id ?>" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Annulla
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salva Modifiche
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sezione gestione foto -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-images me-2"></i>Gestione Foto</h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="projectPhotos">
                            <?php
                            $stmt = $db->prepare("SELECT * FROM foto_progetti WHERE progetto_id = ?");
                            $stmt->execute([$project_id]);
                            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($photos)): ?>
                                <div class="col-12">
                                    <p class="text-muted">Nessuna foto caricata</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($photos as $photo): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="<?= htmlspecialchars($photo['path']) ?>" 
                                                 class="card-img-top" style="height: 200px; object-fit: cover;">
                                            <div class="card-body p-2">
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deletePhoto(<?= $photo['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php if ($photo['is_primary']): ?>
                                                    <span class="badge bg-success">Principale</span>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="setPrimaryPhoto(<?= $photo['id'] ?>)">
                                                        Imposta come principale
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3">
                            <form id="uploadPhotoForm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="photo" 
                                           accept="image/*" required>
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-upload"></i> Carica Foto
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Imposta data minima per la data limite
        document.getElementById('data_limite').min = new Date().toISOString().split('T')[0];
        
        // Gestione upload foto
        document.getElementById('uploadPhotoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/upload-photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore durante l\'upload: ' + data.message);
                }
            })
            .catch(error => {
                alert('Errore di connessione');
            });
        });
        
        function deletePhoto(photoId) {
            if (confirm('Sei sicuro di voler eliminare questa foto?')) {
                fetch('api/delete-photo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        photo_id: photoId,
                        csrf_token: '<?= $csrf_token ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Errore durante l\'eliminazione');
                    }
                });
            }
        }
        
        function setPrimaryPhoto(photoId) {
            fetch('api/set-primary-photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo_id: photoId,
                    csrf_token: '<?= $csrf_token ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore durante l\'aggiornamento');
                }
            });
        }
    </script>
</body>
</html>
