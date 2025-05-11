-- Script per definire le relazioni tra le tabelle

ALTER TABLE comments ADD FOREIGN KEY (author_id) REFERENCES users(id);
ALTER TABLE financing ADD FOREIGN KEY (project_id) REFERENCES projects(id);