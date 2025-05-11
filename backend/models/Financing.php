<?php

class Financing {
    private $id;
    private $amount;
    private $projectId;

    public function __construct($id, $amount, $projectId) {
        $this->id = $id;
        $this->amount = $amount;
        $this->projectId = $projectId;
    }

    public function getId() {
        return $this->id;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getProjectId() {
        return $this->projectId;
    }
}