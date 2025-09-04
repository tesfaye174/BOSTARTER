<?php
session_start();
require_once 'includes/init.php';

// Verifica autenticazione
if (!isAuthenticated()) {
    header('Location: auth/login.php');
    exit();
}

$userType = getUserType();
$userId = $_SESSION['user_id'];

// Recupera statistiche
$statistiche = [];
$error = null;

try {
    $response = file_get_contents("http://localhost/BOSTARTER/backend/api/statistiche.php");
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success']) {
        $statistiche = $data['data'];
    } else {
        $error = $data['error'] ?? 'Errore nel recupero statistiche';
    }
} catch (Exception $e) {
    $error = 'Errore di connessione: ' . $e->getMessage();
}

include 'includes/head.php';
?>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="mb-2">
                    <i class="fas fa-chart-bar"></i>
                    Statistiche BOSTARTER
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle"></i>
                    Panoramica completa della piattaforma di crowdfunding
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <!-- Statistiche Generali -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-bostarter-primary mb-3"></i>
                            <h3 class="card-title"><?php echo number_format($statistiche['generali']['totale_utenti']); ?></h3>
                            <p class="card-text text-muted">Utenti Totali</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-lightbulb fa-3x text-bostarter-success mb-3"></i>
                            <h3 class="card-title"><?php echo number_format($statistiche['generali']['totale_progetti']); ?></h3>
                            <p class="card-text text-muted">Progetti Totali</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-euro-sign fa-3x text-bostarter-warning mb-3"></i>
                            <h3 class="card-title">€<?php echo number_format($statistiche['generali']['totale_finanziato'], 0); ?></h3>
                            <p class="card-text text-muted">Totale Finanziato</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-handshake fa-3x text-bostarter-info mb-3"></i>
                            <h3 class="card-title"><?php echo number_format($statistiche['generali']['candidature_accettate']); ?></h3>
                            <p class="card-text text-muted">Candidature Accettate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 3 Creatori per Affidabilità -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-trophy"></i>
                        Top 3 Creatori per Affidabilità
                    </h5>
                    </div>
                    <div class="card-body">
                    <?php if (empty($statistiche['top_creatori'])): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessun creatore disponibile per le statistiche.</p>
                        </div>
                        <?php else: ?>
                            <div class="row">
                            <?php foreach ($statistiche['top_creatori'] as $index => $creatore): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card text-center h-100">
                                        <div class="card-body">
                                            <div class="position-relative mb-3">
                                                <?php if ($index === 0): ?>
                                                    <i class="fas fa-crown fa-3x text-warning"></i>
                                                <?php elseif ($index === 1): ?>
                                                    <i class="fas fa-medal fa-3x text-secondary"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-award fa-3x text-bronze"></i>
                                                <?php endif; ?>
                                                <div class="position-absolute top-0 start-100 translate-middle badge bg-bostarter-primary rounded-pill">
                                                    #<?php echo $index + 1; ?>
                                                </div>
                                            </div>
                                            <h5 class="card-title">@<?php echo htmlspecialchars($creatore['nickname']); ?></h5>
                                            <div class="mb-2">
                                                <span class="badge bg-bostarter-success fs-6">
                                                    <?php echo $creatore['affidabilita']; ?>% Affidabilità
                                                </span>
                                            </div>
                                            <div class="row text-muted small">
                                                <div class="col-6">
                                                    <strong><?php echo $creatore['progetti_creati']; ?></strong><br>
                                                    Progetti Creati
                                                </div>
                                                <div class="col-6">
                                                    <strong><?php echo $creatore['progetti_completati']; ?></strong><br>
                                                    Completati
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
            </div>
        </div>

            <!-- Top 3 Progetti Quasi Completati -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-target"></i>
                        Top 3 Progetti Quasi Completati
                    </h5>
                    </div>
                    <div class="card-body">
                    <?php if (empty($statistiche['progetti_quasi_completi'])): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-target fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessun progetto aperto disponibile per le statistiche.</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($statistiche['progetti_quasi_completi'] as $index => $progetto): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h6 class="card-title"><?php echo htmlspecialchars($progetto['nome']); ?></h6>
                                                <span class="badge bg-bostarter-primary">#<?php echo $index + 1; ?></span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">Progresso</small>
                                                    <small class="text-muted"><?php echo $progetto['percentuale_completamento']; ?>%</small>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-bostarter-success" role="progressbar" 
                                                        style="width: <?php echo $progetto['percentuale_completamento']; ?>%">
                                                    </div>
                                                </div>
                                        </div>
                                            
                                            <div class="row text-muted small mb-3">
                                                <div class="col-6">
                                                    <strong>€<?php echo number_format($progetto['budget_raccolto'], 0); ?></strong><br>
                                                    Raccolto
                                        </div>
                                                <div class="col-6">
                                                    <strong>€<?php echo number_format($progetto['budget_richiesto'], 0); ?></strong><br>
                                                    Richiesto
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo $progetto['giorni_rimanenti']; ?> giorni
                                                </small>
                                                <a href="view.php?id=<?php echo $progetto['id'] ?? '#'; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Vedi
                                                </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
            </div>
        </div>

            <!-- Top 3 Finanziatori -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-heart"></i>
                            Top 3 Finanziatori
                    </h5>
                    </div>
                    <div class="card-body">
                    <?php if (empty($statistiche['top_finanziatori'])): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessun finanziamento disponibile per le statistiche.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-bostarter">
                                    <tr>
                                        <th>Posizione</th>
                                        <th>Utente</th>
                                        <th>Numero Finanziamenti</th>
                                        <th>Totale Finanziato</th>
                                        <th>Importo Medio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statistiche['top_finanziatori'] as $index => $finanziatore): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index === 0): ?>
                                                    <i class="fas fa-crown text-warning"></i>
                                                <?php elseif ($index === 1): ?>
                                                    <i class="fas fa-medal text-secondary"></i>
                        <?php else: ?>
                                                    <i class="fas fa-award text-bronze"></i>
                                                <?php endif; ?>
                                                <strong>#<?php echo $index + 1; ?></strong>
                                            </td>
                                            <td>
                                                <strong>@<?php echo htmlspecialchars($finanziatore['nickname']); ?></strong>
                                                <?php if (isset($finanziatore['nome'])): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars($finanziatore['nome'] . ' ' . $finanziatore['cognome']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-bostarter-info">
                                                    <?php echo $finanziatore['numero_finanziamenti']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    €<?php echo number_format($finanziatore['totale_finanziato'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    €<?php echo number_format($finanziatore['totale_finanziato'] / $finanziatore['numero_finanziamenti'], 2); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiche Dettagliate -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-chart-pie"></i> Progetti per Stato</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Aperti</span>
                                <span class="badge bg-success"><?php echo $statistiche['generali']['progetti_aperti']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Chiusi</span>
                                <span class="badge bg-secondary"><?php echo $statistiche['generali']['progetti_chiusi']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Creatori</span>
                                <span class="badge bg-bostarter-primary"><?php echo $statistiche['generali']['totale_creatori']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-chart-line"></i> Performance</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Tasso Completamento</span>
                                <span class="badge bg-bostarter-success">
                                    <?php 
                                    $tasso = $statistiche['generali']['totale_progetti'] > 0 
                                        ? round(($statistiche['generali']['progetti_chiusi'] / $statistiche['generali']['totale_progetti']) * 100, 1)
                                        : 0;
                                    echo $tasso . '%';
                                    ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Finanziamenti Attivi</span>
                                <span class="badge bg-bostarter-warning">
                                    <?php echo $statistiche['generali']['progetti_aperti']; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Utenti Attivi</span>
                                <span class="badge bg-bostarter-info">
                                    <?php echo $statistiche['generali']['totale_utenti']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/scripts.php'; ?>
    
    <script>
        // Aggiorna statistiche in tempo reale
        function refreshStats() {
            window.location.reload();
        }

        // Auto-refresh ogni 5 minuti
        setInterval(refreshStats, 300000);

        // Animazioni per i numeri
        function animateNumbers() {
            const numbers = document.querySelectorAll('.card-title');
            numbers.forEach(number => {
                const finalValue = parseInt(number.textContent.replace(/,/g, ''));
                if (!isNaN(finalValue)) {
                    animateValue(number, 0, finalValue, 2000);
                }
            });
        }

        function animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        }

        // Inizializza animazioni quando la pagina è caricata
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(animateNumbers, 500);
        });
    </script>
</body>
</html>
