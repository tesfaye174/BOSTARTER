<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-40">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="/" class="flex items-center space-x-2">
                <img src="/frontend/images/logo1.svg" alt="BOSTARTER Logo" class="h-8 w-auto">
                <span class="text-2xl font-bold text-primary dark:text-white">BOSTARTER</span>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="/explore.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    Esplora
                </a>
                <a href="/creatori.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    Creatori
                </a>
                <a href="/come-funziona.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    Come Funziona
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-4">
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="p-2 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    <i class="ri-sun-line dark:hidden"></i>
                    <i class="ri-moon-line hidden dark:block"></i>
                </button>

                <?php if ($isLoggedIn): ?>
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notifications-toggle" class="p-2 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            <i class="ri-notification-3-line"></i>
                            <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center hidden">
                                0
                            </span>
                        </button>
                        <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50">
                            <!-- Notifications will be loaded here -->
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative">
                        <button id="user-menu-toggle" class="flex items-center space-x-2 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            <img src="<?php echo htmlspecialchars($user['avatar'] ?? '/frontend/images/default-avatar.png'); ?>" 
                                 alt="Avatar" 
                                 class="h-8 w-8 rounded-full object-cover">
                            <span class="hidden md:block"><?php echo htmlspecialchars($user['nickname']); ?></span>
                            <i class="ri-arrow-down-s-line"></i>
                        </button>
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50">
                            <a href="/dashboard.php" class="block px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Dashboard
                            </a>
                            <a href="/profilo.php" class="block px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Profilo
                            </a>
                            <a href="/impostazioni.php" class="block px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Impostazioni
                            </a>
                            <hr class="my-2 border-gray-200 dark:border-gray-700">
                            <a href="/auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Esci
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login/Register Buttons -->
                    <div class="hidden md:flex items-center space-x-4">
                        <button onclick="openLoginModal()" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Accedi
                        </button>
                        <a href="/auth/register.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors duration-300">
                            Registrati
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    <i class="ri-menu-line"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden mt-4 py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col space-y-4">
                <a href="/explore.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    Esplora
                </a>
                <a href="/creatori.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    Creatori
                </a>
                <a href="/come-funziona.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    Come Funziona
                </a>
                <?php if (!$isLoggedIn): ?>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <button onclick="openLoginModal()" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300 text-left">
                        Accedi
                    </button>
                    <a href="/auth/register.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors duration-300 text-center">
                        Registrati
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<script>
// Theme Toggle
const themeToggle = document.getElementById('theme-toggle');
const html = document.documentElement;

// Check for saved theme preference
if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    html.classList.add('dark');
} else {
    html.classList.remove('dark');
}

themeToggle.addEventListener('click', () => {
    html.classList.toggle('dark');
    localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
});

// Mobile Menu Toggle
const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
const mobileMenu = document.getElementById('mobile-menu');

mobileMenuToggle.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
});

// User Menu Toggle
const userMenuToggle = document.getElementById('user-menu-toggle');
const userMenu = document.getElementById('user-menu');

if (userMenuToggle && userMenu) {
    userMenuToggle.addEventListener('click', () => {
        userMenu.classList.toggle('hidden');
    });
}

// Notifications Toggle
const notificationsToggle = document.getElementById('notifications-toggle');
const notificationsDropdown = document.getElementById('notifications-dropdown');

if (notificationsToggle && notificationsDropdown) {
    notificationsToggle.addEventListener('click', () => {
        notificationsDropdown.classList.toggle('hidden');
        // Qui puoi aggiungere la logica per caricare le notifiche
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (userMenu && !userMenuToggle.contains(e.target)) {
        userMenu.classList.add('hidden');
    }
    if (notificationsDropdown && !notificationsToggle.contains(e.target)) {
        notificationsDropdown.classList.add('hidden');
    }
});
</script> 