# Modello ER BOSTARTER

## Entità Principali

1. UTENTE (⚪)
   - id_utente (PK)
   - email (UK)
   - nickname (UK)
   - altri attributi (password_hash, nome_completo, etc.)
   Vincoli:
   - email UNIQUE
   - nickname UNIQUE

2. PROGETTO (⚪)
   - id_progetto (PK)
   - id_creator (FK → UTENTE)
   - attributi (titolo, descrizione, budget, etc.)
   Vincoli:
   - budget > 0
   - data_scadenza > data_creazione

3. COMPETENZA (⚪)
   - id_competenza (PK)
   - nome (UK)
   - creato_da (FK → UTENTE)

## Relazioni

1. CREA (→)
   - UTENTE (1) --- PROGETTO (0..N)
   Note: Un utente creator può creare più progetti

2. FINANZIA (◇)
   - UTENTE (0..N) --- FINANZIAMENTO (1..1) --- PROGETTO (0..N)
   Attributi:
   - importo
   - data_transazione

3. POSSIEDE_COMPETENZA (◇)
   - UTENTE (0..N) --- COMPETENZA_UTENTE (1..1) --- COMPETENZA (0..N)
   Attributi:
   - livello
   - verificata

4. RICHIEDE_COMPETENZA (◇)
   - PROGETTO (0..N) --- COMPETENZA_RICHIESTA (1..1) --- COMPETENZA (0..N)
   Attributi:
   - livello_minimo

5. CANDIDATURA (◇)
   - UTENTE (0..N) --- CANDIDATURA (1..1) --- PROGETTO (0..N)
   Attributi:
   - stato
   - motivazione

6. COMMENTO (⚪)
   - id_commento (PK)
   - id_progetto (FK → PROGETTO)
   - id_utente (FK → UTENTE)
   - id_padre (FK → COMMENTO) [per risposte]

7. RICOMPENSA (⚪)
   - id_ricompensa (PK)
   - id_progetto (FK → PROGETTO)
   Attributi:
   - importo_minimo
   - quantita_disponibile

## Legenda

⚪ Entità
→ Relazione 1:N
◇ Relazione N:N
UK: Unique Key
FK: Foreign Key
PK: Primary Key

## Note implementative

1. Le relazioni N:N sono implementate con tabelle ponte
2. Ogni relazione include timestamp per tracking temporale
3. Sono implementati soft delete dove appropriato
4. Indici creati su tutte le FK e campi di ricerca frequente
