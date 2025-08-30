<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . "/../backend/config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$nickname = $_SESSION["nickname"];
$tipo_utente = $_SESSION["tipo_utente"];

$stats = ["progetti_creati" => 0, "fondi_raccolti" => 0, "finanziamenti_fatti" => 0, "totale_investito" => 0];
$progetti = [];
$finanziamenti = [];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($tipo_utente === "creatore") {
        // Statistiche creatore
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM progetti WHERE creatore_id = ?");
        $stmt->execute([$user_id]);
        $stats["progetti_creati"] = $stmt->fetch()["count"] ?? 0;
        
        $stmt = $conn->prepare("SELECT COALESCE(SUM(budget_raccolto), 0) as totale FROM progetti WHERE creatore_id = ?");
        $stmt->execute([$user_id]);
        $stats["fondi_raccolti"] = $stmt->fetch()["totale"] ?? 0;
        
        // Progetti del creatore
        $stmt = $conn->prepare("
            SELECT * FROM progetti 
            WHERE creatore_id = ? 
            ORDER BY data_inserimento DESC
        ");
        $stmt->execute([$user_id]);
        $progetti = $stmt->fetchAll();
    } else {
        // Statistiche investitore - per ora zero dato che non ci sono finanziamenti
        $stats["finanziamenti_fatti"] = 0;
        $stats["totale_investito"] = 0;
        $finanziamenti = [];
    }
} catch(Exception $e) {
    $error = "Errore nel caricamento dei dati: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<?php $page_title = 'Dashboard'; include __DIR__ . '/includes/head.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="home.php">Home</a>
                <a class="nav-link" href="view.php">Progetti</a>
                <?php if ($tipo_utente === "creatore"): ?>
                    <a class="nav-link" href="new.php">Nuovo Progetto</a>
                <?php endif; ?>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($nickname) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="auth/exit.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4 page-offset">
        <!-- Header con messaggio personalizzato -->
        <div class="row mb-4">
            <div class="col-12">
                <?php 
                $welcome_messages = [
                    "Ciao {name}! Bentornato nella tua dashboard.",
                    "Eccoti qui, {name}! Pronto per nuove avventure?",
                    "Benvenuto {name}! Vediamo cosa puoi creare oggi.",
                    "Ciao {name}! La tua creativity station ti aspetta."
                ];
                $random_welcome = str_replace('{name}', htmlspecialchars($nickname), $welcome_messages[array_rand($welcome_messages)]);
                ?>
                <h1 class="display-6"><?= $random_welcome ?></h1>
                <p class="text-muted">
                    <?php if ($tipo_utente === "creatore"): ?>
                        Dashboard Creatore - Qui puoi gestire i tuoi progetti e vedere come stanno andando
                    <?php else: ?>
                        Dashboard Investitore - Tieni traccia dei progetti che hai supportato
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <?php 
            $error_variations = [
                "Attenzione! Si è verificato un piccolo problema: ",
                "Qualcosa non ha funzionato come doveva: ",
                "Abbiamo riscontrato un inconveniente: ",
                "C'è stato un intoppo: "
            ];
            $error_prefix = $error_variations[array_rand($error_variations)];
            ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error_prefix . htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="row mb-4">
            <?php if ($tipo_utente === "creatore"): ?>
                <div class="col-md-6">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-project-diagram me-2"></i>Progetti Creati</h5>
                            <h2><?= $stats["progetti_creati"] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-euro-sign me-2"></i>Fondi Raccolti</h5>
                            <h2>€<?= number_format($stats["fondi_raccolti"], 2) ?></h2>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-hand-holding-usd me-2"></i>Finanziamenti</h5>
                            <h2><?= $stats["finanziamenti_fatti"] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-coins me-2"></i>Totale Investito</h5>
                            <h2>€<?= number_format($stats["totale_investito"], 2) ?></h2>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contenuto principale -->
        <div class="row">
            <div class="col-12">
                <?php if ($tipo_utente === "creatore"): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">I Tuoi Progetti</h5>
                            <a href="new.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Nuovo Progetto
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($progetti)): ?>
                                <?php 
                                $empty_messages = [
                                    "Ancora nessun progetto? È il momento perfetto per iniziare!",
                                    "La tua prima grande idea ti aspetta. Crea il tuo primo progetto!",
                                    "Questo spazio sembra un po' vuoto... che ne dici di riempirlo con le tue idee?",
                                    "Zero progetti finora. Trasforma la tua creatività in realtà!"
                                ];
                                ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-lightbulb fa-3x text-muted mb-3"></i>
                                    <p class="text-muted"><?= $empty_messages[array_rand($empty_messages)] ?></p>
                                    <a href="new.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Crea il Tuo Primo Progetto
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Stato</th>
                                                <th>Raccolto</th>
                                                <th>Data</th>
                                                <th>Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($progetti as $progetto): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($progetto['nome']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $progetto['stato'] === 'aperto' ? 'success' : 'secondary' ?>">
                                                            <?= ucfirst($progetto['stato']) ?>
                                                        </span>
                                                    </td>
                                                    <td>€<?= number_format($progetto['budget_raccolto'], 2) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($progetto['data_inserimento'])) ?></td>
                                                    <td>
                                                        <a href="view.php?id=<?= $progetto['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">I Tuoi Finanziamenti</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($finanziamenti)): ?>
                                <?php 
                                $investor_messages = [
                                    "Non hai ancora supportato nessun progetto. Scopri le idee più interessanti!",
                                    "Che ne dici di investire nella prossima grande innovazione?",
                                    "Il tuo primo investimento ti aspetta. Esplora i progetti disponibili!",
                                    "Ancora nessun finanziamento? È tempo di supportare qualche creatore!"
                                ];
                                ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <p class="text-muted"><?= $investor_messages[array_rand($investor_messages)] ?></p>
                                    <a href="view.php" class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>Esplora i Progetti
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Progetto</th>
                                                <th>Importo</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($finanziamenti as $finanziamento): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($finanziamento['nome']) ?></td>
                                                    <td>€<?= number_format($finanziamento['importo'], 2) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($finanziamento['data_finanziamento'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/scripts.php'; ?>
</body>
</html>
