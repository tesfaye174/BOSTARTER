<?php
// Enhanced accessible footer component for BOSTARTER
?>
<footer class="bg-dark text-light py-5 mt-5" role="contentinfo" aria-label="Informazioni del sito e collegamenti">
    <div class="container">
        <div class="row">
            <!-- Brand Section -->
            <div class="col-md-4 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="images/logo1.svg" 
                         alt="" 
                         class="me-2" 
                         style="height: 24px;"
                         role="img"
                         aria-hidden="true">
                    <span class="fs-5 fw-bold">BOSTARTER</span>
                </div>
                <p class="text-muted">
                    La piattaforma italiana che trasforma idee creative in realtà attraverso il crowdfunding.
                    Supporta innovazione, tecnologia e creatività.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div class="col-md-2 mb-4">
                <nav aria-labelledby="explore-heading">
                    <h6 class="fw-bold mb-3" id="explore-heading">Esplora</h6>
                    <ul class="list-unstyled" role="list">
                        <li>
                            <a href="projects/list.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Visualizza tutti i progetti disponibili">
                                Tutti i Progetti
                            </a>
                        </li>
                        <li>
                            <a href="projects/category.php?type=hardware" 
                               class="text-muted text-decoration-none"
                               aria-label="Esplora progetti hardware">
                                Hardware
                            </a>
                        </li>
                        <li>
                            <a href="projects/category.php?type=software" 
                               class="text-muted text-decoration-none"
                               aria-label="Esplora progetti software">
                                Software
                            </a>
                        </li>
                        <li>
                            <a href="stats/index.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Visualizza statistiche della piattaforma">
                                Statistiche
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- For Creators -->
            <div class="col-md-2 mb-4">
                <nav aria-labelledby="creators-heading">
                    <h6 class="fw-bold mb-3" id="creators-heading">Creatori</h6>
                    <ul class="list-unstyled" role="list">
                        <li>
                            <a href="projects/create.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Inizia a creare il tuo progetto">
                                Crea Progetto
                            </a>
                        </li>
                        <li>
                            <a href="help/guidelines.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Leggi le linee guida per i creatori">
                                Linee Guida
                            </a>
                        </li>
                        <li>
                            <a href="help/fees.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Informazioni su commissioni e costi">
                                Commissioni
                            </a>
                        </li>
                        <li>
                            <a href="help/support.php" 
                               class="text-muted text-decoration-none"
                               aria-label="Ottieni supporto e assistenza">
                                Supporto
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- Social & Contact -->
            <div class="col-md-4 mb-4">
                <section aria-labelledby="social-heading">
                    <h6 class="fw-bold mb-3" id="social-heading">Seguici</h6>
                    <div class="d-flex mb-3" role="list" aria-label="Collegamenti social media">
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Seguici su Facebook (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Seguici su Twitter (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-twitter" aria-hidden="true"></i>
                        </a>
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Seguici su Instagram (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-instagram" aria-hidden="true"></i>
                        </a>
                        <a href="#" 
                           class="text-muted me-3 fs-5" 
                           aria-label="Collegati con noi su LinkedIn (link esterno)"
                           rel="noopener noreferrer"
                           target="_blank">
                            <i class="fab fa-linkedin" aria-hidden="true"></i>
                        </a>
                    </div>
                    <address class="text-muted small mb-0">
                        <i class="fas fa-envelope me-2" aria-hidden="true"></i>
                        <a href="mailto:info@bostarter.it" 
                           class="text-muted text-decoration-none"
                           aria-label="Invia email a info@bostarter.it">
                            info@bostarter.it
                        </a>
                    </address>
                </section>
            </div>
        </div>
        
        <hr class="my-4" aria-hidden="true">
        
        <!-- Copyright and Legal -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted small mb-0" role="contentinfo">
                    &copy; 2025 BOSTARTER. Tutti i diritti riservati. Made with ❤️ in Italy
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <nav aria-label="Collegamenti legali">
                    <a href="legal/privacy.php" 
                       class="text-muted text-decoration-none small me-3"
                       aria-label="Leggi la nostra privacy policy">
                        Privacy Policy
                    </a>
                    <a href="legal/terms.php" 
                       class="text-muted text-decoration-none small"
                       aria-label="Leggi i termini di servizio">
                        Termini di Servizio
                    </a>
                </nav>
            </div>
        </div>
    </div>
</footer>
