<?php
/**
 * Fund Project - Process project funding
 * BOSTARTER - Crowdfunding Platform
 */

session_start();
require_once '../config/db_config.php';
require_once '../utils/Auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../frontend/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = (int)$_POST['project_id'] ?? 0;
    $amount = (float)$_POST['amount'] ?? 0;
    $user_id = $_SESSION['user_id'];
    
    // Validate input
    if ($project_id <= 0 || $amount <= 0) {        $_SESSION['error'] = "Dati non validi";
        header('Location: ../../frontend/projects/view_project.php?id=' . $project_id);
        exit();
    }
    
    try {
        $pdo = getDbConnection();
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if project exists and is active
        $stmt = $pdo->prepare("
            SELECT id, titolo, obiettivo_finanziario, data_scadenza, stato 
            FROM progetti 
            WHERE id = ? AND stato = 'attivo' AND data_scadenza > NOW()
        ");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if (!$project) {
            throw new Exception("Progetto non trovato o non più attivo");
        }
        
        // Check if user has sufficient funds (if implementing user wallet)
        // For now, we'll assume payment is handled externally
        
        // Record the funding
        $stmt = $pdo->prepare("
            INSERT INTO finanziamenti (progetto_id, utente_id, importo, data_finanziamento, stato_pagamento) 
            VALUES (?, ?, ?, NOW(), 'completato')
        ");
        $stmt->execute([$project_id, $user_id, $amount]);
        
        // Update project's current funding
        $stmt = $pdo->prepare("
            UPDATE progetti 
            SET finanziamento_attuale = finanziamento_attuale + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $project_id]);
        
        // Check if project reached its goal
        $stmt = $pdo->prepare("
            SELECT finanziamento_attuale, obiettivo_finanziario 
            FROM progetti 
            WHERE id = ?
        ");
        $stmt->execute([$project_id]);
        $updated_project = $stmt->fetch();
        
        if ($updated_project['finanziamento_attuale'] >= $updated_project['obiettivo_finanziario']) {
            $stmt = $pdo->prepare("
                UPDATE progetti 
                SET stato = 'finanziato' 
                WHERE id = ?
            ");
            $stmt->execute([$project_id]);
        }
        
        // Create notification for project creator
        $stmt = $pdo->prepare("
            SELECT creatore_id FROM progetti WHERE id = ?
        ");
        $stmt->execute([$project_id]);
        $creator = $stmt->fetch();
        
        if ($creator) {
            $stmt = $pdo->prepare("
                INSERT INTO notifiche (utente_id, tipo, messaggio, data_creazione) 
                VALUES (?, 'finanziamento', ?, NOW())
            ");
            $message = "Il tuo progetto ha ricevuto un nuovo finanziamento di €" . number_format($amount, 2);
            $stmt->execute([$creator['creatore_id'], $message]);
        }
        
        $pdo->commit();
          $_SESSION['success'] = "Finanziamento completato con successo!";
        header('Location: ../../frontend/projects/view_project.php?id=' . $project_id);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Funding error: " . $e->getMessage());        $_SESSION['error'] = "Errore durante il finanziamento: " . $e->getMessage();
        header('Location: ../../frontend/projects/view_project.php?id=' . $project_id);
    }
    
} else {
    // Redirect to home if not POST request
    header('Location: ../../frontend/index.php');
}

exit();
?>
