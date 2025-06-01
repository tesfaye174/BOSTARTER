// ===== GOOGLE ANALYTICS CONFIGURATION =====
// Analytics and tracking functionality

// Initialize Google Analytics
window.dataLayer = window.dataLayer || [];
function gtag() { dataLayer.push(arguments); }
gtag('js', new Date());
gtag('config', 'GA_MEASUREMENT_ID', {
    page_title: 'BOSTARTER - Homepage',
    page_location: window.location.href
});

// Export gtag function for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { gtag };
} else if (typeof window !== 'undefined') {
    window.gtag = gtag;
}
