// ===== STRUCTURED DATA FOR SEO =====
// JSON-LD structured data configuration

const structuredData = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "BOSTARTER",
    "description": "Piattaforma leader per il crowdfunding di progetti creativi in Italia",
    "url": "https://www.bostarter.it",
    "logo": "https://www.bostarter.it/frontend/images/logo1.svg",
    "sameAs": [
        "https://twitter.com/bostarter",
        "https://facebook.com/bostarter",
        "https://instagram.com/bostarter",
        "https://linkedin.com/company/bostarter"
    ],
    "potentialAction": {
        "@type": "SearchAction",
        "target": "https://www.bostarter.it/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
    }
};

// Function to inject structured data into the page
function injectStructuredData() {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(structuredData);
    document.head.appendChild(script);
}

// Initialize structured data when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectStructuredData);
} else {
    injectStructuredData();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { structuredData, injectStructuredData };
} else if (typeof window !== 'undefined') {
    window.structuredData = structuredData;
    window.injectStructuredData = injectStructuredData;
}
