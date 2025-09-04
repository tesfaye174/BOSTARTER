# Frontend Layout Guidelines

## General Structure

```
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <!-- Top navigation bar -->
</nav>

<div class="container mt-4">
    <main>
        <!-- Page content -->
    </main>
</div>

<footer class="footer mt-auto py-3 bg-light">
    <!-- Footer content -->
</footer>
```

## Required Pages

1. Home Page (`home.php`)
   - Hero section con slider dei progetti in evidenza
   - Griglia dei progetti più recenti
   - Sezione statistiche (top creators, progetti vicini al goal)
   - Call-to-action per creators

2. Project Page (`view.php`)
   - Immagine principale
   - Barra di progresso funding
   - Tab per:
     - Descrizione
     - Rewards
     - Updates
     - Comments
     - Team/Applications
   - Sidebar con:
     - Stato progetto
     - Budget/raccolto
     - Tempo rimanente
     - Pulsante "Finanzia"

3. Creator Dashboard (`dash.php`)
   - Statistiche personali
   - Lista progetti gestiti
   - Gestione candidature
   - Form nuovo progetto

4. User Profile
   - Info personali
   - Skills e livelli
   - Progetti finanziati
   - Candidature inviate

## Bootstrap Components da Utilizzare

1. Navigation

   ```html
   <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
     <div class="container-fluid">
       <a class="navbar-brand" href="#">BOSTARTER</a>
       <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
         <span class="navbar-toggler-icon"></span>
       </button>
       <div class="collapse navbar-collapse" id="navbarNav">
         <!-- Menu items -->
       </div>
     </div>
   </nav>
   ```

2. Cards per Progetti

   ```html
   <div class="card">
     <img src="project-image.jpg" class="card-img-top" alt="Project Image">
     <div class="card-body">
       <h5 class="card-title">Titolo Progetto</h5>
       <div class="progress mb-3">
         <div class="progress-bar" role="progressbar" style="width: 75%"></div>
       </div>
       <p class="card-text">Breve descrizione del progetto...</p>
       <a href="#" class="btn btn-primary">Dettagli</a>
     </div>
   </div>
   ```

3. Forms

   ```html
   <form class="needs-validation" novalidate>
     <div class="mb-3">
       <label for="title" class="form-label">Titolo</label>
       <input type="text" class="form-control" id="title" required>
     </div>
     <!-- Other fields -->
     <button type="submit" class="btn btn-primary">Salva</button>
   </form>
   ```

4. Tabbed Content

   ```html
   <ul class="nav nav-tabs" id="projectTabs" role="tablist">
     <li class="nav-item">
       <a class="nav-link active" data-bs-toggle="tab" href="#description">Descrizione</a>
     </li>
     <!-- Other tabs -->
   </ul>
   ```

## CSS Customizzazioni

```css
:root {
  --bs-primary: #0066cc;
  --bs-secondary: #6c757d;
  --bs-success: #28a745;
}

.project-card {
  transition: transform 0.2s;
}

.project-card:hover {
  transform: translateY(-5px);
}

.progress-bar {
  background-color: var(--bs-success);
}

.funding-sidebar {
  position: sticky;
  top: 20px;
}
```

## JavaScript Moduli

1. `core.js` - Funzioni utility
2. `api.js` - Wrapper per chiamate API
3. `auth.js` - Gestione autenticazione
4. Per ogni pagina:
   - `home.js`
   - `view.js`
   - `dash.js`
   etc.

## Best Practices

1. Responsive Design
   - Utilizzare le classi grid di Bootstrap
   - Media queries per casi specifici
   - Immagini responsive

2. Performance
   - Lazy loading per immagini
   - Minificazione CSS/JS
   - Caching lato client

3. Accessibilità
   - ARIA labels
   - Focus styles
   - Alt text per immagini

4. UX
   - Loading states
   - Error handling
   - Feedback utente
   - Conferme per azioni importanti
