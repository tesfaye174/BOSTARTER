# SCHEMA RELAZIONALE BOSTARTER (A.A. 2024/2025)

## Utenti

- id (PK)
- email (univoco)
- nickname (univoco)
- password_hash
- nome
- cognome
- anno_nascita
- luogo_nascita
- tipo_utente (standard/creatore/amministratore)
- codice_sicurezza (solo admin)
- nr_progetti (solo creatore, ridondanza)
- affidabilita (solo creatore)
- created_at
- last_access

## Competenze

- id (PK)
- nome (univoco)
- created_at

## Skill Utente

- id (PK)
- utente_id (FK utenti)
- competenza_id (FK competenze)
- livello (0-5)
- created_at

## Progetti

- id (PK)
- nome (univoco)
- descrizione
- data_inserimento
- foto (JSON)
- budget_richiesto
- data_limite
- stato (aperto/chiuso)
- creatore_id (FK utenti)
- tipo_progetto (hardware/software)
- created_at

## Reward

- id (PK)
- codice (univoco)
- progetto_id (FK progetti)
- descrizione
- foto
- created_at

## Componenti Hardware

- id (PK)
- progetto_id (FK progetti)
- nome (univoco per progetto)
- descrizione
- prezzo
- quantita (>0)
- created_at

## Profili Software

- id (PK)
- progetto_id (FK progetti)
- nome
- max_contributori
- created_at

## Skill Richieste Profilo

- id (PK)
- profilo_id (FK profili_software)
- competenza_id (FK competenze)
- livello_richiesto (0-5)
- created_at

## Finanziamenti

- id (PK)
- utente_id (FK utenti)
- progetto_id (FK progetti)
- importo
- data_finanziamento
- reward_id (FK reward)
- created_at

## Commenti

- id (PK)
- utente_id (FK utenti)
- progetto_id (FK progetti)
- testo
- data_commento
- created_at

## Risposte Commenti

- id (PK)
- commento_id (FK commenti, univoco)
- creatore_id (FK utenti)
- testo
- data_risposta
- created_at

## Candidature

- id (PK)
- utente_id (FK utenti)
- progetto_id (FK progetti)
- profilo_id (FK profili_software)
- data_candidatura
- stato (pending/accepted/rejected)
- data_risposta
- created_at

## Tabelle di supporto

- utenti_competenze (relazione molti-a-molti utenti/competenze, con livello)
- backups (log backup database)
- sistema_log (log eventi di sistema)

## Trigger principali

- Aggiornamento affidabilità creatore (ogni nuovo progetto e ogni finanziamento)
- Incremento/decremento nr_progetti
- Chiusura automatica progetto al raggiungimento del budget

## Eventi

- Chiusura automatica progetti scaduti (1 volta al giorno)

## Stored Procedure

- Tutte le operazioni CRUD principali (registrazione, login, inserimento skill, progetti, reward, finanziamenti, commenti, candidature, ecc.)

## Viste

- Top 3 creatori per affidabilità
- Top 3 progetti vicini al completamento
- Top 3 utenti per finanziamenti erogati

---

Questo schema è conforme alla traccia PDF e riflette la struttura reale del database BOSTARTER.

---

## Schema ER (PlantUML)

```plantuml
@startuml BOSTARTER_ER
entity "Utenti" as utenti {
  *id : int <<PK>>
  email : varchar <<UNIQUE>>
  nickname : varchar <<UNIQUE>>
  password_hash : varchar
  nome : varchar
  cognome : varchar
  anno_nascita : year
  luogo_nascita : varchar
  tipo_utente : enum
  codice_sicurezza : varchar
  nr_progetti : int
  affidabilita : decimal
  created_at : timestamp
  last_access : timestamp
}

entity "Competenze" as competenze {
  *id : int <<PK>>
  nome : varchar <<UNIQUE>>
  created_at : timestamp
}

entity "Skill Utente" as skill_utente {
  *id : int <<PK>>
  utente_id : int <<FK utenti>>
  competenza_id : int <<FK competenze>>
  livello : tinyint
  created_at : timestamp
}

entity "Progetti" as progetti {
  *id : int <<PK>>
  nome : varchar <<UNIQUE>>
  descrizione : text
  data_inserimento : timestamp
  foto : json
  budget_richiesto : decimal
  data_limite : date
  stato : enum
  creatore_id : int <<FK utenti>>
  tipo_progetto : enum
  created_at : timestamp
}

entity "Reward" as reward {
  *id : int <<PK>>
  codice : varchar <<UNIQUE>>
  progetto_id : int <<FK progetti>>
  descrizione : text
  foto : varchar
  created_at : timestamp
}

entity "Componenti Hardware" as componenti_hardware {
  *id : int <<PK>>
  progetto_id : int <<FK progetti>>
  nome : varchar <<UNIQUE>>
  descrizione : text
  prezzo : decimal
  quantita : int
  created_at : timestamp
}

entity "Profili Software" as profili_software {
  *id : int <<PK>>
  progetto_id : int <<FK progetti>>
  nome : varchar
  max_contributori : int
  created_at : timestamp
}

entity "Skill Richieste Profilo" as skill_richieste_profilo {
  *id : int <<PK>>
  profilo_id : int <<FK profili_software>>
  competenza_id : int <<FK competenze>>
  livello_richiesto : tinyint
  created_at : timestamp
}

entity "Finanziamenti" as finanziamenti {
  *id : int <<PK>>
  utente_id : int <<FK utenti>>
  progetto_id : int <<FK progetti>>
  importo : decimal
  data_finanziamento : timestamp
  reward_id : int <<FK reward>>
  created_at : timestamp
}

entity "Commenti" as commenti {
  *id : int <<PK>>
  utente_id : int <<FK utenti>>
  progetto_id : int <<FK progetti>>
  testo : text
  data_commento : timestamp
  created_at : timestamp
}

entity "Risposte Commenti" as risposte_commenti {
  *id : int <<PK>>
  commento_id : int <<FK commenti>>
  creatore_id : int <<FK utenti>>
  testo : text
  data_risposta : timestamp
  created_at : timestamp
}

entity "Candidature" as candidature {
  *id : int <<PK>>
  utente_id : int <<FK utenti>>
  progetto_id : int <<FK progetti>>
  profilo_id : int <<FK profili_software>>
  data_candidatura : timestamp
  stato : enum
  data_risposta : timestamp
  created_at : timestamp
}

entity "utenti_competenze" {
  *id : int <<PK>>
  utente_id : int <<FK utenti>>
  competenza_id : int <<FK competenze>>
  livello : tinyint
  created_at : timestamp
}

entity "backups" {
  *id : int <<PK>>
  backup_name : varchar
  backup_date : timestamp
  backup_path : varchar
  status : enum
  notes : text
}

entity "sistema_log" {
  *id : int <<PK>>
  log_type : varchar
  log_message : text
  created_at : timestamp
  user_id : int <<FK utenti>>
}

utenti ||--o{ skill_utente : ""
utenti ||--o{ progetti : ""
utenti ||--o{ finanziamenti : ""
utenti ||--o{ commenti : ""
utenti ||--o{ candidature : ""
utenti ||--o{ risposte_commenti : ""
utenti ||--o{ utenti_competenze : ""
progetti ||--o{ reward : ""
progetti ||--o{ componenti_hardware : ""
progetti ||--o{ profili_software : ""
progetti ||--o{ finanziamenti : ""
progetti ||--o{ commenti : ""
progetti ||--o{ candidature : ""
profili_software ||--o{ skill_richieste_profilo : ""
profili_software ||--o{ candidature : ""
competenze ||--o{ skill_utente : ""
competenze ||--o{ skill_richieste_profilo : ""
competenze ||--o{ utenti_competenze : ""
skill_utente }o--|| competenze : ""
skill_richieste_profilo }o--|| competenze : ""
candidature }o--|| profili_software : ""
finanziamenti }o--|| reward : ""
commenti ||--o| risposte_commenti : ""
@enduml
```

---

Questo schema ER è conforme alla traccia PDF e riflette le relazioni reali nel database BOSTARTER.
