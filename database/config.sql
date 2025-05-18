-- Script di configurazione del database BOSTARTER
-- Questo script configura il database per supportare registrazione, login e gestione creatori

-- Assicurati di essere nel database corretto
USE bostarter;

-- Esegui gli script di schema
SOURCE schema/tables.sql;
SOURCE schema/update_tables.sql;
SOURCE schema/indexes.sql;
SOURCE schema/relationships.sql;

-- Esegui gli script delle procedure
SOURCE procedures/auth_procedures.sql;
SOURCE procedures/project_procedures.sql;

-- Esegui gli script dei trigger
SOURCE triggers/project_triggers.sql;

-- Esegui gli script delle viste
SOURCE views/statistics.sql;

-- Istruzioni per l'installazione:
-- 1. Assicurati che il database 'bostarter' esista
-- 2. Esegui questo script con: mysql -u root -p < database/config.sql
-- 3. Verifica che tutte le tabelle, procedure e trigger siano stati creati correttamente

-- Nota: questo script collega il database alla registrazione, login e alla sezione creatori
-- Le procedure auth_procedures.sql gestiscono registrazione e login
-- Le procedure project_procedures.sql gestiscono i progetti e il collegamento con i creatori
-- I trigger project_triggers.sql gestiscono automaticamente gli aggiornamenti quando vengono effettuate operazioni