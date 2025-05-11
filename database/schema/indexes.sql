-- Script per definire gli indici nel database

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_projects_title ON projects(title);
CREATE INDEX idx_rewards_name ON rewards(name);
CREATE INDEX idx_comments_author_id ON comments(author_id);
CREATE INDEX idx_financing_project_id ON financing(project_id);
CREATE INDEX idx_skills_name ON skills(name);