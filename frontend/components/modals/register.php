<div id="register-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="min-h-screen px-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <!-- Modal Panel -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="absolute top-0 right-0 pt-4 pr-4">
                <button type="button" onclick="closeRegisterModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>

            <div class="px-6 py-8">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Unisciti a BOSTARTER</h3>
                    <p class="text-gray-600 dark:text-gray-300">Crea il tuo account per iniziare</p>
                </div>

                <form id="register-form" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nome
                            </label>
                            <input type="text" 
                                   id="nome" 
                                   name="nome" 
                                   required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                                   placeholder="Il tuo nome">
                        </div>

                        <div>
                            <label for="cognome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Cognome
                            </label>
                            <input type="text" 
                                   id="cognome" 
                                   name="cognome" 
                                   required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                                   placeholder="Il tuo cognome">
                        </div>
                    </div>

                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nickname
                        </label>
                        <input type="text" 
                               id="nickname" 
                               name="nickname" 
                               required 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                               placeholder="Il tuo nickname">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Questo sarà il tuo nome utente visibile sulla piattaforma
                        </p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                               placeholder="La tua email">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                                   placeholder="La tua password">
                            <button type="button" 
                                    onclick="togglePasswordVisibility('password')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-500">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Minimo 8 caratteri, una lettera maiuscola, un numero e un carattere speciale
                        </p>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Conferma Password
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                                   placeholder="Conferma la password">
                            <button type="button" 
                                    onclick="togglePasswordVisibility('password_confirm')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-500">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="terms" 
                               name="terms" 
                               required
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Accetto i <a href="/termini.php" class="text-primary hover:text-primary-dark">Termini di Servizio</a> e la 
                            <a href="/privacy.php" class="text-primary hover:text-primary-dark">Privacy Policy</a>
                        </label>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors duration-300 flex items-center justify-center">
                            <span>Registrati</span>
                            <i class="ri-user-add-line ml-2"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-800 text-gray-500">
                                Oppure registrati con
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" 
                                onclick="registerWithGoogle()"
                                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <i class="ri-google-fill text-xl mr-2"></i>
                            Google
                        </button>
                        <button type="button" 
                                onclick="registerWithFacebook()"
                                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <i class="ri-facebook-fill text-xl mr-2"></i>
                            Facebook
                        </button>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Hai già un account? 
                        <button onclick="switchToLogin()" class="text-primary hover:text-primary-dark font-medium">
                            Accedi
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funzioni per il modale di registrazione
function openRegisterModal() {
    document.getElementById('register-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRegisterModal() {
    document.getElementById('register-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function switchToLogin() {
    closeRegisterModal();
    openLoginModal();
}

// Validazione password
function validatePassword(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    return password.length >= minLength && 
           hasUpperCase && 
           hasLowerCase && 
           hasNumbers && 
           hasSpecialChar;
}

// Gestione form di registrazione
const registerForm = document.getElementById('register-form');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = {
            nome: formData.get('nome'),
            cognome: formData.get('cognome'),
            nickname: formData.get('nickname'),
            email: formData.get('email'),
            password: formData.get('password'),
            password_confirm: formData.get('password_confirm'),
            terms: formData.get('terms') === 'on'
        };
        
        // Validazione password
        if (!validatePassword(data.password)) {
            showNotification('La password non soddisfa i requisiti di sicurezza', 'error');
            return;
        }
        
        // Verifica corrispondenza password
        if (data.password !== data.password_confirm) {
            showNotification('Le password non coincidono', 'error');
            return;
        }
        
        try {
            const response = await fetch('/api/auth/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Registrazione completata con successo!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(result.message || 'Errore durante la registrazione', 'error');
            }
        } catch (error) {
            showNotification('Errore di connessione', 'error');
        }
    });
}

// Registrazione con social
function registerWithGoogle() {
    window.location.href = '/api/auth/google.php?action=register';
}

function registerWithFacebook() {
    window.location.href = '/api/auth/facebook.php?action=register';
}

// Chiudi il modale quando si clicca fuori
document.getElementById('register-modal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        closeRegisterModal();
    }
});
</script> 