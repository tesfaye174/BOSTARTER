# Sistema di Autenticazione Unificato BOSTARTER

Questo documento descrive il nuovo sistema di autenticazione unificato implementato per il progetto BOSTARTER.

## Panoramica

Il sistema di autenticazione è stato unificato per risolvere la duplicazione di codice e funzionalità tra i file `backend/auth.php` e `database/auth.php`. La nuova implementazione:

- Utilizza la classe `User` come base per tutte le operazioni di autenticazione
- Fornisce un'API unificata attraverso il file `auth.php` nella root del progetto
- Offre un endpoint API dedicato in `backend/auth_api.php` per le richieste AJAX

## Struttura dei File

- `classes/User.php`: Classe principale che gestisce tutte le operazioni relative agli utenti
- `auth.php`: File principale che fornisce funzioni wrapper per utilizzare la classe User
- `backend/auth_api.php`: Endpoint API per le richieste di autenticazione via AJAX

## Come Utilizzare il Sistema

### Inclusione nel Progetto

```php
// Includi il sistema di autenticazione
require_once 'auth.php';
```

### Registrazione Utente

```php
$dati = [
    'email' => 'utente@esempio.com',
    'nickname' => 'utente123',
    'password' => 'Password123',
    'nome' => 'Mario',
    'cognome' => 'Rossi',
    'anno_nascita' => 1990,
    'luogo_nascita' => 'Roma',
    'tipo_utente' => 'standard' // o 'creatore'
];

$risultato = registraUtente($dati);

if ($risultato['success']) {
    echo "Utente registrato con successo! ID: {$risultato['user_id']}";
} else {
    echo "Errore: {$risultato['message']}";
}
```

### Login Utente

```php
$email = 'utente@esempio.com';
$password = 'Password123';
$ricordami = true; // Per il login automatico

$risultato = loginUtente($email, $password, $ricordami);

if ($risultato['success']) {
    echo "Login effettuato con successo!";
    // Reindirizza l'utente alla dashboard
} else {
    echo "Errore: {$risultato['message']}";
}
```

### Verifica Utente Loggato

```php
if (isLogged()) {
    echo "Utente loggato";
    $utente = getCurrentUser();
    echo "Benvenuto, {$utente['nickname']}!";
} else {
    echo "Utente non loggato";
    // Reindirizza alla pagina di login
}
```

### Verifica Ruolo Utente

```php
if (isAdmin()) {
    // Mostra funzionalità amministrative
} elseif (isCreatore()) {
    // Mostra funzionalità per creatori
} else {
    // Mostra funzionalità standard
}
```

### Logout

```php
logoutUtente();
// Reindirizza alla home page
```

## API Endpoint

L'endpoint API `backend/auth_api.php` supporta le seguenti azioni via POST:

### Login

```javascript
$.post('backend/auth_api.php', {
    action: 'login',
    email: 'utente@esempio.com',
    password: 'Password123',
    remember: 'true' // opzionale
}).done(function(response) {
    if (response.success) {
        window.location.href = response.redirect;
    }
}).fail(function(xhr) {
    alert('Errore: ' + xhr.responseJSON.message);
});
```

### Registrazione

```javascript
$.post('backend/auth_api.php', {
    action: 'register',
    email: 'utente@esempio.com',
    nickname: 'utente123',
    password: 'Password123',
    nome: 'Mario',
    cognome: 'Rossi',
    anno_nascita: 1990,
    luogo_nascita: 'Roma',
    tipo_utente: 'standard'
}).done(function(response) {
    if (response.success) {
        window.location.href = response.redirect;
    }
}).fail(function(xhr) {
    alert('Errore: ' + xhr.responseJSON.message);
});
```

### Logout

```javascript
$.post('backend/auth_api.php', {
    action: 'logout'
}).done(function() {
    window.location.href = 'index.html';
});
```

### Verifica Autenticazione

```javascript
$.post('backend/auth_api.php', {
    action: 'check_auth'
}).done(function(response) {
    if (response.authenticated) {
        console.log('Utente autenticato:', response.user);
    } else {
        console.log('Utente non autenticato');
    }
});
```

## Migrazione dal Vecchio Sistema

Per migrare dal vecchio sistema di autenticazione:

1. Sostituisci le inclusioni di `backend/auth.php` o `database/auth.php` con `auth.php`
2. Aggiorna le chiamate API frontend per utilizzare `backend/auth_api.php` invece di `backend/auth.php`
3. I nomi delle funzioni sono stati mantenuti compatibili, quindi non dovrebbero essere necessarie altre modifiche

## Sicurezza

Il sistema implementa diverse misure di sicurezza:

- Hashing delle password con `password_hash()` e `PASSWORD_DEFAULT`
- Protezione contro attacchi CSRF tramite token di sessione
- Validazione degli input
- Gestione sicura dei cookie per il login automatico
- Registrazione delle attività di autenticazione