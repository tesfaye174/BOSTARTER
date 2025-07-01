<?php
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/database.php';
class GestoreRicompense {
    private $database;
    private $connessione;
    public function __construct() {
        $this->database = Database::getInstance();
        $this->connessione = $this->database->getConnection();
    }    
    public function ottieniListaRicompense($idProgetto) {
        try {
            $statement = $this->connessione->prepare("
                SELECT *
                FROM reward
                WHERE progetto_id = ?
                ORDER BY created_at ASC
            ");
            $statement->execute([$idProgetto]);
            return $statement->fetchAll();
        } catch (PDOException $errore) {
            error_log("Errore nel recupero ricompense: " . $errore->getMessage());
            return [];
        }
    }
    public function ottieniDettagliRicompensa($idRicompensa) {
        try {
            $statement = $this->connessione->prepare("
                SELECT r.*, p.nome as titolo_progetto, u.nickname as nickname_creatore
                FROM reward r
                JOIN progetti p ON r.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE r.id = ?
            ");
            $statement->execute([$idRicompensa]);
            $ricompensa = $statement->fetch();
            if (!$ricompensa) {
                return null;
            }
            $statement = $this->connessione->prepare("
                SELECT COUNT(*) as numero_donazioni
                FROM finanziamenti
                WHERE reward_id = ?            ");
            $statement->execute([$idRicompensa]);
            $ricompensa['numero_donazioni'] = $statement->fetchColumn();
            return $ricompensa;
        } catch (PDOException $errore) {
            error_log("Errore nel recupero dettagli ricompensa: " . $errore->getMessage());
            return null;
        }
    }
    public function creaNuovaRicompensa($datiRicompensa) {
        try {
            $statement = $this->connessione->prepare("
                INSERT INTO reward (
                    progetto_id, codice, descrizione
                ) VALUES (?, ?, ?)
            ");
              $statement->execute([
                $datiRicompensa['progetto_id'],
                $datiRicompensa['codice'],
                $datiRicompensa['descrizione']
            ]);
            return [
                'successo' => true,
                'id_ricompensa' => $this->connessione->lastInsertId(),
                'messaggio' => 'Ricompensa creata con successo'
            ];
        } catch (PDOException $errore) {
            error_log("Errore nella creazione ricompensa: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante la creazione della ricompensa'
            ];
        }
    }
    public function aggiornaRicompensa($idRicompensa, $datiAggiornamento) {
        try {
            $campiConsentiti = [
                'titolo', 'descrizione', 'importo_minimo',
                'quantita_disponibile', 'data_consegna'
            ];
            $aggiornamenti = [];
            $parametri = [];
            foreach ($datiAggiornamento as $campo => $valore) {
                if (in_array($campo, $campiConsentiti)) {
                    $aggiornamenti[] = "$campo = ?";
                    $parametri[] = $valore;
                }
            }
            if (empty($aggiornamenti)) {
                return [
                    'successo' => false,
                    'messaggio' => 'Nessun campo valido da aggiornare'
                ];
            }
            $parametri[] = $idRicompensa;
            $query = "UPDATE reward SET " . implode(', ', $aggiornamenti) . " WHERE id = ?";
            $statement = $this->connessione->prepare($query);
            $statement->execute($parametri);
            return [
                'successo' => true,
                'messaggio' => 'Ricompensa aggiornata con successo'
            ];
        } catch (PDOException $errore) {
            error_log("Errore nell'aggiornamento ricompensa: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'aggiornamento della ricompensa'
            ];
        }
    }
    public function eliminaRicompensa($idRicompensa) {
        try {
            $statement = $this->connessione->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $statement->execute([$idRicompensa]);
            if ($statement->fetchColumn() > 0) {
                return [
                    'successo' => false,
                    'messaggio' => 'Impossibile eliminare la ricompensa: ci sono donazioni associate'
                ];
            }
            $statement = $this->connessione->prepare("DELETE FROM reward WHERE id = ?");
            $statement->execute([$idRicompensa]);
              return [
                'successo' => true,
                'messaggio' => 'Ricompensa eliminata con successo'
            ];
        } catch (PDOException $errore) {
            error_log("Errore nell'eliminazione ricompensa: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'eliminazione della ricompensa'
            ];
        }
    }
    public function verificaDisponibilita($idRicompensa) {
        try {
            $statement = $this->connessione->prepare("
                SELECT COUNT(*) as disponibile
                FROM reward
                WHERE id = ?
            ");
            $statement->execute([$idRicompensa]);
            $risultato = $statement->fetchColumn();
            return [
                'disponibile' => $risultato > 0,
                'quantita' => 1 
            ];
        } catch (PDOException $errore) {
            error_log("Errore nella verifica disponibilità: " . $errore->getMessage());
            return [
                'disponibile' => false,
                'quantita' => 0
            ];
        }
    }
    public function ottieniDonazioniRicompensa($idRicompensa, $pagina = 1, $elementiPerPagina = 10) {
        try {
            $offset = ($pagina - 1) * $elementiPerPagina;
            $statement = $this->connessione->prepare("
                SELECT f.*, u.nickname as nickname_donatore, u.avatar as avatar_donatore
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                WHERE f.reward_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT ? OFFSET ?
            ");
            $statement->execute([$idRicompensa, $elementiPerPagina, $offset]);
            $donazioni = $statement->fetchAll();
            $statement = $this->connessione->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $statement->execute([$idRicompensa]);
            $totaleDonazioni = $statement->fetchColumn();
            return [
                'donazioni' => $donazioni,
                'totale' => $totaleDonazioni,
                'pagina' => $pagina,
                'per_pagina' => $elementiPerPagina,
                'totale_pagine' => ceil($totaleDonazioni / $elementiPerPagina)
            ];
        } catch (PDOException $errore) {
            error_log("Errore nel recupero donazioni: " . $errore->getMessage());
            return null;
        }
    }
    public function ottieniStatisticheProgetto($idProgetto) {
        try {
            $statement = $this->connessione->prepare("
                SELECT COUNT(*) as numero_ricompense,
                       COUNT(*) as totale_disponibili
                FROM reward
                WHERE progetto_id = ?
            ");
            $statement->execute([$idProgetto]);
            $statistiche = $statement->fetch();
            $statement = $this->connessione->prepare("
                SELECT r.id, r.descrizione,
                       COUNT(f.id) as numero_donazioni,
                       SUM(f.importo) as totale_donazioni
                FROM reward r
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.progetto_id = ?
                GROUP BY r.id
                ORDER BY r.created_at ASC
            ");
            $statement->execute([$idProgetto]);
            $statistiche['ricompense'] = $statement->fetchAll();
            return $statistiche;
        } catch (PDOException $errore) {
            error_log("Errore nel recupero statistiche progetto: " . $errore->getMessage());
            return null;
        }
    }
}
