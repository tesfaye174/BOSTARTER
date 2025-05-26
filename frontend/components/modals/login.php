<div id="login-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="min-h-screen px-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <!-- Modal Panel -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="absolute top-0 right-0 pt-4 pr-4">
                <button type="button" onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>

            <div class="px-6 py-8">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Bentornato!</h3>
                    <p class="text-gray-600 dark:text-gray-300">Accedi per continuare</p>
                </div>

                <form id="login-form" class="space-y-6">
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
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="remember" 
                                   name="remember" 
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Ricordami
                            </label>
                        </div>
                        <a href="/auth/reset-password.php" class="text-sm text-primary hover:text-primary-dark">
                            Password dimenticata?
                        </a>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors duration-300 flex items-center justify-center">
                            <span>Accedi</span>
                            <i class="ri-login-box-line ml-2"></i>
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
                                Oppure continua con
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" 
                                onclick="loginWithGoogle()"
                                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <i class="ri-google-fill text-xl mr-2"></i>
                            Google
                        </button>
                        <button type="button" 
                                onclick="loginWithFacebook()"
                                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <i class="ri-facebook-fill text-xl mr-2"></i>
                            Facebook
                        </button>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Non hai un account? 
                        <a href="/auth/register.php" class="text-primary hover:text-primary-dark font-medium">
                            Registrati
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funzioni per il modale di login
function openLoginModal() {
    document.getElementById('login-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
    document.getElementById('login-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ri-eye-line');
        icon.classList.add('ri-eye-off-line');
    } else {
        input.type = 'password';
        icon.classList.remove('ri-eye-off-line');
        icon.classList.add('ri-eye-line');
    }
}

// Gestione form di login
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = {
            email: formData.get('email'),
            password: formData.get('password'),
            remember: formData.get('remember') === 'on'
        };
        
        try {
            const response = await fetch('/api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Login effettuato con successo!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(result.message || 'Errore durante il login', 'error');
            }
        } catch (error) {
            showNotification('Errore di connessione', 'error');
        }
    });
}

// Login con social
function loginWithGoogle() {
    window.location.href = '/api/auth/google.php';
}

function loginWithFacebook() {
    window.location.href = '/api/auth/facebook.php';
}

// Chiudi il modale quando si clicca fuori
document.getElementById('login-modal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        closeLoginModal();
    }
});
</script> 