/**
 * BOSTARTER - Script per la homepage
 * Gestisce il caricamento dei dati per la homepage, incluse statistiche e progetti
 */
+// Import necessary modules
+import { API } from './api.js';
+import { Auth, ErrorHandler } from './auth.js'; // Assuming ErrorHandler is exported from auth.js
+// import { CachedAPI } from './cache.js'; // Import if using cached data
 
 document.addEventListener('DOMContentLoaded', async function() {
     // Verifica se l'utente è già autenticato
     try {
         await Auth.checkAuth();
     } catch (error) {
         console.error('Errore verifica autenticazione:', error);
     }
     
     // Aggiorna UI in base allo stato di autenticazione
     updateAuthUI();
     
     // Carica tutte le sezioni della homepage
     loadHomepageData();
 });
 
 /**
 * Aggiorna l'interfaccia utente in base allo stato di autenticazione
 */
 function updateAuthUI() {
     const user = Auth.getUser();
     const isAuth = Auth.isAuthenticated();
     
     // Elementi del menu
     const loginMenu = document.getElementById('login-menu');
     const registerMenu = document.getElementById('register-menu');
     const profileMenu = document.getElementById('profile-menu');
     const logoutMenu = document.getElementById('logout-menu');
     const creatorMenu = document.getElementById('creator-menu');
     const adminMenu = document.getElementById('admin-menu');
     
     if (isAuth && user) {
         // Nascondi elementi per utenti non autenticati
         if (loginMenu) loginMenu.style.display = 'none';
         if (registerMenu) registerMenu.style.display = 'none';
         
         // Mostra elementi per utenti autenticati
         if (profileMenu) profileMenu.style.display = 'block';
         if (logoutMenu) logoutMenu.style.display = 'block';
         
         // Mostra menu creator se l'utente è un creatore
         if (user.role === 'creator' || user.role === 'admin') {
             if (creatorMenu) creatorMenu.style.display = 'block';
         }
         
         // Mostra menu admin se l'utente è un amministratore
         if (user.role === 'admin') {
             if (adminMenu) adminMenu.style.display = 'block';
         }
     } else {
         // Mostra elementi per utenti non autenticati
         if (loginMenu) loginMenu.style.display = 'block';
         if (registerMenu) registerMenu.style.display = 'block';
         
         // Nascondi elementi per utenti autenticati
         if (profileMenu) profileMenu.style.display = 'none';
         if (logoutMenu) logoutMenu.style.display = 'none';
         if (creatorMenu) creatorMenu.style.display = 'none';
         if (adminMenu) adminMenu.style.display = 'none';
     }
 }
 
 /**
 * Carica tutti i dati necessari per la homepage
 */
 async function loadHomepageData() {
     const loadingPromises = [
-        loadWithRetry(loadTopCreators, 3),
-        loadWithRetry(loadNearCompletionProjects, 3),
-        loadWithRetry(loadTopFunders, 3),
-        loadWithRetry(loadFeaturedProjects, 3),
-        loadWithRetry(loadRecentProjects, 3)
+        // Use direct calls or CachedAPI if preferred
+        // Removing retry logic for simplicity, can be added back if needed
+        loadTopCreators(),
+        loadNearCompletionProjects(),
+        loadTopFunders(),
+        loadFeaturedProjects(),
+        loadRecentProjects()
     ];
     
     try {
         // Carica in parallelo per ottimizzare i tempi di caricamento
         await Promise.allSettled(loadingPromises);
     } catch (error) {
         console.error('Errore nel caricamento dei dati della homepage:', error);
         showErrorMessage('Si è verificato un errore nel caricamento dei dati. Riprova più tardi.');
     }
 }
 
-/**
- * Funzione helper per caricare con retry
- */
-async function loadWithRetry(loadFunction, maxRetries) {
-    let retries = 0;
-    
-    while (retries < maxRetries) {
-        try {
-            return await loadFunction();
-        } catch (error) {
-            retries++;
-            console.warn(`Tentativo ${retries}/${maxRetries} fallito per ${loadFunction.name}:`, error);
-            
-            if (retries >= maxRetries) {
-                throw error;
-            }
-            
-            // Attesa esponenziale tra tentativi
-            await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, retries - 1)));
-        }
-    }
-}
-
-/**
- * Mostra un messaggio di errore nella pagina
- */
-function showErrorMessage(message) {
-    const alertDiv = document.createElement('div');
-    alertDiv.className = 'alert alert-danger mt-3 animate__animated animate__fadeIn';
-    alertDiv.role = 'alert';
-    alertDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${message}`;
-    
-    // Inserisci l'alert all'inizio della pagina
-    const main = document.querySelector('main');
-    main.insertBefore(alertDiv, main.firstChild);
-    
-    // Rimuovi dopo 5 secondi
-    setTimeout(() => {
-        alertDiv.classList.replace('animate__fadeIn', 'animate__fadeOut');
-        setTimeout(() => alertDiv.remove(), 500);
-    }, 5000);
-}
+// Removed loadWithRetry helper function
+// Removed custom showErrorMessage, use ErrorHandler.showError
+
+// Helper to render empty state
+function renderEmptyState(container, message, iconClass = 'bi-info-circle') {
+    if (container) {
+        container.innerHTML = `<div class="text-center text-muted p-4"><i class="bi ${iconClass} fs-3 d-block mb-2"></i>${message}</div>`;
+    }
+}
+
+// Helper to render error state
+function renderErrorState(container, message) {
+    if (container) {
+        // Use ErrorHandler to display the error message in a designated area if possible,
+        // or render directly into the container.
+        container.innerHTML = `<div class="text-center text-danger p-4"><i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2"></i>${message}</div>`;
+        // Optionally, use ErrorHandler.showError('Error message', 'specific-error-element-id');
+    }
+}
 
 /**
 * Carica i top creator
 */
 async function loadTopCreators() {
     try {
         const container = document.getElementById('top-creators');
         if (!container) return;
         
         const data = await API.getTopCreators();
         
         if (!data || !data.creators || data.creators.length === 0) {
-            container.innerHTML = createEmptyState('Nessun creator trovato', 'bi-people');
+            renderEmptyState(container, 'Nessun creator trovato', 'bi-people');
             return;
         }
         
         let html = '';
         data.creators.forEach((creator, index) => {
             html += `
                 <div class="d-flex align-items-center mb-2 ${index === 0 ? 'top-creator' : ''}">
                     <div class="position-relative me-3">
                         <img src="${creator.avatar || 'img/avatar-placeholder.png'}" 
                              alt="${creator.nickname}" 
                              class="rounded-circle" 
                              width="40" height="40">
                         ${index === 0 ? '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark"><i class="bi bi-trophy-fill"></i></span>' : ''}
                     </div>
                     <div>
                         <h6 class="mb-0">${creator.nickname}</h6>
                         <small class="text-muted">Affidabilità: ${creator.reliability}%</small>
                     </div>
                 </div>`;
         });
         
         container.innerHTML = html;
     } catch (error) {
         console.error('Errore nel caricamento dei top creator:', error);
         const container = document.getElementById('top-creators');
-        if (container) {
-            container.innerHTML = createErrorState('Impossibile caricare i top creator');
-        }
-        throw error; // Rilancia l'errore per la gestione dei retry
+        renderErrorState(container, 'Impossibile caricare i top creator');
+        // ErrorHandler.showError('Impossibile caricare i top creator', 'global-alerts'); // Show in global area
     }
 }
 
 
 /**
 * Carica i progetti vicini al completamento
 */
 async function loadNearCompletionProjects() {
     try {
         const container = document.getElementById('near-completion');
         if (!container) return;
         
         const data = await API.getNearCompletionProjects();
         
         if (!data || !data.projects || data.projects.length === 0) {
-            container.innerHTML = createEmptyState('Nessun progetto trovato', 'bi-clipboard-x');
+            renderEmptyState(container, 'Nessun progetto vicino al completamento', 'bi-clipboard-x');
             return;
         }
         
         let html = '';
         data.projects.forEach(project => {
             const percentage = Math.round((project.current_amount / project.target_amount) * 100);
             html += `
                 <div class="mb-3">
                     <div class="d-flex justify-content-between">
                         <h6 class="mb-1">${project.name}</h6>
                         <span class="badge bg-primary">${percentage}%</span>
                     </div>
                     <div class="progress" style="height: 8px;">
                         <div class="progress-bar" role="progressbar" style="width: ${percentage}%" 
                              aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                     </div>
                     <div class="d-flex justify-content-between mt-1">
                         <small>${formatCurrency(project.current_amount)}</small>
                         <small>su ${formatCurrency(project.target_amount)}</small>
                     </div>
                 </div>`;
         });
         
         container.innerHTML = html;
     } catch (error) {
         console.error('Errore nel caricamento dei progetti vicini al completamento:', error);
         const container = document.getElementById('near-completion');
-        if (container) {
-            container.innerHTML = createErrorState('Impossibile caricare i progetti');
-        }
-        throw error;
+        renderErrorState(container, 'Impossibile caricare i progetti vicini al completamento');
+        // ErrorHandler.showError('Impossibile caricare i progetti vicini al completamento', 'global-alerts');
     }
 }
 
 
 /**
 * Carica i top finanziatori
 */
 async function loadTopFunders() {
     try {
         const container = document.getElementById('top-funders');
         if (!container) return;
         
         const data = await API.getTopFunders();
         
         if (!data || !data.funders || data.funders.length === 0) {
-            container.innerHTML = createEmptyState('Nessun finanziatore trovato', 'bi-person-badge');
+            renderEmptyState(container, 'Nessun finanziatore trovato', 'bi-person-badge');
             return;
         }
         
         let html = '';
         data.funders.forEach((funder) => {
             html += `
                 <div class="d-flex align-items-center mb-2">
                     <div class="me-3">
                         <img src="${funder.avatar || 'img/avatar-placeholder.png'}" 
                              alt="${funder.nickname}" 
                              class="rounded-circle" 
                              width="40" height="40">
                     </div>
                     <div>
                         <h6 class="mb-0">${funder.nickname}</h6>
                         <small>${formatCurrency(funder.total_funding)}</small>
                     </div>
                 </div>`;
         });
         
         container.innerHTML = html;
     } catch (error) {
         console.error('Errore nel caricamento dei top finanziatori:', error);
         const container = document.getElementById('top-funders');
-        if (container) {
-            container.innerHTML = createErrorState('Impossibile caricare i finanziatori');
-        }
-        throw error;
+        renderErrorState(container, 'Impossibile caricare i top finanziatori');
+        // ErrorHandler.showError('Impossibile caricare i top finanziatori', 'global-alerts');
     }
 }
 
 
 /**
 * Carica i progetti in evidenza
 */
 async function loadFeaturedProjects() {
     try {
         const container = document.getElementById('featured-projects');
         if (!container) return;
         
         const data = await API.getProjects(1, 3, { featured: true });
         
         if (!data || !data.projects || data.projects.length === 0) {
-            container.innerHTML = createEmptyState('Nessun progetto in evidenza', 'bi-star');
+            renderEmptyState(container, 'Nessun progetto in evidenza', 'bi-star');
             return;
         }
         
         let html = '';
         data.projects.forEach(project => {
             html += createProjectCard(project);
         });
         
         container.innerHTML = html;
     } catch (error) {
         console.error('Errore nel caricamento dei progetti in evidenza:', error);
         const container = document.getElementById('featured-projects');
-        if (container) {
-            container.innerHTML = createErrorState('Impossibile caricare i progetti');
-        }
-        throw error;
+        renderErrorState(container, 'Impossibile caricare i progetti in evidenza');
+        // ErrorHandler.showError('Impossibile caricare i progetti in evidenza', 'global-alerts');
     }
 }
 
 
 /**
 * Carica i progetti recenti
 */
 async function loadRecentProjects() {
     try {
         const container = document.getElementById('recent-projects');
         if (!container) return;
         
         const data = await API.getProjects(1, 4);
         
         if (!data || !data.projects || data.projects.length === 0) {
-            container.innerHTML = createEmptyState('Nessun progetto recente', 'bi-clipboard-x');
+            renderEmptyState(container, 'Nessun progetto recente', 'bi-clipboard-x');
             return;
         }
         
         let html = '';
         data.projects.forEach(project => {
             html += createProjectCard(project);
         });
         
         container.innerHTML = html;
     } catch (error) {
         console.error('Errore nel caricamento dei progetti recenti:', error);
         const container = document.getElementById('recent-projects');
-        if (container) {
-            container.innerHTML = createErrorState('Impossibile caricare i progetti');
-        }
-        throw error;
+        renderErrorState(container, 'Impossibile caricare i progetti recenti');
+        // ErrorHandler.showError('Impossibile caricare i progetti recenti', 'global-alerts');
     }
 }
 
-/**
- * Crea HTML per stato vuoto
- */
-function createEmptyState(message, iconClass) {
-    return `<div class="text-center text-muted p-4"><i class="bi ${iconClass} fs-3 d-block mb-2"></i>${message}</div>`;
-}
-
-/**
- * Crea HTML per stato di errore
- */
-function createErrorState(message) {
-    return `<div class="text-center text-danger p-4"><i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2"></i>${message}</div>`;
-}
+// Removed createEmptyState and createErrorState helpers
+
+// Add event listener for logout
+const logoutLink = document.getElementById('logout-menu')?.querySelector('a');
+if (logoutLink) {
+    logoutLink.addEventListener('click', (event) => {
+        event.preventDefault();
+        Auth.logout();
+    });
+}
+// Ensure the global logout function is not needed if the onclick is removed from HTML
+// If onclick="logout(event)" remains, this function needs to be global:
+// window.logout = function(event) {
+//     event.preventDefault();
+//     Auth.logout();
+// }
 
 /**
 * Crea un elemento HTML per stato vuoto
 */
 function createEmptyState(message, icon = 'bi-exclamation-circle') {
     return `
         <div class="empty-state">
             <i class="bi ${icon}"></i>
             <h4>Nessun dato disponibile</h4>
             <p>${message}</p>
         </div>
     `;
 }
 
 /**
 * Crea un elemento HTML per stato di errore
 */
 function createErrorState(message) {
     return createEmptyState(message, 'bi-exclamation-triangle-fill');
 }
 
 /**
 * Calcola i giorni rimanenti
 */
 function getDaysLeft(endDate) {
     const end = new Date(endDate);
     const now = new Date();
     const diff = end - now;
     return Math.ceil(diff / (1000 * 60 * 60 * 24));
 }
 
 /**
 * Formatta un importo come valuta
 */
 function formatCurrency(amount) {
     return new Intl.NumberFormat('it-IT', {
         style: 'currency',
         currency: 'EUR'
     }).format(amount);
 }
 
 /**
 * Gestisce il logout
 */
 function logout(event) {
     event.preventDefault();
     Auth.logout();
 }
