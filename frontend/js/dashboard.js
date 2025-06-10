/**
 * BOSTARTER - Sistema Dashboard Utente
 * Gestisce tutte le funzionalità della dashboard dell'utente
 * Include gestione progetti, statistiche, notifiche e impostazioni
 */

'use strict';

// Classe principale per gestire la dashboard
class GestoreDashboard {
    constructor() {
        this.datiUtente = null;
        this.progetti = [];
        this.notifiche = [];
        this.statistiche = {};

        // Configurazione
        this.configurazione = {
            urlApi: '/backend/api/',
            intervalloAggiornamento: 30000, // 30 secondi
            maxNotificheMostrate: 5
        };

        // Elementi DOM
        this.elementi = {};

        // Timer per aggiornamenti automatici
        this.timerAggiornamento = null;

        // Bind dei metodi
        this.caricaDatiDashboard = this.caricaDatiDashboard.bind(this);
        this.aggiornaStatistiche = this.aggiornaStatistiche.bind(this);
        this.gestisciNotifiche = this.gestisciNotifiche.bind(this);
    }

    /**
     * Inizializza la dashboard
     */
    async inizializza() {
        console.log('🏠 Inizializzazione Dashboard BOSTARTER');

        try {
            // Recuperiamo i riferimenti agli elementi DOM
            this.recuperaElementiDOM();

            // Configuriamo gli event listener
            this.configuraEventListener();

            // Carichiamo i dati iniziali
            await this.caricaDatiDashboard();

            // Avviamo gli aggiornamenti automatici
            this.avviaAggiornamentoAutomatico();

            console.log('✅ Dashboard inizializzata con successo');

        } catch (errore) {
            console.error('❌ Errore nell\'inizializzazione della dashboard:', errore);
            this.mostraMessaggioErrore('Errore nel caricamento della dashboard');
        }
    }

    /**
     * Recupera i riferimenti agli elementi DOM della dashboard
     */
    recuperaElementiDOM() {
        this.elementi = {
            // Sezione statistiche
            statisticheContainer: document.querySelector('.statistiche-container, #statistiche'),
            numeroProgetti: document.querySelector('.numero-progetti, #numero-progetti'),
            importoTotale: document.querySelector('.importo-totale, #importo-totale'),
            donazioniRicevute: document.querySelector('.donazioni-ricevute, #donazioni-ricevute'),

            // Sezione progetti
            progettiContainer: document.querySelector('.progetti-container, #progetti-utente'),
            listaProgetti: document.querySelector('.lista-progetti, #lista-progetti'),
            pulsanteNuovoProgetto: document.querySelector('.nuovo-progetto, #nuovo-progetto'),

            // Sezione notifiche
            notificheContainer: document.querySelector('.notifiche-container, #notifiche'),
            listaNotifiche: document.querySelector('.lista-notifiche, #lista-notifiche'),
            contatorNotifiche: document.querySelector('.contatore-notifiche, #contatore-notifiche'),
            pulsanteMarcaTutte: document.querySelector('.marca-tutte-lette, #marca-tutte-lette'),

            // Elementi di interfaccia
            indicatoreCaricamento: document.querySelector('.caricamento, #caricamento'),
            messaggiContainer: document.querySelector('.messaggi, #messaggi'),
            pulsanteAggiorna: document.querySelector('.aggiorna-dashboard, #aggiorna-dashboard')
        };
    }

    /**
     * Configura tutti gli event listener
     */
    configuraEventListener() {
        // Pulsante per creare nuovo progetto
        if (this.elementi.pulsanteNuovoProgetto) {
            this.elementi.pulsanteNuovoProgetto.addEventListener('click', () => {
                this.apriModalNuovoProgetto();
            });
        }

        // Pulsante per marcare tutte le notifiche come lette
        if (this.elementi.pulsanteMarcaTutte) {
            this.elementi.pulsanteMarcaTutte.addEventListener('click', () => {
                this.marcaTutteNotificheLette();
            });
        }

        // Pulsante per aggiornare manualmente
        if (this.elementi.pulsanteAggiorna) {
            this.elementi.pulsanteAggiorna.addEventListener('click', () => {
                this.caricaDatiDashboard(true);
            });
        }

        // Event listener per i progetti individuali (delegazione eventi)
        if (this.elementi.listaProgetti) {
            this.elementi.listaProgetti.addEventListener('click', (evento) => {
                this.gestisciClickProgetto(evento);
            });
        }

        // Event listener per le notifiche individuali
        if (this.elementi.listaNotifiche) {
            this.elementi.listaNotifiche.addEventListener('click', (evento) => {
                this.gestisciClickNotifica(evento);
            });
        }
    }

    /**
     * Carica tutti i dati della dashboard
     */
    async caricaDatiDashboard(forzaAggiornamento = false) {
        if (!forzaAggiornamento) {
            this.mostraIndicatoreCaricamento(true);
        }

        try {
            // Carichiamo i dati in parallelo per migliori prestazioni
            const [statistiche, progetti, notifiche] = await Promise.all([
                this.caricaStatistiche(),
                this.caricaProgetti(),
                this.caricaNotifiche()
            ]);

            // Aggiorniamo l'interfaccia con i nuovi dati
            this.aggiornaStatistiche(statistiche);
            this.aggiornaProgetti(progetti);
            this.aggiornaNotifiche(notifiche);

            console.log('📊 Dati dashboard aggiornati');

        } catch (errore) {
            console.error('❌ Errore nel caricamento dati:', errore);
            this.mostraMessaggioErrore('Errore nel caricamento dei dati');
        } finally {
            this.mostraIndicatoreCaricamento(false);
        }
    }

    /**
     * Carica le statistiche dell'utente
     */
    async caricaStatistiche() {
        try {
            const risposta = await fetch(`${this.configurazione.urlApi}stats_compliant.php`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!risposta.ok) {
                throw new Error(`Errore HTTP: ${risposta.status}`);
            }

            const dati = await risposta.json();
            this.statistiche = dati;
            return dati;

        } catch (errore) {
            console.error('Errore nel caricamento statistiche:', errore);
            return null;
        }
    }

    /**
     * Carica i progetti dell'utente
     */
    async caricaProgetti() {
        try {
            const risposta = await fetch(`${this.configurazione.urlApi}projects_compliant.php?azione=utente_progetti`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!risposta.ok) {
                throw new Error(`Errore HTTP: ${risposta.status}`);
            }

            const dati = await risposta.json();
            this.progetti = dati.progetti || [];
            return this.progetti;

        } catch (errore) {
            console.error('Errore nel caricamento progetti:', errore);
            return [];
        }
    }

    /**
     * Carica le notifiche dell'utente
     */
    async caricaNotifiche() {
        try {
            const risposta = await fetch(`${this.configurazione.urlApi}notifications.php`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!risposta.ok) {
                throw new Error(`Errore HTTP: ${risposta.status}`);
            }

            const dati = await risposta.json();
            this.notifiche = dati.notifiche || [];
            return this.notifiche;

        } catch (errore) {
            console.error('Errore nel caricamento notifiche:', errore);
            return [];
        }
    }

    /**
     * Aggiorna la sezione statistiche
     */
    aggiornaStatistiche(statistiche) {
        if (!statistiche) return;

        // Aggiorniamo i contatori
        if (this.elementi.numeroProgetti) {
            this.elementi.numeroProgetti.textContent = statistiche.progetti_creati || 0;
        }

        if (this.elementi.importoTotale) {
            const importo = this.formattaImporto(statistiche.totale_ricevuto || 0);
            this.elementi.importoTotale.textContent = importo;
        }

        if (this.elementi.donazioniRicevute) {
            this.elementi.donazioniRicevute.textContent = statistiche.donazioni_ricevute || 0;
        }
    }

    /**
     * Aggiorna la sezione progetti
     */
    aggiornaProgetti(progetti) {
        if (!this.elementi.listaProgetti) return;

        if (!progetti || progetti.length === 0) {
            this.elementi.listaProgetti.innerHTML = `
                <div class="nessun-progetto">
                    <p>Non hai ancora creato nessun progetto.</p>
                    <button class="btn btn-primary" onclick="location.href='/projects/create'">
                        Crea il tuo primo progetto
                    </button>
                </div>
            `;
            return;
        }

        // Generiamo l'HTML per ogni progetto
        const htmlProgetti = progetti.map(progetto => this.generaHtmlProgetto(progetto)).join('');
        this.elementi.listaProgetti.innerHTML = htmlProgetti;
    }

    /**
     * Aggiorna la sezione notifiche
     */
    aggiornaNotifiche(notifiche) {
        if (!this.elementi.listaNotifiche) return;

        // Aggiorniamo il contatore
        const notificheNonLette = notifiche.filter(n => !n.is_read);
        if (this.elementi.contatorNotifiche) {
            this.elementi.contatorNotifiche.textContent = notificheNonLette.length;
            this.elementi.contatorNotifiche.style.display = notificheNonLette.length > 0 ? 'inline' : 'none';
        }

        if (!notifiche || notifiche.length === 0) {
            this.elementi.listaNotifiche.innerHTML = '<p class="nessuna-notifica">Nessuna notifica</p>';
            return;
        }

        // Mostriamo solo le ultime notifiche
        const notificheDaMostrare = notifiche.slice(0, this.configurazione.maxNotificheMostrate);
        const htmlNotifiche = notificheDaMostrare.map(notifica => this.generaHtmlNotifica(notifica)).join('');
        this.elementi.listaNotifiche.innerHTML = htmlNotifiche;
    }

    /**
     * Genera l'HTML per un singolo progetto
     */
    generaHtmlProgetto(progetto) {
        const percentualeCompletamento = progetto.percentuale_completamento || 0;
        const statusClasse = this.ottieniClasseStatusProgetto(progetto.stato);

        return `
            <div class="carta-progetto" data-progetto-id="${progetto.id}">
                <div class="intestazione-progetto">
                    <h4>${progetto.nome}</h4>
                    <span class="badge ${statusClasse}">${progetto.stato}</span>
                </div>
                <div class="corpo-progetto">
                    <p class="descrizione">${progetto.descrizione_breve || ''}</p>
                    <div class="progresso">
                        <div class="barra-progresso">
                            <div class="progresso-riempimento" style="width: ${percentualeCompletamento}%"></div>
                        </div>
                        <small>${percentualeCompletamento.toFixed(1)}% completato</small>
                    </div>
                    <div class="statistiche-progetto">
                        <span>💰 €${this.formattaImporto(progetto.totale_finanziamenti || 0)}</span>
                        <span>📅 ${progetto.giorni_rimanenti || 0} giorni rimasti</span>
                    </div>
                </div>
                <div class="azioni-progetto">
                    <button class="btn btn-sm btn-outline-primary visualizza-progetto" data-id="${progetto.id}">
                        Visualizza
                    </button>
                    <button class="btn btn-sm btn-outline-secondary modifica-progetto" data-id="${progetto.id}">
                        Modifica
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Genera l'HTML per una singola notifica
     */
    generaHtmlNotifica(notifica) {
        const classeLettura = notifica.is_read ? 'letta' : 'non-letta';
        const dataFormattata = this.formattaData(notifica.created_at);

        return `
            <div class="notifica ${classeLettura}" data-notifica-id="${notifica.id}">
                <div class="icona-notifica">
                    ${this.ottieniIconaNotifica(notifica.type)}
                </div>
                <div class="contenuto-notifica">
                    <h6>${notifica.title}</h6>
                    <p>${notifica.message}</p>
                    <small class="tempo">${dataFormattata}</small>
                </div>
                <div class="azioni-notifica">
                    <button class="btn-marca-letta" data-id="${notifica.id}" title="Marca come letta">
                        ✓
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Gestisce i click sui progetti
     */
    gestisciClickProgetto(evento) {
        const pulsante = evento.target.closest('button');
        if (!pulsante) return;

        const idProgetto = pulsante.dataset.id;

        if (pulsante.classList.contains('visualizza-progetto')) {
            window.location.href = `/projects/view.php?id=${idProgetto}`;
        } else if (pulsante.classList.contains('modifica-progetto')) {
            window.location.href = `/projects/edit.php?id=${idProgetto}`;
        }
    }

    /**
     * Gestisce i click sulle notifiche
     */
    async gestisciClickNotifica(evento) {
        const pulsante = evento.target.closest('.btn-marca-letta');
        if (!pulsante) return;

        const idNotifica = pulsante.dataset.id;
        await this.marcaNotificaComeLetta(idNotifica);
    }

    /**
     * Marca una notifica come letta
     */
    async marcaNotificaComeLetta(idNotifica) {
        try {
            const risposta = await fetch(`${this.configurazione.urlApi}notifications.php`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    azione: 'marca_letta',
                    id: idNotifica
                })
            });

            if (risposta.ok) {
                // Aggiorniamo l'interfaccia
                const elementoNotifica = document.querySelector(`[data-notifica-id="${idNotifica}"]`);
                if (elementoNotifica) {
                    elementoNotifica.classList.remove('non-letta');
                    elementoNotifica.classList.add('letta');
                }
            }

        } catch (errore) {
            console.error('Errore nel marcare notifica come letta:', errore);
        }
    }

    /**
     * Marca tutte le notifiche come lette
     */
    async marcaTutteNotificheLette() {
        try {
            const risposta = await fetch(`${this.configurazione.urlApi}notifications.php`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    azione: 'marca_tutte_lette'
                })
            });

            if (risposta.ok) {
                // Ricarichiamo le notifiche
                await this.caricaNotifiche();
                this.aggiornaNotifiche(this.notifiche);
            }

        } catch (errore) {
            console.error('Errore nel marcare tutte le notifiche:', errore);
        }
    }

    /**
     * Avvia l'aggiornamento automatico dei dati
     */
    avviaAggiornamentoAutomatico() {
        this.timerAggiornamento = setInterval(() => {
            this.caricaDatiDashboard(false);
        }, this.configurazione.intervalloAggiornamento);
    }

    /**
     * Ferma l'aggiornamento automatico
     */
    fermaAggiornamentoAutomatico() {
        if (this.timerAggiornamento) {
            clearInterval(this.timerAggiornamento);
            this.timerAggiornamento = null;
        }
    }

    /**
     * Utility: Formatta un importo in euro
     */
    formattaImporto(importo) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(importo);
    }

    /**
     * Utility: Formatta una data
     */
    formattaData(dataString) {
        const data = new Date(dataString);
        return data.toLocaleDateString('it-IT', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Utility: Ottiene la classe CSS per lo status del progetto
     */
    ottieniClasseStatusProgetto(stato) {
        const classi = {
            'attivo': 'badge-success',
            'completato': 'badge-primary',
            'scaduto': 'badge-danger',
            'bozza': 'badge-secondary'
        };
        return classi[stato] || 'badge-secondary';
    }

    /**
     * Utility: Ottiene l'icona per il tipo di notifica
     */
    ottieniIconaNotifica(tipo) {
        const icone = {
            'nuovo_finanziamento': '💰',
            'progetto_completato': '🎉',
            'nuovo_commento': '💬',
            'sistema': '⚙️',
            'default': '📢'
        };
        return icone[tipo] || icone.default;
    }

    /**
     * Mostra/nasconde l'indicatore di caricamento
     */
    mostraIndicatoreCaricamento(mostra) {
        if (this.elementi.indicatoreCaricamento) {
            this.elementi.indicatoreCaricamento.style.display = mostra ? 'block' : 'none';
        }
    }

    /**
     * Mostra un messaggio di errore
     */
    mostraMessaggioErrore(messaggio) {
        if (this.elementi.messaggiContainer) {
            this.elementi.messaggiContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible">
                    ${messaggio}
                    <button type="button" class="close" onclick="this.parentElement.remove()">
                        <span>&times;</span>
                    </button>
                </div>
            `;
        } else {
            console.error(messaggio);
        }
    }

    /**
     * Apre il modal per creare un nuovo progetto
     */
    apriModalNuovoProgetto() {
        // Per ora reindirizziamo alla pagina di creazione
        window.location.href = '/projects/create.php';
    }

    /**
     * Cleanup quando la pagina viene chiusa
     */
    cleanup() {
        this.fermaAggiornamentoAutomatico();
    }
}

// Inizializzazione quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function () {
    const gestoreDashboard = new GestoreDashboard();
    gestoreDashboard.inizializza();

    // Cleanup quando la pagina viene chiusa
    window.addEventListener('beforeunload', () => {
        gestoreDashboard.cleanup();
    });
});
