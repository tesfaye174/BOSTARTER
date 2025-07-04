<?php
session_start();
require_once __DIR__ . "/../backend/config/database.php";

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Verifica che sia stato passato un ID progetto
if (!isset($_GET['progetto_id']) || !is_numeric($_GET['progetto_id'])) {
    header("Location: home.php");
    exit();
}

$progetto_id = intval($_GET['progetto_id']);

// Carica informazioni progetto software
$stmt = $conn->prepare("
    SELECT p.*, u.nickname as creatore_nickname
    FROM progetti p 
    JOIN utenti u ON p.creatore_id = u.id 
    WHERE p.id = ? AND p.tipo = 'software'
");
$stmt->execute([$progetto_id]);
$progetto = $stmt->fetch();

if (!$progetto) {
    header("Location: home.php");
    exit();
}

$is_creatore = ($user_id == $progetto['creatore_id']);

// Carica profili richiesti
$stmt = $conn->prepare("SELECT * FROM profili_software WHERE progetto_id = ? ORDER BY nome");
$stmt->execute([$progetto_id]);
$profili = $stmt->fetchAll();

// Carica skill dell'utente corrente
$stmt = $conn->prepare("
    SELECT su.competenza_id, su.livello, c.nome 
    FROM skill_utenti su 
    JOIN competenze c ON su.competenza_id = c.id 
    WHERE su.utente_id = ?
");
$stmt->execute([$user_id]);
$skill_utenti = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Gestione candidatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_creatore) {
    if (isset($_POST['action']) && $_POST['action'] === 'candidati') {
        $profilo_id = intval($_POST['profilo_id']);
        
        // Verifica che il profilo esista
        $stmt = $conn->prepare("SELECT * FROM profili_software WHERE id = ? AND progetto_id = ?");
        $stmt->execute([$profilo_id, $progetto_id]);
        $profilo = $stmt->fetch();
        
        if ($profilo) {
            // Verifica skill richieste
            $stmt = $conn->prepare("
                SELECT sp.competenza_id, sp.livello_richiesto, c.nome
                FROM skill_profili sp
                JOIN competenze c ON sp.competenza_id = c.id
                WHERE sp.profilo_id = ?
            ");
            $stmt->execute([$profilo_id]);
            $skill_richieste = $stmt->fetchAll();
            
            $puo_candidarsi = true;
            $skill_mancanti = [];
            
            foreach ($skill_richieste as $skill_req) {
                $comp_id = $skill_req['competenza_id'];
                $livello_req = $skill_req['livello_richiesto'];
                $livello_utente = $skill_utenti[$comp_id] ?? 0;
                
                if ($livello_utente < $livello_req) {
                    $puo_candidarsi = false;
                    $skill_mancanti[] = [
                        'nome' => $skill_req['nome'],
                        'richiesto' => $livello_req,
                        'attuale' => $livello_utente
                    ];
                }
            }
            
            if ($puo_candidarsi) {
                // Verifica se non è già candidato
                $stmt = $conn->prepare("SELECT id FROM candidature WHERE utente_id = ? AND profilo_id = ?");
                $stmt->execute([$user_id, $profilo_id]);
                
                if (!$stmt->fetch()) {
                    $stmt = $conn->prepare("
                        INSERT INTO candidature (utente_id, profilo_id, data_candidatura, stato) 
                        VALUES (?, ?, NOW(), 'in_attesa')
                    ");
                    $stmt->execute([$user_id, $profilo_id]);
                    $success_message = "Candidatura inviata con successo!";
                } else {
                    $error_message = "Ti sei già candidato per questo profilo";
                }
            } else {
                $error_message = "Non hai le skill necessarie per candidarti a questo profilo";
            }
        } else {
            $error_message = "Profilo non trovato";
        }
    }
}

// Se è il creatore, gestisci accettazione/rifiuto candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_creatore) {
    if (isset($_POST['action']) && in_array($_POST['action'], ['accetta', 'rifiuta'])) {
        $candidatura_id = intval($_POST['candidatura_id']);
        $nuovo_stato = $_POST['action'] === 'accetta' ? 'accettata' : 'rifiutata';
        
        $stmt = $conn->prepare("
            UPDATE candidature 
            SET stato = ?, data_risposta = NOW() 
            WHERE id = ? AND profilo_id IN (SELECT id FROM profili_software WHERE progetto_id = ?)
        ");
        $stmt->execute([$nuovo_stato, $candidatura_id, $progetto_id]);
        $success_message = "Candidatura " . ($nuovo_stato === 'accettata' ? 'accettata' : 'rifiutata') . " con successo!";
    }
}

// Carica candidature (se è il creatore) o stato candidature (se è un utente normale)
if ($is_creatore) {
    $stmt = $conn->prepare("
        SELECT 
            c.id, c.stato, c.data_candidatura, c.data_risposta,
            u.nickname, u.nome, u.cognome,
            ps.nome as profilo_nome
        FROM candidature c
        JOIN utenti u ON c.utente_id = u.id
        JOIN profili_software ps ON c.profilo_id = ps.id
        WHERE ps.progetto_id = ?
        ORDER BY c.data_candidatura DESC
    ");
    $stmt->execute([$progetto_id]);
    $candidature = $stmt->fetchAll();
} else {
    $stmt = $conn->prepare("
        SELECT 
            c.id, c.stato, c.data_candidatura, c.data_risposta,
            ps.nome as profilo_nome
        FROM candidature c
        JOIN profili_software ps ON c.profilo_id = ps.id
        WHERE c.utente_id = ? AND ps.progetto_id = ?
        ORDER BY c.data_candidatura DESC
    ");
    $stmt->execute([$user_id, $progetto_id]);
    $mie_candidature = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidature - <?= htmlspecialchars($progetto['nome']) ?> - BOSTARTER</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> <?= $is_creatore ? 'Gestione' : '' ?> Candidature - <?= htmlspecialchars($progetto['nome']) ?></h3>
                        <small class="text-muted">Progetto di <?= htmlspecialchars($progetto['creatore_nickname']) ?></small>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($skill_mancanti) && !empty($skill_mancanti)): ?>
                            <div class="alert alert-warning">
                                <h6>Skill mancanti per la candidatura:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($skill_mancanti as $skill): ?>
                                        <li><?= htmlspecialchars($skill['nome']) ?>: hai livello <?= $skill['attuale'] ?>, richiesto <?= $skill['richiesto'] ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <small>Aggiorna le tue skill nella <a href="skill.php">pagina dedicata</a> prima di candidarti.</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$is_creatore): ?>
                            <!-- Sezione candidatura per utenti normali -->
                            <h5>Profili disponibili per candidatura:</h5>
                            <?php if (empty($profili)): ?>
                                <div class="alert alert-info">
                                    Nessun profilo disponibile per questo progetto.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($profili as $profilo): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?= htmlspecialchars($profilo['nome']) ?></h6>
                                                    <p class="card-text"><?= htmlspecialchars($profilo['descrizione']) ?></p>
                                                    
                                                    <!-- Skill richieste -->
                                                    <?php
                                                    $stmt = $conn->prepare("
                                                        SELECT sp.livello_richiesto, c.nome
                                                        FROM skill_profili sp
                                                        JOIN competenze c ON sp.competenza_id = c.id
                                                        WHERE sp.profilo_id = ?
                                                        ORDER BY c.nome
                                                    ");
                                                    $stmt->execute([$profilo['id']]);
                                                    $skill_profilo = $stmt->fetchAll();
                                                    ?>
                                                    
                                                    <h6>Skill richieste:</h6>
                                                    <ul class="list-unstyled">
                                                        <?php foreach ($skill_profilo as $skill): ?>
                                                            <li>
                                                                <span class="badge bg-secondary">
                                                                    <?= htmlspecialchars($skill['nome']) ?> - Livello <?= $skill['livello_richiesto'] ?>
                                                                </span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    
                                                    <!-- Verifica già candidato -->
                                                    <?php
                                                    $stmt = $conn->prepare("SELECT stato FROM candidature WHERE utente_id = ? AND profilo_id = ?");
                                                    $stmt->execute([$user_id, $profilo['id']]);
                                                    $candidatura_esistente = $stmt->fetch();
                                                    ?>
                                                    
                                                    <?php if ($candidatura_esistente): ?>
                                                        <span class="badge bg-<?= $candidatura_esistente['stato'] === 'accettata' ? 'success' : ($candidatura_esistente['stato'] === 'rifiutata' ? 'danger' : 'warning') ?>">
                                                            Candidatura <?= ucfirst($candidatura_esistente['stato']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="candidati">
                                                            <input type="hidden" name="profilo_id" value="<?= $profilo['id'] ?>">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-paper-plane"></i> Candidati
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Le mie candidature -->
                            <?php if (!empty($mie_candidature)): ?>
                                <h5 class="mt-4">Le mie candidature:</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Profilo</th>
                                                <th>Data Candidatura</th>
                                                <th>Stato</th>
                                                <th>Data Risposta</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mie_candidature as $cand): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($cand['profilo_nome']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($cand['data_candidatura'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $cand['stato'] === 'accettata' ? 'success' : ($cand['stato'] === 'rifiutata' ? 'danger' : 'warning') ?>">
                                                            <?= ucfirst($cand['stato']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= $cand['data_risposta'] ? date('d/m/Y H:i', strtotime($cand['data_risposta'])) : '-' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <!-- Sezione gestione candidature per creatori -->
                            <h5>Candidature ricevute:</h5>
                            <?php if (empty($candidature)): ?>
                                <div class="alert alert-info">
                                    Nessuna candidatura ricevuta ancora.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Candidato</th>
                                                <th>Profilo</th>
                                                <th>Data Candidatura</th>
                                                <th>Stato</th>
                                                <th>Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($candidature as $cand): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($cand['nickname']) ?></strong><br>
                                                        <small><?= htmlspecialchars($cand['nome'] . ' ' . $cand['cognome']) ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars($cand['profilo_nome']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($cand['data_candidatura'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $cand['stato'] === 'accettata' ? 'success' : ($cand['stato'] === 'rifiutata' ? 'danger' : 'warning') ?>">
                                                            <?= ucfirst($cand['stato']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($cand['stato'] === 'in_attesa'): ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="accetta">
                                                                <input type="hidden" name="candidatura_id" value="<?= $cand['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success">
                                                                    <i class="fas fa-check"></i> Accetta
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="rifiuta">
                                                                <input type="hidden" name="candidatura_id" value="<?= $cand['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-times"></i> Rifiuta
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <small class="text-muted">
                                                                Risposto il <?= date('d/m/Y', strtotime($cand['data_risposta'])) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="view.php?id=<?= $progetto_id ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Torna al Progetto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
