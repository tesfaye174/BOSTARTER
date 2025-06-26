<!-- 
    BOSTARTER Homepage Content
    HTML content separated from index.php for better modularity
    Contains all the main sections without PHP logic (only presentation)
-->

<!-- Miglioramenti accessibilità, SEO, performance immagini, focus visibili e micro-interazioni su homepage -->
<!-- Skip Links per Accessibilità -->
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary text-white px-4 py-2 rounded-lg z-50">Salta al contenuto principale</a>

<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500" aria-hidden="true">
    <div class="flex flex-col items-center space-y-4">
        <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        <p class="text-lg font-medium text-gray-600">Caricamento...</p>
    </div>
</div>

<!-- Notifications Container -->
<div id="notifications-container" class="fixed top-4 right-4 z-40 space-y-2 max-w-sm" role="region" aria-live="polite" aria-label="Notifiche"></div>

<!-- Modern Enhanced Header -->
<header class="bg-white/95 backdrop-blur-lg shadow-xl border-b border-gray-100 sticky top-0 z-50 transition-all duration-300" role="banner">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4" role="navigation" aria-label="Navigazione principale">
        <div class="flex items-center justify-between">
            <!-- Enhanced Logo/Brand -->
            <a href="/BOSTARTER/frontend/" class="group flex items-center space-x-3 transition-transform duration-200 hover:scale-105">
                <div class="relative">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-dark rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-rocket text-white text-lg group-hover:animate-bounce" aria-hidden="true"></i>
                    </div>
                    <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                </div>
                <div class="hidden sm:block">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-transparent">
                        BOSTARTER
                    </h1>
                    <p class="text-xs text-gray-500 -mt-1">Crowdfunding Innovativo</p>
                </div>
            </a>

            <!-- Desktop Navigation Enhanced -->
            <div class="hidden lg:flex items-center space-x-1" role="menubar">
                <?php 
                $navItems = [
                    ['url' => 'hardware_projects', 'icon' => 'fas fa-microchip', 'color' => 'from-blue-500 to-cyan-500', 'label' => 'Hardware'],
                    ['url' => 'software_projects', 'icon' => 'fas fa-code', 'color' => 'from-green-500 to-emerald-500', 'label' => 'Software'],
                    ['url' => 'projects', 'icon' => 'fas fa-grid-3x3', 'color' => 'from-purple-500 to-pink-500', 'label' => 'Progetti'],
                    ['url' => 'about', 'icon' => 'fas fa-info-circle', 'color' => 'from-orange-500 to-red-500', 'label' => 'Chi Siamo']
                ];
                
                foreach ($navItems as $item): ?>
                    <a href="<?php echo NavigationHelper::url($item['url']); ?>" 
                       class="group flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-primary/10 transition-all duration-200 text-gray-700 hover:text-primary font-medium" 
                       role="menuitem"
                       aria-label="Vai alla sezione <?php echo htmlspecialchars($item['label']); ?>">
                        <div class="w-6 h-6 bg-gradient-to-br <?php echo $item['color']; ?> rounded-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <i class="<?php echo $item['icon']; ?> text-white text-xs" aria-hidden="true"></i>
                        </div>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- User Actions Enhanced -->
            <div class="flex items-center space-x-3" role="group" aria-label="Azioni utente">
                <!-- Theme Toggle -->
                <button id="theme-toggle" 
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible" 
                        aria-label="Cambia tema">
                    <i class="ri-sun-line dark:hidden text-xl text-gray-600" aria-hidden="true"></i>
                    <i class="ri-moon-line hidden dark:block text-xl text-gray-300" aria-hidden="true"></i>
                </button>

                <!-- Notification Bell (for logged in users) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors focus-visible" 
                        aria-label="Notifiche">
                    <i class="fas fa-bell text-xl text-gray-600" aria-hidden="true"></i>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                </button>
                <?php endif; ?>

                <?php if ($is_logged_in): ?>
                    <!-- Enhanced User Menu -->
                    <div class="user-menu-container-enhanced" id="user-menu-container">
                        <button id="user-menu-button" 
                                class="flex items-center space-x-2 bg-gradient-to-r from-primary to-primary-dark text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all duration-200 group focus-visible"
                                aria-expanded="false" 
                                aria-haspopup="true"
                                aria-label="Menu utente">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=ffffff&color=3176FF&size=32&rounded=true" 
                                 alt="Avatar di <?php echo htmlspecialchars($username); ?>" 
                                 class="w-8 h-8 rounded-full border-2 border-white/50 group-hover:border-white transition-colors">
                            <span class="font-medium"><?php echo $username; ?></span>
                            <i class="fas fa-chevron-down text-sm transition-transform duration-200 group-hover:rotate-180" aria-hidden="true"></i>
                        </button>

                        <div id="user-menu" 
                             class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 opacity-0 invisible transform scale-95 transition-all duration-200 z-50"
                             role="menu"
                             aria-labelledby="user-menu-button">
                            <!-- User Info Header -->
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($username); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['email'] ?? 'Utente BOSTARTER'); ?></p>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-1">
                                <a href="<?php echo NavigationHelper::url('dashboard'); ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors" 
                                   role="menuitem">
                                    <i class="fas fa-tachometer-alt w-5 text-center mr-3 text-primary" aria-hidden="true"></i>
                                    Dashboard
                                </a>
                                <a href="<?php echo NavigationHelper::url('profile'); ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors" 
                                   role="menuitem">
                                    <i class="fas fa-user w-5 text-center mr-3 text-primary" aria-hidden="true"></i>
                                    Il Mio Profilo
                                </a>
                                <a href="<?php echo NavigationHelper::url('my_projects'); ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors" 
                                   role="menuitem">
                                    <i class="fas fa-rocket w-5 text-center mr-3 text-primary" aria-hidden="true"></i>
                                    I Miei Progetti
                                </a>
                                <a href="<?php echo NavigationHelper::url('settings'); ?>" 
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors" 
                                   role="menuitem">
                                    <i class="fas fa-cog w-5 text-center mr-3 text-primary" aria-hidden="true"></i>
                                    Impostazioni
                                </a>
                            </div>

                            <div class="border-t border-gray-100 py-1">
                                <a href="/BOSTARTER/frontend/logout.php" 
                                   class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors" 
                                   role="menuitem">
                                    <i class="fas fa-sign-out-alt w-5 text-center mr-3" aria-hidden="true"></i>
                                    Disconnetti
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest User Actions -->
                    <div class="flex items-center space-x-3">
                        <a href="/BOSTARTER/frontend/auth/login.php" 
                           class="px-4 py-2 text-primary hover:text-primary-dark font-medium transition-colors focus-visible">
                            Accedi
                        </a>
                        <a href="/BOSTARTER/frontend/auth/register.php" 
                           class="bg-gradient-to-r from-primary to-primary-dark text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all duration-200 font-medium focus-visible">
                            Registrati
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" 
                        class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors focus-visible" 
                        aria-expanded="false" 
                        aria-controls="mobile-menu"
                        aria-label="Apri menu navigazione" class="... focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/50 ...">
                    <span class="block w-6 h-0.5 bg-gray-600 transition-all duration-300"></span>
                    <span class="block w-6 h-0.5 bg-gray-600 mt-1.5 transition-all duration-300"></span>
                    <span class="block w-6 h-0.5 bg-gray-600 mt-1.5 transition-all duration-300"></span>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" 
             class="lg:hidden overflow-hidden transition-all duration-300 max-h-0" 
             role="navigation" 
             aria-label="Menu mobile">
            <div class="px-4 py-4 border-t border-gray-100 mt-4 space-y-2">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo NavigationHelper::url($item['url']); ?>" 
                       class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-primary/10 transition-colors text-gray-700 hover:text-primary">
                        <div class="w-8 h-8 bg-gradient-to-br <?php echo $item['color']; ?> rounded-lg flex items-center justify-center">
                            <i class="<?php echo $item['icon']; ?> text-white text-sm" aria-hidden="true"></i>
                        </div>
                        <span class="font-medium"><?php echo $item['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>
</header>

<!-- Main Content -->
<main id="main-content" role="main">
    <!-- Enhanced Hero Section -->
    <section class="hero-section bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-800 text-white py-20 lg:py-32 relative overflow-hidden" 
             role="banner" 
             aria-labelledby="hero-title">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-white/10 to-purple-300/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-tr from-blue-300/20 to-white/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-gradient-to-r from-purple-400/20 to-pink-400/20 rounded-full blur-2xl animate-spin animate-slow"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <h1 id="hero-title" class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent leading-tight">
                    Trasforma le tue <span class="text-yellow-300">idee</span><br>
                    in <span class="text-green-300">realtà</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto leading-relaxed">
                    La piattaforma di crowdfunding più innovativa d'Italia per progetti <strong>Hardware</strong> e <strong>Software</strong>
                </p>
                
                <!-- Enhanced CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    <a href="<?php echo NavigationHelper::url('projects'); ?>" 
                       class="magnetic btn-primary bg-white text-purple-700 hover:bg-blue-50 px-8 py-4 rounded-xl font-bold text-lg transition-all duration-300 hover:shadow-2xl hover:scale-105 focus-visible">
                        <i class="fas fa-search mr-2" aria-hidden="true"></i>
                        Esplora Progetti
                    </a>
                    <?php if (!$is_logged_in): ?>
                    <a href="/BOSTARTER/frontend/auth/register.php" 
                       class="magnetic bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-300 hover:shadow-2xl hover:scale-105 focus-visible">
                        <i class="fas fa-rocket mr-2" aria-hidden="true"></i>
                        Lancia il Tuo Progetto
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Trust Indicators -->
                <div class="flex flex-wrap justify-center items-center gap-8 text-blue-200 text-sm">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-shield-alt text-green-300" aria-hidden="true"></i>
                        <span>Sicuro e Verificato</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-users text-blue-300" aria-hidden="true"></i>
                        <span>Community Attiva</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-award text-yellow-300" aria-hidden="true"></i>
                        <span>Progetti di Qualità</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-heart text-red-300" aria-hidden="true"></i>
                        <span>Made in Italy</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <i class="fas fa-chevron-down text-white/70 text-2xl" aria-hidden="true"></i>
        </div>
    </section>

    <!-- Statistics Section Enhanced -->
    <section class="py-16 bg-white" role="region" aria-labelledby="stats-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 id="stats-title" class="sr-only">Statistiche della piattaforma</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="statistic-card text-center group hover:scale-105 transition-all duration-300" data-aos="fade-up" data-aos-delay="100">
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-rocket text-white text-2xl" aria-hidden="true"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo number_format($stats['total_projects']); ?></div>
                    <div class="text-sm text-gray-600 font-medium">Progetti Attivi</div>
                </div>

                <div class="statistic-card text-center group hover:scale-105 transition-all duration-300" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-euro-sign text-white text-2xl" aria-hidden="true"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2">€<?php echo number_format($stats['total_funding']); ?></div>
                    <div class="text-sm text-gray-600 font-medium">Raccolti</div>
                </div>

                <div class="statistic-card text-center group hover:scale-105 transition-all duration-300" data-aos="fade-up" data-aos-delay="300">
                    <div class="bg-gradient-to-br from-purple-500 to-pink-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-users text-white text-2xl" aria-hidden="true"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo number_format($stats['total_backers']); ?></div>
                    <div class="text-sm text-gray-600 font-medium">Sostenitori</div>
                </div>

                <div class="statistic-card text-center group hover:scale-105 transition-all duration-300" data-aos="fade-up" data-aos-delay="400">
                    <div class="bg-gradient-to-br from-orange-500 to-red-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:rotate-12 transition-transform duration-300">
                        <i class="fas fa-chart-line text-white text-2xl" aria-hidden="true"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo $stats['success_rate']; ?>%</div>
                    <div class="text-sm text-gray-600 font-medium">Tasso di Successo</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Featured Projects Section -->
    <section class="py-20 bg-gradient-to-br from-gray-50 to-blue-50" role="region" aria-labelledby="featured-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 id="featured-title" class="text-4xl md:text-5xl font-bold text-gray-800 mb-6">
                    Progetti <span class="bg-gradient-to-r from-primary to-purple-600 bg-clip-text text-transparent">in Evidenza</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Scopri i progetti più innovativi e promettenti della nostra community
                </p>
            </div>

            <?php if (!empty($featured_projects)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                <?php foreach (array_slice($featured_projects, 0, 6) as $project): ?>
                <article class="project-card bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group" 
                         role="listitem"
                         aria-labelledby="project-<?php echo $project['id']; ?>">
                    <!-- Project Image -->
                    <div class="relative overflow-hidden h-64">
                        <img src="<?php echo htmlspecialchars($project['image_url'] ?? '/BOSTARTER/frontend/images/default-project.jpg'); ?>"
                             alt="Immagine del progetto <?php echo htmlspecialchars($project['name']); ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                             loading="lazy" decoding="async" srcset="<?php echo htmlspecialchars($project['image_url'] ?? '/BOSTARTER/frontend/images/default-project.jpg'); ?> 1x, <?php echo htmlspecialchars($project['image_url'] ?? '/BOSTARTER/frontend/images/default-project@2x.jpg'); ?> 2x">
                        
                        <!-- Category Badge -->
                        <div class="absolute top-4 left-4">
                            <span class="bg-white/90 backdrop-blur-sm text-primary px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo htmlspecialchars($project['category'] ?? 'Generale'); ?>
                            </span>
                        </div>

                        <!-- Progress Overlay -->
                        <div class="absolute bottom-4 left-4 right-4">
                            <div class="bg-white/90 backdrop-blur-sm rounded-lg p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-800">Progresso</span>
                                    <span class="text-sm font-bold text-primary"><?php echo $project['progress']; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2" role="progressbar" aria-valuenow="<?php echo $project['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="bg-gradient-to-r from-primary to-purple-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo $project['progress']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Content -->
                    <div class="p-6">
                        <h3 id="project-<?php echo $project['id']; ?>" class="text-xl font-bold text-gray-800 mb-3 line-clamp-2 group-hover:text-primary transition-colors">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </h3>
                        
                        <p class="text-gray-600 mb-4 line-clamp-2">
                            <?php echo htmlspecialchars($project['description']); ?>
                        </p>

                        <!-- Project Stats -->
                        <div class="flex justify-between items-center mb-4 text-sm">
                            <div class="text-gray-500">
                                <i class="fas fa-euro-sign mr-1" aria-hidden="true"></i>
                                <span class="font-medium">€<?php echo number_format($project['current_funding']); ?></span>
                                <span class="text-gray-400"> / €<?php echo number_format($project['funding_goal']); ?></span>
                            </div>
                            <div class="text-gray-500">
                                <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                <span class="font-medium"><?php echo $project['days_left']; ?> giorni</span>
                            </div>
                        </div>

                        <!-- Creator Info -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($project['creator_name']); ?>&background=3176FF&color=fff&size=32&rounded=true" 
                                     alt="Avatar di <?php echo htmlspecialchars($project['creator_name']); ?>" 
                                     class="w-8 h-8 rounded-full">
                                <span class="text-sm text-gray-600">
                                    di <span class="font-medium"><?php echo htmlspecialchars($project['creator_name']); ?></span>
                                </span>
                            </div>
                            
                            <!-- Quick Action Button -->
                            <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium focus-visible" 
                                    aria-label="Sostieni il progetto <?php echo htmlspecialchars($project['name']); ?>" class="... transition-transform duration-200 hover:scale-105 active:scale-95 ...">
                                <i class="fas fa-heart mr-1" aria-hidden="true"></i>
                                Sostieni
                            </button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 w-24 h-24 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-rocket text-white text-3xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Progetti in arrivo!</h3>
                <p class="text-gray-600 max-w-md mx-auto mb-8">
                    Stiamo preparando progetti incredibili. Registrati per essere il primo a scoprirli!
                </p>
                <?php if (!$is_logged_in): ?>
                <a href="/BOSTARTER/frontend/auth/register.php" 
                   class="bg-gradient-to-r from-primary to-purple-600 text-white px-8 py-3 rounded-lg hover:shadow-lg transition-all duration-300 font-medium focus-visible">
                    <i class="fas fa-bell mr-2" aria-hidden="true"></i>
                    Resta Aggiornato
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- View All Button -->
            <div class="text-center">
                <a href="<?php echo NavigationHelper::url('projects'); ?>" 
                   class="inline-flex items-center bg-white text-primary border-2 border-primary px-8 py-3 rounded-xl hover:bg-primary hover:text-white transition-all duration-300 font-medium focus-visible">
                    <span>Visualizza Tutti i Progetti</span>
                    <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Enhanced Recent Projects Section -->
    <section class="py-20 bg-white" role="region" aria-labelledby="recent-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 id="recent-title" class="text-4xl md:text-5xl font-bold text-gray-800 mb-6">
                    Progetti <span class="bg-gradient-to-r from-green-500 to-emerald-600 bg-clip-text text-transparent">Recenti</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Le ultime novità dalla nostra community di innovatori
                </p>
            </div>

            <?php if (!empty($recent_projects)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach (array_slice($recent_projects, 0, 4) as $project): ?>
                <article class="project-card bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group" 
                         role="listitem"
                         aria-labelledby="recent-project-<?php echo $project['id']; ?>">
                    <!-- Compact Project Image -->
                    <div class="relative overflow-hidden h-48">
                        <img src="<?php echo htmlspecialchars($project['image_url'] ?? '/BOSTARTER/frontend/images/default-project.jpg'); ?>"
                             alt="Immagine del progetto <?php echo htmlspecialchars($project['name']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                             loading="lazy" decoding="async" srcset="<?php echo htmlspecialchars($project['image_url'] ?? '/BOSTARTER/frontend/images/default-project.jpg'); ?> 1x, <?php echo htmlspecialchars($project['image_url'] ?? '/BOSTARTER/frontend/images/default-project@2x.jpg'); ?> 2x">
                        
                        <!-- New Badge -->
                        <div class="absolute top-3 right-3">
                            <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                NUOVO
                            </span>
                        </div>
                    </div>

                    <!-- Compact Project Content -->
                    <div class="p-4">
                        <h3 id="recent-project-<?php echo $project['id']; ?>" class="text-lg font-bold text-gray-800 mb-2 line-clamp-2 group-hover:text-primary transition-colors">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </h3>
                        
                        <!-- Quick Stats -->
                        <div class="flex justify-between items-center text-sm text-gray-600 mb-3">
                            <span class="font-medium">€<?php echo number_format($project['current_funding']); ?></span>
                            <span class="bg-primary/10 text-primary px-2 py-1 rounded-full text-xs">
                                <?php echo $project['progress']; ?>%
                            </span>
                        </div>

                        <!-- Creator -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($project['creator_name']); ?>&background=random&size=24&rounded=true" 
                                     alt="Avatar di <?php echo htmlspecialchars($project['creator_name']); ?>" 
                                     class="w-6 h-6 rounded-full">
                                <span class="text-xs text-gray-600 font-medium">
                                    <?php echo htmlspecialchars($project['creator_name']); ?>
                                </span>
                            </div>
                            
                            <button class="text-gray-400 hover:text-primary transition-colors" 
                                    aria-label="Aggiungi ai preferiti">
                                <i class="fas fa-heart text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Empty State for Recent Projects -->
            <div class="text-center py-12">
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-plus text-white text-2xl" aria-hidden="true"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Nuovi progetti presto disponibili</h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    Torna presto per scoprire i progetti più recenti della community!
                </p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Enhanced Call to Action Section -->
    <section class="py-20 bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-700 text-white relative overflow-hidden" 
             role="region" 
             aria-labelledby="cta-title">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-gradient-to-br from-purple-600/50 to-blue-800/50"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <h2 id="cta-title" class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                    Hai un'idea <span class="text-yellow-300">brillante</span>?
                </h2>
                <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto leading-relaxed">
                    Unisciti alla nostra community di innovatori e trasforma la tua visione in un progetto di successo
                </p>
                
                <?php if (!$is_logged_in): ?>
                <div class="flex flex-col sm:flex-row gap-6 justify-center mb-12">
                    <a href="/BOSTARTER/frontend/auth/register.php" 
                       class="magnetic bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-10 py-4 rounded-xl font-bold text-lg transition-all duration-300 hover:shadow-2xl hover:scale-105 focus-visible">
                        <i class="fas fa-rocket mr-3" aria-hidden="true"></i>
                        Inizia Subito - È Gratis!
                    </a>
                    <a href="<?php echo NavigationHelper::url('how_it_works'); ?>" 
                       class="magnetic bg-white/20 backdrop-blur-sm text-white border-2 border-white/30 px-10 py-4 rounded-xl font-bold text-lg transition-all duration-300 hover:bg-white/30 hover:scale-105 focus-visible">
                        <i class="fas fa-play-circle mr-3" aria-hidden="true"></i>
                        Come Funziona
                    </a>
                </div>
                <?php else: ?>
                <div class="flex flex-col sm:flex-row gap-6 justify-center mb-12">
                    <a href="<?php echo NavigationHelper::url('create_project'); ?>" 
                       class="magnetic bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-10 py-4 rounded-xl font-bold text-lg transition-all duration-300 hover:shadow-2xl hover:scale-105 focus-visible">
                        <i class="fas fa-plus mr-3" aria-hidden="true"></i>
                        Crea il Tuo Progetto
                    </a>
                    <a href="<?php echo NavigationHelper::url('dashboard'); ?>" 
                       class="magnetic bg-white/20 backdrop-blur-sm text-white border-2 border-white/30 px-10 py-4 rounded-xl font-bold text-lg transition-all duration-300 hover:bg-white/30 hover:scale-105 focus-visible">
                        <i class="fas fa-tachometer-alt mr-3" aria-hidden="true"></i>
                        Vai alla Dashboard
                    </a>
                </div>
                <?php endif; ?>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="text-center">
                        <div class="bg-white/20 backdrop-blur-sm w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-lightbulb text-yellow-300 text-2xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Idea Innovativa</h3>
                        <p class="text-blue-200">Condividi la tua visione con la community</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="bg-white/20 backdrop-blur-sm w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-green-300 text-2xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Community Attiva</h3>
                        <p class="text-blue-200">Trova sostenitori appassionati</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="bg-white/20 backdrop-blur-sm w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-line text-blue-300 text-2xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Crescita Garantita</h3>
                        <p class="text-blue-200">Strumenti per il successo</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Enhanced Footer -->
<footer class="bg-gray-900 text-white py-16 relative overflow-hidden" role="contentinfo">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 to-gray-800"></div>
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.03"%3E%3Ccircle cx="20" cy="20" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Main Footer Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <!-- Brand Section -->
            <div class="lg:col-span-1">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-rocket text-white text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">BOSTARTER</h3>
                        <p class="text-gray-400 text-sm">Crowdfunding Innovativo</p>
                    </div>
                </div>
                <p class="text-gray-300 mb-6 leading-relaxed">
                    La piattaforma italiana per trasformare idee innovative in progetti di successo attraverso il crowdfunding.
                </p>
                
                <!-- Social Links -->
                <div class="flex space-x-4">
                    <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-primary rounded-lg flex items-center justify-center transition-colors focus-visible" aria-label="Facebook BOSTARTER">
                        <i class="fab fa-facebook-f text-white" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-primary rounded-lg flex items-center justify-center transition-colors focus-visible" aria-label="Twitter BOSTARTER">
                        <i class="fab fa-twitter text-white" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-primary rounded-lg flex items-center justify-center transition-colors focus-visible" aria-label="Instagram BOSTARTER">
                        <i class="fab fa-instagram text-white" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-primary rounded-lg flex items-center justify-center transition-colors focus-visible" aria-label="LinkedIn BOSTARTER">
                        <i class="fab fa-linkedin-in text-white" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-bold mb-6 text-white">Esplora</h4>
                <ul class="space-y-3">
                    <li><a href="<?php echo NavigationHelper::url('projects'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Tutti i Progetti
                    </a></li>
                    <li><a href="<?php echo NavigationHelper::url('categories'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Categorie
                    </a></li>
                    <li><a href="<?php echo NavigationHelper::url('success_stories'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Storie di Successo
                    </a></li>
                    <li><a href="<?php echo NavigationHelper::url('creators'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Creatori
                    </a></li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h4 class="text-lg font-bold mb-6 text-white">Supporto</h4>
                <ul class="space-y-3">
                    <li><a href="<?php echo NavigationHelper::url('help'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Centro Assistenza
                    </a></li>
                    <li><a href="<?php echo NavigationHelper::url('how_it_works'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Come Funziona
                    </a></li>
                    <li><a href="<?php echo NavigationHelper::url('fees'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Commissioni
                    </a></li>
                    <li><a href="<?php echo NavigationHelper::url('contact'); ?>" class="text-gray-300 hover:text-primary transition-colors flex items-center">
                        <i class="fas fa-chevron-right text-xs mr-2 text-primary" aria-hidden="true"></i>
                        Contattaci
                    </a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <h4 class="text-lg font-bold mb-6 text-white">Resta Aggiornato</h4>
                <p class="text-gray-300 mb-4">
                    Ricevi le ultime novità sui progetti più interessanti
                </p>
                
                <form action="/BOSTARTER/backend/newsletter/subscribe.php" method="POST" class="space-y-3">
                    <div class="relative">
                        <label for="newsletter-email" class="sr-only">Email</label>
                        <input id="newsletter-email" type="email" 
                               name="email" 
                               placeholder="La tua email" 
                               required
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors">
                        <button type="submit" 
                                class="absolute right-2 top-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md transition-colors focus-visible">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                        Non condividiamo mai la tua email. Cancellazione facile.
                    </p>
                </form>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-700 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <p class="text-sm text-gray-400 text-center md:text-left">
                    &copy; <?php echo date('Y'); ?> BOSTARTER. Tutti i diritti riservati. Made with ❤️ in Italy
                </p>
                <div class="flex flex-wrap justify-center md:justify-end items-center space-x-6 text-sm">
                    <a href="<?php echo NavigationHelper::url('privacy'); ?>" class="text-gray-400 hover:text-primary transition-colors">
                        <i class="fas fa-shield-alt mr-1"></i>Privacy Policy
                    </a>
                    <a href="<?php echo NavigationHelper::url('terms'); ?>" class="text-gray-400 hover:text-primary transition-colors">
                        <i class="fas fa-file-contract mr-1"></i>Termini di Servizio
                    </a>
                    <a href="<?php echo NavigationHelper::url('cookies'); ?>" class="text-gray-400 hover:text-primary transition-colors">
                        <i class="fas fa-cookie-bite mr-1"></i>Cookie Policy
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
