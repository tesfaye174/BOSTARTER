<?php
/**
 * BOSTARTER Message Manager
 * Gestisce messaggi dinamici e più naturali per l'interfaccia utente
 */

class MessageManager {
    private static $messages = [
        'login_success' => [
            'Bentornato! Accesso effettuato con successo.',
            'Eccoti qui! Login completato correttamente.',
            'Perfetto! Sei di nuovo online.',
            'Ciao! È bello rivederti.'
        ],
        'login_failed' => [
            'Ops! Le credenziali inserite non sono corrette.',
            'Email o password non validi. Riprova per favore.',
            'Non riesco a trovarti nel sistema. Controlla i tuoi dati.',
            'Qualcosa non torna con i dati inseriti.'
        ],
        'signup_success' => [
            'Fantastico! Il tuo account è stato creato con successo.',
            'Benvenuto a bordo! Registrazione completata.',
            'Perfetto! Ora fai parte della community BOSTARTER.',
            'Eccellente! Il tuo profilo è pronto all\'uso.'
        ],
        'email_taken' => [
            'Questa email risulta già associata a un account esistente.',
            'Sembra che tu abbia già un account con questa email.',
            'Questa email è già in uso. Hai dimenticato la password?',
            'Un account con questa email esiste già nel sistema.'
        ],
        'project_created' => [
            'Ottimo lavoro! Il tuo progetto è stato pubblicato.',
            'Perfetto! Il progetto è ora visibile a tutti.',
            'Fantastico! Il tuo progetto è online e pronto per ricevere finanziamenti.',
            'Eccellente! Il tuo progetto è stato caricato con successo.',
            'Grande! Il tuo progetto è ora disponibile per i sostenitori.'
        ],
        'insufficient_permissions' => [
            'Non hai i permessi necessari per questa operazione.',
            'Spiacente, questa azione richiede privilegi che non possiedi.',
            'Accesso negato: non sei autorizzato per questa funzione.',
            'Ti serve un livello di accesso superiore per continuare.'
        ],
        'validation_error' => [
            'Controlla i dati inseriti, qualcosa non va.',
            'Alcuni campi necessitano di correzioni.',
            'Per favore, verifica le informazioni inserite.',
            'Ci sono degli errori da correggere nel modulo.'
        ],
        'server_error' => [
            'Si è verificato un problema tecnico. Riprova tra poco.',
            'Ops! Qualcosa è andato storto dal nostro lato.',
            'Errore interno del sistema. I nostri tecnici sono al lavoro.',
            'Problema temporaneo del server. Ci scusiamo per il disagio.'
        ]
    ];

    /**
     * Ottiene un messaggio casuale per il tipo specificato
     */
    public static function get($type, $default = null) {
        if (!isset(self::$messages[$type])) {
            return $default ?? "Operazione completata.";
        }
        
        $messages = self::$messages[$type];
        $randomIndex = array_rand($messages);
        return $messages[$randomIndex];
    }

    /**
     * Aggiunge varietà ai messaggi di errore con dettagli contestuali
     */
    public static function getValidationError($field, $type) {
        $errors = [
            'required' => [
                "Il campo '$field' è obbligatorio.",
                "Non dimenticare di compilare il campo '$field'.",
                "Per favore, inserisci un valore per '$field'."
            ],
            'email' => [
                "L'email inserita non sembra valida.",
                "Controlla che l'indirizzo email sia scritto correttamente.",
                "Formato email non riconosciuto."
            ],
            'password_weak' => [
                "La password deve essere più sicura.",
                "Scegli una password con almeno 8 caratteri.",
                "Per la tua sicurezza, usa una password più complessa."
            ]
        ];

        if (!isset($errors[$type])) {
            return "Errore di validazione per il campo '$field'.";
        }

        $messages = $errors[$type];
        return $messages[array_rand($messages)];
    }

    /**
     * Genera messaggi di successo personalizzati con il nome dell'utente
     */
    public static function personalizedSuccess($type, $userName = null) {
        $prefix = $userName ? "Ciao $userName! " : "";
        
        switch ($type) {
            case 'login':
                return $prefix . self::get('login_success');
            case 'project_creation':
                return $prefix . self::get('project_created');
            default:
                return $prefix . "Operazione completata con successo.";
        }
    }
}