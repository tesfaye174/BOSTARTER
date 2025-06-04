/**
 * Statistics counter animation
 * Animates number counters with smooth counting effect
 */

document.addEventListener('DOMContentLoaded', function () {
    // Animazione contatori statistiche stile Kickstarter
    function animateCounters() {
        document.querySelectorAll('.counter').forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const isEuro = counter.textContent.includes('€');
            let count = 0;
            const increment = Math.max(1, Math.floor(target / 100));

            function update() {
                count += increment;
                if (count >= target) {
                    counter.textContent = isEuro ? target.toLocaleString('it-IT') + '€' : target.toLocaleString('it-IT');
                } else {
                    counter.textContent = isEuro ? count.toLocaleString('it-IT') + '€' : count.toLocaleString('it-IT');
                    requestAnimationFrame(update);
                }
            }
            update();
        });
    }

    // Initialize counter animation
    animateCounters();
});
