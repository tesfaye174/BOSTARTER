<?php
// Include la configurazione principale
require_once __DIR__ . '/../config/config.php';

// La configurazione gestisce già display_errors e error_reporting in base a DEBUG_MODE

// Imposta l'header per le risposte JSON di default (può essere sovrascritto)
header('Content-Type: application/json; charset=utf-8');

// Gestione CORS (Cross-Origin Resource Sharing) - Adattare in produzione!
header("Access-Control-Allow-Origin: *"); // Permette a qualsiasi origine (insicuro per produzione)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Gestione richiesta OPTIONS per preflight CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// Include l'autoloader
require_once __DIR__ . '/autoload.php';

// Usa le classi necessarie
use BOSTARTER\Backend\Router;
use BOSTARTER\Backend\Controllers\AuthController;
use BOSTARTER\Backend\Controllers\ProjectController; // Includi il controller dei progetti
use BOSTARTER\Backend\Controllers\FundingController; // Includi il controller dei finanziamenti
use BOSTARTER\Backend\Controllers\SkillController; // Includi il controller delle skills
use BOSTARTER\Backend\Controllers\RewardController; // Includi il controller delle ricompense
use BOSTARTER\Backend\Controllers\CommentController; // Includi il controller dei commenti
use BOSTARTER\Backend\Controllers\ApplicationController; // Includi il controller delle candidature

// Inizia la sessione (se usi l'autenticazione basata su sessione)
session_start();

// Istanzia il router
$router = new Router();

// --- Definizione delle Rotte --- 

// Rotte di Autenticazione
$router->addRoute('POST', '/api/register', [AuthController::class, 'register']);
$router->addRoute('POST', '/api/login', [AuthController::class, 'login']);
$router->addRoute('POST', '/api/logout', [AuthController::class, 'logout']); // POST o GET a seconda delle preferenze
$router->addRoute('GET', '/api/session', [AuthController::class, 'checkSession']);

// Rotte Progetti
$router->addRoute('GET', '/api/projects', [ProjectController::class, 'getAllProjects']);
// Nota: La rotta per nome progetto usa un parametro GET (?name=...) come implementato nel controller
$router->addRoute('GET', '/api/project', [ProjectController::class, 'getProjectByName']); // Es: /api/project?name=NomeProgetto
$router->addRoute('POST', '/api/projects', [ProjectController::class, 'createProject']); // Richiede autenticazione (essere Creatore)

// Rotte Finanziamenti
$router->addRoute('POST', '/api/fundings', [FundingController::class, 'addFunding']); // Richiede autenticazione
$router->addRoute('GET', '/api/project/fundings', [FundingController::class, 'getProjectFundings']); // Es: /api/project/fundings?projectName=NomeProgetto
$router->addRoute('GET', '/api/user/fundings', [FundingController::class, 'getUserFundings']); // Richiede autenticazione

// Rotte Skills
$router->addRoute('GET', '/api/skills', [SkillController::class, 'getAllSkills']);
$router->addRoute('POST', '/api/skills', [SkillController::class, 'createSkill']); // Potrebbe richiedere privilegi admin

// Rotte Ricompense (associate ai progetti)
$router->addRoute('GET', '/api/projects/{projectId}/rewards', [RewardController::class, 'getProjectRewards']);
$router->addRoute('POST', '/api/projects/{projectId}/rewards', [RewardController::class, 'createReward']); // Richiede autenticazione (creatore progetto)

// Rotte Commenti (associate ai progetti)
$router->addRoute('GET', '/api/projects/{projectId}/comments', [CommentController::class, 'getProjectComments']);
$router->addRoute('POST', '/api/projects/{projectId}/comments', [CommentController::class, 'createComment']); // Richiede autenticazione

// Rotte Candidature
$router->addRoute('GET', '/api/projects/{projectId}/applications', [ApplicationController::class, 'getProjectApplications']); // Richiede autenticazione (creatore progetto)
$router->addRoute('GET', '/api/user/applications', [ApplicationController::class, 'getUserApplications']); // Richiede autenticazione (utente loggato)
$router->addRoute('POST', '/api/projects/{projectId}/applications', [ApplicationController::class, 'createApplication']); // Richiede autenticazione
$router->addRoute('PUT', '/api/applications/{applicationId}/status', [ApplicationController::class, 'updateApplicationStatus']); // Richiede autenticazione (creatore progetto)

// --- Gestione della Richiesta --- 

$router->handleRequest();

?>