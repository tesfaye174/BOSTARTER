# BOSTARTER
Si	vuole	realizzare	la	piattaforma	BOSTARTER per	supportare	la	creazione	di	campagne	di	
crowdfounding	finalizzate	alla	realizzazione	di	progetti	hardware/software.	La	piattaforma	è	
liberamente	ispirata	alla	piattaforma	Kickststarter	(https://www.kickstarter.com).	La	
piattaforma	consente	agli	utenti	di	creare	un	progetto	hardware	o	software	che	si	vuole	
finanziare,	indicando	il	budget	da	raggiungere	e	la	data	limite	entro	cui	ottenere	tale	importo.	
Altri	utenti	della	piattaforma	possono	finanziare	il	progetto,	ricevendo	in	cambio	una	qualche	
reward	(non	economica)	dai	creatori.	E’	prevista	la	possibilità	di	candidarsi	allo	sviluppo	di	un	
progetto	software,	se	le	skill	possedute	dall’utente	fanno	matching	con	quelle	dei	profili	
richiesti	dal	progetto.	Infine,	la	piattaforma	consente	l’inserimento	di	commenti.	
SPECIFICA	DELLA	PIATTAFORMA.	
La	piattaforma	BOSTARTER gestisce	i	dati	degli	utenti	registrati.	Ogni	utente	dispone	di	
indirizzo	email	(univoco),	nickname,	password,	nome,	cognome,	anno	di	nascita,	luogo	di	
nascita.	Inoltre,	ogni	utente	può	indicare	le	proprie	skill	di	curriculum.	Le	skill	di	curriculum	
consistono	in	una	sequenza	di:	<competenza,	livello>,	dove	la	competenza	è	una	stringa	ed	il	
livello	è	un	numero	tra	0	e	5	(es.	<AI,	3>).	La	lista	delle	competenze	è	comune	a	tutti	gli	utenti	
della	piattaforma.	Alcuni	utenti	-ma	non	tutti-	possono	appartenere	a	due	sotto-categorie:	
utenti	amministratori,	o	utenti	creatori.	Gli	utenti	amministratori	dispongono	anche	di	un	
codice	di	sicurezza.	Solo	gli	utenti	amministratori	possono	popolare	la	lista	delle	competenze.	
Un	utente	creatore	dispone	anche	dei	campi:	#nr_progetti	(ridondanza	concettuale,	vedi	
sotto)	ed	affidabilità.	Un	utente	creatore	–	e	solo	lui-	può	inserire	uno	o	più	progetti.	Ogni	
progetto	dispone	di	un	nome	(univoco),	un	campo	descrizione,	una	data	di	inserimento,	una	o	
più	foto,	un	budget	da	raggiungere	per	avviare	il	progetto,	una	data	limite	entro	cui	
raggiungere	il	budget,	uno	stato.	Lo	stato	è	un	campo	di	tipo	enum	(aperto/chiuso).	Ogni	
progetto	è	associato	ad	un	solo	utente	creatore.	Inoltre,	ogni	progetto	prevede	una	lista	di	
reward:	una	reward	dispone	di	un	codice	univoco,	una	breve	descrizione,	una	foto.	I	progetti	
appartengono	esclusivamente	a	due	categorie:	progetti	hardware	o	progetti	software.	Nel	
caso	dei	progetti	hardware,	è	presente	anche	la	lista	delle	componenti	necessarie:	ogni	
componente	ha	un	nome	univoco,	una	descrizione,	un	prezzo,	una	quantità	(>0).	Nel	caso	dei	
progetti	software,	viene	elencata	la	lista	dei	profili	necessari	per	lo	sviluppo.	Ogni	profilo	
dispone	di	un	nome	(es.	“Esperto	AI”)	e	di	skill	richieste:	come	nel	caso	delle	skill	di	
curriculum,	esse	consistono	in	una	sequenza	<competenza,	livello>,	dove	la	competenza	è	una	
stringa	-tra	quelle	presenti	in	piattaforma-	ed	il	livello	è	un	numero	tra	0	e	5.	Ogni	utente	della	
piattaforma	può	finanziare	un	progetto:	ogni	finanziamento	dispone	di	un	importo	ed	una	
data.	Un	utente	potrebbe	inserire	più	finanziamenti	per	lo	stesso	progetto,	ma	in	date	diverse.	
Nel	momento	in	cui	la	somma	totale	degli	importi	dei	finanziamenti	supera	il	budget	del	
progetto,	oppure	il	progetto	resta	in	stato	aperto	oltre	la	data	limite,	lo	stato	di	tale	progetto	
diventa	pari	a	chiuso:	un	progetto	chiuso	non	accetta	ulteriori	finanziamenti.		Ad	ogni	
finanziamento	è	associata	una	sola	reward,	tra	quelle	previste	per	il	progetto	finanziato.	Un	
utente	può	inserire	commenti	relativi	ad	un	progetto.	Ogni	commento	dispone	di	un	id	
(univoco),	una	data	ed	un	campo	testo.	L’	utente	creatore	può	eventualmente	inserire	una	
risposta	per	ogni	singolo	commento	(un	commento	ha	al	massimo	1	risposta).	Infine,	è	
prevista	la	possibilità	per	gli	utenti	di	candidarsi	come	partecipanti	allo	sviluppo	di	un	
progetto	software.	Un	utente	può	candidarsi	ad	un	numero	qualsiasi	di	profili.	Un	progetto	
software	può	ricevere	un	numero	qualsiasi	di	candidature	per	un	certo	profilo.	La	piattaforma	
consente	ad	un	utente	di	inserire	una	candidatura	su	un	profilo	SOLO	se,	per	ogni	skill	
richiesta	da	un	profilo,	l’utente	dispone	di	un	livello	superiore	o	uguale	al	valore	richiesto.	
L’utente	creatore	può	accettare	o	meno	la	candidatura.	
Infine,	si	vuole	tenere	traccia	di	tutti	gli	eventi	che	occorrono	nella	piattaforma,	
relativamente	all’inserimento	di	nuovi	dati	(es.	nuovi	utenti,	nuovi	progetti,	etc).	Tali	
eventi	vanno	inseriti,	sotto	forma	di	messaggi	di	testo,	all’interno	di	un	log,	
implementato	in	un’	apposita	collezione	MongoDB.		
Operazioni	sui	dati1:	
Operazioni	che	riguardano	tutti	gli	utenti:	
• Autenticazione/registrazione	sulla	piattaforma	
• Inserimento	delle	proprie	skill	di	curriculum	
• Visualizzazione	dei	progetti	disponibili	
• Finanziamento	di	un	progetto	(aperto).	Un	utente	può	finanziare	anche	il	progetto	di	cui	è	
creatore.	
• Scelta	della	reward	a	valle	del	finanziamento	di	un	progetto	
• Inserimento	di	un	commento	relativo	ad	un	progetto	
• Inserimento	di	una	candidatura	per	un	profilo	richiesto	per	la	realizzazione	di	un	progetto	
software	
Operazioni	che	riguardano	SOLO	gli	amministratori:	
• Inserimento	di	una	nuova	stringa	nella	lista	delle	competenze	
• In	fase	di	autenticazione,	oltre	a	username	e	password,	viene	richiesto	anche	il	codice	di	
sicurezza	
Operazioni	che	riguardano	SOLO	gli	utenti	creatori:	
• Inserimento	di	un	nuovo	progetto	
• Inserimento	delle	reward	per	un	progetto	
• Inserimento	di	una	risposta	ad	un	commento		
• Inserimento	di	un	profilo	-solo	per	la	realizzazione	di	un	progetto	software	
• Accettazione	o	meno	di	una	candidatura	
Statistiche	(visibili	da	tutti	gli	utenti):	
• Visualizzare	la	classifica	degli	utenti	creatori,	in	base	al	loro	valore	di	affidabilità.	Mostrare	
solo	il	nickname	dei	primi	3	utenti.		
• Visualizzare	i	progetti	APERTI	che	sono	più	vicini	al	proprio	completamento	(=	minore	
differenza	tra	budget	richiesto	e	somma	totale	dei	finanziamenti	ricevuti).	Mostrare	solo	i	
primi	3	progetti.	
• Visualizzare	la	classifica	degli	utenti,	ordinati	in	base	al	TOTALE	di	finanziamenti	erogati.	
Mostrare	solo	i	nickname	dei	primi	3	utenti.	
1 La	lista	contiene	le	operazioni	di	base:	può	essere	estesa/modificata	a	discrezione	dello	
studente.	
Popolamento	della	piattaforma:	
Non	richiesta,	bastano	i	dati	sufficienti	per	la	demo	in	sede	d’esame.	
Vincoli	sull’implementazione:	- - - - - - 
Implementare	tutte	le	operazioni	sui	dati	(ove	possibile)	attraverso	stored	procedure.	
Implementare	le	tre	statistiche	menzionate	in	precedenza	mediante	viste.	
Utilizzare	dei	trigger	per	aggiornare	l’affidabilità	di	un	utente	creatore.	L’affidabilità	viene	
calcolata	come	X	è	la	percentuale	di	progetti	creati	dall’utente	che	hanno	ottenuto	almeno	
un	finanziamento.	L’affidabilità	viene	aggiornata:	(i)	ogni	qualvolta	un	utente	crea	un	
progetto	(denominatore);	(ii)	ogni	qualvolta	un	progetto	dell’utente	riceve	un	
finanziamento	(contribuisce	al	numeratore).	
Utilizzare	un	trigger	per	cambiare	lo	stato	di	un	progetto.	Lo	stato	di	un	progetto	diventa	
CHIUSO	quando	ha	raggiunto	un	valore	complessivo	di	finanziamenti	pari	al	budget	
richiesto.		
Utilizzare	un	trigger	per	incrementare	il	campo	#nr_progetti.	Ogni	qualvolta	un	utente	
creatore	inserisce	un	progetto,	il	campo	viene	incrementato	di	un’unità.	
Utilizzare	un	evento	per	cambiare	lo	stato	di	un	progetto.	Lo	stato	di	un	progetto	diventa	
CHIUSO	quando	la	data	attuale	è	posteriore	alla	data	di	chiusura	del	progetto	stesso.	
L’evento	viene	eseguito	1	volta	al	giorno.	
Tabelle	dei	volumi:	- 
Valutare	se	la	seguente	ridondanza:		
campo	#nr_progetti	relativo	ad	un	utente	creatore	
debba	essere	tenuta	o	eliminata,	sulla	base	delle	seguenti	operazioni:	
o Aggiungere	un	nuovo	progetto	ad	un	utente	creatore	esistente	(1	volte/mese,	
interattiva)	
o Visualizzare	tutti	i	progetti	e	tutti	i	finanziamenti	(1	volta/mese,	batch)	
o Contare	il	numero	di	progetti	associati	ad	uno	specifico	utente	(3	volte/mese,	batch)	- Coefficienti	per	l’analisi:	wI	=	1,	wB	=	0.5,	a	=	2	- Tabella	dei	volumi:	10	progetti,	3	finanziamenti	per	progetto,	5	utenti,	2	progetti	per	
utente	
Bonus:	
Il	punteggio	massimo	ottenibile	è	30/30	se	si	implementano	correttamente	tutte	le	specifiche	
menzionate	fin	qui.		E’	previsto	il	seguente	bonus:	- 
(per	la	lode,	solo	se	i	punti	precedenti	sono	stati	sviluppati	correttamente)	Utilizzo	di	
librerie	
CSS	per	la	realizzazione	del	front-end	Web	(es.	Bootstrap	
https://getbootstrap.com)
## Server Structure
```bash
/src/server/
├── api/                    # API endpoints
│   ├── auth/              # Authentication endpoints
│   ├── projects/          # Project management
│   ├── users/             # User management
│   └── admin/             # Admin operations
├── controllers/           # Business logic
├── models/               # Data models
├── middleware/           # Request middleware
├── utils/               # Helper functions
└── config/              # Configuration files