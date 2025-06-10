/**
 * BOSTARTER - Sistema di Registrazione Client-Side
 * Gestisce la validazione del form di registrazione e l'esperienza utente
 * Include controlli di sicurezza e feedback interattivo
 */

'use strict';

// Classe per gestire il sistema di registrazione
class GestoreRegistrazione {
    constructor() {
        this.formRegistrazione = null;
        this.campiForm = {};
        this.validatori = {};
        this.indicatoreCaricamento = null;

        // Colleghiamo i metodi al contesto della classe
        this.gestisciInvioForm = this.gestisciInvioForm.bind(this);
        this.validaForm = this.validaForm.bind(this);
        this.validaCampoSingolo = this.validaCampoSingolo.bind(this);
    }

    /**
     * Inizializza il gestore della registrazione
     */
    inizializza() {
        // Cerchiamo il form di registrazione nella pagina
        this.formRegistrazione = document.querySelector('.register-form, #register-form, form[data-type="register"]');

        if (!this.formRegistrazione) {
            console.log('Form di registrazione non trovato nella pagina');
            return;
        }

        console.log('🎯 Inizializzazione sistema di registrazione');

        // Recuperiamo i riferimenti ai campi del form
        this.recuperaCampiForm();

        // Configuriamo i validatori per ogni campo
        this.configuraValidatori();

        // Aggiungiamo gli event listener
        this.aggiungiEventListener();

        // Configuriamo il feedback visivo
        this.configuraFeedbackVisivo();
    }

    /**
     * Recupera i riferimenti ai campi del form
     */
    recuperaCampiForm() {
        this.campiForm = {
            nomeUtente: this.formRegistrazione.querySelector('input[name="username"], input[name="nickname"]'),
            email: this.formRegistrazione.querySelector('input[name="email"]'),
            password: this.formRegistrazione.querySelector('input[name="password"]'),
            confermaPassword: this.formRegistrazione.querySelector('input[name="confirm_password"], input[name="conferma_password"]'),
            nome: this.formRegistrazione.querySelector('input[name="nome"]'),
            cognome: this.formRegistrazione.querySelector('input[name="cognome"]'),
            dataNascita: this.formRegistrazione.querySelector('input[name="data_nascita"]'),
            luogoNascita: this.formRegistrazione.querySelector('input[name="luogo_nascita"]'),
            tipoUtente: this.formRegistrazione.querySelector('select[name="tipo_utente"]')
        };

        // Rimuoviamo i campi null (non presenti nel form)
        Object.keys(this.campiForm).forEach(chiave => {
            if (!this.campiForm[chiave]) {
                delete this.campiForm[chiave];
            }
        });
    }

    /**
     * Configura i validatori per ogni tipo di campo
     */
    configuraValidatori() {
        this.validatori = {
            nomeUtente: (valore) => {
                if (!valore || valore.length < 3) {
                    return 'Il nome utente deve essere di almeno 3 caratteri';
                }
                if (!/^[a-zA-Z0-9_]+$/.test(valore)) {
                    return 'Il nome utente può contenere solo lettere, numeri e underscore';
                }
                return null;
            },

            email: (valore) => {
                if (!valore) {
                    return 'L\'email è obbligatoria';
                }
                const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!regexEmail.test(valore)) {
                    return 'Inserisci un indirizzo email valido';
                }
                return null;
            },

            password: (valore) => {
                if (!valore) {
                    return 'La password è obbligatoria';
                }
                if (valore.length < 8) {
                    return 'La password deve essere di almeno 8 caratteri';
                }
                if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(valore)) {
                    return 'La password deve contenere almeno una lettera minuscola, una maiuscola e un numero';
                }
                return null;
            },

            confermaPassword: (valore) => {
                const password = this.campiForm.password ? this.campiForm.password.value : '';
                if (!valore) {
                    return 'Conferma la password';
                }
                if (valore !== password) {
                    return 'Le password non coincidono';
                }
                return null;
            },

            nome: (valore) => {
                if (!valore || valore.trim().length < 2) {
                    return 'Il nome deve essere di almeno 2 caratteri';
                }
                return null;
            },

            cognome: (valore) => {
                if (!valore || valore.trim().length < 2) {
                    return 'Il cognome deve essere di almeno 2 caratteri';
                }
                return null;
            }
        };
    }

    /**
     * Aggiunge tutti gli event listener necessari
     */
    aggiungiEventListener() {
        // Event listener per l'invio del form
        this.formRegistrazione.addEventListener('submit', this.gestisciInvioForm);

        // Event listener per la validazione in tempo reale
        Object.keys(this.campiForm).forEach(nomeCampo => {
            const campo = this.campiForm[nomeCampo];
            if (campo) {
                campo.addEventListener('blur', () => this.validaCampoSingolo(nomeCampo));
                campo.addEventListener('input', () => this.rimuoviErrore(nomeCampo));
            }
        });
    }

    /**
     * Gestisce l'invio del form di registrazione
     */
    gestisciInvioForm(evento) {
        console.log('📝 Tentativo di registrazione in corso...');

        // Preveniamo l'invio automatico per validare prima
        evento.preventDefault();

        // Validazione completa del form
        const risultatoValidazione = this.validaForm();

        if (!risultatoValidazione.valido) {
            console.log('❌ Validazione fallita:', risultatoValidazione.errori);
            this.mostraErroriValidazione(risultatoValidazione.errori);
            return;
        }

        // Se la validazione è passata, possiamo procedere
        console.log('✅ Validazione completata con successo');
        this.mostraIndicatoreCaricamento();

        // Inviamo il form
        this.formRegistrazione.submit();
    }

    /**
     * Valida tutti i campi del form
     */
    validaForm() {
        const errori = {};
        let formValido = true;

        // Validazione di tutti i campi
        Object.keys(this.campiForm).forEach(nomeCampo => {
            const risultatoValidazione = this.validaCampoSingolo(nomeCampo);
            if (risultatoValidazione) {
                errori[nomeCampo] = risultatoValidazione;
                formValido = false;
            }
        });

        return {
            valido: formValido,
            errori: errori
        };
    }

    /**
     * Valida un singolo campo
     */
    validaCampoSingolo(nomeCampo) {
        const campo = this.campiForm[nomeCampo];
        const validatore = this.validatori[nomeCampo];

        if (!campo || !validatore) {
            return null;
        }

        const valore = campo.value.trim();
        const messaggioErrore = validatore(valore);

        if (messaggioErrore) {
            this.mostraErroreCampo(nomeCampo, messaggioErrore);
        } else {
            this.rimuoviErrore(nomeCampo);
        }

        return messaggioErrore;
    }

    /**
     * Mostra un errore per un campo specifico
     */
    mostraErroreCampo(nomeCampo, messaggio) {
        const campo = this.campiForm[nomeCampo];
        if (!campo) return;

        // Aggiungiamo la classe di errore al campo
        campo.classList.add('errore');

        // Cerchiamo o creiamo l'elemento per il messaggio di errore
        let elementoErrore = campo.parentNode.querySelector('.messaggio-errore');
        if (!elementoErrore) {
            elementoErrore = document.createElement('div');
            elementoErrore.className = 'messaggio-errore';
            campo.parentNode.appendChild(elementoErrore);
        }

        elementoErrore.textContent = messaggio;
        elementoErrore.style.display = 'block';
    }

    /**
     * Rimuove l'errore da un campo
     */
    rimuoviErrore(nomeCampo) {
        const campo = this.campiForm[nomeCampo];
        if (!campo) return;

        campo.classList.remove('errore');

        const elementoErrore = campo.parentNode.querySelector('.messaggio-errore');
        if (elementoErrore) {
            elementoErrore.style.display = 'none';
        }
    }

    /**
     * Mostra tutti gli errori di validazione
     */
    mostraErroriValidazione(errori) {
        // Primo errore prende il focus
        const primoErrore = Object.keys(errori)[0];
        if (primoErrore && this.campiForm[primoErrore]) {
            this.campiForm[primoErrore].focus();
        }

        // Mostriamo un messaggio generale se necessario
        this.mostraMessaggioGenerale('Correggi gli errori evidenziati prima di procedere', 'errore');
    }

    /**
     * Configura il feedback visivo per una migliore UX
     */
    configuraFeedbackVisivo() {
        // Aggiungiamo stili CSS dinamici se non presenti
        if (!document.querySelector('#stili-registrazione')) {
            const stili = document.createElement('style');
            stili.id = 'stili-registrazione';
            stili.textContent = `
                .campo-form.errore {
                    border-color: #e74c3c !important;
                    box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25) !important;
                }
                .messaggio-errore {
                    color: #e74c3c;
                    font-size: 0.875rem;
                    margin-top: 0.25rem;
                    display: none;
                }
                .indicatore-caricamento {
                    display: none;
                    text-align: center;
                    padding: 1rem;
                    color: #007bff;
                }
            `;
            document.head.appendChild(stili);
        }
    }

    /**
     * Mostra l'indicatore di caricamento
     */
    mostraIndicatoreCaricamento() {
        // Disabilitiamo il pulsante di invio
        const pulsanteInvio = this.formRegistrazione.querySelector('button[type="submit"], input[type="submit"]');
        if (pulsanteInvio) {
            pulsanteInvio.disabled = true;
            pulsanteInvio.textContent = 'Registrazione in corso...';
        }
    }

    /**
     * Mostra un messaggio generale all'utente
     */
    mostraMessaggioGenerale(messaggio, tipo = 'info') {
        // Per ora usiamo console.log, ma si può implementare un toast o alert personalizzato
        console.log(`${tipo.toUpperCase()}: ${messaggio}`);
    }
}

// Inizializzazione quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function () {
    const gestoreRegistrazione = new GestoreRegistrazione();
    gestoreRegistrazione.inizializza();
});
