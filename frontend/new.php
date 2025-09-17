<?php
/**
 * Creazione Nuovo Progetto BOSTARTER
 *
 * Form completo per creare progetti:
 * - Informazioni base (nome, descrizione, categoria)
 * - Obiettivo finanziario e scadenza
 * - Tipo progetto (hardware/software)
 * - Validazione e invio sicuro
 */
session_start();

/**
 * Verifica autenticazione utente
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

/**
 * Ottieni tipo utente dalla sessione
 */
function getUserType() {
    return $_SESSION['user_type'] ?? '';
}

// Connessione database
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/models/Project.php';
require_once __DIR__ . '/../backend/utils/RoleManager.php';

// Verifica permessi
$roleManager = new RoleManager();
if (!$roleManager->isAuthenticated()) {
    header('Location: auth/login.php?msg=login_required');
    exit();
}

if (!$roleManager->hasPermission('can_create_project')) {
    header('Location: home.php?error=insufficient_permissions');
    exit();
}

// Connessione database
$db = Database::getInstance();

// Messaggi di successo per feedback utente
$successMessages = [
    'Ottimo! Il tuo progetto è pronto per essere pubblicato.',
    'Bene! Hai creato un progetto molto interessante.',
    'Perfetto! Il tuo progetto è ora visibile a tutti.',
    'Ottimo lavoro! Il progetto è stato caricato con successo.'
];

$message = '';
$error = '';

// Gestione messaggi dalla URL (redirect dopo operazioni)
if (isset($_GET['success'])) {
    $message = $successMessages[array_rand($successMessages)];
}

if (isset($_GET['error'])) {
    $errorTypes = [
        'validation' => 'Controlla i dati inseriti, alcuni campi necessitano correzioni.',
        'duplicate' => 'Esiste già un progetto con questo nome. Prova qualcosa di diverso!',
        'server' => 'Si è verificato un problema tecnico. Riprova tra poco.',
        'insufficient_permissions' => 'Non hai i permessi necessari per creare progetti.'
    ];
    $error = $errorTypes[$_GET['error']] ?? 'Si è verificato un errore imprevisto.';
}

// Titolo pagina per header moderno
$page_title = 'Crea Nuovo Progetto - BOSTARTER';

// Includi header moderno
require_once 'includes/head.php';

// Includi navbar moderno
require_once 'includes/navbar.php';
?>

    .project-form-card {
        background: white;
        border-radius: 16px;
    box-shadow: 0 10px 40px rgba(var(--bostarter-secondary-rgb), 0.08);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section h4 {
        color: var(--bostarter-secondary);
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .form-control,
    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
    border-color: var(--bostarter-secondary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bostarter-secondary-rgb), 0.18);
    }

    .btn-create {
    background: var(--gradient-primary);
        border: none;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-create:hover {
        transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--bostarter-secondary-rgb), 0.18);
    }

    /* Image upload/preview removed — uploads not supported in this build. */

    /* Contatore caratteri per la descrizione (protetto){
        position: relative;
    }*/

    .funding-goal-input::before {
        content: '$';
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-weight: 500;
        z-index: 5;
    }

    .funding-goal-input input {
        padding-left: 2rem;
    }

    /* Animazioni e feedback visivo */
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .spin {
        animation: spin 1s linear infinite;
    }

    .form-control.valid {
        border-color: #28a745;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.8-.8-.8-.8 1.54 1.54L6.7 3.8l-.8-.8-1.06 1.06z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .form-control.invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .btn-create.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .category-help {
    background: rgba(var(--bostarter-secondary-rgb), 0.06);
        padding: 0.5rem;
        border-radius: 6px;
    border-left: 3px solid var(--bostarter-secondary);
    }

    .project-type-option {
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .project-type-option:hover {
    border-color: var(--bostarter-secondary);
    background-color: rgba(var(--bostarter-secondary-rgb), 0.04);
    }

    .project-type-option.selected {
    border-color: var(--bostarter-secondary);
    background-color: rgba(var(--bostarter-secondary-rgb), 0.06);
    }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="brand-bo">BO</span><span class="brand-starter">STARTER</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dash.php">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>
    <div class="create-project-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="display-5 fw-bold text-gradient text-gradient-hero">Crea il Tuo Progetto</h1>
                        <p class="lead text-muted">Trasforma la tua idea in realtà con il supporto della nostra
                            community</p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Form Progetto -->
                    <form method="POST" class="project-form-card" id="projectForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <!-- Informazioni Base -->
                        <div class="form-section">
                            <h4><i class="bi bi-info-circle me-2"></i>Informazioni Principali</h4>
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Nome del Progetto *</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Dai un nome accattivante al tuo progetto" required
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                <div class="form-text">Scegli un nome che catturi l'attenzione e descriva la tua idea.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold">Descrizione del Progetto
                                    *</label>
                                <textarea class="form-control" id="description" name="description" rows="5"
                                    placeholder="Racconta la tua storia: cosa stai creando, perché è speciale e perché le persone dovrebbero sostenerti"
                                    required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Spiega chiaramente la tua visione e cosa rende unico il tuo
                                    progetto.</div>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label fw-semibold">Categoria *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Seleziona la categoria più adatta</option>
                                    <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"
                                        <?php echo (isset($_POST['category']) && $_POST['category'] === $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Aiuta le persone a trovare il tuo progetto scegliendo la
                                    categoria giusta.</div>
                            </div>
                        </div>

                        <!-- Dettagli Finanziamento -->
                        <div class="form-section">
                            <h4><i class="bi bi-currency-euro me-2"></i>Obiettivo Finanziario</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="funding_goal" class="form-label fw-semibold">Obiettivo di Raccolta (€)
                                        *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control" id="funding_goal" name="funding_goal"
                                            min="100" step="50" placeholder="1000" required
                                            value="<?php echo isset($_POST['funding_goal']) ? $_POST['funding_goal'] : ''; ?>">
                                    </div>
                                    <div class="form-text">Stabilisci un obiettivo realistico per il tuo progetto
                                        (minimo €100).</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="deadline" class="form-label fw-semibold">Data di Scadenza *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" required
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                        max="<?php echo date('Y-m-d', strtotime('+90 days')); ?>"
                                        value="<?php echo isset($_POST['deadline']) ? $_POST['deadline'] : ''; ?>">
                                    <div class="form-text">Scegli quando la tua campagna dovrebbe terminare (massimo 90
                                        giorni).</div>
                                </div>
                            </div>
                        </div>
                        <!-- Immagine del progetto rimossa (upload non supportato) -->

                        <!-- Tipo di Progetto -->
                        <div class="form-section">
                            <h4><i class="bi bi-gear me-2"></i>Tipologia Progetto</h4>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Che tipo di progetto stai creando? *</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="project_type"
                                                id="software" value="software" required>
                                            <label class="form-check-label" for="software">
                                                <i class="bi bi-code-slash me-2"></i>Software/Digitale
                                            </label>
                                        </div>
                                        <div class="form-text">App, siti web, giochi digitali, servizi online</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="project_type"
                                                id="hardware" value="hardware" required>
                                            <label class="form-check-label" for="hardware">
                                                <i class="bi bi-cpu me-2"></i>Hardware/Fisico
                                            </label>
                                        </div>
                                        <div class="form-text">Prodotti fisici, dispositivi, oggetti tangibili</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pulsanti di Azione -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dash.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Annulla
                            </a>
                            <button type="submit" class="btn btn-create text-white" id="submitBtn">
                                <i class="bi bi-plus-circle me-2"></i><span id="btnText">Crea Progetto</span>
                            </button>
                        </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    </div>
    <!-- Scripts -->
    <script>
    // Image upload/preview removed — uploads not supported in this build.

    // Contatore caratteri per la descrizione (protetto)
    const descriptionTextarea = document.getElementById('description');
    const maxLength = 1000;

    function updateCharacterCount() {
        if (!descriptionTextarea) return;
        const remaining = maxLength - descriptionTextarea.value.length;
        let counter = document.getElementById('charCounter');

        if (!counter) {
            counter = document.createElement('div');
            counter.id = 'charCounter';
            counter.className = 'form-text text-end';
            descriptionTextarea.parentNode.appendChild(counter);
        }

        if (remaining >= 0) {
            counter.textContent = `${remaining} caratteri rimanenti`;
            counter.className = 'form-text text-end text-muted';
        } else {
            counter.textContent = `Hai superato di ${Math.abs(remaining)} caratteri il limite!`;
            counter.className = 'form-text text-end text-danger';
        }
    }

    if (descriptionTextarea) {
        descriptionTextarea.addEventListener('input', updateCharacterCount);
        descriptionTextarea.setAttribute('maxlength', maxLength);
        updateCharacterCount();
    }

    // Validazione in tempo reale (protetta)
    function validateForm() {
        const nameEl = document.getElementById('name');
        const descriptionEl = document.getElementById('description');
        const categoryEl = document.getElementById('category');
        const fundingEl = document.getElementById('funding_goal');
        const deadlineEl = document.getElementById('deadline');
        const projectType = document.querySelector('input[name="project_type"]:checked');

        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');

        if (!submitBtn || !btnText) return;

        const name = nameEl ? nameEl.value.trim() : '';
        const description = descriptionEl ? descriptionEl.value.trim() : '';
        const category = categoryEl ? categoryEl.value : '';
        const fundingGoal = fundingEl ? parseFloat(fundingEl.value) : 0;
        const deadline = deadlineEl ? deadlineEl.value : '';

        const isValid = name && description && category && fundingGoal >= 100 && deadline && projectType;

        if (isValid) {
            submitBtn.disabled = false;
            submitBtn.className = 'btn btn-create text-white';
            btnText.textContent = 'Crea Progetto';
        } else {
            submitBtn.disabled = true;
            submitBtn.className = 'btn btn-create text-white disabled';
            btnText.textContent = 'Completa i campi richiesti';
        }
    }

    // Aggiungi validazione in tempo reale solo se gli elementi esistono
    ['name', 'description', 'category', 'funding_goal', 'deadline'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', validateForm);
        el.addEventListener('change', validateForm);
    });

    // Semplici check (rimosso logging verboso)
    // If message helpers missing, UI will fall back to alerts.

    document.querySelectorAll('input[name="project_type"]').forEach(radio => {
        radio.addEventListener('change', validateForm);
    });

    // Gestione submit del form
    const projectForm = document.getElementById('projectForm');
    if (projectForm) {
        projectForm.addEventListener('submit', async function(e) {
            e.preventDefault();

                    // Form submit

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            if (!submitBtn || !btnText) return;

            // Mostra stato di caricamento
            submitBtn.disabled = true;
            btnText.innerHTML = '<i class="bi bi-arrow-clockwise me-2 spin"></i>Creazione in corso...';

            // Controlla se le funzioni di messaggio esistono
            let loadingMessage = null;
            if (typeof showLoading === 'function') {
                try { loadingMessage = showLoading('Stiamo creando il tuo progetto...'); } catch (e) { /* ignore */ }
            }

            const formData = new FormData(this);
            const projectData = {
                nome: formData.get('name'),
                descrizione: formData.get('description'),
                tipo: formData.get('project_type'),
                budget_richiesto: parseFloat(formData.get('funding_goal')),
                data_limite: formData.get('deadline'),
                categoria: formData.get('category'),
                csrf_token: formData.get('csrf_token')
            };

            // projectData prepared

            try {
                // Sending request to backend

                // Always send JSON payload (uploads not supported)
                const fetchOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(projectData)
                };

                const response = await fetch('../backend/api/project.php', fetchOptions);

                const result = await response.json();

                // Rimuovi il messaggio di caricamento in modo sicuro
                try {
                    if (loadingMessage && loadingMessage.parentNode) loadingMessage.parentNode.removeChild(
                        loadingMessage);
                } catch (e) {
                    /* ignore */ }

                if (result && result.success) {
                    const successText = result.message ||
                        'Fantastico! Il tuo progetto è stato creato con successo.';
                    if (typeof showSuccess === 'function') {
                        showSuccess(successText);
                    } else {
                        alert(successText);
                    }
                    setTimeout(() => {
                        window.location.href = 'dash.php';
                    }, 1400);
                } else {
                    const errorMsg = (result && (result.message || result.error)) ||
                        'Si è verificato un errore durante la creazione del progetto.';
                    if (typeof showError === 'function') {
                        showError(errorMsg);
                    } else {
                        alert('Errore: ' + errorMsg);
                    }
                    // Ripristina il pulsante
                    submitBtn.disabled = false;
                    btnText.textContent = 'Crea Progetto';
                }
            } catch (error) {
                // network or unexpected error

                // Rimuovi il messaggio di caricamento
                try {
                    if (loadingMessage && loadingMessage.parentNode) loadingMessage.parentNode.removeChild(
                        loadingMessage);
                } catch (e) {}

                const errorMsg = 'Problemi di connessione. Verifica la tua connessione e riprova.';
                if (typeof showError === 'function') {
                    showError(errorMsg);
                } else {
                    alert(errorMsg);
                }

                // Ripristina il pulsante
                submitBtn.disabled = false;
                btnText.textContent = 'Crea Progetto';
            }
        });
    }

    // Inizializza la validazione
    validateForm();

    // Test delle funzioni di messaggio (no verbose logging)

    // Suggerimenti dinamici per le categorie
    const categoryHelp = {
        'tecnologia': 'Perfetto per app, software, innovazioni tech e startup digitali.',
        'arte': 'Ideale per opere d\'arte, installazioni, progetti creativi e design.',
        'musica': 'Ottimo per album, concerti, strumenti musicali e produzioni audio.',
        'video': 'Adatto per film, documentari, web series e contenuti video.',
        'giochi': 'Perfetto per videogiochi, giochi da tavolo e esperienze interattive.',
        'editoria': 'Ideale per libri, riviste, fumetti e contenuti editoriali.',
        'cibo': 'Ottimo per ristoranti, prodotti alimentari e esperienze culinarie.',
        'moda': 'Adatto per abbigliamento, accessori e progetti di moda.',
        'salute': 'Perfetto per prodotti benessere, fitness e innovazioni mediche.',
        'educazione': 'Ideale per corsi, materiali didattici e progetti formativi.',
        'sociale': 'Ottimo per cause sociali, volontariato e impatto sociale.',
        'ambiente': 'Adatto per progetti green, sostenibilità e tutela ambientale.'
    };

    document.getElementById('category').addEventListener('change', function() {
        const selectedCategory = this.value;
        let helpText = this.parentNode.querySelector('.category-help');

        if (selectedCategory && categoryHelp[selectedCategory]) {
            if (!helpText) {
                helpText = document.createElement('div');
                helpText.className = 'category-help form-text text-info mt-2';
                helpText.innerHTML = '<i class="bi bi-lightbulb me-1"></i>';
                this.parentNode.appendChild(helpText);
            }
            helpText.innerHTML = '<i class="bi bi-lightbulb me-1"></i>' + categoryHelp[selectedCategory];
        } else if (helpText) {
            helpText.remove();
        }
    });
    </script>

    <?php include __DIR__ . '/includes/scripts.php'; ?>
</body>

</html>
