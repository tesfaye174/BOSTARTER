/**
 * BOSTARTER - Sistema di Login e Autenticazione Client-Side
 * Gestisce la validazione del form di login e l'esperienza utente
 * Include funzionalità di sicurezza e feedback visivo
 */

'use strict';

// Classe per gestire il sistema di login
class GestoreLogin {
    constructor() {
        this.formLogin = null;
        this.campiForm = {};
        this.indicatoreCaricamento = null;

        // Colleghiamo i metodi al contesto della classe
        this.gestisciInvioForm = this.gestisciInvioForm.bind(this);
        this.validaForm = this.validaForm.bind(this);
    }

    /**
     * Inizializza il gestore del login
     */
    inizializza() {
        // Cerchiamo il form di login nella pagina
        this.formLogin = document.querySelector('.login-form, #login-form, form[data-type="login"]');

        if (!this.formLogin) {
            console.log('Form di login non trovato nella pagina');
            return;
        }

        // Identifichiamo i campi del form
        this.campiForm = {
            email: this.formLogin.querySelector('[name="email"], [type="email"]'),
            password: this.formLogin.querySelector('[name="password"], [type="password"]'),
            ricordami: this.formLogin.querySelector('[name="remember_me"], [name="ricordami"]'),
            bottoneInvio: this.formLogin.querySelector('[type="submit"], .btn-login')
        };

        // Configuriamo gli event listener
        this.configuraEventListener();

        // Configuriamo la validazione in tempo reale
        this.configuraValidazioneTempoReale();

        console.log('Sistema di login inizializzato correttamente');
    }

    /**
     * Configura tutti gli event listener necessari
     */
    configuraEventListener() {
        // Listener per l'invio del form
        this.formLogin.addEventListener('submit', this.gestisciInvioForm);

        // Listener per il recupero automatico del focus
        const primoInput = this.campiForm.email || this.formLogin.querySelector('input[type="text"], input[type="email"]');
        if (primoInput) {
            primoInput.focus();
        }

        // Listener per il tasto Invio sui campi
        Object.values(this.campiForm).forEach(campo => {
            if (campo && campo.tagName === 'INPUT') {
                campo.addEventListener('keypress', (evento) => {
                    if (evento.key === 'Enter') {
                        this.gestisciInvioForm(evento);
                    }
                });
            }
        });
    }

    /**
     * Configura la validazione in tempo reale dei campi
     */
    configuraValidazioneTempoReale() {
        // Validazione email in tempo reale
        if (this.campiForm.email) {
            this.campiForm.email.addEventListener('blur', () => {
                this.validaCampoEmail();
            });
        }

        // Validazione password in tempo reale
        if (this.campiForm.password) {
            this.campiForm.password.addEventListener('input', () => {
                this.validaCampoPassword();
            });
        }
    }

    /**
     * Gestisce l'invio del form di login
     */
    gestisciInvioForm(evento) {
        evento.preventDefault();

        // Eseguiamo la validazione completa
        if (!this.validaForm()) {
            this.mostraErrore('Per favore, correggi gli errori evidenziati');
            return false;
        }

        // Mostriamo l'indicatore di caricamento
        this.mostraCaricamento();

        // Prepariamo i dati per l'invio
        const datiLogin = this.raccogliDatiForm();

        // Inviamo la richiesta di login
        this.inviaRichiestaLogin(datiLogin);
    }

    /**
     * Valida tutti i campi del form
     */
    validaForm() {
        let formValido = true;

        // Rimuoviamo eventuali messaggi di errore precedenti
        this.rimuoviErrori();

        // Validazione email
        if (!this.validaCampoEmail()) {
            formValido = false;
        }

        // Validazione password
        if (!this.validaCampoPassword()) {
            formValido = false;
        }

        return formValido;
    }

    /**
     * Valida il campo email
     */
    validaCampoEmail() {
        const email = this.campiForm.email?.value?.trim();

        if (!email) {
            this.mostraErroreCampo(this.campiForm.email, 'L\'email è obbligatoria');
            return false;
        }

        // Regex per validazione email più robusta
        const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!regexEmail.test(email)) {
            this.mostraErroreCampo(this.campiForm.email, 'Inserisci un\'email valida');
            return false;
        }

        this.rimuoviErroreCampo(this.campiForm.email);
        return true;
    }

    /**
     * Valida il campo password
     */
    validaCampoPassword() {
        const password = this.campiForm.password?.value;

        if (!password) {
            this.mostraErroreCampo(this.campiForm.password, 'La password è obbligatoria');
            return false;
        }

        if (password.length < 6) {
            this.mostraErroreCampo(this.campiForm.password, 'La password deve essere di almeno 6 caratteri');
            return false;
        }

        this.rimuoviErroreCampo(this.campiForm.password);
        return true;
    }

    /**
     * Raccoglie i dati dal form
     */
    raccogliDatiForm() {
        return {
            email: this.campiForm.email?.value?.trim(),
            password: this.campiForm.password?.value,
            ricordami: this.campiForm.ricordami?.checked || false
        };
    }

    /**
     * Invia la richiesta di login al server
     */
    async inviaRichiestaLogin(datiLogin) {
        try {
            // Qui invieremo la richiesta al server
            // Per ora simuliamo con il form normale
            this.formLogin.submit();

        } catch (errore) {
            console.error('Errore durante il login:', errore);
            this.nascondiCaricamento();
            this.mostraErrore('Si è verificato un errore. Riprova più tardi.');
        }
    }

    /**
     * Mostra un errore specifico per un campo
     */
    mostraErroreCampo(campo, messaggio) {
        if (!campo) return;

        // Aggiungiamo la classe di errore al campo
        campo.classList.add('error', 'is-invalid');

        // Cerchiamo o creiamo l'elemento per il messaggio di errore
        let messaggioErrore = campo.parentNode.querySelector('.error-message');
        if (!messaggioErrore) {
            messaggioErrore = document.createElement('div');
            messaggioErrore.className = 'error-message text-danger small mt-1';
            campo.parentNode.appendChild(messaggioErrore);
        }

        messaggioErrore.textContent = messaggio;
        messaggioErrore.style.display = 'block';
    }

    /**
     * Rimuove l'errore da un campo specifico
     */
    rimuoviErroreCampo(campo) {
        if (!campo) return;

        campo.classList.remove('error', 'is-invalid');
        const messaggioErrore = campo.parentNode.querySelector('.error-message');
        if (messaggioErrore) {
            messaggioErrore.style.display = 'none';
        }
    }

    /**
     * Rimuove tutti gli errori dal form
     */
    rimuoviErrori() {
        // Rimuoviamo le classi di errore da tutti i campi
        const campiConErrore = this.formLogin.querySelectorAll('.error, .is-invalid');
        campiConErrore.forEach(campo => {
            campo.classList.remove('error', 'is-invalid');
        });

        // Nascondiamo tutti i messaggi di errore
        const messaggiErrore = this.formLogin.querySelectorAll('.error-message');
        messaggiErrore.forEach(messaggio => {
            messaggio.style.display = 'none';
        });
    }

    /**
     * Mostra un messaggio di errore generale
     */
    mostraErrore(messaggio) {
        // Cerchiamo un contenitore per errori generali
        let contenitoreErrore = this.formLogin.querySelector('.alert-danger, .error-container');

        if (!contenitoreErrore) {
            contenitoreErrore = document.createElement('div');
            contenitoreErrore.className = 'alert alert-danger error-container mt-3';
            this.formLogin.insertBefore(contenitoreErrore, this.formLogin.firstChild);
        }

        contenitoreErrore.textContent = messaggio;
        contenitoreErrore.style.display = 'block';

        // Scorriamo verso l'errore
        contenitoreErrore.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Mostra l'indicatore di caricamento
     */
    mostraCaricamento() {
        if (this.campiForm.bottoneInvio) {
            this.campiForm.bottoneInvio.disabled = true;
            this.campiForm.bottoneInvio.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';
        }
    }

    /**
     * Nasconde l'indicatore di caricamento
     */
    nascondiCaricamento() {
        if (this.campiForm.bottoneInvio) {
            this.campiForm.bottoneInvio.disabled = false;
            this.campiForm.bottoneInvio.innerHTML = 'Accedi';
        }
    }
}

// Inizializziamo il sistema quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function () {
    const gestoreLogin = new GestoreLogin();
    gestoreLogin.inizializza();
});

// Esportiamo la classe per uso in altri moduli se necessario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GestoreLogin;
}
