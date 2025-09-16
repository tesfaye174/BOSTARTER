<?php
/**
 * BOSTARTER - Gestione Candidature
 *
 * Questa pagina permette agli utenti di visualizzare e gestire le proprie candidature
 * per progetti software, e ai creatori di gestire le candidature ricevute.
 *
 * Funzionalità implementate:
 * - Visualizzazione candidature inviate (per utenti normali)
 * - Gestione candidature ricevute (per creatori)
 * - Accettazione/rifiuto candidature
 * - Filtro per progetto/profilo specifico
 * - Integrazione con sistema skill-matching
 *
 * Sicurezza:
 * - Solo utenti autenticati possono accedere
 * - Controllo permessi basato sul tipo utente
 * - Validazione input e sanitizzazione
 * - Protezione CSRF per operazioni critiche
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
function isAuthenticated() {
    return isset($_SESSION["user_id"]);
}

/**
 * Ottiene il tipo di utente dalla sessione
 * @return string Tipo di utente (creatore, admin, utente)
 */
function getUserType() {
    return $_SESSION['user_type'] ?? '';
}

/**
 * Funzione per effettuare chiamate API sicure
 * @param string $url URL dell'endpoint API
 * @return mixed Risultato della chiamata API
 */
function callAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "Errore API: HTTP $httpCode"];
    }

    return json_decode($response, true);
}

// Verifica autenticazione - redirect se non loggato
if (!isAuthenticated()) {
    header('Location: auth/login.php?msg=login_required');
    exit();
}

// Recupero dati utente dalla sessione
$userType = getUserType();
$userId = $_SESSION['user_id'];
$isCreator = ($userType === 'creatore');
$isAdmin = ($userType === 'amministratore');

// Inizializzazione variabili
$candidature = [];
$error = null;
$success = null;

// Gestione azioni POST (accettazione/rifiuto candidature)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidaturaId = $_POST['candidatura_id'] ?? null;
    $azione = $_POST['azione'] ?? null;

    if ($candidaturaId && in_array($azione, ['accetta', 'rifiuta'])) {
        // Chiamata API per aggiornare stato candidatura
        $apiData = [
            'candidatura_id' => $candidaturaId,
            'azione' => $azione
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/BOSTARTER/backend/api/candidature.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['success']) && $result['success']) {
            $success = $azione === 'accetta' ? 'Candidatura accettata con successo!' : 'Candidatura rifiutata.';
        } else {
            $error = $result['error'] ?? 'Errore nell\'aggiornamento della candidatura.';
        }
    }
}

// Recupero candidature in base al contesto
try {
    if (isset($_GET['profilo_id'])) {
        // Candidature per un profilo specifico (solo creatore/admin del progetto)
        $profiloId = (int)$_GET['profilo_id'];
        $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/candidature.php?profilo_id=$profiloId");

        if (isset($apiResult['success']) && $apiResult['success']) {
            $candidature = $apiResult['data'];
        } else {
            $error = $apiResult['error'] ?? 'Errore nel recupero candidature per profilo';
        }
    } elseif (isset($_GET['progetto_id'])) {
        // Candidature per un progetto specifico
        $progettoId = (int)$_GET['progetto_id'];
        $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/candidature.php?progetto_id=$progettoId");

        if (isset($apiResult['success']) && $apiResult['success']) {
            $candidature = $apiResult['data'];
        } else {
            $error = $apiResult['error'] ?? 'Errore nel recupero candidature per progetto';
        }
    } else {
        // Candidature dell'utente corrente
        $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/candidature.php");

        if (isset($apiResult['success']) && $apiResult['success']) {
            $candidature = $apiResult['data'];
        } else {
            $error = $apiResult['error'] ?? 'Errore nel recupero candidature utente';
        }
    }
} catch (Exception $e) {
    $error = 'Errore di connessione: ' . $e->getMessage();
}

// Titolo pagina dinamico
$page_title = $isCreator ? 'Gestione Candidature' : 'Le Mie Candidature';

// Includi header comune
require_once dirname(__DIR__, 1) . '/backend/config/SecurityConfig.php';
require_once __DIR__.'/includes/head.php';

// Recupera progetti software per candidature
$progettiSoftware = [];
try {
    $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/project.php?tipo=software&stato=aperto");

    if (isset($apiResult['success']) && $apiResult['success']) {
        $progettiSoftware = $apiResult['data']['projects'] ?? $apiResult['data'] ?? [];
    }
} catch (Exception $e) {
    // Ignora errori per progetti software
}
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
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-bostarter-primary {
            background-color: var(--bostarter-primary);
            border-color: var(--bostarter-primary);
        }

        .btn-bostarter-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .table-bostarter thead th {
            background-color: var(--bostarter-primary);
            color: white;
            border: none;
        }

        .badge.bg-bostarter-secondary {
            background-color: var(--bostarter-secondary) !important;
        }

        .badge.bg-bostarter-info {
            background-color: var(--bostarter-info) !important;
        }

        .avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--bostarter-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
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
                    <?php if ($isCreator): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="new.php">
                            <i class="fas fa-plus"></i> Crea Progetto
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="skill.php">Le Mie Skill</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="candidature.php">Candidature</a>
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

    <div class="container mt-4">
        <!-- Header con breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($page_title); ?>
                </li>
            </ol>
        </nav>

        <!-- Messaggi di feedback -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
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
                    <i class="fas fa-users-cog me-2"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>

                <!-- Scheda informativa -->
                <div class="card mb-4 border-info">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Sistema di Candidature BOSTARTER
                                </h5>
                                <p class="card-text">
                                    <?php if ($isCreator): ?>
                                    Gestisci le candidature ricevute per i profili richiesti nei tuoi progetti software.
                                    Puoi accettare o rifiutare le candidature in base alle competenze degli utenti.
                                    <?php else: ?>
                                    Candidati per posizioni aperte nei progetti software. Assicurati che le tue skill
                                    corrispondano ai requisiti del profilo per avere successo.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div>
                                        <i class="fas fa-handshake fa-3x text-info mb-2"></i>
                                        <p class="text-muted small mb-0">Sistema Matching</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Nuova Candidatura -->
                <?php if (!isset($_GET['profilo_id']) && !$isCreator): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Nuova Candidatura</h5>
                    </div>
                    <div class="card-body">
                        <form id="candidaturaForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="progetto_id" class="form-label">Progetto Software *</label>
                                        <select class="form-select" id="progetto_id" name="progetto_id" required>
                                            <option value="">Seleziona progetto...</option>
                                            <?php foreach ($progettiSoftware as $progetto): ?>
                                            <option value="<?php echo $progetto['id']; ?>">
                                                <?php echo htmlspecialchars($progetto['titolo'] ?? $progetto['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="profilo_id" class="form-label">Profilo Richiesto *</label>
                                        <select class="form-select" id="profilo_id" name="profilo_id" required disabled>
                                            <option value="">Prima seleziona un progetto...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="motivazione" class="form-label">Motivazione *</label>
                                <textarea class="form-control" id="motivazione" name="motivazione" rows="4"
                                    placeholder="Spiega perché sei la persona giusta per questo profilo..."
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-bostarter-primary">
                                <i class="fas fa-paper-plane"></i> Invia Candidatura
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista Candidature -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <i class="fas fa-list"></i>
                            <?php if (isset($_GET['profilo_id'])): ?>
                            Candidature Ricevute
                            <?php else: ?>
                            Le Mie Candidature
                            <?php endif; ?>
                        </h5>
                        <span class="badge bg-bostarter-primary"><?php echo count($candidature); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($candidature)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">
                                <?php if (isset($_GET['profilo_id'])): ?>
                                Nessuna candidatura ricevuta per questo profilo.
                                <?php else: ?>
                                Non hai ancora inviato candidature.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-bostarter">
                                    <tr>
                                        <th>Utente</th>
                                        <th>Progetto</th>
                                        <th>Profilo</th>
                                        <th>Data</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidature as $candidatura): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($candidatura['nickname'] ?? $candidatura['utente_nickname']); ?></strong>
                                                    <?php if (isset($candidatura['nome'])): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars($candidatura['nome'] . ' ' . $candidatura['cognome']); ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($candidatura['progetto_nome']); ?></strong>
                                            <?php if (isset($candidatura['progetto_tipo'])): ?>
                                            <br><small class="badge bg-bostarter-secondary">
                                                <?php echo ucfirst($candidatura['progetto_tipo']); ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-bostarter-info">
                                                <?php echo htmlspecialchars($candidatura['profilo_nome']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($candidatura['data_candidatura'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                                    $stato = $candidatura['stato'] ?? 'in_valutazione';
                                                    $statoClass = [
                                                        'in_valutazione' => 'bg-warning',
                                                        'accettata' => 'bg-success',
                                                        'rifiutata' => 'bg-danger'
                                                    ];
                                                    $statoText = [
                                                        'in_valutazione' => 'In Valutazione',
                                                        'accettata' => 'Accettata',
                                                        'rifiutata' => 'Rifiutata'
                                                    ];
                                                    ?>
                                            <span class="badge <?php echo $statoClass[$stato]; ?>">
                                                <?php echo $statoText[$stato]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                    onclick="viewCandidatura(<?php echo $candidatura['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <?php if ($isCreator || $isAdmin): ?>
                                                <?php if ($stato === 'in_valutazione'): ?>
                                                <button type="button" class="btn btn-outline-success"
                                                    onclick="updateCandidaturaStatus(<?php echo $candidatura['id']; ?>, 'accettata')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="updateCandidaturaStatus(<?php echo $candidatura['id']; ?>, 'rifiutata')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (!$isCreator || $candidatura['utente_id'] == $userId): ?>
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="deleteCandidatura(<?php echo $candidatura['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dettaglio Candidatura -->
    <div class="modal fade" id="candidaturaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dettaglio Candidatura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="candidaturaModalBody">
                    <!-- Contenuto caricato dinamicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>

    <script>
    // Gestione form candidatura
    document.getElementById('candidaturaForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            profilo_id: formData.get('profilo_id'),
            motivazione: formData.get('motivazione')
        };

        fetch('/BOSTARTER/backend/api/candidature.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Candidatura inviata con successo!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'invio candidatura');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    });

    // Carica profili quando si seleziona un progetto
    document.getElementById('progetto_id')?.addEventListener('change', function() {
        const progettoId = this.value;
        const profiloSelect = document.getElementById('profilo_id');

        if (!progettoId) {
            profiloSelect.disabled = true;
            profiloSelect.innerHTML = '<option value="">Prima seleziona un progetto...</option>';
            return;
        }

        // Carica profili del progetto
        fetch(`/BOSTARTER/backend/api/project.php?id=${progettoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const progetto = data.data;
                    if (progetto.profili_richiesti) {
                        profiloSelect.innerHTML = '<option value="">Seleziona profilo...</option>';
                        progetto.profili_richiesti.forEach(profilo => {
                            const option = document.createElement('option');
                            option.value = profilo.id;
                            option.textContent = profilo.nome;
                            profiloSelect.appendChild(option);
                        });
                        profiloSelect.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // Visualizza dettaglio candidatura
    function viewCandidatura(candidaturaId) {
        fetch(`/BOSTARTER/backend/api/candidature.php?id=${candidaturaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const candidatura = data.data;
                    document.getElementById('candidaturaModalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informazioni Utente</h6>
                                <p><strong>Nickname:</strong> ${candidatura.nickname}</p>
                                <p><strong>Nome:</strong> ${candidatura.nome} ${candidatura.cognome}</p>
                                <p><strong>Email:</strong> ${candidatura.email || 'Non disponibile'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Informazioni Candidatura</h6>
                                <p><strong>Progetto:</strong> ${candidatura.progetto_nome}</p>
                                <p><strong>Profilo:</strong> ${candidatura.profilo_nome}</p>
                                <p><strong>Data:</strong> ${new Date(candidatura.data_candidatura).toLocaleDateString('it-IT')}</p>
                                <p><strong>Stato:</strong> 
                                    <span class="badge bg-${getStatusColor(candidatura.stato)}">
                                        ${getStatusText(candidatura.stato)}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6>Motivazione</h6>
                            <div class="border rounded p-3 bg-light">
                                ${candidatura.motivazione}
                            </div>
                        </div>
                    `;

                    new bootstrap.Modal(document.getElementById('candidaturaModal')).show();
                } else {
                    showMessage('error', data.error || 'Errore nel caricamento candidatura');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    }

    // Aggiorna stato candidatura
    function updateCandidaturaStatus(candidaturaId, stato) {
        if (!confirm(`Sei sicuro di voler ${stato === 'accettata' ? 'accettare' : 'rifiutare'} questa candidatura?`)) {
            return;
        }

        fetch('/BOSTARTER/backend/api/candidature.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    candidatura_id: candidaturaId,
                    stato: stato
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Stato candidatura aggiornato');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nell\'aggiornamento');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    }

    // Cancella candidatura
    function deleteCandidatura(candidaturaId) {
        if (!confirm('Sei sicuro di voler cancellare questa candidatura?')) {
            return;
        }

        fetch(`/BOSTARTER/backend/api/candidature.php?id=${candidaturaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message || 'Candidatura cancellata');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Errore nella cancellazione');
                }
            })
            .catch(error => {
                showMessage('error', 'Errore di connessione');
                console.error('Error:', error);
            });
    }

    // Utility functions
    function getStatusColor(stato) {
        const colors = {
            'in_valutazione' => 'warning',
            'accettata' => 'success',
            'rifiutata' => 'danger'
        };
        return colors[stato] || 'secondary';
    }

    function getStatusText(stato) {
        const texts = {
            'in_valutazione' => 'In Valutazione',
            'accettata' => 'Accettata',
            'rifiutata' => 'Rifiutata'
        };
        return texts[stato] || 'Sconosciuto';
    }

    function getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function showMessage(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show`;
        alert.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

        document.querySelector('.container').insertBefore(alert, document.querySelector('.row'));

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    </script>
</body>

</html>