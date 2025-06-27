<?php
namespace BOSTARTER\Controllers;

/**
 * Controller Progetti BOSTARTER
 * 
 * Questo controller implementa tutte le operazioni CRUD relative ai progetti:
 * - Creazione e validazione di nuovi progetti
 * - Aggiunta di ricompense ai progetti
 * - Pubblicazione e gestione visibilità progetti
 * - Operazioni di finanziamento e transazioni monetarie
 * - Recupero progetti per creatore o categoria
 * 
 * Interagisce con le stored procedure del database per garantire
 * l'integrità transazionale e la sicurezza delle operazioni.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.5.0 - Aggiunta sicurezza transazionale
 */

// Includiamo le dipendenze necessarie
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/BaseController.php';

use BOSTARTER\Utils\BaseController;

class ProjectController extends BaseController {
    /** @var Database $database Istanza del database */
    private Database $database;
    
    /** @var MongoLogger $logger Logger per operazioni */
    private MongoLogger $logger;
    
    /** @var Validator $validator Validatore dati */
    private Validator $validator;
    
    /** @var array $config Configurazione progetti */
    private array $config;
    
    /** @var array $allowedCategories Categorie permesse */
    private const ALLOWED_CATEGORIES = [
        'Technology', 'Design', 'Gaming', 'Art', 'Music', 'Film', 'Publishing', 'Food', 'Fashion', 'Other'
    ];
    
    /** @var array $projectStatuses Stati del progetto */
    private const PROJECT_STATUSES = [
        'draft' => 'Bozza',
        'review' => 'In Revisione',
        'active' => 'Attivo',
        'funded' => 'Finanziato',
        'completed' => 'Completato',
        'cancelled' => 'Annullato'
    ];
    
    /** @var int MIN_FUNDING_GOAL Obiettivo minimo di finanziamento */
    private const MIN_FUNDING_GOAL = 500;
    
    /** @var int MAX_FUNDING_GOAL Obiettivo massimo di finanziamento */
    private const MAX_FUNDING_GOAL = 1000000;

    /**
     * Costruttore - Inizializza le dipendenze
     * 
     * @throws Exception Se l'inizializzazione fallisce
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->database = Database::getInstance();
        $this->logger = new MongoLogger();
        $this->validator = new Validator();
        $this->config = require __DIR__ . '/../config/project_config.php';
    }

    /**
     * Crea un nuovo progetto con validazione completa
     * 
     * @param array $projectData Dati del progetto
     * @param int $creatorId ID del creatore
     * @return array Risultato dell'operazione
     */
    public function createProject(array $projectData, int $creatorId): array
    {
        try {
            // Validazione dati progetto
            $validation = $this->validateProjectData($projectData);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['message'], $validation['errors']);
            }

            // Controllo limiti utente
            if (!$this->canUserCreateProject($creatorId)) {
                return $this->createErrorResponse('Hai raggiunto il numero massimo di progetti attivi.');
            }

            // Inizio transazione
            $this->database->getConnection()->beginTransaction();

            // Preparazione dati per inserimento
            $projectData = $this->sanitizeProjectData($projectData);
            $projectData['creator_id'] = $creatorId;
            $projectData['status'] = 'draft';
            $projectData['created_at'] = date('Y-m-d H:i:s');
            $projectData['updated_at'] = date('Y-m-d H:i:s');

            // Inserimento progetto
            $projectId = $this->insertProject($projectData);
            
            if (!$projectId) {
                throw new Exception('Creazione del progetto fallita');
            }

            // Gestione immagini se presenti
            if (!empty($projectData['images'])) {
                $this->handleProjectImages($projectId, $projectData['images']);
            }

            // Gestione competenze richieste
            if (!empty($projectData['skills'])) {
                $this->attachProjectSkills($projectId, $projectData['skills']);
            }

            // Commit transazione
            $this->database->getConnection()->commit();

            // Log dell'operazione
            $this->logger->logUserAction('project_created', [
                'project_id' => $projectId,
                'creator_id' => $creatorId,
                'title' => $projectData['title']
            ]);

            return $this->createSuccessResponse('Progetto creato con successo', [
                'project_id' => $projectId,
                'status' => 'draft'
            ]);

        } catch (Exception $e) {
            // Rollback transazione
            if ($this->database->getConnection()->inTransaction()) {
                $this->database->getConnection()->rollBack();
            }

            $this->logger->logError('Creazione progetto fallita: ' . $e->getMessage(), [
                'creator_id' => $creatorId,
                'project_data' => $projectData
            ]);

            return $this->createErrorResponse('Creazione progetto fallita: ' . $e->getMessage());
        }
    }

    /**
     * Aggiorna un progetto esistente
     * 
     * @param int $projectId ID del progetto
     * @param array $updateData Dati da aggiornare
     * @param int $userId ID dell'utente che richiede l'aggiornamento
     * @return array Risultato dell'operazione
     */
    public function updateProject(int $projectId, array $updateData, int $userId): array
    {
        try {
            // Verifica permessi
            if (!$this->canUserEditProject($projectId, $userId)) {
                return $this->createErrorResponse('Non hai permesso di modificare questo progetto.');
            }

            // Recupera progetto esistente
            $existingProject = $this->getProjectById($projectId);
            if (!$existingProject) {
                return $this->createErrorResponse('Progetto non trovato.');
            }

            // Controllo stato progetto
            if (!$this->canProjectBeEdited($existingProject['status'])) {
                return $this->createErrorResponse('Questo progetto non può essere modificato nello stato attuale.');
            }

            // Validazione dati di aggiornamento
            $validation = $this->validateProjectUpdateData($updateData, $existingProject);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['message'], $validation['errors']);
            }

            // Inizio transazione
            $this->database->getConnection()->beginTransaction();

            // Preparazione dati
            $updateData = $this->sanitizeProjectData($updateData);
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            // Aggiornamento progetto
            $success = $this->performProjectUpdate($projectId, $updateData);
            
            if (!$success) {
                throw new Exception('Aggiornamento del progetto fallito');
            }

            // Gestione cambiamenti nelle immagini
            if (isset($updateData['images'])) {
                $this->updateProjectImages($projectId, $updateData['images']);
            }

            // Gestione cambiamenti nelle competenze
            if (isset($updateData['skills'])) {
                $this->updateProjectSkills($projectId, $updateData['skills']);
            }

            // Commit transazione
            $this->database->getConnection()->commit();

            // Log dell'operazione
            $this->logger->logUserAction('project_updated', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'updated_fields' => array_keys($updateData)
            ]);

            return $this->createSuccessResponse('Progetto aggiornato con successo');

        } catch (Exception $e) {
            // Rollback transazione
            if ($this->database->getConnection()->inTransaction()) {
                $this->database->getConnection()->rollBack();
            }

            $this->logger->logError('Aggiornamento progetto fallito: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'user_id' => $userId
            ]);

            return $this->createErrorResponse('Aggiornamento progetto fallito: ' . $e->getMessage());
        }
    }

    /**
     * Pubblica un progetto (cambia stato da draft a review)
     * 
     * @param int $projectId ID del progetto
     * @param int $userId ID dell'utente
     * @return array Risultato dell'operazione
     */
    public function publishProject(int $projectId, int $userId): array
    {
        try {
            // Verifica permessi
            if (!$this->canUserEditProject($projectId, $userId)) {
                return $this->createErrorResponse('Non hai permesso di pubblicare questo progetto.');
            }

            // Recupera progetto
            $project = $this->getProjectById($projectId);
            if (!$project) {
                return $this->createErrorResponse('Progetto non trovato.');
            }

            // Controllo stato
            if ($project['status'] !== 'draft') {
                return $this->createErrorResponse('Solo i progetti in bozza possono essere pubblicati.');
            }

            // Validazione completezza progetto
            $completeness = $this->validateProjectCompleteness($project);
            if (!$completeness['valid']) {
                return $this->createErrorResponse('Il progetto è incompleto', $completeness['missing_fields']);
            }

            // Aggiornamento stato
            $success = $this->updateProjectStatus($projectId, 'review');
            
            if (!$success) {
                throw new Exception('Pubblicazione del progetto fallita');
            }

            // Log dell'operazione
            $this->logger->logUserAction('project_published', [
                'project_id' => $projectId,
                'user_id' => $userId
            ]);

            // Notifica agli amministratori per revisione
            $this->notifyAdministratorsForReview($projectId);

            return $this->createSuccessResponse('Progetto inviato per revisione con successo');

        } catch (Exception $e) {
            $this->logger->logError('Pubblicazione progetto fallita: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'user_id' => $userId
            ]);

            return $this->createErrorResponse('Pubblicazione progetto fallita: ' . $e->getMessage());
        }
    }

    /**
     * Crea un nuovo progetto sulla piattaforma
     * 
     * Implementa la validazione dei dati e utilizza stored procedure
     * per garantire l'atomicità dell'operazione e la consistenza dei dati.
     * Gestisce anche errori di database con messaggi appropriati.
     * 
     * @param array $projectData Dati del progetto con chiavi:
     *                          - name: Nome del progetto (string)
     *                          - creator_id: ID dell'utente creatore (int)
     *                          - description: Descrizione dettagliata (string)
     *                          - budget: Budget richiesto in euro (float)
     *                          - project_type: Tipologia del progetto (string)
     *                          - end_date: Data termine raccolta fondi (YYYY-MM-DD)
     * @return array Risultato dell'operazione con status, messaggio e ID progetto se successo
     * @throws PDOException In caso di errori di database
     */
    public function createProjectOld($projectData) {
        try {
            // Prima di tutto, controlliamo che i dati siano validi
            $validation = Validator::validateProjectData($projectData);
            if ($validation !== true) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Dati del progetto non validi: ' . implode(', ', $validation)
                ];
            }

            // Prepariamo la chiamata alla stored procedure per creare il progetto
            $statement = $this->connessioneDatabase->prepare("CALL create_project(?, ?, ?, ?, ?, ?, @p_project_id, @p_success, @p_message)");
            
            // Associamo i parametri in modo sicuro
            $statement->bindParam(1, $projectData['name']);
            $statement->bindParam(2, $projectData['creator_id'], \PDO::PARAM_INT);
            $statement->bindParam(3, $projectData['description']);
            $statement->bindParam(4, $projectData['budget']);
            $statement->bindParam(5, $projectData['project_type']);
            $statement->bindParam(6, $projectData['end_date']);
            $statement->execute();

            // Otteniamo il risultato della stored procedure
            $risultato = $this->connessioneDatabase->query("SELECT @p_project_id as project_id, @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            if ($risultato['success']) {
                return [
                    'stato' => 'successo',
                    'messaggio' => 'Progetto creato con successo! Ora è visibile sulla piattaforma.',
                    'id_progetto' => $risultato['project_id']
                ];
            } else {
                return [
                    'stato' => 'errore',
                    'messaggio' => $risultato['message'],
                    'id_progetto' => null
                ];
            }
            
        } catch (\PDOException $errore) {
            // Log dell'errore per il debug
            error_log("Errore nella creazione progetto: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un problema durante la creazione del progetto. Riprova più tardi.'
            ];
        } catch (\Exception $errore) {
            // Errore generico
            error_log("Errore generico nella creazione progetto: " . $errore->getMessage());
              return [
                'stato' => 'errore',
                'messaggio' => 'Errore imprevisto. Il nostro team è stato notificato.'
            ];
        }
    }

    /**
     * Aggiunge una ricompensa a un progetto esistente
     * 
     * Le ricompense sono elementi fondamentali nei progetti di crowdfunding
     * e vengono gestite come entità separate ma collegate al progetto principale.
     * 
     * @param array $rewardData Dati della ricompensa con chiavi:
     *                         - project_id: ID del progetto associato (int)
     *                         - title: Titolo della ricompensa (string)
     *                         - description: Descrizione della ricompensa (string)
     *                         - amount: Importo minimo di donazione per ottenerla (float)
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function addProjectReward($rewardData) {
        // Validiamo i dati della ricompensa
        $validation = Validator::validateRewardData($rewardData);
        if ($validation !== true) {
            return [
                'stato' => 'errore',
                'messaggio' => implode(', ', $validation)
            ];
        }

        try {            
            // Prepariamo la chiamata per aggiungere la ricompensa
            $statement = $this->connessioneDatabase->prepare("CALL add_project_reward(?, ?, ?, ?, @p_success, @p_message)");
            
            $statement->bindParam(1, $rewardData['project_id'], \PDO::PARAM_INT);
            $statement->bindParam(2, $rewardData['title']);
            $statement->bindParam(3, $rewardData['description']);
            $statement->bindParam(4, $rewardData['amount']);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];        
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco ad aggiungere la ricompensa: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Pubblica un progetto rendendolo visibile a tutti gli utenti
     * 
     * La pubblicazione è un'operazione critica che implica controlli di validità
     * e completezza del progetto. Solo progetti completi possono essere pubblicati.
     * La stored procedure verifica la presenza di tutti i campi obbligatori e
     * almeno una ricompensa prima della pubblicazione.
     * 
     * @param int $projectId L'ID del progetto da pubblicare
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function publishProjectOld($projectId) {        
        try {
            $statement = $this->connessioneDatabase->prepare("CALL publish_project(?, @p_success, @p_message)");            
            $statement->bindParam(1, $projectId, \PDO::PARAM_INT);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];        
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a pubblicare il progetto: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Recupera tutti i progetti di un creatore specifico
     * 
     * Questo metodo utilizza una stored procedure ottimizzata per recuperare
     * tutti i progetti associati a un creatore, inclusi i dati sullo stato
     * attuale di finanziamento.
     * 
     * @param int $creatorId L'ID dell'utente creatore
     * @return array Risultato contenente lo stato dell'operazione e la lista dei progetti
     * @throws PDOException In caso di errori di database
     */
    public function getCreatorProjects($creatorId) {        
        try {
            $statement = $this->connessioneDatabase->prepare("CALL get_creator_projects(?)");
            
            $statement->bindParam(1, $creatorId, \PDO::PARAM_INT);
            $statement->execute();

            return [
                'stato' => 'successo',
                'progetti' => $statement->fetchAll(\PDO::FETCH_ASSOC)
            ];
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare i progetti: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Gestisce il finanziamento di un progetto da parte di un utente
     * 
     * Implementa una transazione per garantire che il finanziamento sia registrato
     * correttamente e che vengano creati tutti i record associati (ricompense, notifiche).
     * La stored procedure gestisce l'atomicità dell'operazione e i controlli di validità.
     * 
     * @param array $fundingData Array con i dati del finanziamento:
     *                          - project_id: ID del progetto da finanziare (int)
     *                          - user_id: ID dell'utente finanziatore (int)
     *                          - amount: Importo del finanziamento in euro (float)
     * @return array Risultato dell'operazione di finanziamento
     * @throws PDOException In caso di errori nelle transazioni
     */
    public function fundProject($fundingData) {
        // Validiamo i dati del finanziamento
        $validation = Validator::validateFundData($fundingData);
        if ($validation !== true) {
            return [
                'stato' => 'errore',
                'messaggio' => implode(', ', $validation)
            ];
        }

        try {            
            // Chiamiamo la stored procedure per il finanziamento
            $statement = $this->connessioneDatabase->prepare("CALL fund_project(?, ?, ?, @p_success, @p_message)");
            
            $statement->bindParam(1, $fundingData['project_id'], \PDO::PARAM_INT);
            $statement->bindParam(2, $fundingData['user_id'], \PDO::PARAM_INT);
            $statement->bindParam(3, $fundingData['amount']);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un problema durante il finanziamento: ' . $errore->getMessage()
            ];        
        }
    }

    /**
     * Valida i dati di un progetto
     * 
     * @param array $projectData
     * @return array
     */
    private function validateProjectData(array $projectData): array
    {
        $errors = [];
        $required = ['title', 'description', 'category', 'funding_goal', 'duration_days'];

        // Controllo campi obbligatori
        foreach ($required as $field) {
            if (empty($projectData[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        // Validazione titolo
        if (!empty($projectData['title'])) {
            if (strlen($projectData['title']) < 10) {
                $errors['title'] = 'Title must be at least 10 characters';
            } elseif (strlen($projectData['title']) > 100) {
                $errors['title'] = 'Title cannot exceed 100 characters';
            }
        }

        // Validazione descrizione
        if (!empty($projectData['description'])) {
            if (strlen($projectData['description']) < 100) {
                $errors['description'] = 'Description must be at least 100 characters';
            } elseif (strlen($projectData['description']) > 5000) {
                $errors['description'] = 'Description cannot exceed 5000 characters';
            }
        }

        // Validazione categoria
        if (!empty($projectData['category']) && !in_array($projectData['category'], self::ALLOWED_CATEGORIES)) {
            $errors['category'] = 'Invalid category selected';
        }

        // Validazione obiettivo di finanziamento
        if (!empty($projectData['funding_goal'])) {
            $goal = floatval($projectData['funding_goal']);
            if ($goal < self::MIN_FUNDING_GOAL) {
                $errors['funding_goal'] = 'Funding goal must be at least €' . number_format(self::MIN_FUNDING_GOAL);
            } elseif ($goal > self::MAX_FUNDING_GOAL) {
                $errors['funding_goal'] = 'Funding goal cannot exceed €' . number_format(self::MAX_FUNDING_GOAL);
            }
        }

        // Validazione durata
        if (!empty($projectData['duration_days'])) {
            $duration = intval($projectData['duration_days']);
            if ($duration < 7) {
                $errors['duration_days'] = 'Campaign duration must be at least 7 days';
            } elseif ($duration > 60) {
                $errors['duration_days'] = 'Campaign duration cannot exceed 60 days';
            }
        }

        // Validazione URL video (opzionale)
        if (!empty($projectData['video_url']) && !filter_var($projectData['video_url'], FILTER_VALIDATE_URL)) {
            $errors['video_url'] = 'Invalid video URL format';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : 'Validation failed',
            'errors' => $errors
        ];
    }

    /**
     * Controlla se un utente può creare un nuovo progetto
     * 
     * @param int $userId
     * @return bool
     */
    private function canUserCreateProject(int $userId): bool
    {
        $stmt = $this->database->getConnection()->prepare("
            SELECT COUNT(*) as active_projects 
            FROM progetti 
            WHERE id_creatore = ? AND status IN ('draft', 'review', 'active')
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        $maxActiveProjects = $this->config['max_active_projects_per_user'] ?? 5;
        
        return $result['active_projects'] < $maxActiveProjects;
    }

    /**
     * Sanitizza i dati del progetto
     * 
     * @param array $data
     * @return array
     */
    private function sanitizeProjectData(array $data): array
    {
        $sanitized = [];
        
        $stringFields = ['title', 'description', 'category', 'video_url'];
        $numericFields = ['funding_goal', 'duration_days'];
        
        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
            }
        }
        
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = is_numeric($data[$field]) ? $data[$field] : 0;
            }
        }
        
        return $sanitized;
    }

    /**
     * Inserisce un nuovo progetto nel database
     * 
     * @param array $projectData
     * @return int|false ID del progetto creato o false in caso di errore
     */
    private function insertProject(array $projectData): int|false
    {
        $stmt = $this->database->getConnection()->prepare("
            INSERT INTO progetti (
                id_creatore, titolo, descrizione, categoria, 
                obiettivo_finanziamento, durata_giorni, url_video, 
                status, data_creazione, data_aggiornamento
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $projectData['creator_id'],
            $projectData['title'],
            $projectData['description'],
            $projectData['category'],
            $projectData['funding_goal'],
            $projectData['duration_days'],
            $projectData['video_url'] ?? null,
            $projectData['status'],
            $projectData['created_at'],
            $projectData['updated_at']
        ]);
        
        return $success ? $this->database->getConnection()->lastInsertId() : false;
    }

    /**
     * Gestisce le immagini del progetto
     * 
     * @param int $projectId
     * @param array $images
     * @return bool
     */
    private function handleProjectImages(int $projectId, array $images): bool
    {
        try {
            $stmt = $this->database->getConnection()->prepare("
                INSERT INTO progetti_immagini (id_progetto, url_immagine, ordine, is_primary) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($images as $index => $imageUrl) {
                $stmt->execute([
                    $projectId,
                    $imageUrl,
                    $index + 1,
                    $index === 0 ? 1 : 0 // Prima immagine come primaria
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->logError('Failed to handle project images: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'images' => $images
            ]);
            
            return false;
        }
    }

    /**
     * Associa competenze al progetto
     * 
     * @param int $projectId
     * @param array $skillIds
     * @return bool
     */
    private function attachProjectSkills(int $projectId, array $skillIds): bool
    {
        try {
            $stmt = $this->database->getConnection()->prepare("
                INSERT INTO progetti_competenze (id_progetto, id_competenza) 
                VALUES (?, ?)
            ");
            
            foreach ($skillIds as $skillId) {
                $stmt->execute([$projectId, $skillId]);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->logError('Failed to attach project skills: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'skill_ids' => $skillIds
            ]);
            
            return false;
        }
    }

    /**
     * Controlla se un utente può modificare un progetto
     * 
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    private function canUserEditProject(int $projectId, int $userId): bool
    {
        $stmt = $this->database->getConnection()->prepare("
            SELECT id_creatore, status 
            FROM progetti 
            WHERE id = ?
        ");
        
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        
        if (!$project) {
            return false;
        }
        
        // Il creatore può sempre modificare
        if ($project['id_creatore'] == $userId) {
            return true;
        }
        
        // Gli amministratori possono modificare progetti in revisione
        if (isset($_SESSION['user']['tipo_utente']) && 
            $_SESSION['user']['tipo_utente'] === 'amministratore' && 
            $project['status'] === 'review') {
            return true;
        }
        
        return false;
    }

    /**
     * Controlla se un progetto può essere modificato in base al suo stato
     * 
     * @param string $status
     * @return bool
     */
    private function canProjectBeEdited(string $status): bool
    {
        $editableStatuses = ['draft', 'review'];
        return in_array($status, $editableStatuses);
    }

    /**
     * Recupera un progetto per ID
     * 
     * @param int $projectId
     * @return array|null
     */
    private function getProjectById(int $projectId): ?array
    {
        $stmt = $this->database->getConnection()->prepare("
            SELECT * FROM progetti WHERE id = ?
        ");
        
        $stmt->execute([$projectId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Valida la completezza di un progetto prima della pubblicazione
     * 
     * @param array $project
     * @return array
     */
    private function validateProjectCompleteness(array $project): array
    {
        $missingFields = [];
        
        // Campi obbligatori per la pubblicazione
        $requiredFields = [
            'titolo' => 'title',
            'descrizione' => 'description',
            'categoria' => 'category',
            'obiettivo_finanziamento' => 'funding goal',
            'durata_giorni' => 'campaign duration'
        ];
        
        foreach ($requiredFields as $field => $label) {
            if (empty($project[$field])) {
                $missingFields[] = $label;
            }
        }
        
        // Controllo presenza di almeno un'immagine
        $stmt = $this->database->getConnection()->prepare("
            SELECT COUNT(*) as image_count 
            FROM progetti_immagini 
            WHERE id_progetto = ?
        ");
        
        $stmt->execute([$project['id']]);
        $imageResult = $stmt->fetch();
        
        if ($imageResult['image_count'] == 0) {
            $missingFields[] = 'at least one project image';
        }
        
        return [
            'valid' => empty($missingFields),
            'missing_fields' => $missingFields
        ];
    }

    /**
     * Aggiorna lo stato di un progetto
     * 
     * @param int $projectId
     * @param string $status
     * @return bool
     */
    private function updateProjectStatus(int $projectId, string $status): bool
    {
        $stmt = $this->database->getConnection()->prepare("
            UPDATE progetti 
            SET status = ?, data_aggiornamento = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$status, $projectId]);
    }

    /**
     * Notifica gli amministratori per la revisione di un progetto
     * 
     * @param int $projectId
     */
    private function notifyAdministratorsForReview(int $projectId): void
    {
        // TODO: Implementare sistema di notifiche
        // Placeholder per il servizio di notifiche
        $this->logger->logUserAction('project_review_requested', [
            'project_id' => $projectId
        ]);
    }

    /**
     * Recupera progetti con filtri e paginazione
     * 
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProjects(array $filters = [], int $page = 1, int $limit = 12): array
    {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = [];
            $params = [];
            
            // Costruzione filtri
            if (!empty($filters['category'])) {
                $whereConditions[] = "categoria = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            } else {
                // Default: solo progetti attivi per utenti normali
                $whereConditions[] = "status = 'active'";
            }
            
            if (!empty($filters['creator_id'])) {
                $whereConditions[] = "id_creatore = ?";
                $params[] = $filters['creator_id'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(titolo LIKE ? OR descrizione LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Query principale
            $sql = "
                SELECT p.*, 
                       u.nome as creator_name, 
                       u.cognome as creator_surname,
                       COALESCE(SUM(f.importo), 0) as current_funding,
                       COUNT(DISTINCT f.id) as backer_count
                FROM progetti p
                LEFT JOIN utenti u ON p.id_creatore = u.id
                LEFT JOIN finanziamenti f ON p.id = f.id_progetto
                $whereClause
                GROUP BY p.id
                ORDER BY p.data_creazione DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            // Query per conteggio totale
            $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM progetti p $whereClause";
            $countStmt = $this->database->getConnection()->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2)); // Rimuove LIMIT e OFFSET
            $totalCount = $countStmt->fetch()['total'];
            
            return [
                'projects' => $projects,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            $this->logger->logError('Failed to retrieve projects: ' . $e->getMessage(), [
                'filters' => $filters,
                'page' => $page,
                'limit' => $limit
            ]);
            
            return [
                'projects' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }
}