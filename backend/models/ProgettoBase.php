<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Utente.php';

class ProgettoBase {
    protected $db;
    protected $table = 'progetti';

    // Proprietà del progetto
    public $id;
    public $creatore_id;
    public $titolo;
    public $slug;
    public $descrizione;
    public $descrizione_breve;
    public $budget;
    public $finanziamento_attuale;
    public $tipo_progetto;
    public $categoria;
    public $copertina_url;
    public $video_url;
    public $data_inizio;
    public $data_fine;
    public $stato;
    public $paese;
    public $citta;
    public $latitudine;
    public $longitudine;
    public $created_at;
    public $updated_at;

    // Costruttore con il database
    public function __construct($db) {
        $this->db = $db;
    }

    // Crea un nuovo progetto
    public function crea($dati) {
        // Verifica se l'utente è un creatore
        $utente = new Utente($this->db);
        if(!$utente->isCreatore($dati['creatore_id'])) {
            throw new Exception("Devi essere un creatore per pubblicare un progetto");
        }

        // Genera lo slug dal titolo
        $slug = $this->generaSlug($dati['titolo']);
        
        // Query di inserimento
        $query = "INSERT INTO " . $this->table . " 
                 (creatore_id, titolo, slug, descrizione, descrizione_breve, budget, 
                  tipo_progetto, categoria, copertina_url, video_url, data_fine, 
                  paese, citta, latitudine, longitudine, stato) 
                 VALUES 
                 (:creatore_id, :titolo, :slug, :descrizione, :descrizione_breve, :budget, 
                  :tipo_progetto, :categoria, :copertina_url, :video_url, :data_fine, 
                  :paese, :citta, :latitudine, :longitudine, 'bozza')";
        
        $stmt = $this->db->prepare($query);
        
        // Pulisci e valida i dati
        $this->creatore_id = intval($dati['creatore_id']);
        $this->titolo = htmlspecialchars(strip_tags($dati['titolo']));
        $this->slug = $slug;
        $this->descrizione = $dati['descrizione'];
        $this->descrizione_breve = htmlspecialchars(strip_tags($dati['descrizione_breve']));
        $this->budget = floatval($dati['budget']);
        $this->tipo_progetto = in_array($dati['tipo_progetto'], ['hardware', 'software']) ? $dati['tipo_progetto'] : 'software';
        $this->categoria = htmlspecialchars(strip_tags($dati['categoria']));
        $this->copertina_url = filter_var($dati['copertina_url'] ?? '', FILTER_SANITIZE_URL);
        $this->video_url = filter_var($dati['video_url'] ?? '', FILTER_SANITIZE_URL);
        $this->data_fine = $dati['data_fine'];
        $this->paese = htmlspecialchars(strip_tags($dati['paese'] ?? ''));
        $this->citta = htmlspecialchars(strip_tags($dati['citta'] ?? ''));
        $this->latitudine = isset($dati['latitudine']) ? floatval($dati['latitudine']) : null;
        $this->longitudine = isset($dati['longitudine']) ? floatval($dati['longitudine']) : null;
        
        // Esegui la query
        $stmt->bindParam(":creatore_id", $this->creatore_id);
        $stmt->bindParam(":titolo", $this->titolo);
        $stmt->bindParam(":slug", $this->slug);
        $stmt->bindParam(":descrizione", $this->descrizione);
        $stmt->bindParam(":descrizione_breve", $this->descrizione_breve);
        $stmt->bindParam(":budget", $this->budget);
        $stmt->bindParam(":tipo_progetto", $this->tipo_progetto);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":copertina_url", $this->copertina_url);
        $stmt->bindParam(":video_url", $this->video_url);
        $stmt->bindParam(":data_fine", $this->data_fine);
        $stmt->bindParam(":paese", $this->paese);
        $stmt->bindParam(":citta", $this->citta);
        $stmt->bindParam(":latitudine", $this->latitudine);
        $stmt->bindParam(":longitudine", $this->longitudine);
        
        if($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            return $this->id;
        }
        
        return false;
    }
    
    // Genera uno slug univoco dal titolo
    protected function generaSlug($titolo) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titolo)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Verifica se lo slug esiste già
        $slugOriginale = $slug;
        $contatore = 1;
        
        while($this->slugEsiste($slug)) {
            $slug = $slugOriginale . '-' . $contatore;
            $contatore++;
        }
        
        return $slug;
    }
    
    // Verifica se uno slug esiste già
    protected function slugEsiste($slug) {
        $query = "SELECT id FROM " . $this->table . " WHERE slug = :slug";
        if(isset($this->id)) {
            $query .= " AND id != " . $this->id;
        }
        $query .= " LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":slug", $slug);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Calcola i giorni rimanenti per la fine del progetto
    protected function calcolaGiorniRimanenti($data_fine) {
        $data_fine = new DateTime($data_fine);
        $oggi = new DateTime();
        $intervallo = $oggi->diff($data_fine);
        
        if($data_fine < $oggi) {
            return 0; // Progetto scaduto
        }
        
        return $intervallo->days;
    }
    
    // Calcola la percentuale di raccolta
    protected function calcolaPercentualeRaccolta($budget, $finanziamento_attuale) {
        if($budget <= 0) return 0;
        $percentuale = ($finanziamento_attuale / $budget) * 100;
        return round(min($percentuale, 100), 2); // Massimo 100%
    }
}
?>
