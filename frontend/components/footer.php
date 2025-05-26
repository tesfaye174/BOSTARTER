<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand Section -->
            <div class="col-span-1 md:col-span-1">
                <a href="/" class="flex items-center space-x-2 mb-4">
                    <img src="/frontend/images/logo1.svg" alt="BOSTARTER Logo" class="h-8 w-auto">
                    <span class="text-2xl font-bold text-primary dark:text-white">BOSTARTER</span>
                </a>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    La piattaforma leader per il crowdfunding di progetti creativi in Italia.
                </p>
                <div class="flex space-x-4">
                    <a href="https://facebook.com/bostarter" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                        <i class="ri-facebook-fill text-xl"></i>
                    </a>
                    <a href="https://twitter.com/bostarter" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                        <i class="ri-twitter-fill text-xl"></i>
                    </a>
                    <a href="https://instagram.com/bostarter" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                        <i class="ri-instagram-fill text-xl"></i>
                    </a>
                    <a href="https://linkedin.com/company/bostarter" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                        <i class="ri-linkedin-fill text-xl"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Link Rapidi</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="/explore.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Esplora Progetti
                        </a>
                    </li>
                    <li>
                        <a href="/creatori.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Scopri Creatori
                        </a>
                    </li>
                    <li>
                        <a href="/come-funziona.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Come Funziona
                        </a>
                    </li>
                    <li>
                        <a href="/blog.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Blog
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Supporto</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="/faq.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            FAQ
                        </a>
                    </li>
                    <li>
                        <a href="/contatti.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Contatti
                        </a>
                    </li>
                    <li>
                        <a href="/privacy.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Privacy Policy
                        </a>
                    </li>
                    <li>
                        <a href="/termini.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                            Termini di Servizio
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Newsletter</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    Iscriviti per ricevere aggiornamenti sui nuovi progetti e iniziative.
                </p>
                <form id="newsletter-form" class="space-y-2">
                    <div class="flex">
                        <input type="email" 
                               placeholder="La tua email" 
                               class="flex-1 px-4 py-2 rounded-l-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                               required>
                        <button type="submit" 
                                class="bg-primary text-white px-4 py-2 rounded-r-lg hover:bg-primary-dark transition-colors duration-300">
                            <i class="ri-send-plane-fill"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-200 dark:border-gray-700 mt-12 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    &copy; <?php echo date('Y'); ?> BOSTARTER. Tutti i diritti riservati.
                </p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="/cookie-policy.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300 text-sm">
                        Cookie Policy
                    </a>
                    <a href="/mappa-sito.php" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300 text-sm">
                        Mappa del Sito
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
// Newsletter Form
const newsletterForm = document.getElementById('newsletter-form');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = e.target.querySelector('input[type="email"]').value;
        
        try {
            const response = await fetch('/api/newsletter/subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Iscrizione completata con successo!', 'success');
                e.target.reset();
            } else {
                showNotification(data.message || 'Errore durante l\'iscrizione', 'error');
            }
        } catch (error) {
            showNotification('Errore di connessione', 'error');
        }
    });
}
</script> 