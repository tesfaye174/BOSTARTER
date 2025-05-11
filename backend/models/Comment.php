<?php

class Comment {
    private $id;
    private $content;
    private $authorId;

    public function __construct($id, $content, $authorId) {
        $this->id = $id;
        $this->content = $content;
        $this->authorId = $authorId;
    }

    public function getId() {
        return $this->id;
    }

    public function getContent() {
        return $this->content;
    }

    public function getAuthorId() {
        return $this->authorId;
    }
}