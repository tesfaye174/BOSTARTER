<?php
namespace BOSTARTER\Config;

/**
 * STANDARD DI SVILUPPO BOSTARTER
 * 
 * Questo file definisce gli standard e le convenzioni che tutti i
 * sviluppatori del team BOSTARTER devono seguire.
 * 
 * È come il "galateo" del nostro codice: le regole che ci permettono
 * di lavorare insieme in armonia e creare software di qualità!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Prima stesura degli standard
 */

class StandardSviluppo 
{
    /**
     * CONVENZIONI DI NOMENCLATURA
     * 
     * Come chiamare le cose nel nostro codice
     */
    const CONVENZIONI_NOMI = [
        // Classi: sempre in italiano con PascalCase
        'classi' => [
            'esempi' => [
                'GestoreUtenti',       // ✅ Corretto
                'ServizioNotifiche',   // ✅ Corretto
                'UserManager'          // ❌ Sbagliato - in inglese
            ],
            'regola' => 'PascalCase in italiano, descrive cosa fa la classe'
        ],
        
        // Metodi: sempre in italiano con camelCase
        'metodi' => [
            'esempi' => [
                'registraNuovoUtente()',    // ✅ Corretto
                'inviaNotificaEmail()',     // ✅ Corretto
                'trovaProgettiAttivi()',    // ✅ Corretto
                'createUser()'              // ❌ Sbagliato - in inglese
            ],
            'regola' => 'camelCase in italiano, verbo che descrive l\'azione'
        ],
        
        // Variabili: sempre in italiano con camelCase
        'variabili' => [
            'esempi' => [
                '$nomeUtente',              // ✅ Corretto
                '$elencoProgetti',          // ✅ Corretto
                '$connessioneDatabase',     // ✅ Corretto
                '$userName'                 // ❌ Sbagliato - in inglese
            ],
            'regola' => 'camelCase in italiano, sostantivo che descrive il contenuto'
        ],
        
        // Costanti: SNAKE_CASE in italiano
        'costanti' => [
            'esempi' => [
                'NUMERO_MASSIMO_TENTATIVI', // ✅ Corretto
                'DURATA_SESSIONE_MINUTI',   // ✅ Corretto
                'MAX_LOGIN_ATTEMPTS'        // ❌ Sbagliato - in inglese
            ],
            'regola' => 'SNAKE_CASE in italiano, tutto maiuscolo'
        ]
    ];
    
    /**
     * STRUTTURA DEI COMMENTI
     * 
     * Come documentare il nostro codice per renderlo comprensibile
     */
    const TEMPLATE_COMMENTI = [
        'intestazione_classe' => '
/**
 * [NOME CLASSE IN MAIUSCOLO] BOSTARTER
 * 
 * Breve descrizione di cosa fa questa classe (1-2 righe).
 * 
 * Spiegazione più dettagliata usando metafore della vita reale:
 * "È come un [metafora] che [cosa fa] per [scopo]"
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - [Descrizione della versione]
 */
        ',
        
        'metodo_pubblico' => '
    /**
     * Breve descrizione di cosa fa questo metodo
     * 
     * Spiegazione più dettagliata con metafora se utile:
     * "È come quando [metafora] e devi [azione]"
     * 
     * @param tipo $parametro Descrizione del parametro
     * @return tipo Descrizione di cosa ritorna
     * @throws ExceptionType Quando può lanciare eccezioni
     */
        ',
        
        'sezioni_codice' => '
        // ===== SEZIONE DEL CODICE =====
        // Descrizione di cosa fa questa sezione
        
        /* 
         * Commento multi-riga per spiegare
         * logica complessa o decisioni importanti
         */
         
        // Commento breve per riga singola
        '
    ];
    
    /**
     * PATTERN DI GESTIONE ERRORI
     * 
     * Come gestire gli errori in modo coerente in tutto il progetto
     */
    const PATTERN_ERRORI = [
        'struttura_risposta' => [
            'successo' => [
                'stato' => 'successo',
                'dati' => '/* risultati */',
                'messaggio' => 'Operazione completata con successo!'
            ],
            'errore' => [
                'stato' => 'errore',
                'messaggio' => 'Descrizione umana dell\'errore',
                'dettagli' => '/* informazioni aggiuntive per debug */'
            ]
        ],
        
        'messaggi_utente' => [
            'regole' => [
                'Sempre in italiano',
                'Comprensibili per utenti non tecnici',
                'Propositivi quando possibile (suggeriscono soluzioni)',
                'Mai codici di errore o dettagli tecnici'
            ],
            'esempi' => [
                'ottimo' => 'Non riesco a trovare questo utente. Controlla di aver inserito l\'email giusta.',
                'buono' => 'Errore nella connessione al database. Riprova tra qualche minuto.',
                'cattivo' => 'SQL Error: Table users doesn\'t exist in line 42'
            ]
        ]
    ];
    
    /**
     * REGOLE DI SICUREZZA
     * 
     * Standard di sicurezza che DEVONO essere sempre rispettati
     */
    const REGOLE_SICUREZZA = [
        'validazione_input' => [
            'tutto_va_validato' => 'Qualsiasi dato che arriva dall\'esterno',
            'usare_whitelist' => 'Definire cosa è permesso, non cosa è vietato',
            'sanitizzazione' => 'Pulire i dati prima di usarli',
            'escape_output' => 'Encode tutto quello che va in output'
        ],
        
        'database' => [
            'prepared_statements' => 'SEMPRE usare prepared statements',
            'mai_concatenazione' => 'MAI concatenare stringhe SQL',
            'principe_privilegio_minimo' => 'Account DB con minimi permessi necessari'
        ],
        
        'password' => [
            'hashing' => 'password_hash() con PASSWORD_ARGON2ID',
            'mai_plaintext' => 'MAI salvare password in chiaro',
            'validazione_robusta' => 'Minimo 8 caratteri, maiusc/minusc/numeri/simboli'
        ]
    ];
    
    /**
     * PERFORMANCE GUIDELINES
     * 
     * Come scrivere codice che performa bene
     */
    const LINEE_GUIDA_PERFORMANCE = [
        'database' => [
            'indici' => 'Creare indici per colonne in WHERE e JOIN',
            'limit_queries' => 'Sempre usare LIMIT nelle query di lista',
            'evitare_n_plus_1' => 'Fare una query con JOIN invece di N query separate',
            'cache_quando_utile' => 'Cache per dati che cambiano raramente'
        ],
        
        'frontend' => [
            'lazy_loading' => 'Caricare immagini e contenuti quando necessari',
            'minimizzazione' => 'Comprimere CSS/JS in produzione',
            'cdn' => 'Usare CDN per risorse statiche',
            'preload_critico' => 'Preload solo per risorse veramente critiche'
        ]
    ];
    
    /**
     * TEMPLATE PER NUOVE CLASSI
     * 
     * Struttura standard per creare nuove classi
     */
    public static function ottieniTemplateClasse($nomeClasse, $namespace = 'BOSTARTER') {
        return "<?php
namespace {$namespace};

/**
 * {$nomeClasse} BOSTARTER
 * 
 * [Descrizione di cosa fa questa classe]
 * 
 * È come [metafora della vita reale] che [cosa fa].
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Prima implementazione
 */

class {$nomeClasse} 
{
    // === PROPRIETÀ DELLA CLASSE ===
    private \$connessioneDatabase;
    private \$configurazioni;
    
    /**
     * Costruttore - Inizializza il nostro gestore
     * 
     * @param PDO \$database Connessione al database
     */
    public function __construct(\$database) {
        \$this->connessioneDatabase = \$database;
        \$this->configurazioni = \$this->caricaConfigurazioni();
    }
    
    /**
     * [Descrizione del metodo principale]
     * 
     * @param mixed \$parametro Descrizione parametro
     * @return array Risultato dell'operazione
     */
    public function metodoPrincipale(\$parametro) {
        try {
            // === VALIDAZIONE INPUT ===
            if (empty(\$parametro)) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Parametro richiesto mancante'
                ];
            }
            
            // === LOGICA PRINCIPALE ===
            // Implementazione...
            
            return [
                'stato' => 'successo',
                'dati' => \$risultato,
                'messaggio' => 'Operazione completata!'
            ];
            
        } catch (\\Exception \$errore) {
            error_log('Errore in {$nomeClasse}: ' . \$errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un problema. Riprova più tardi.'
            ];
        }
    }
    
    /**
     * Carica le configurazioni per questa classe
     * 
     * @return array Configurazioni
     */
    private function caricaConfigurazioni() {
        return [
            // Configurazioni specifiche della classe
        ];
    }
}

// Alias per compatibilità con codice esistente
// class_alias('{$namespace}\\{$nomeClasse}', 'VecchioNomeClasse');
";
    }
    
    /**
     * CHECKLIST PRE-COMMIT
     * 
     * Controlli da fare prima di committare codice
     */
    const CHECKLIST_COMMIT = [
        'codice' => [
            '✅ Tutti i nomi sono in italiano',
            '✅ Commenti presenti e comprensibili',
            '✅ Gestione errori implementata',
            '✅ Validazione input presente',
            '✅ Non ci sono credenziali hardcoded',
            '✅ Query ottimizzate con prepared statements'
        ],
        
        'test' => [
            '✅ Codice testato localmente',
            '✅ Nessun errore PHP',
            '✅ Funzionalità verificata',
            '✅ Performance accettabili'
        ],
        
        'documentazione' => [
            '✅ README aggiornato se necessario',
            '✅ Changelog aggiornato',
            '✅ Commenti del commit descrittivi'
        ]
    ];
    
    /**
     * EMOJI PER COMMIT MESSAGES
     * 
     * Emoji standard per i nostri commit
     */
    const EMOJI_COMMIT = [
        '✨' => 'Nuova funzionalità',
        '🐛' => 'Bug fix',
        '📝' => 'Documentazione',
        '💄' => 'Stili UI/CSS',
        '♻️' => 'Refactoring',
        '⚡' => 'Performance',
        '🔒' => 'Sicurezza',
        '📦' => 'Dipendenze',
        '🔧' => 'Configurazione',
        '🗃️' => 'Database',
        '🎨' => 'Struttura codice',
        '🚀' => 'Deploy',
        '✅' => 'Test',
        '🔥' => 'Rimozione codice'
    ];
}

/**
 * UTILITIES PER SVILUPPATORI
 * 
 * Funzioni di utility per aiutare nello sviluppo
 */
class UtilitySviluppo 
{
    /**
     * Valida che una classe rispetti i nostri standard
     * 
     * @param string $pathFile Path del file da validare
     * @return array Report della validazione
     */
    public static function validaStandardClasse($pathFile) {
        $contenuto = file_get_contents($pathFile);
        $problemi = [];
        
        // Controlla nomenclatura italiana
        if (preg_match('/class [A-Z][a-z]*[A-Z][a-z]*/', $contenuto)) {
            // Probabile nome inglese
            $problemi[] = 'Possibile nome classe in inglese';
        }
        
        // Controlla presenza commenti
        if (!preg_match('/\/\*\*.*?\*\//s', $contenuto)) {
            $problemi[] = 'Mancano commenti DocBlock';
        }
        
        // Controlla gestione errori
        if (!preg_match('/try\s*{/', $contenuto)) {
            $problemi[] = 'Manca gestione errori try-catch';
        }
        
        return [
            'file' => $pathFile,
            'conforme' => empty($problemi),
            'problemi' => $problemi,
            'messaggio' => empty($problemi) ? 
                '✅ File conforme agli standard!' : 
                '⚠️ File ha ' . count($problemi) . ' problemi'
        ];
    }
    
    /**
     * Genera template per nuovo controller
     * 
     * @param string $nomeController Nome del controller da creare
     * @return string Codice del template
     */
    public static function generaTemplateController($nomeController) {
        return StandardSviluppo::ottieniTemplateClasse(
            "Gestore{$nomeController}Controller",
            'BOSTARTER\Controllers'
        );
    }
    
    /**
     * Genera template per nuovo service
     * 
     * @param string $nomeService Nome del service da creare
     * @return string Codice del template
     */
    public static function generaTemplateService($nomeService) {
        return StandardSviluppo::ottieniTemplateClasse(
            "Servizio{$nomeService}",
            'BOSTARTER\Services'
        );
    }
}
