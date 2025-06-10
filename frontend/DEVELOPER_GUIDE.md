# BOSTARTER Frontend - Guida per Sviluppatori

## 🚀 Come Utilizzare il Nuovo Sistema di Design

### Struttura CSS Modulare

```css
/* Ordine di inclusione raccomandato */
1. design-system.css  /* Variabili e utilities */
2. components.css     /* Componenti UI */
3. critical.css       /* Stili critici */
4. main.css          /* Layout specifici */
5. page-specific.css  /* CSS specifici per pagina */
```

### Variabili CSS Disponibili

#### Colori

```css
/* Colori principali */
--primary-50 to --primary-950
--secondary-50 to --secondary-950
--gray-50 to --gray-950

/* Colori semantici */
--success-500, --danger-500, --warning-500, --info-500

/* Utilizzo */
.mio-elemento {
    background: var(--primary-600);
    color: var(--white);
}
```

#### Spaziature

```css
/* Scale spaziature */
--space-xs: 0.25rem;   /* 4px */
--space-sm: 0.5rem;    /* 8px */
--space-md: 1rem;      /* 16px */
--space-lg: 1.5rem;    /* 24px */
--space-xl: 2rem;      /* 32px */
--space-2xl: 3rem;     /* 48px */
--space-3xl: 4rem;     /* 64px */
--space-4xl: 6rem;     /* 96px */

/* Utilizzo */
.container {
    padding: var(--space-lg);
    margin-bottom: var(--space-2xl);
}
```

### Classi Utility Disponibili

#### Layout e Flexbox

```html
<!-- Container responsive -->
<div class="container">...</div>

<!-- Flex utilities -->
<div class="flex items-center justify-between">...</div>
<div class="flex-col gap-md">...</div>

<!-- Grid system -->
<div class="grid grid-cols-3 gap-lg">...</div>
```

#### Spaziature

```html
<!-- Margin -->
<div class="m-lg">...</div>      <!-- margin: var(--space-lg) -->
<div class="mt-xl mb-md">...</div> <!-- margin-top: xl, margin-bottom: md -->

<!-- Padding -->
<div class="p-md">...</div>       <!-- padding: var(--space-md) -->
<div class="px-lg py-sm">...</div> <!-- padding x: lg, padding y: sm -->
```

#### Tipografia

```html
<!-- Font sizes -->
<h1 class="text-4xl font-bold">...</h1>
<p class="text-lg text-gray-600">...</p>

<!-- Font weights -->
<span class="font-light">...</span>  <!-- 300 -->
<span class="font-medium">...</span> <!-- 500 -->
<span class="font-bold">...</span>   <!-- 700 -->
```

### Componenti Pronti all'Uso

#### Bottoni

```html
<!-- Bottoni base -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-outline">Outline</button>

<!-- Sizes -->
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary btn-lg">Large</button>

<!-- Stati -->
<button class="btn btn-primary" disabled>Disabled</button>
<button class="btn btn-primary loading">Loading</button>
```

#### Cards

```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Titolo Card</h3>
    </div>
    <div class="card-content">
        <p>Contenuto della card...</p>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary">Azione</button>
    </div>
</div>
```

#### Form Elements

```html
<!-- Input group -->
<div class="form-group">
    <label class="form-label">Email</label>
    <input type="email" class="form-input" placeholder="tua@email.com">
    <span class="form-error">Messaggio di errore</span>
</div>

<!-- Select -->
<select class="form-select">
    <option>Scegli opzione</option>
</select>

<!-- Stati di validazione -->
<input class="form-input is-valid">
<input class="form-input is-error">
```

#### Alert e Notifiche

```html
<!-- Alert statiche -->
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    Operazione completata con successo!
</div>

<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    Si è verificato un errore.
</div>
```

```javascript
// Notifiche dinamiche
window.boNotifications.success('Operazione completata!');
window.boNotifications.error('Errore durante l\'operazione');
window.boNotifications.warning('Attenzione: verifica i dati');
window.boNotifications.info('Informazione importante');
```

### JavaScript API

#### Navigazione

```javascript
// Accesso all'istanza di navigazione
const nav = window.boNavigation;

// Controllo menu mobile
nav.openMobileMenu();
nav.closeMobileMenu();

// Controllo user menu
nav.openUserMenu();
nav.closeUserMenu();
```

#### Notifiche

```javascript
// Sistema notifiche
const notifications = window.boNotifications;

// Notifica base
notifications.show('Messaggio', 'tipo', durata);

// Notifiche tipizzate
notifications.success('Successo!', 5000);
notifications.error('Errore!', 0); // Non si chiude automaticamente
notifications.warning('Attenzione!', 7000);
notifications.info('Info', 3000);

// Rimozione manuale
const notification = notifications.success('Test');
notifications.remove(notification);
```

### Best Practices

#### CSS

1. **Usa sempre le variabili CSS** invece di valori hardcoded
2. **Preferisci le classi utility** per spacing e layout base
3. **Crea componenti riutilizzabili** per elementi complessi
4. **Mantieni la specificità CSS bassa** per facilità di override

#### HTML

1. **Usa markup semantico** (header, nav, main, section, article)
2. **Includi ARIA labels** per accessibilità
3. **Testa la navigazione da tastiera**
4. **Verifica il contrasto colori**

#### JavaScript

1. **Usa event delegation** per performance migliori
2. **Gestisci sempre gli stati di errore**
3. **Implementa debouncing** per eventi scroll/resize
4. **Testa su dispositivi reali**

### Responsive Design

#### Breakpoints

```css
/* Mobile first approach */
.elemento {
    /* Stili mobile (default) */
}

@media (min-width: 640px) {
    /* Tablet */
}

@media (min-width: 768px) {
    /* Desktop small */
}

@media (min-width: 1024px) {
    /* Desktop */
}

@media (min-width: 1280px) {
    /* Desktop large */
}
```

#### Utility Responsive

```html
<!-- Visibilità condizionale -->
<div class="hidden md:block">Visibile solo da tablet in su</div>
<div class="block md:hidden">Visibile solo su mobile</div>

<!-- Layout responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
    <!-- Si adatta automaticamente -->
</div>
```

### Performance Tips

1. **CSS Critico**: Mantieni critical.css sotto i 14KB
2. **Lazy Loading**: Carica componenti non critici in modo differito
3. **Minificazione**: Usa strumenti per minificare CSS/JS in produzione
4. **Caching**: Implementa cache headers appropriati

### Debugging

#### CSS

```css
/* Debug layout */
* { outline: 1px solid red; }

/* Debug responsive */
body::before {
    content: 'Mobile';
    position: fixed;
    top: 0;
    left: 0;
    background: red;
    color: white;
    padding: 4px;
    z-index: 9999;
}

@media (min-width: 768px) {
    body::before { content: 'Tablet'; }
}

@media (min-width: 1024px) {
    body::before { content: 'Desktop'; }
}
```

#### JavaScript

```javascript
// Debug navigazione
console.log('Navigation instance:', window.boNavigation);
console.log('Notifications instance:', window.boNotifications);

// Monitor eventi
document.addEventListener('click', (e) => {
    console.log('Clicked:', e.target);
});
```

---

## 📝 Checklist per Nuove Pagine

- [ ] Inclusi tutti i CSS nell'ordine corretto
- [ ] JavaScript navigation.js incluso
- [ ] Skip links implementati
- [ ] ARIA labels aggiunti
- [ ] Testato su mobile
- [ ] Verificato contrasto colori
- [ ] Performance check con Lighthouse
- [ ] Cross-browser testing

---

**Aggiornato**: Dicembre 2024  
**Versione**: 2.0  
**Autore**: Team BOSTARTER Frontend
