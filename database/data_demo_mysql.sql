-- =====================================================
-- BOSTARTER - Dati Demo MySQL
-- Versione: 2.0 MySQL
-- Data: 2025-01-08
-- Descrizione: Dati di esempio per test sistema
-- =====================================================

USE bostarter;

-- Disabilita controlli foreign key temporaneamente
SET FOREIGN_KEY_CHECKS = 0;

-- Pulisci tabelle esistenti
TRUNCATE TABLE system_log;
TRUNCATE TABLE foto_progetti;
TRUNCATE TABLE componenti;
TRUNCATE TABLE candidature;
TRUNCATE TABLE commenti;
TRUNCATE TABLE finanziamenti;
TRUNCATE TABLE ricompense;
TRUNCATE TABLE profili_competenze;
TRUNCATE TABLE profili;
TRUNCATE TABLE progetti_competenze;
TRUNCATE TABLE progetti;
TRUNCATE TABLE utenti_competenze;
TRUNCATE TABLE competenze;
TRUNCATE TABLE categorie;
TRUNCATE TABLE utenti;

-- Riabilita controlli foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- INSERIMENTO DATI DEMO
-- =====================================================

-- Categorie
INSERT INTO categorie (id, nome, descrizione, icona, colore, attiva) VALUES
(1, 'Tecnologia', 'Progetti innovativi nel campo tecnologico', 'fas fa-laptop-code', '#3498db', TRUE),
(2, 'Arte e Design', 'Progetti creativi e artistici', 'fas fa-palette', '#e74c3c', TRUE),
(3, 'Musica', 'Progetti musicali e audio', 'fas fa-music', '#9b59b6', TRUE),
(4, 'Film e Video', 'Produzioni cinematografiche e video', 'fas fa-video', '#f39c12', TRUE),
(5, 'Giochi', 'Sviluppo di giochi e gaming', 'fas fa-gamepad', '#2ecc71', TRUE),
(6, 'Editoria', 'Libri, riviste e pubblicazioni', 'fas fa-book', '#34495e', TRUE),
(7, 'Moda', 'Design di moda e accessori', 'fas fa-tshirt', '#e91e63', TRUE),
(8, 'Cibo', 'Progetti culinari e gastronomici', 'fas fa-utensils', '#ff5722', TRUE);

-- Competenze
INSERT INTO competenze (id, nome, descrizione, categoria_id, livello_richiesto, attiva) VALUES
(1, 'Sviluppo Web', 'Competenze in HTML, CSS, JavaScript, PHP', 1, 'INTERMEDIO', TRUE),
(2, 'Mobile Development', 'Sviluppo app iOS e Android', 1, 'AVANZATO', TRUE),
(3, 'UI/UX Design', 'Design interfacce utente ed esperienza', 2, 'INTERMEDIO', TRUE),
(4, 'Graphic Design', 'Design grafico e visual identity', 2, 'BASE', TRUE),
(5, 'Marketing Digitale', 'Social media, SEO, advertising online', 1, 'INTERMEDIO', TRUE),
(6, 'Project Management', 'Gestione progetti e team', 1, 'AVANZATO', TRUE),
(7, 'Fotografia', 'Fotografia professionale e editing', 2, 'INTERMEDIO', TRUE),
(8, 'Video Editing', 'Montaggio e post-produzione video', 4, 'AVANZATO', TRUE),
(9, 'Composizione Musicale', 'Creazione e arrangiamento musicale', 3, 'AVANZATO', TRUE),
(10, 'Sound Design', 'Design audio e effetti sonori', 3, 'INTERMEDIO', TRUE);

-- Utenti
INSERT INTO utenti (id, nickname, email, password_hash, nome, cognome, data_nascita, citta, biografia, tipo_utente, affidabilita, nr_progetti, data_registrazione, attivo, email_verificata) VALUES
(1, 'admin', 'admin@bostarter.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', '1990-01-01', 'Milano', 'Amministratore del sistema BOSTARTER', 'ADMIN', 5.00, 0, '2024-01-01 10:00:00', TRUE, TRUE),
(2, 'mario_rossi', 'mario.rossi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', '1985-03-15', 'Roma', 'Sviluppatore full-stack con 10 anni di esperienza. Appassionato di tecnologie innovative.', 'CREATORE', 4.50, 3, '2024-01-15 09:30:00', TRUE, TRUE),
(3, 'giulia_bianchi', 'giulia.bianchi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giulia', 'Bianchi', '1992-07-22', 'Milano', 'Designer UI/UX freelance. Amo creare esperienze digitali intuitive e belle.', 'CREATORE', 4.20, 2, '2024-01-20 14:15:00', TRUE, TRUE),
(4, 'luca_verdi', 'luca.verdi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca', 'Verdi', '1988-11-08', 'Torino', 'Musicista e produttore. Specializzato in colonne sonore per film e videogiochi.', 'CREATORE', 3.80, 1, '2024-02-01 11:45:00', TRUE, TRUE),
(5, 'anna_neri', 'anna.neri@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna', 'Neri', '1995-05-12', 'Napoli', 'Appassionata di crowdfunding e startup innovative.', 'UTENTE', 2.10, 0, '2024-02-10 16:20:00', TRUE, TRUE),
(6, 'francesco_blu', 'francesco.blu@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Francesco', 'Blu', '1990-09-30', 'Bologna', 'Investitore e sostenitore di progetti creativi.', 'UTENTE', 1.50, 0, '2024-02-15 10:10:00', TRUE, TRUE),
(7, 'sara_gialli', 'sara.gialli@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sara', 'Gialli', '1993-12-03', 'Firenze', 'Fotografa professionista e content creator.', 'CREATORE', 3.90, 1, '2024-02-20 13:30:00', TRUE, TRUE);

-- Utenti-Competenze
INSERT INTO utenti_competenze (utente_id, competenza_id, livello, anni_esperienza, certificato) VALUES
(2, 1, 'AVANZATO', 8, TRUE),
(2, 2, 'INTERMEDIO', 5, FALSE),
(2, 6, 'AVANZATO', 6, TRUE),
(3, 3, 'AVANZATO', 6, TRUE),
(3, 4, 'AVANZATO', 7, FALSE),
(3, 7, 'INTERMEDIO', 3, FALSE),
(4, 9, 'ESPERTO', 12, TRUE),
(4, 10, 'AVANZATO', 8, FALSE),
(7, 7, 'ESPERTO', 10, TRUE),
(7, 8, 'INTERMEDIO', 4, FALSE);

-- Progetti
INSERT INTO progetti (id, titolo, descrizione, descrizione_breve, categoria_id, creatore_id, budget_richiesto, budget_raccolto, data_inizio, data_fine, stato, immagine_copertina, localizzazione, nr_sostenitori, nr_commenti, data_creazione, visibilita) VALUES
(1, 'EcoApp - App per la Sostenibilità', 'Un\'applicazione mobile innovativa che aiuta gli utenti a ridurre il loro impatto ambientale attraverso sfide quotidiane, tracking delle emissioni di CO2 e una community di eco-warriors. L\'app includerà funzionalità di gamification, marketplace di prodotti sostenibili e partnership con aziende green.', 'App mobile per uno stile di vita più sostenibile con gamification e community', 1, 2, 25000.00, 18750.00, '2024-03-01', '2024-06-30', 'ATTIVO', 'ecoapp_cover.jpg', 'Milano, Italia', 125, 23, '2024-03-01 10:00:00', 'PUBBLICA'),
(2, 'Artisan Marketplace - Piattaforma Artigiani', 'Una piattaforma e-commerce dedicata agli artigiani italiani per vendere le loro creazioni uniche. Include sistema di personalizzazione prodotti, storytelling degli artigiani, e certificazione di autenticità blockchain. Supportiamo la tradizione artigianale italiana nel mondo digitale.', 'E-commerce per artigiani italiani con certificazione blockchain', 2, 3, 35000.00, 28000.00, '2024-02-15', '2024-07-15', 'ATTIVO', 'artisan_cover.jpg', 'Firenze, Italia', 89, 31, '2024-02-15 14:30:00', 'PUBBLICA'),
(3, 'SoundScape VR - Esperienza Musicale Immersiva', 'Un\'esperienza di realtà virtuale che permette agli utenti di immergersi completamente nella musica. Utilizzando tecnologie audio spaziali avanzate, gli utenti possono "camminare" all\'interno delle composizioni musicali, vedere le note come elementi 3D e interagire con gli strumenti virtuali.', 'Esperienza VR immersiva per esplorare la musica in 3D', 3, 4, 45000.00, 12000.00, '2024-03-15', '2024-08-15', 'ATTIVO', 'soundscape_cover.jpg', 'Torino, Italia', 34, 12, '2024-03-15 16:45:00', 'PUBBLICA'),
(4, 'GreenTech Startup Incubator', 'Un incubatore specializzato in startup GreenTech che offre mentorship, spazi di co-working sostenibili, e accesso a una rete di investitori focalizzati sull\'ambiente. Il progetto include la costruzione di un hub fisico alimentato al 100% da energie rinnovabili.', 'Incubatore per startup GreenTech con hub sostenibile', 1, 2, 75000.00, 45000.00, '2024-01-01', '2024-05-01', 'COMPLETATO', 'greentech_cover.jpg', 'Milano, Italia', 156, 45, '2024-01-01 09:00:00', 'PUBBLICA'),
(5, 'Fotografia Sociale - Progetto Documentario', 'Un progetto fotografico documentario che racconta le storie di resilienza delle comunità urbane marginali. Include mostre itineranti, libro fotografico e piattaforma digitale interattiva. L\'obiettivo è sensibilizzare sui temi sociali attraverso l\'arte fotografica.', 'Documentario fotografico su comunità urbani marginali', 2, 7, 15000.00, 8500.00, '2024-04-01', '2024-09-01', 'ATTIVO', 'fotosociale_cover.jpg', 'Roma, Italia', 67, 18, '2024-04-01 11:20:00', 'PUBBLICA');

-- Progetti-Competenze
INSERT INTO progetti_competenze (progetto_id, competenza_id, livello_richiesto, obbligatoria) VALUES
(1, 1, 'AVANZATO', TRUE),
(1, 2, 'AVANZATO', TRUE),
(1, 3, 'INTERMEDIO', FALSE),
(1, 5, 'INTERMEDIO', FALSE),
(2, 1, 'AVANZATO', TRUE),
(2, 3, 'AVANZATO', TRUE),
(2, 4, 'INTERMEDIO', FALSE),
(2, 5, 'AVANZATO', TRUE),
(3, 2, 'ESPERTO', TRUE),
(3, 9, 'AVANZATO', TRUE),
(3, 10, 'AVANZATO', TRUE),
(4, 6, 'ESPERTO', TRUE),
(4, 5, 'AVANZATO', TRUE),
(5, 7, 'ESPERTO', TRUE),
(5, 4, 'INTERMEDIO', FALSE);

-- Ricompense
INSERT INTO ricompense (id, progetto_id, titolo, descrizione, importo_minimo, quantita_disponibile, quantita_prenotata, data_consegna_stimata, spedizione_inclusa, digitale, attiva, ordine_visualizzazione) VALUES
(1, 1, 'Early Bird - App Beta', 'Accesso anticipato alla versione beta dell\'app EcoApp con funzionalità esclusive per i primi sostenitori.', 25.00, 100, 45, '2024-05-15', FALSE, TRUE, TRUE, 1),
(2, 1, 'Eco Starter Kit', 'Kit fisico con prodotti eco-friendly selezionati + accesso premium all\'app per 1 anno.', 75.00, 200, 38, '2024-07-01', TRUE, FALSE, TRUE, 2),
(3, 1, 'Eco Champion', 'Tutto del kit precedente + consulenza personalizzata sulla sostenibilità + certificato digitale.', 150.00, 50, 12, '2024-07-15', TRUE, FALSE, TRUE, 3),
(4, 2, 'Prodotto Artigianale Esclusivo', 'Un prodotto artigianale unico creato appositamente per i sostenitori del progetto.', 50.00, 150, 67, '2024-06-01', TRUE, FALSE, TRUE, 1),
(5, 2, 'Workshop Artigianale', 'Partecipazione a un workshop con un maestro artigiano + prodotto personalizzato.', 120.00, 30, 15, '2024-08-01', FALSE, FALSE, TRUE, 2),
(6, 3, 'VR Experience Preview', 'Accesso esclusivo a una demo dell\'esperienza VR SoundScape.', 40.00, 80, 22, '2024-06-01', FALSE, TRUE, TRUE, 1),
(7, 3, 'Colonna Sonora Personalizzata', 'Composizione musicale personalizzata creata dal team + esperienza VR completa.', 200.00, 25, 8, '2024-09-01', FALSE, TRUE, TRUE, 2),
(8, 5, 'Stampa Fotografica Firmata', 'Stampa fine art di una foto del progetto, firmata dall\'autrice.', 35.00, 100, 28, '2024-08-01', TRUE, FALSE, TRUE, 1),
(9, 5, 'Portfolio Fotografico Completo', 'Libro fotografico completo del progetto + stampa esclusiva + invito all\'inaugurazione.', 85.00, 50, 18, '2024-10-01', TRUE, FALSE, TRUE, 2);

-- Finanziamenti
INSERT INTO finanziamenti (id, progetto_id, utente_id, ricompensa_id, importo, messaggio, anonimo, stato, metodo_pagamento, transaction_id, data_finanziamento, data_elaborazione) VALUES
(1, 1, 5, 1, 25.00, 'Fantastica idea! Non vedo l\'ora di provare l\'app.', FALSE, 'COMPLETATO', 'CARD', 'TXN_001', '2024-03-05 14:30:00', '2024-03-05 14:31:00'),
(2, 1, 6, 2, 75.00, 'Supporto totale per la sostenibilità!', FALSE, 'COMPLETATO', 'PAYPAL', 'TXN_002', '2024-03-08 10:15:00', '2024-03-08 10:16:00'),
(3, 1, 7, 1, 25.00, NULL, TRUE, 'COMPLETATO', 'CARD', 'TXN_003', '2024-03-10 16:45:00', '2024-03-10 16:46:00'),
(4, 2, 5, 4, 50.00, 'Bellissimo progetto per valorizzare l\'artigianato italiano!', FALSE, 'COMPLETATO', 'CARD', 'TXN_004', '2024-02-20 11:20:00', '2024-02-20 11:21:00'),
(5, 2, 6, 5, 120.00, 'Voglio assolutamente partecipare al workshop!', FALSE, 'COMPLETATO', 'BANK_TRANSFER', 'TXN_005', '2024-02-25 09:30:00', '2024-02-25 09:35:00'),
(6, 3, 5, 6, 40.00, 'La realtà virtuale applicata alla musica è geniale!', FALSE, 'COMPLETATO', 'CARD', 'TXN_006', '2024-03-20 13:15:00', '2024-03-20 13:16:00'),
(7, 4, 6, NULL, 200.00, 'Investimento nel futuro green!', FALSE, 'COMPLETATO', 'CARD', 'TXN_007', '2024-01-15 15:45:00', '2024-01-15 15:46:00'),
(8, 4, 7, NULL, 150.00, NULL, FALSE, 'COMPLETATO', 'PAYPAL', 'TXN_008', '2024-01-20 12:30:00', '2024-01-20 12:31:00'),
(9, 5, 6, 8, 35.00, 'La fotografia sociale è molto importante.', FALSE, 'COMPLETATO', 'CARD', 'TXN_009', '2024-04-05 17:20:00', '2024-04-05 17:21:00'),
(10, 1, 4, 3, 150.00, 'Come musicista, apprezzo molto i progetti sostenibili!', FALSE, 'COMPLETATO', 'CARD', 'TXN_010', '2024-03-12 19:10:00', '2024-03-12 19:11:00');

-- Commenti
INSERT INTO commenti (id, progetto_id, utente_id, parent_id, contenuto, data_commento, approvato) VALUES
(1, 1, 5, NULL, 'Progetto fantastico! Quando sarà disponibile la beta?', '2024-03-06 10:30:00', TRUE),
(2, 1, 2, 1, 'Grazie! La beta sarà disponibile a maggio per i sostenitori Early Bird.', '2024-03-06 11:15:00', TRUE),
(3, 1, 6, NULL, 'Ottima idea per sensibilizzare sulla sostenibilità. Avete pensato a partnership con scuole?', '2024-03-09 14:20:00', TRUE),
(4, 2, 5, NULL, 'Finalmente una piattaforma dedicata ai nostri artigiani! Come funziona la certificazione blockchain?', '2024-02-22 16:45:00', TRUE),
(5, 2, 3, 4, 'Utilizziamo una blockchain privata per certificare l\'autenticità e la provenienza di ogni prodotto.', '2024-02-22 17:30:00', TRUE),
(6, 3, 5, NULL, 'La realtà virtuale musicale è il futuro! Sarà compatibile con tutti i visori VR?', '2024-03-22 12:15:00', TRUE),
(7, 4, 6, NULL, 'Complimenti per aver raggiunto l\'obiettivo! Quando aprirà l\'incubatore?', '2024-04-15 09:45:00', TRUE),
(8, 5, 6, NULL, 'Progetto molto toccante. La fotografia può davvero cambiare la percezione sociale.', '2024-04-07 20:30:00', TRUE);

-- Candidature
INSERT INTO candidature (id, progetto_id, utente_id, messaggio, cv_allegato, portfolio_url, stato, data_candidatura, data_valutazione, note_valutazione) VALUES
(1, 1, 3, 'Sono una designer UI/UX con esperienza in app mobile. Vorrei contribuire al design dell\'interfaccia di EcoApp.', 'cv_giulia_bianchi.pdf', 'https://giuliabianchi.portfolio.com', 'ACCETTATA', '2024-03-03 11:30:00', '2024-03-05 09:15:00', 'Ottimo portfolio, perfetta per il team!'),
(2, 2, 7, 'Come fotografa, posso aiutare nella documentazione visiva degli artigiani e dei loro prodotti.', 'cv_sara_gialli.pdf', 'https://saragialli.photo', 'ACCETTATA', '2024-02-18 14:20:00', '2024-02-20 10:30:00', 'Esperienza perfetta per il progetto'),
(3, 3, 2, 'Sviluppatore con esperienza in VR. Posso contribuire allo sviluppo tecnico dell\'applicazione.', 'cv_mario_rossi.pdf', 'https://github.com/mariorossi', 'IN_VALUTAZIONE', '2024-03-18 16:45:00', NULL, NULL),
(4, 5, 4, 'Come musicista, posso creare una colonna sonora per accompagnare il documentario fotografico.', 'cv_luca_verdi.pdf', 'https://soundcloud.com/lucaverdi', 'INVIATA', '2024-04-03 13:15:00', NULL, NULL);

-- Componenti (membri del team)
INSERT INTO componenti (id, progetto_id, utente_id, ruolo, descrizione_ruolo, data_ingresso, attivo) VALUES
(1, 1, 3, 'UI/UX Designer', 'Responsabile del design dell\'interfaccia utente e dell\'esperienza utente', '2024-03-05 10:00:00', TRUE),
(2, 2, 7, 'Fotografa Ufficiale', 'Documentazione fotografica degli artigiani e dei prodotti', '2024-02-20 11:00:00', TRUE),
(3, 4, 2, 'CTO', 'Chief Technology Officer - Responsabile tecnico dell\'incubatore', '2024-01-01 09:00:00', TRUE);

-- Profili (team members descrittivi)
INSERT INTO profili (id, progetto_id, nome, ruolo, descrizione, email, linkedin, foto, ordine_visualizzazione) VALUES
(1, 1, 'Mario Rossi', 'Founder & CEO', 'Sviluppatore full-stack con 10 anni di esperienza. Visionario della sostenibilità digitale.', 'mario.rossi@email.com', 'https://linkedin.com/in/mariorossi', 'mario_profile.jpg', 1),
(2, 1, 'Giulia Bianchi', 'Head of Design', 'Designer UI/UX specializzata in app mobile sostenibili. Crede nel design che fa la differenza.', 'giulia.bianchi@email.com', 'https://linkedin.com/in/giuliabianchi', 'giulia_profile.jpg', 2),
(3, 2, 'Giulia Bianchi', 'Co-Founder & Designer', 'Designer appassionata di tradizioni artigianali e innovazione digitale.', 'giulia.bianchi@email.com', 'https://linkedin.com/in/giuliabianchi', 'giulia_profile.jpg', 1),
(4, 2, 'Sara Gialli', 'Visual Storyteller', 'Fotografa professionista specializzata nella narrazione visiva dell\'artigianato.', 'sara.gialli@email.com', 'https://linkedin.com/in/saragialli', 'sara_profile.jpg', 2);

-- Profili-Competenze
INSERT INTO profili_competenze (profilo_id, competenza_id, livello) VALUES
(1, 1, 'AVANZATO'),
(1, 2, 'INTERMEDIO'),
(1, 6, 'AVANZATO'),
(2, 3, 'AVANZATO'),
(2, 4, 'AVANZATO'),
(3, 3, 'AVANZATO'),
(3, 4, 'AVANZATO'),
(4, 7, 'ESPERTO');

-- Foto progetti
INSERT INTO foto_progetti (id, progetto_id, filename, path_completo, alt_text, descrizione, ordine_visualizzazione, principale, data_upload) VALUES
(1, 1, 'ecoapp_main.jpg', '/uploads/progetti/1/ecoapp_main.jpg', 'Screenshot principale EcoApp', 'Schermata principale dell\'applicazione EcoApp', 1, TRUE, '2024-03-01 10:30:00'),
(2, 1, 'ecoapp_features.jpg', '/uploads/progetti/1/ecoapp_features.jpg', 'Funzionalità EcoApp', 'Panoramica delle funzionalità principali', 2, FALSE, '2024-03-01 10:35:00'),
(3, 2, 'artisan_marketplace.jpg', '/uploads/progetti/2/artisan_marketplace.jpg', 'Homepage Artisan Marketplace', 'Homepage della piattaforma artigiani', 1, TRUE, '2024-02-15 15:00:00'),
(4, 3, 'soundscape_vr.jpg', '/uploads/progetti/3/soundscape_vr.jpg', 'Esperienza SoundScape VR', 'Utente che utilizza l\'esperienza VR', 1, TRUE, '2024-03-15 17:00:00'),
(5, 5, 'fotografia_sociale.jpg', '/uploads/progetti/5/fotografia_sociale.jpg', 'Esempio fotografia sociale', 'Una delle fotografie del progetto documentario', 1, TRUE, '2024-04-01 11:45:00');

-- System Log (alcuni esempi)
INSERT INTO system_log (id, utente_id, azione, tabella_interessata, record_id, dettagli, ip_address, user_agent, timestamp) VALUES
(1, 2, 'CREAZIONE_PROGETTO', 'progetti', 1, '{"titolo": "EcoApp - App per la Sostenibilità", "budget": 25000}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2024-03-01 10:00:00'),
(2, 5, 'FINANZIAMENTO', 'finanziamenti', 1, '{"progetto_id": 1, "importo": 25}', '192.168.1.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)', '2024-03-05 14:30:00'),
(3, 3, 'CANDIDATURA_ACCETTATA', 'candidature', 1, '{"progetto_id": 1}', '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '2024-03-05 09:15:00'),
(4, 2, 'OBIETTIVO_RAGGIUNTO', 'progetti', 4, '{"budget_richiesto": 75000, "budget_raccolto": 75000}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2024-04-01 12:00:00');

-- =====================================================
-- AGGIORNAMENTO CONTATORI FINALI
-- =====================================================

-- Aggiorna contatori progetti (i trigger dovrebbero averlo fatto, ma per sicurezza)
UPDATE progetti p SET 
    nr_sostenitori = (SELECT COUNT(DISTINCT f.utente_id) FROM finanziamenti f WHERE f.progetto_id = p.id AND f.stato = 'COMPLETATO'),
    nr_commenti = (SELECT COUNT(*) FROM commenti c WHERE c.progetto_id = p.id AND c.approvato = TRUE),
    nr_candidature = (SELECT COUNT(*) FROM candidature ca WHERE ca.progetto_id = p.id);

-- Aggiorna quantità prenotate ricompense
UPDATE ricompense r SET 
    quantita_prenotata = (SELECT COUNT(*) FROM finanziamenti f WHERE f.ricompensa_id = r.id AND f.stato = 'COMPLETATO');

SELECT 'Dati demo MySQL BOSTARTER inseriti con successo!' as messaggio;
SELECT CONCAT('Inseriti: ', 
    (SELECT COUNT(*) FROM utenti), ' utenti, ',
    (SELECT COUNT(*) FROM progetti), ' progetti, ',
    (SELECT COUNT(*) FROM finanziamenti), ' finanziamenti, ',
    (SELECT COUNT(*) FROM categorie), ' categorie'
) as riepilogo;
