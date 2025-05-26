<?php
/**
 * Logging su MongoDB per eventi di sistema.
 * Richiede la libreria mongodb/mongodb installata via Composer.
 */

require_once __DIR__ . '/../vendor/autoload.php'; // MongoDB PHP Library

/**
 * Registra un evento su MongoDB.
 *
 * @param int $user_id      ID utente che ha generato l'evento
 * @param string $azione    Azione eseguita (es: 'login', 'finanziamento', ecc.)
 * @param mixed $dettagli   Array o stringa con dettagli aggiuntivi
 * @return bool             true se successo, false se errore
 */
function log_event($user_id, $azione, $dettagli) {
    try {
        // Verifica che la classe MongoDB sia caricata
        if (!class_exists('MongoDB\Client')) {
            throw new Exception('Estensione MongoDB non caricata');
        }

        $mongo = new MongoDB\Client("mongodb://localhost:27017");
        $collection = $mongo->bostarter->eventi_log;

        $result = $collection->insertOne([
            'user_id' => $user_id,
            'azione' => $azione,
            'dettagli' => $dettagli,
            'data' => new MongoDB\BSON\UTCDateTime()
        ]);

        return $result->isAcknowledged();
    } catch (Exception $e) {
        error_log('Errore log_event MongoDB: ' . $e->getMessage());
        return false;
    }
}
?>
