<?php
/**
 * GESTIONE CANDIDATURE BOSTARTER
 * Visualizzazione e gestione candidature per progetti software
 */
session_start();

/**
 * Verifica autenticazione utente
 */
function isAuthenticated() {
    return isset($_SESSION["user_id"]);
}

/**
 * Recupera tipo utente dalla sessione
 */
function getUserType() {
    return $_SESSION['user_type'] ?? '';
}

/**
 * Effettua chiamate API sicure
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

// Verifica autenticazione
if (!isAuthenticated()) {
    header('Location: auth/login.php?msg=login_required');
    exit();
}

// Recupero dati utente
$userType = getUserType();
$userId = $_SESSION['user_id'];
$isCreator = ($userType === 'creatore');
$isAdmin = ($userType === 'amministratore');

// Inizializzazione variabili
$candidature = [];
$error = null;
$success = null;

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidaturaId = $_POST['candidatura_id'] ?? null;
    $azione = $_POST['azione'] ?? null;

    if ($candidaturaId && in_array($azione, ['accetta', 'rifiuta'])) {
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
            $success = $azione === 'accetta' ? 'Candidatura accettata!' : 'Candidatura rifiutata.';
        } else {
            $error = $result['error'] ?? 'Errore aggiornamento candidatura.';
        }
    }
}

// Recupero candidature
try {
    if (isset($_GET['profilo_id'])) {
        $profiloId = (int)$_GET['profilo_id'];
        $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/candidature.php?profilo_id=$profiloId");

        if (isset($apiResult['success']) && $apiResult['success']) {
            $candidature = $apiResult['data'];
        } else {
            $error = $apiResult['error'] ?? 'Errore recupero candidature profilo';
        }
    } elseif (isset($_GET['progetto_id'])) {
        $progettoId = (int)$_GET['progetto_id'];
        $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/candidature.php?progetto_id=$progettoId");

        if (isset($apiResult['success']) && $apiResult['success']) {
            $candidature = $apiResult['data'];
        } else {
            $error = $apiResult['error'] ?? 'Errore recupero candidature progetto';
        }
    } else {
        $apiResult = callAPI("http://localhost/BOSTARTER/backend/api/candidature.php");

        if (isset($apiResult['success']) && $apiResult['success']) {
            $candidature = $apiResult['data'];
        } else {
            $error = $apiResult['error'] ?? 'Errore recupero candidature utente';
        }
    }
} catch (Exception $e) {
    $error = 'Errore connessione: ' . $e->getMessage();
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
                                    <tr data-candidatura-id="<?php echo $candidatura['id']; ?>">
                                        <td>
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
        // Variabili globali
        let progettiSoftwareCache = null;
        let isLoading = false;

        // Gestione errori API
        function handleApiError(error, context = '') {
            console.error(`API Error ${context}:`, error);
            let message = 'Errore connessione server';

            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                message = 'Impossibile contattare server';
            } else if (error.status) {
                message = `Errore server (${error.status})`;
            }

            showMessage('error', message);
            return false;
        }

        // Gestione stati caricamento
        function setLoadingState(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Caricamento...';
            } else {
                button.disabled = false;
                const icon = button.querySelector('i');
                if (icon && icon.classList.contains('fa-spinner')) {
                    button.innerHTML = '<i class="fas fa-paper-plane"></i> Invia Candidatura';
                }
            }
        }

        // Validazione form
        function validateCandidaturaForm(formData) {
            const errors = [];

            if (!formData.get('profilo_id')) {
                errors.push('Seleziona profilo');
            }

            const motivazione = formData.get('motivazione')?.trim();
            if (!motivazione) {
                errors.push('Inserisci motivazione');
            } else if (motivazione.length < 50) {
                errors.push('Motivazione minima 50 caratteri');
            } else if (motivazione.length > 1000) {
                errors.push('Motivazione massima 1000 caratteri');
            }

            return errors;
        }

        // Gestione form candidatura
        document.getElementById('candidaturaForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const errors = validateCandidaturaForm(formData);

            if (errors.length) {
                showMessage('error', 'Errore validazione: ' + errors.join(', '));
                return;
            }

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
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message || 'Candidatura inviata!');
                        this.reset();
                        document.getElementById('profilo_id').disabled = true;
                        document.getElementById('profilo_id').innerHTML = '<option value="">Prima seleziona progetto...</option>';
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showMessage('error', data.error || 'Errore invio candidatura');
                    }
                })
                .catch(error => handleApiError(error, 'invio candidatura'));
        });

        // Carica profili progetto
        document.getElementById('progetto_id')?.addEventListener('change', function() {
            const progettoId = this.value;
            const profiloSelect = document.getElementById('profilo_id');

            if (!progettoId) {
                profiloSelect.disabled = true;
                profiloSelect.innerHTML = '<option value="">Prima seleziona progetto...</option>';
                return;
            }

            profiloSelect.disabled = true;
            profiloSelect.innerHTML = '<option value="">Caricamento profili...</option>';

            fetch(`/BOSTARTER/backend/api/project.php?id=${progettoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const progetto = data.data;
                        if (progetto.profili_richiesti && progetto.profili_richiesti.length > 0) {
                            profiloSelect.innerHTML = '<option value="">Seleziona profilo...</option>';
                            progetto.profili_richiesti.forEach(profilo => {
                                const option = document.createElement('option');
                                option.value = profilo.id;
                                option.textContent = profilo.nome;
                                profiloSelect.appendChild(option);
                            });
                            profiloSelect.disabled = false;
                        } else {
                            profiloSelect.innerHTML = '<option value="">Nessun profilo disponibile</option>';
                            profiloSelect.disabled = true;
                        }
                    } else {
                        throw new Error(data.error || 'Errore caricamento profili');
                    }
                })
                .catch(error => {
                    profiloSelect.innerHTML = '<option value="">Errore caricamento</option>';
                    profiloSelect.disabled = true;
                    handleApiError(error, 'caricamento profili');
                });
        });

        // Visualizza dettaglio candidatura
        function viewCandidatura(candidaturaId) {
            if (isLoading) return;
            isLoading = true;

            fetch(`/BOSTARTER/backend/api/candidature.php?id=${candidaturaId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const candidatura = data.data;
                        document.getElementById('candidaturaModalBody').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Informazioni Utente</h6>
                                    <p><strong>Nickname:</strong> ${candidatura.nickname || 'N/A'}</p>
                                    <p><strong>Nome:</strong> ${candidatura.nome || ''} ${candidatura.cognome || ''}</p>
                                    <p><strong>Email:</strong> ${candidatura.email || 'Non disponibile'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Informazioni Candidatura</h6>
                                    <p><strong>Progetto:</strong> ${candidatura.progetto_nome || 'N/A'}</p>
                                    <p><strong>Profilo:</strong> ${candidatura.profilo_nome || 'N/A'}</p>
                                    <p><strong>Data:</strong> ${candidatura.data_candidatura ? new Date(candidatura.data_candidatura).toLocaleDateString('it-IT') : 'N/A'}</p>
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
                                    ${candidatura.motivazione ? candidatura.motivazione.replace(/\n/g, '<br>') : 'Nessuna motivazione fornita'}
                                </div>
                            </div>
                        `;

                        new bootstrap.Modal(document.getElementById('candidaturaModal')).show();
                    } else {
                        throw new Error(data.error || 'Errore caricamento candidatura');
                    }
                })
                .catch(error => handleApiError(error, 'visualizzazione candidatura'))
                .finally(() => {
                    isLoading = false;
                });
        }

        // Aggiorna stato candidatura
        function updateCandidaturaStatus(candidaturaId, stato) {
            if (isLoading) return;
            if (!confirm(`Sei sicuro di voler ${stato === 'accettata' ? 'accettare' : 'rifiutare'} questa candidatura?`)) {
                return;
            }

            isLoading = true;

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
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message || 'Stato candidatura aggiornato');
                        updateCandidaturaInTable(candidaturaId, stato);
                    } else {
                        throw new Error(data.error || 'Errore aggiornamento');
                    }
                })
                .catch(error => handleApiError(error, 'aggiornamento stato'))
                .finally(() => {
                    isLoading = false;
                });
        }

        // Cancella candidatura
        function deleteCandidatura(candidaturaId) {
            if (isLoading) return;
            if (!confirm('Sei sicuro di voler cancellare questa candidatura? Questa azione non può essere annullata.')) {
                return;
            }

            isLoading = true;

            fetch(`/BOSTARTER/backend/api/candidature.php?id=${candidaturaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCSRFToken()
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message || 'Candidatura cancellata');
                        removeCandidaturaFromTable(candidaturaId);
                    } else {
                        throw new Error(data.error || 'Errore cancellazione');
                    }
                })
                .catch(error => handleApiError(error, 'cancellazione candidatura'))
                .finally(() => {
                    isLoading = false;
                });
        }

        // Helper functions
        function updateCandidaturaInTable(candidaturaId, nuovoStato) {
            const row = document.querySelector(`tr[data-candidatura-id="${candidaturaId}"]`);
            if (row) {
                const badge = row.querySelector('.badge');
                if (badge) {
                    badge.className = `badge bg-${getStatusColor(nuovoStato)}`;
                    badge.textContent = getStatusText(nuovoStato);
                }
            }
        }

        function removeCandidaturaFromTable(candidaturaId) {
            const row = document.querySelector(`tr[data-candidatura-id="${candidaturaId}"]`);
            if (row) {
                row.remove();
                const badge = document.querySelector('.card-header .badge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent) || 0;
                    badge.textContent = Math.max(0, currentCount - 1);
                }
            }
        }

        // Utility functions
        function getStatusColor(stato) {
            const colors = {
                'in_valutazione': 'warning',
                'accettata': 'success',
                'rifiutata': 'danger'
            };
            return colors[stato] || 'secondary';
        }

        function getStatusText(stato) {
            const texts = {
                'in_valutazione': 'In Valutazione',
                'accettata': 'Accettata',
                'rifiutata': 'Rifiutata'
            };
            return texts[stato] || 'Sconosciuto';
        }

        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function showMessage(type, message) {
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="fas fa-${icon} me-2"></i>
                <strong>${type === 'success' ? 'Successo!' : 'Errore!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alert);

            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>

</html>