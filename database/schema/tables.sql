-- Script di creazione delle tabelle

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT
);

CREATE TABLE rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT
);

CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content TEXT NOT NULL,
    author_id INT,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE financing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    amount DECIMAL(10, 2) NOT NULL,
    project_id INT,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL
);