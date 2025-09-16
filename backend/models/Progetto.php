<?php
require_once __DIR__ . '/ProgettoBase.php';
require_once __DIR__ . '/../utils/MongoLogger.php';

class Progetto extends ProgettoBase {
    // Costruttore con il database
    public function __construct($db) {
        parent::__construct($db);
        $this->mongoLogger = MongoLogger::getInstance();
    }
    
    // Ottieni progetto per ID
    public function getById($id) {
        $query = "SELECT p.*, u.nickname as creatore_nickname, u.avatar_url as creatore_avatar 
                 FROM " . $this->table . " p
                 JOIN utenti u ON p.creatore_id = u.id
                 WHERE p.id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcola i giorni rimanenti e la percentuale di raccolta
            $progetto['giorni_rimanenti'] = $this->calcolaGiorniRimanenti($progetto['data_fine']);
            $progetto['percentuale_raccolta'] = $this->calcolaPercentualeRaccolta($progetto['budget'], $progetto['finanziamento_attuale']);
            
            return $progetto;
        }
        
        return null;
    }
    
    // Aggiorna un progetto
    public function aggiorna($id, $dati, $utente_id) {
        // Verifica che l'utente sia il creatore del progetto o un amministratore
        if(!$this->utentePuoModificareProgetto($id, $utente_id)) {
            $this->mongoLogger->logSecurity('unauthorized_project_update', $utente_id, [
                'project_id' => $id,
                'attempted_changes' => $dati,
                'severity' => 'high'
            ]);
            throw new Exception("Non hai i permessi per modificare questo progetto");
        }
        
        // Costruisci dinamicamente la query di aggiornamento
        $campiDaAggiornare = [];
        $parametri = [':id' => $id];
        
        // Campi che possono essere aggiornati
        $campiModificabili = [
            'titolo', 'descrizione', 'descrizione_breve', 'budget', 'tipo_progetto', 
            'categoria', 'copertina_url', 'video_url', 'data_fine', 'paese', 'citta',
            'latitudine', 'longitudine', 'stato'
        ];
        
        foreach($campiModificabili as $campo) {
            if(isset($dati[$campo])) {
                // Pulisci i dati in base al tipo di campo
                switch($campo) {
                    case 'titolo':
                    case 'descrizione_breve':
                    case 'categoria':
                    case 'paese':
                    case 'citta':
                        $valore = htmlspecialchars(strip_tags($dati[$campo]));
                        break;
                        
                    case 'descrizione':
                        $valore = $dati[$campo]; // Contenuto HTML, non fare strip_tags
                        break;
                        
                    case 'budget':
                    case 'latitudine':
                    case 'longitudine':
                        $valore = floatval($dati[$campo]);
                        break;
                        
                    case 'copertina_url':
                    case 'video_url':
                        $valore = filter_var($dati[$campo], FILTER_SANITIZE_URL);
                        break;
                        
                    case 'tipo_progetto':
                        $valore = in_array($dati[$campo], ['hardware', 'software']) ? $dati[$campo] : 'software';
                        break;
                        
                    case 'stato':
                        $statiAmmessi = ['bozza', 'in_revisione', 'pubblicato', 'sospeso'];
                        $valore = in_array($dati[$campo], $statiAmmessi) ? $dati[$campo] : 'bozza';
                        break;
                        
                    default:
                        $valore = $dati[$campo];
                }
                
                // Se è stato aggiornato il titolo, rigenera lo slug
                if($campo === 'titolo') {
                    $slug = $this->generaSlug($valore);
                    $campiDaAggiornare[] = "slug = :slug";
                    $parametri[':slug'] = $slug;
                }
                
                $campiDaAggiornare[] = "$campo = :$campo";
                $parametri[":$campo"] = $valore;
                
                // Aggiorna la proprietà dell'oggetto
                $this->$campo = $valore;
            }
        }
        
        if(empty($campiDaAggiornare)) {
            return false; // Nessun campo da aggiornare
        }
        
        // Aggiungi la data di aggiornamento
        $campiDaAggiornare[] = "updated_at = NOW()";
        
        $query = "UPDATE " . $this->table . " SET " . implode(", ", $campiDaAggiornare) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $result = $stmt->execute($parametri);
        
        // Log the update operation
        if($result) {
            $this->mongoLogger->logProject('update', $id, $utente_id, [
                'fields_updated' => array_keys($dati),
                'old_values' => $this->getById($id),
                'new_values' => $dati
            ]);
        }
        
        return $result;
    }
    
    // Ottieni progetto per slug
    public function getBySlug($slug) {
        $query = "SELECT p.*, u.nickname as creatore_nickname, u.avatar_url as creatore_avatar,
                         c.affidabilita as creatore_affidabilita
                 FROM " . $this->table . " p
                 JOIN utenti u ON p.creatore_id = u.id
                 LEFT JOIN creatori c ON p.creatore_id = c.utente_id
                 WHERE p.slug = :slug LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":slug", $slug);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcola i giorni rimanenti e la percentuale di raccolta
            $progetto['giorni_rimanenti'] = $this->calcolaGiorniRimanenti($progetto['data_fine']);
            $progetto['percentuale_raccolta'] = $this->calcolaPercentualeRaccolta($progetto['budget'], $progetto['finanziamento_attuale']);
            
            return $progetto;
        }
        
        return null;
    }
    
    // Verifica se l'utente può modificare il progetto
    public function utentePuoModificareProgetto($progetto_id, $utente_id) {
        // Se l'utente è amministratore, può modificare qualsiasi progetto
        $utente = new Utente($this->db);
        if($utente->isAmministratore($utente_id)) {
            return true;
        }
        
        // Altrimenti, verifica se è il creatore del progetto
        $query = "SELECT id FROM " . $this->table . " WHERE id = :id AND creatore_id = :creatore_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $progetto_id);
        $stmt->bindParam(":creatore_id", $utente_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Elenca i progetti con filtri e paginazione
    public function elenca($filtri = [], $pagina = 1, $per_pagina = 10) {
        $dove = [];
        $parametri = [];
        $offset = ($pagina - 1) * $per_pagina;
        
        // Costruisci la clausola WHERE in base ai filtri
        if(isset($filtri['stato'])) {
            $dove[] = "p.stato = :stato";
            $parametri[':stato'] = $filtri['stato'];
        } else {
            // Di default, mostra solo i progetti pubblicati
            $dove[] = "p.stato = 'pubblicato'";
        }
        
        if(isset($filtri['tipo_progetto'])) {
            $dove[] = "p.tipo_progetto = :tipo_progetto";
            $parametri[':tipo_progetto'] = $filtri['tipo_progetto'];
        }
        
        if(isset($filtri['categoria'])) {
            $dove[] = "p.categoria = :categoria";
            $parametri[':categoria'] = $filtri['categoria'];
        }
        
        if(isset($filtri['creatore_id'])) {
            $dove[] = "p.creatore_id = :creatore_id";
            $parametri[':creatore_id'] = $filtri['creatore_id'];
        }
        
        if(isset($filtri['ricerca'])) {
            $dove[] = "(p.titolo LIKE :ricerca OR p.descrizione_breve LIKE :ricerca OR p.descrizione LIKE :ricerca)";
            $parametri[':ricerca'] = "%" . $filtri['ricerca'] . "%";
        }
        
        // Costruisci la query
        $query = "SELECT p.*, u.nickname as creatore_nickname, u.avatar_url as creatore_avatar 
                 FROM " . $this->table . " p
                 JOIN utenti u ON p.creatore_id = u.id";
        
        if(!empty($dove)) {
            $query .= " WHERE " . implode(" AND ", $dove);
        }
        
        // Aggiungi ordinamento
        $ordine = "p.updated_at DESC";
        if(isset($filtri['ordina_per'])) {
            $direzione = isset($filtri['ordine']) && strtoupper($filtri['ordine']) === 'ASC' ? 'ASC' : 'DESC';
            
            switch($filtri['ordina_per']) {
                case 'data_creazione':
                    $ordine = "p.created_at $direzione";
                    break;
                case 'data_scadenza':
                    $ordine = "p.data_fine $direzione";
                    break;
                case 'popolarita':
                    $ordine = "p.finanziamento_attuale $direzione";
                    break;
            }
        }
        
        $query .= " ORDER BY $ordine";
        
        // Aggiungi limiti per la paginazione
        $query .= " LIMIT :offset, :per_pagina";
        
        // Prepara ed esegui la query
        $stmt = $this->db->prepare($query);
        
        // Aggiungi i parametri
        foreach($parametri as $chiave => $valore) {
            $stmt->bindValue($chiave, $valore);
        }
        
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':per_pagina', (int)$per_pagina, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $progetti = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calcola i giorni rimanenti e la percentuale di raccolta
            $row['giorni_rimanenti'] = $this->calcolaGiorniRimanenti($row['data_fine']);
            $row['percentuale_raccolta'] = $this->calcolaPercentualeRaccolta($row['budget'], $row['finanziamento_attuale']);
            
            $progetti[] = $row;
        }
        
        // Ottieni il conteggio totale per la paginazione
        $conteggio = $this->contaProgetti($filtri);
        
        return [
            'progetti' => $progetti,
            'paginazione' => [
                'pagina_corrente' => $pagina,
                'per_pagina' => $per_pagina,
                'totale' => $conteggio,
                'pagine_totali' => ceil($conteggio / $per_pagina)
            ]
        ];
    }
    
    // Conta i progetti in base ai filtri
    private function contaProgetti($filtri = []) {
        $dove = [];
        $parametri = [];
        
        // Stessa logica di filtraggio del metodo elenca()
        if(isset($filtri['stato'])) {
            $dove[] = "stato = :stato";
            $parametri[':stato'] = $filtri['stato'];
        } else {
            $dove[] = "stato = 'pubblicato'";
        }
        
        if(isset($filtri['tipo_progetto'])) {
            $dove[] = "tipo_progetto = :tipo_progetto";
            $parametri[':tipo_progetto'] = $filtri['tipo_progetto'];
        }
        
        if(isset($filtri['categoria'])) {
            $dove[] = "categoria = :categoria";
            $parametri[':categoria'] = $filtri['categoria'];
        }
        
        if(isset($filtri['creatore_id'])) {
            $dove[] = "creatore_id = :creatore_id";
            $parametri[':creatore_id'] = $filtri['creatore_id'];
        }
        
        if(isset($filtri['ricerca'])) {
            $dove[] = "(titolo LIKE :ricerca OR descrizione_breve LIKE :ricerca OR descrizione LIKE :ricerca)";
            $parametri[':ricerca'] = "%" . $filtri['ricerca'] . "%";
        }
        
        $query = "SELECT COUNT(*) as totale FROM " . $this->table;
        
        if(!empty($dove)) {
            $query .= " WHERE " . implode(" AND ", $dove);
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach($parametri as $chiave => $valore) {
            $stmt->bindValue($chiave, $valore);
        }
        
        $stmt->execute();
        $risultato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$risultato['totale'];
    }
    
    // Metodo getAll per compatibilità con l'API
    public function getAll($filters = []) {
        $result = $this->elenca($filters, 1, isset($filters['limit']) ? (int)$filters['limit'] : 10);
        return $result['progetti'];
    }
    
    // Ottieni le categorie dei progetti
    public function getCategorie() {
        $query = "SELECT DISTINCT categoria FROM " . $this->table . " WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Pubblica un progetto (cambia stato da bozza a in_revisione)
    public function pubblica($progetto_id, $utente_id) {
        // Verifica che l'utente sia il creatore del progetto
        $progetto = $this->getById($progetto_id);
        
        if(!$progetto) {
            throw new Exception("Progetto non trovato");
        }
        
        if($progetto['creatore_id'] != $utente_id) {
            $this->mongoLogger->logSecurity('unauthorized_project_publish', $utente_id, [
                'project_id' => $progetto_id,
                'severity' => 'high'
            ]);
            throw new Exception("Non hai i permessi per pubblicare questo progetto");
        }
        
        // Verifica che il progetto non sia già pubblicato
        if($progetto['stato'] !== 'bozza') {
            throw new Exception("Questo progetto è già stato inviato per la pubblicazione");
        }
        
        // Verifica che il progetto abbia tutti i campi obbligatori
        $campiObbligatori = [
            'titolo' => $progetto['titolo'],
            'descrizione' => $progetto['descrizione'],
            'descrizione_breve' => $progetto['descrizione_breve'],
            'budget' => $progetto['budget'],
            'categoria' => $progetto['categoria'],
            'copertina_url' => $progetto['copertina_url'],
            'data_fine' => $progetto['data_fine']
        ];
        
        $campiMancanti = [];
        foreach($campiObbligatori as $campo => $valore) {
            if(empty($valore)) {
                $campiMancanti[] = $campo;
            }
        }
        
        if(!empty($campiMancanti)) {
            $this->mongoLogger->logProject('publish_failed', $progetto_id, $utente_id, [
                'missing_fields' => $campiMancanti,
                'reason' => 'required_fields_missing'
            ]);
            throw new Exception("Compila tutti i campi obbligatori prima di pubblicare: " . implode(", ", $campiMancanti));
        }
        
        // Verifica che la data di fine sia nel futuro
        $oggi = new DateTime();
        $data_fine = new DateTime($progetto['data_fine']);
        
        if($data_fine <= $oggi) {
            $this->mongoLogger->logProject('publish_failed', $progetto_id, $utente_id, [
                'reason' => 'invalid_end_date',
                'end_date' => $progetto['data_fine']
            ]);
            throw new Exception("La data di fine progetto deve essere nel futuro");
        }
        
        // Verifica che il budget sia maggiore di 0
        if($progetto['budget'] <= 0) {
            $this->mongoLogger->logProject('publish_failed', $progetto_id, $utente_id, [
                'reason' => 'invalid_budget',
                'budget' => $progetto['budget']
            ]);
            throw new Exception("Il budget deve essere maggiore di 0");
        }
        
        // Aggiorna lo stato del progetto
        $query = "UPDATE " . $this->table . " SET stato = 'in_revisione', updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $progetto_id);
        
        $result = $stmt->execute();
        
        if($result) {
            $this->mongoLogger->logProject('publish', $progetto_id, $utente_id, [
                'new_status' => 'in_revisione',
                'project_data' => $progetto
            ]);
        }
        
        return $result;
    }
    
    // Approva un progetto (da parte di un amministratore)
    public function approva($progetto_id, $amministratore_id) {
        // Verifica que l'utente sia un amministratore
        $utente = new Utente($this->db);
        if(!$utente->isAmministratore($amministratore_id)) {
            $this->mongoLogger->logSecurity('unauthorized_project_approval', $amministratore_id, [
                'project_id' => $progetto_id,
                'severity' => 'high'
            ]);
            throw new Exception("Solo un amministratore può approvare un progetto");
        }
        
        // Verifica que il progetto esista e sia in attesa di approvazione
        $progetto = $this->getById($progetto_id);
        
        if(!$progetto) {
            throw new Exception("Progetto non trovato");
        }
        
        if($progetto['stato'] !== 'in_revisione') {
            $this->mongoLogger->logProject('approval_failed', $progetto_id, $amministratore_id, [
                'reason' => 'invalid_status',
                'current_status' => $progetto['stato']
            ]);
            throw new Exception("Questo progetto non è in attesa di approvazione");
        }
        
        // Aggiorna lo stato del progetto e imposta la data di inizio
        $query = "UPDATE " . $this->table . " 
                 SET stato = 'pubblicato', 
                     data_inizio = NOW(),
                     updated_at = NOW() 
                 WHERE id = :id";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $progetto_id);
        
        $result = $stmt->execute();
        
        if($result) {
            $this->mongoLogger->logProject('approval', $progetto_id, $amministratore_id, [
                'new_status' => 'pubblicato',
                'start_date' => date('Y-m-d H:i:s'),
                'project_data' => $progetto
            ]);
        }
        
        return $result;
    }
    
    // Rifiuta un progetto (da parte di un amministratore)
    public function rifiuta($progetto_id, $amministratore_id, $motivo) {
        // Verifica que l'utente sia un amministratore
        $utente = new Utente($this->db);
        if(!$utente->isAmministratore($amministratore_id)) {
            $this->mongoLogger->logSecurity('unauthorized_project_rejection', $amministratore_id, [
                'project_id' => $progetto_id,
                'severity' => 'high'
            ]);
            throw new Exception("Solo un amministratore può rifiutare un progetto");
        }
        
        // Verifica que il progetto esista e sia in attesa di approvazione
        $progetto = $this->getById($progetto_id);
        
        if(!$progetto) {
            throw new Exception("Progetto non trovato");
        }
        
        if($progetto['stato'] !== 'in_revisione') {
            $this->mongoLogger->logProject('rejection_failed', $progetto_id, $amministratore_id, [
                'reason' => 'invalid_status',
                'current_status' => $progetto['stato']
            ]);
            throw new Exception("Questo progetto non è in attesa di approvazione");
        }
        
        // Pulisci il motivo
        $motivo = htmlspecialchars(strip_tags($motivo));
        
        // Aggiorna lo stato del progetto e aggiungi il motivo del rifiuto
        $query = "UPDATE " . $this->table . " 
                 SET stato = 'bozza', 
                     note_rifiuto = :motivo,
                     updated_at = NOW() 
                 WHERE id = :id";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $progetto_id);
        $stmt->bindParam(":motivo", $motivo);
        
        $result = $stmt->execute();
        
        if($result) {
            $this->mongoLogger->logProject('rejection', $progetto_id, $amministratore_id, [
                'new_status' => 'bozza',
                'rejection_reason' => $motivo,
                'project_data' => $progetto
            ]);
        }
        
        return $result;
    }
    
    // Chiudi un progetto (quando viene raggiunto il budget o scade il tempo)
    public function chiudi($progetto_id, $utente_id) {
        // Verifica que l'utente sia il creatore del progetto o un amministratore
        $progetto = $this->getById($progetto_id);
        
        if(!$progetto) {
            throw new Exception("Progetto non trovato");
        }
        
        $utente = new Utente($this->db);
        if($progetto['creatore_id'] != $utente_id && !$utente->isAmministratore($utente_id)) {
            $this->mongoLogger->logSecurity('unauthorized_project_closure', $utente_id, [
                'project_id' => $progetto_id,
                'severity' => 'high'
            ]);
            throw new Exception("Non hai i permessi per chiudere questo progetto");
        }
        
        // Verifica que il progetto non sia già chiuso
        if($progetto['stato'] === 'chiuso') {
            $this->mongoLogger->logProject('closure_failed', $progetto_id, $utente_id, [
                'reason' => 'already_closed'
            ]);
            throw new Exception("Questo progetto è già stato chiuso");
        }
        
        // Aggiorna lo stato del progetto
        $query = "UPDATE " . $this->table . " 
                 SET stato = 'chiuso', 
                     data_chiusura = NOW(),
                     updated_at = NOW() 
                 WHERE id = :id";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $progetto_id);
        
        $result = $stmt->execute();
        
        if($result) {
            $this->mongoLogger->logProject('closure', $progetto_id, $utente_id, [
                'new_status' => 'chiuso',
                'closure_date' => date('Y-m-d H:i:s'),
                'project_data' => $progetto
            ]);
        }
        
        return $result;
    }
    
    // Aggiungi un'immagine alla galleria del progetto
    public function aggiungiImmagineGalleria($progetto_id, $url_immagine, $didascalia = '') {
        // Verifica che il progetto esista
        $progetto = $this->getById($progetto_id);
        
        if(!$progetto) {
            throw new Exception("Progetto non trovato");
        }
        
        // Pulisci i dati
        $url_immagine = filter_var($url_immagine, FILTER_SANITIZE_URL);
        $didascalia = htmlspecialchars(strip_tags($didascalia));
        
        // Inserisci l'immagine nella galleria
        $query = "INSERT INTO galleria_progetti 
                 (progetto_id, url_immagine, didascalia) 
                 VALUES 
                 (:progetto_id, :url_immagine, :didascalia)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":progetto_id", $progetto_id);
        $stmt->bindParam(":url_immagine", $url_immagine);
        $stmt->bindParam(":didascalia", $didascalia);
        
        $result = $stmt->execute();
        
        if($result) {
            $imageId = $this->db->lastInsertId();
            $this->mongoLogger->logProject('gallery_add', $progetto_id, $progetto['creatore_id'], [
                'image_id' => $imageId,
                'image_url' => $url_immagine,
                'caption' => $didascalia
            ]);
        }
        
        return $result;
    }
    
    // Ottieni le immagini della galleria di un progetto
    public function getGalleria($progetto_id) {
        $query = "SELECT * FROM galleria_progetti WHERE progetto_id = :progetto_id ORDER BY ordine, id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":progetto_id", $progetto_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Rimuovi un'immagine dalla galleria del progetto
    public function rimuoviImmagineGalleria($immagine_id, $utente_id) {
        // Verifica che l'utente abbia i permessi
        $query = "SELECT p.creatore_id 
                 FROM galleria_progetti gp
                 JOIN progetti p ON gp.progetto_id = p.id
                 WHERE gp.id = :immagine_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":immagine_id", $immagine_id);
        $stmt->execute();
        
        if($stmt->rowCount() === 0) {
            $this->mongoLogger->logProject('gallery_remove_failed', null, $utente_id, [
                'reason' => 'image_not_found',
                'image_id' => $immagine_id
            ]);
            throw new Exception("Immagine non trovata");
        }
        
        $risultato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $utente = new Utente($this->db);
        if($risultato['creatore_id'] != $utente_id && !$utente->isAmministratore($utente_id)) {
            $this->mongoLogger->logSecurity('unauthorized_gallery_remove', $utente_id, [
                'image_id' => $immagine_id,
                'severity' => 'medium'
            ]);
            throw new Exception("Non hai i permessi per rimuovere questa immagine");
        }
        
        // Elimina l'immagine
        $query = "DELETE FROM galleria_progetti WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $immagine_id);
        
        $result = $stmt->execute();
        
        if($result) {
            $this->mongoLogger->logProject('gallery_remove', $risultato['progetto_id'], $utente_id, [
                'image_id' => $immagine_id
            ]);
        }
        
        return $result;
    }
}
?>
