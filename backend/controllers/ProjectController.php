<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\ProjectModel;
use BOSTARTER\Backend\Models\UserModel; // Potrebbe servire per verifiche sul creatore
use BOSTARTER\Backend\Router; // Per usare jsonResponse

class ProjectController {

    private $projectModel;
    private $userModel;

    public function __construct() {
        $this->projectModel = new ProjectModel();
        $this->userModel = new UserModel();
    }

    /**
     * Ottiene tutti i progetti (con paginazione).
     */
    public function getAllProjects(): void {
        // Ottieni parametri di paginazione dalla query string (opzionale)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        try {
            $projects = $this->projectModel->getAll($limit, $offset);
            $totalProjects = $this->projectModel->countAll();
            $totalPages = ceil($totalProjects / $limit);

            Router::jsonResponse([
                'projects' => $projects,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalProjects' => $totalProjects,
                    'limit' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Errore in getAllProjects: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore nel recupero dei progetti.'], 500);
        }
    }

    /**
     * Ottiene i dettagli di un singolo progetto per nome.
     * Nota: Questo richiede una modifica al Router per gestire parametri nell'URL (es. /api/projects/{nome})
     * Per ora, assumiamo che il nome sia passato come parametro GET ?name=...
     */
    public function getProjectByName(): void {
        if (!isset($_GET['name'])) {
            Router::jsonResponse(['error' => 'Nome del progetto mancante.'], 400);
            return;
        }
        $projectName = $_GET['name'];

        try {
            // Usiamo getFullProjectDetails per avere tutte le info correlate
            $project = $this->projectModel->getFullProjectDetails($projectName);

            if ($project) {
                Router::jsonResponse($project);
            } else {
                Router::jsonResponse(['error' => 'Progetto non trovato.'], 404);
            }
        } catch (\Exception $e) {
            error_log("Errore in getProjectByName: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore nel recupero del progetto.'], 500);
        }
    }

    /**
     * Crea un nuovo progetto.
     * Richiede autenticazione (l'utente deve essere loggato e essere un Creatore).
     */
    public function createProject(): void {
        session_start(); // Assicurati che la sessione sia attiva

        // Verifica autenticazione e ruolo Creatore
        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
            return;
        }
        if (!$_SESSION['is_creator']) { // Assumendo che is_creator sia impostato al login
             // Alternativa: $isCreator = $this->userModel->isCreator($_SESSION['user_email']);
             // if (!$isCreator) { ... }
            Router::jsonResponse(['error' => 'Solo i creatori possono creare progetti.'], 403); // Forbidden
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validazione base (da migliorare)
        if (!isset($input['nome'], $input['descrizione'], $input['budget'], $input['data_limite'], $input['tipo'])) {
            Router::jsonResponse(['error' => 'Dati mancanti per la creazione del progetto.'], 400);
            return;
        }
        if (!in_array($input['tipo'], ['hardware', 'software'])) {
             Router::jsonResponse(['error' => 'Tipo di progetto non valido.'], 400);
             return;
        }
        // TODO: Validare formato data_limite, budget numerico > 0, etc.

        // Verifica unicità nome progetto
        if ($this->projectModel->findByName($input['nome'])) {
            Router::jsonResponse(['error' => 'Esiste già un progetto con questo nome.'], 409); // Conflict
            return;
        }

        $projectData = [
            'nome' => $input['nome'],
            'descrizione' => $input['descrizione'],
            'budget' => $input['budget'], // Assicurati sia un formato numerico valido per il DB
            'data_limite' => $input['data_limite'], // Assicurati sia in formato YYYY-MM-DD
            'creatore_email' => $_SESSION['user_email'],
            'tipo' => $input['tipo']
        ];

        try {
            if ($this->projectModel->create($projectData)) {
                // Dopo la creazione base, potresti voler gestire l'aggiunta di:
                // - Foto (richiede upload file)
                // - Rewards
                // - Componenti Hardware / Profili Software + Skills
                // Questo richiederebbe endpoint aggiuntivi o una logica più complessa qui.

                Router::jsonResponse(['message' => 'Progetto creato con successo.', 'project' => $projectData], 201);
            } else {
                Router::jsonResponse(['error' => 'Errore durante la creazione del progetto.'], 500);
            }
        } catch (\PDOException $e) {
             error_log("Errore DB in createProject: " . $e->getMessage());
             // Controlla se è un errore di chiave duplicata
             if ($e->getCode() == 23000) { // Codice SQLSTATE per violazione di integrità (es. unique key)
                 Router::jsonResponse(['error' => 'Esiste già un progetto con questo nome.'], 409);
             } else {
                 Router::jsonResponse(['error' => 'Errore database durante la creazione del progetto.'], 500);
             }
        } catch (\Exception $e) {
            error_log("Errore generico in createProject: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore interno del server.'], 500);
        }
    }

    // TODO: Implementare metodi per:
    // - Aggiornare un progetto (updateProject)
    // - Eliminare un progetto (deleteProject)
    // - Aggiungere/rimuovere foto, rewards, componenti, profili, skills
    // - Gestire finanziamenti (FundingController?)
    // - Gestire commenti (CommentController?)
    // - Gestire candidature (ApplicationController?)

    /**
     * Aggiorna un progetto esistente.
     * Richiede autenticazione e che l'utente sia il creatore del progetto.
     */
    public function updateProject(): void {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nome'])) {
            Router::jsonResponse(['error' => 'Nome del progetto mancante.'], 400);
            return;
        }
        $project = $this->projectModel->findByName($input['nome']);
        if (!$project) {
            Router::jsonResponse(['error' => 'Progetto non trovato.'], 404);
            return;
        }
        if ($project['creatore_email'] !== $_SESSION['user_email']) {
            Router::jsonResponse(['error' => 'Non autorizzato a modificare questo progetto.'], 403);
            return;
        }
        // Validazione input (rafforzata)
        $fields = ['descrizione', 'budget', 'data_limite', 'tipo'];
        foreach ($fields as $field) {
            if (!isset($input[$field])) {
                Router::jsonResponse(['error' => "Campo '$field' mancante."], 400);
                return;
            }
        }
        if (!in_array($input['tipo'], ['hardware', 'software'])) {
            Router::jsonResponse(['error' => 'Tipo di progetto non valido.'], 400);
            return;
        }
        // TODO: Validare formato data_limite, budget numerico > 0, ecc.
        $data = [
            'descrizione' => $input['descrizione'],
            'budget' => $input['budget'],
            'data_limite' => $input['data_limite'],
            'tipo' => $input['tipo']
        ];
        try {
            $success = $this->projectModel->updateProject($input['nome'], $data);
            if ($success) {
                Router::jsonResponse(['message' => 'Progetto aggiornato con successo.']);
            } else {
                Router::jsonResponse(['error' => 'Errore durante l\'aggiornamento del progetto.'], 500);
            }
        } catch (\Exception $e) {
            error_log("Errore in updateProject: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore interno del server.'], 500);
        }
    }

    /**
     * Elimina un progetto esistente.
     * Richiede autenticazione e che l'utente sia il creatore del progetto.
     */
    public function deleteProject(): void {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nome'])) {
            Router::jsonResponse(['error' => 'Nome del progetto mancante.'], 400);
            return;
        }
        $project = $this->projectModel->findByName($input['nome']);
        if (!$project) {
            Router::jsonResponse(['error' => 'Progetto non trovato.'], 404);
            return;
        }
        if ($project['creatore_email'] !== $_SESSION['user_email']) {
            Router::jsonResponse(['error' => 'Non autorizzato a eliminare questo progetto.'], 403);
            return;
        }
        try {
            $success = $this->projectModel->deleteProject($input['nome']);
            if ($success) {
                Router::jsonResponse(['message' => 'Progetto eliminato con successo.']);
            } else {
                Router::jsonResponse(['error' => 'Errore durante l\'eliminazione del progetto.'], 500);
            }
        } catch (\Exception $e) {
            error_log("Errore in deleteProject: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore interno del server.'], 500);
        }
    }

    /**
     * Updates the color settings for a project.
     * Requires authentication and that the user is the creator of the project.
     */
    public function updateProjectColor(): void {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['name'], $input['color'])) {
            Router::jsonResponse(['error' => 'Project name and color are required.'], 400);
            return;
        }
        $project = $this->projectModel->findByName($input['name']);
        if (!$project) {
            Router::jsonResponse(['error' => 'Project not found.'], 404);
            return;
        }
        if ($project['creator_email'] !== $_SESSION['user_email']) {
            Router::jsonResponse(['error' => 'Not authorized to update this project.'], 403);
            return;
        }
        // Validate color format (e.g., hex code)
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $input['color'])) {
            Router::jsonResponse(['error' => 'Invalid color format.'], 400);
            return;
        }
        try {
            $success = $this->projectModel->updateProjectColor($input['name'], $input['color']);
            if ($success) {
                Router::jsonResponse(['message' => 'Project color updated successfully.']);
            } else {
                Router::jsonResponse(['error' => 'Failed to update project color.'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in updateProjectColor: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Internal server error.'], 500);
        }
    }

    /**
     * Mirrors a project to another platform.
     * Requires authentication and that the user is the creator of the project.
     */
    public function mirrorProject(): void {
        session_start();
        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['name'], $input['platform'])) {
            Router::jsonResponse(['error' => 'Project name and platform are required.'], 400);
            return;
        }
        $project = $this->projectModel->findByName($input['name']);
        if (!$project) {
            Router::jsonResponse(['error' => 'Project not found.'], 404);
            return;
        }
        if ($project['creator_email'] !== $_SESSION['user_email']) {
            Router::jsonResponse(['error' => 'Not authorized to mirror this project.'], 403);
            return;
        }
        // Example logic for mirroring
        try {
            $success = $this->projectModel->mirrorToPlatform($input['name'], $input['platform']);
            if ($success) {
                Router::jsonResponse(['message' => 'Project mirrored successfully.']);
            } else {
                Router::jsonResponse(['error' => 'Failed to mirror project.'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error in mirrorProject: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Internal server error.'], 500);
        }
    }
}
?>