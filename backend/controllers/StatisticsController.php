<?php
// filepath: c:/xampp/htdocs/BOSTARTER/backend/controllers/StatisticsController.php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/FundingModel.php';
require_once __DIR__ . '/../utils/Database.php';

class StatisticsController {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function topCreators() {
        $sql = "SELECT nickname, reliability FROM top_creators_by_reliability";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function topProjects() {
        $sql = "SELECT name, diff FROM top_open_projects_by_completion";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function topFunders() {
        $sql = "SELECT nickname, total_funded FROM top_funders";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
