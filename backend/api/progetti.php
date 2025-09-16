<?php

require_once __DIR__ . '/../services/ProjectService.php';
require_once __DIR__ . '/../utils/MessageManager.php';
require_once __DIR__ . '/../utils/Validator.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $validator = new Validator();
    $validator->required('nome', $input['nome'] ?? '')->maxLength(255);
    $validator->required('descrizione', $input['descrizione'] ?? '');
    $validator->required('tipo', $input['tipo'] ?? '')->inArray(['hardware', 'software']);
    $validator->required('budget', $input['budget'] ?? '')->floatVal();
    $validator->required('data_limite', $input['data_limite'] ?? '')->dateFormat('Y-m-d');
    
    if (!$validator->isValid()) {
        http_response_code(400);
        echo json_encode(['error' => $validator->getErrors()]);
        exit;
    }

    $projectService = new ProjectService();
    try {
        $project = $projectService->createProject(
            $_SESSION['user_id'],
            $input['nome'],
            $input['descrizione'],
            $input['tipo'],
            $input['budget'],
            $input['data_limite']
        );
        echo json_encode($project);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
