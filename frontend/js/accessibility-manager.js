// Enhanced Accessibility Manager
class AccessibilityManager {
    constructor() {
        this.focusTracker = null;
        this.announcements = [];
        this.keyboardNavigation = null;
        this.contrastMode = false;
        this.reducedMotion = false;
        this.screenReaderMode = false;
        this.init();
    }

    init() {
        this.detectAccessibilityPreferences();
        this.setupKeyboardNavigation();
        this.setupFocusManagement();
        this.setupScreenReaderSupport();
        this.setupColorContrastTools();
        this.setupMotionReduction();
        this.setupAccessibilityShortcuts();
        this.createAccessibilityPanel();
        this.enhanceFormAccessibility();
        this.setupSkipLinks();
        this.auditAccessibility();
    }

    // Detect user accessibility preferences
    detectAccessibilityPreferences() {
        // Check for prefers-reduced-motion
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Check for prefers-contrast
        this.contrastMode = window.matchMedia('(prefers-contrast: high)').matches;

        // Check for prefers-color-scheme
        const darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Apply preferences
        if (this.reducedMotion) {
            document.documentElement.classList.add('reduced-motion');
        }

        if (this.contrastMode) {
            document.documentElement.classList.add('high-contrast');
        }

        if (darkMode) {
            document.documentElement.classList.add('dark-theme');
        }

        // Listen for changes
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
            this.reducedMotion = e.matches;
            document.documentElement.classList.toggle('reduced-motion', e.matches);
        });

        window.matchMedia('(prefers-contrast: high)').addEventListener('change', (e) => {
            this.contrastMode = e.matches;
            document.documentElement.classList.toggle('high-contrast', e.matches);
        });
    }

    // Enhanced keyboard navigation
    setupKeyboardNavigation() {
        this.keyboardNavigation = new KeyboardNavigationManager();

        // Tab navigation enhancement
        document.addEventListener('keydown', (e) => {
            this.handleGlobalKeydown(e);
        });

        // Focus visible enhancement
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });

        // Roving tabindex for complex widgets
        this.setupRovingTabindex();
    }

    handleGlobalKeydown(e) {
        // Escape key handling
        if (e.key === 'Escape') {
            this.handleEscape();
        }

        // Arrow key navigation for grid/list items
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
            this.handleArrowNavigation(e);
        }

        // Enter/Space for activation
        if (e.key === 'Enter' || e.key === ' ') {
            this.handleActivation(e);
        }

        // Home/End navigation
        if (e.key === 'Home' || e.key === 'End') {
            this.handleHomeEnd(e);
        }
    }

    handleEscape() {
        // Close modals, dropdowns, etc.
        const openModals = document.querySelectorAll('.modal.open, .dropdown.open, .tooltip.open');
        openModals.forEach(element => {
            this.closeElement(element);
        });

        // Return focus to trigger element
        if (this.focusTracker?.returnElement) {
            this.focusTracker.returnElement.focus();
            this.focusTracker.returnElement = null;
        }
    }

    handleArrowNavigation(e) {
        const target = e.target;

        // Grid navigation
        if (target.closest('[role="grid"]')) {
            this.navigateGrid(e);
        }

        // List navigation
        else if (target.closest('[role="listbox"], [role="menu"]')) {
            this.navigateList(e);
        }

        // Tab navigation
        else if (target.closest('[role="tablist"]')) {
            this.navigateTabs(e);
        }
    }

    handleActivation(e) {
        const target = e.target;

        // Only handle if element is focusable but not naturally activatable
        if (target.hasAttribute('tabindex') &&
            !['button', 'a', 'input', 'select', 'textarea'].includes(target.tagName.toLowerCase())) {

            // Prevent default for space to avoid scrolling
            if (e.key === ' ') {
                e.preventDefault();
            }

            // Trigger click event
            target.click();
        }
    }

    // Focus management
    setupFocusManagement() {
        this.focusTracker = {
            previousElement: null,
            returnElement: null,
            trapElement: null
        };

        // Track focus changes
        document.addEventListener('focusin', (e) => {
            this.focusTracker.previousElement = e.target;
        });

        // Focus trap for modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && this.focusTracker.trapElement) {
                this.trapFocus(e);
            }
        });
    }

    trapFocus(e) {
        const trapElement = this.focusTracker.trapElement;
        const focusableElements = this.getFocusableElements(trapElement);

        if (focusableElements.length === 0) return;

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey) {
            // Shift + Tab
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    getFocusableElements(container) {
        const selector = `
            a[href],
            button:not([disabled]),
            input:not([disabled]),
            select:not([disabled]),
            textarea:not([disabled]),
            [tabindex]:not([tabindex="-1"]),
            [contenteditable="true"]
        `;

        return Array.from(container.querySelectorAll(selector))
            .filter(el => this.isVisible(el));
    }

    isVisible(element) {
        const style = window.getComputedStyle(element);
        return style.display !== 'none' &&
            style.visibility !== 'hidden' &&
            style.opacity !== '0';
    }

    // Screen reader support
    setupScreenReaderSupport() {
        // Create live region for announcements
        this.createLiveRegion();

        // Enhance dynamic content updates
        this.setupDynamicContentUpdates();

        // Improve form labels and descriptions
        this.enhanceFormLabels();

        // Add landmark roles
        this.addLandmarkRoles();
    }

    createLiveRegion() {
        const liveRegion = document.createElement('div');
        liveRegion.id = 'live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.cssText = `
            position: absolute;
            left: -10000px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        `;
        document.body.appendChild(liveRegion);

        // Also create assertive region for urgent messages
        const assertiveRegion = liveRegion.cloneNode();
        assertiveRegion.id = 'live-region-assertive';
        assertiveRegion.setAttribute('aria-live', 'assertive');
        document.body.appendChild(assertiveRegion);
    }

    announce(message, priority = 'polite') {
        const regionId = priority === 'assertive' ? 'live-region-assertive' : 'live-region';
        const region = document.getElementById(regionId);

        if (region) {
            // Clear previous message
            region.textContent = '';

            // Add new message after a brief delay to ensure it's announced
            setTimeout(() => {
                region.textContent = message;
            }, 100);

            // Clear after announcement
            setTimeout(() => {
                region.textContent = '';
            }, 3000);
        }
    }

    setupDynamicContentUpdates() {
        // Monitor for dynamic content changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    this.handleContentUpdate(mutation);
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    handleContentUpdate(mutation) {
        const addedNodes = Array.from(mutation.addedNodes);

        addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                // Announce new notifications or alerts
                if (node.matches('.notification, .alert, .toast')) {
                    const message = node.textContent || node.getAttribute('aria-label');
                    if (message) {
                        this.announce(message, 'assertive');
                    }
                }

                // Announce loading states
                if (node.matches('.loading, .spinner')) {
                    this.announce('Loading content, please wait', 'polite');
                }

                // Announce new form errors
                if (node.matches('.error, .field-error')) {
                    const message = node.textContent || 'Form validation error';
                    this.announce(message, 'assertive');
                }
            }
        });
    }

    // Color contrast and visual enhancements
    setupColorContrastTools() {
        // Add contrast adjustment controls
        this.createContrastControls();

        // Monitor for insufficient contrast
        this.auditColorContrast();
    }

    createContrastControls() {
        const contrastLevels = [
            { name: 'Normal', value: 'normal' },
            { name: 'High', value: 'high' },
            { name: 'Extra High', value: 'extra-high' }
        ];

        // This will be added to the accessibility panel
        this.contrastControls = contrastLevels;
    }

    auditColorContrast() {
        // Simple contrast audit (basic implementation)
        const elements = document.querySelectorAll('*');
        elements.forEach(element => {
            const style = window.getComputedStyle(element);
            const bgColor = style.backgroundColor;
            const textColor = style.color;

            if (this.hasInsufficientContrast(bgColor, textColor)) {
                element.classList.add('contrast-warning');
            }
        });
    }

    hasInsufficientContrast(bg, text) {
        // Simplified contrast check - in production, use a proper contrast ratio calculator
        return false; // Placeholder
    }

    // Motion reduction
    setupMotionReduction() {
        if (this.reducedMotion) {
            this.disableAnimations();
        }

        // Add motion toggle
        this.createMotionToggle();
    }

    disableAnimations() {
        const style = document.createElement('style');
        style.textContent = `
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        `;
        document.head.appendChild(style);
    }

    // Accessibility shortcuts
    setupAccessibilityShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Alt + 1: Skip to main content
            if (e.altKey && e.key === '1') {
                e.preventDefault();
                this.skipToMain();
            }

            // Alt + 2: Skip to navigation
            if (e.altKey && e.key === '2') {
                e.preventDefault();
                this.skipToNavigation();
            }

            // Alt + 3: Skip to search
            if (e.altKey && e.key === '3') {
                e.preventDefault();
                this.skipToSearch();
            }

            // Alt + 0: Show accessibility help
            if (e.altKey && e.key === '0') {
                e.preventDefault();
                this.showAccessibilityHelp();
            }

            // Ctrl + Alt + H: Toggle high contrast
            if (e.ctrlKey && e.altKey && e.key === 'h') {
                e.preventDefault();
                this.toggleHighContrast();
            }
        });
    }

    // Skip links
    setupSkipLinks() {
        const skipLinks = document.createElement('div');
        skipLinks.className = 'skip-links';
        skipLinks.innerHTML = `
            <a href="#main-content" class="skip-link">Skip to main content</a>
            <a href="#navigation" class="skip-link">Skip to navigation</a>
            <a href="#search" class="skip-link">Skip to search</a>
        `;

        // Insert at beginning of body
        document.body.insertBefore(skipLinks, document.body.firstChild);

        // Add styles
        this.addSkipLinkStyles();
    }

    addSkipLinkStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .skip-links {
                position: absolute;
                top: -40px;
                left: 6px;
                z-index: 10000;
            }
            
            .skip-link {
                position: absolute;
                top: -40px;
                left: 6px;
                background: #000;
                color: #fff;
                padding: 8px 16px;
                border-radius: 0 0 4px 4px;
                text-decoration: none;
                font-weight: bold;
                z-index: 10001;
                transition: top 0.3s ease;
            }
            
            .skip-link:focus {
                top: 0;
            }
            
            .skip-link:hover,
            .skip-link:focus {
                background: #333;
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);
    }

    // Form accessibility enhancements
    enhanceFormAccessibility() {
        // Add proper labels and descriptions
        document.querySelectorAll('input, select, textarea').forEach(field => {
            this.enhanceFormField(field);
        });

        // Add form validation announcements
        document.addEventListener('invalid', (e) => {
            const field = e.target;
            const message = field.validationMessage || 'Please check this field';
            this.announce(`${this.getFieldLabel(field)}: ${message}`, 'assertive');
        });
    }

    enhanceFormField(field) {
        // Ensure proper labeling
        if (!field.getAttribute('aria-label') && !field.getAttribute('aria-labelledby')) {
            const label = this.findFieldLabel(field);
            if (label) {
                field.setAttribute('aria-labelledby', label.id || this.generateId('label'));
                if (!label.id) label.id = field.getAttribute('aria-labelledby');
            }
        }

        // Add descriptions for help text
        const helpText = this.findFieldHelpText(field);
        if (helpText) {
            const helpId = helpText.id || this.generateId('help');
            if (!helpText.id) helpText.id = helpId;
            field.setAttribute('aria-describedby', helpId);
        }

        // Add required indicators
        if (field.hasAttribute('required')) {
            field.setAttribute('aria-required', 'true');
        }
    }

    findFieldLabel(field) {
        // Look for explicit label
        const labelId = field.getAttribute('aria-labelledby');
        if (labelId) return document.getElementById(labelId);

        // Look for implicit label
        const label = field.closest('label') ||
            document.querySelector(`label[for="${field.id}"]`);
        return label;
    }

    findFieldHelpText(field) {
        const helpSelectors = [
            '.help-text',
            '.field-help',
            '.description',
            '[data-help]'
        ];

        const parent = field.closest('.field, .form-group, .input-group');
        if (parent) {
            for (const selector of helpSelectors) {
                const helpText = parent.querySelector(selector);
                if (helpText) return helpText;
            }
        }

        return null;
    }

    // Accessibility panel
    createAccessibilityPanel() {
        const panel = document.createElement('div');
        panel.id = 'accessibility-panel';
        panel.className = 'accessibility-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-labelledby', 'accessibility-panel-title');
        panel.innerHTML = `
            <div class="panel-header">
                <h2 id="accessibility-panel-title">Accessibility Settings</h2>
                <button class="close-panel" aria-label="Close accessibility settings">×</button>
            </div>
            <div class="panel-content">
                <div class="setting-group">
                    <h3>Visual</h3>
                    <label>
                        <input type="checkbox" id="high-contrast-toggle"> High Contrast
                    </label>
                    <label>
                        <input type="range" id="font-size-slider" min="12" max="24" value="16"> 
                        Font Size: <span id="font-size-value">16px</span>
                    </label>
                </div>
                <div class="setting-group">
                    <h3>Motion</h3>
                    <label>
                        <input type="checkbox" id="reduce-motion-toggle"> Reduce Motion
                    </label>
                </div>
                <div class="setting-group">
                    <h3>Navigation</h3>
                    <button id="show-landmarks">Show Landmarks</button>
                    <button id="show-headings">Show Headings</button>
                </div>
            </div>
        `;

        document.body.appendChild(panel);
        this.addAccessibilityPanelStyles();
        this.bindAccessibilityPanelEvents();
    }

    addAccessibilityPanelStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .accessibility-panel {
                position: fixed;
                top: 20px;
                right: -400px;
                width: 350px;
                max-height: 80vh;
                background: white;
                border: 2px solid #333;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 10000;
                transition: right 0.3s ease;
                overflow-y: auto;
            }
            
            .accessibility-panel.open {
                right: 20px;
            }
            
            .panel-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px;
                border-bottom: 1px solid #ddd;
                background: #f5f5f5;
            }
            
            .panel-content {
                padding: 16px;
            }
            
            .setting-group {
                margin-bottom: 24px;
            }
            
            .setting-group h3 {
                margin-bottom: 12px;
                font-size: 14px;
                text-transform: uppercase;
                color: #666;
            }
            
            .setting-group label {
                display: block;
                margin-bottom: 8px;
                cursor: pointer;
            }
            
            .setting-group input[type="checkbox"] {
                margin-right: 8px;
            }
            
            .setting-group input[type="range"] {
                width: 100%;
                margin: 8px 0;
            }
            
            .setting-group button {
                display: block;
                width: 100%;
                margin-bottom: 8px;
                padding: 8px 12px;
                background: #007cba;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            
            .close-panel {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 4px;
            }
            
            .accessibility-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                background: #007cba;
                color: white;
                border: none;
                border-radius: 50%;
                font-size: 24px;
                cursor: pointer;
                z-index: 9999;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            }
            
            .accessibility-toggle:hover {
                background: #005a87;
            }
        `;
        document.head.appendChild(style);
    }

    bindAccessibilityPanelEvents() {
        // Toggle panel
        const toggleButton = document.createElement('button');
        toggleButton.className = 'accessibility-toggle';
        toggleButton.innerHTML = '♿';
        toggleButton.setAttribute('aria-label', 'Open accessibility settings');
        document.body.appendChild(toggleButton);

        toggleButton.addEventListener('click', () => {
            this.toggleAccessibilityPanel();
        });

        // Panel controls
        const panel = document.getElementById('accessibility-panel');

        panel.querySelector('.close-panel').addEventListener('click', () => {
            this.closeAccessibilityPanel();
        });

        // High contrast toggle
        document.getElementById('high-contrast-toggle').addEventListener('change', (e) => {
            this.toggleHighContrast(e.target.checked);
        });

        // Font size slider
        const fontSlider = document.getElementById('font-size-slider');
        const fontValue = document.getElementById('font-size-value');

        fontSlider.addEventListener('input', (e) => {
            const size = e.target.value;
            fontValue.textContent = `${size}px`;
            this.setFontSize(size);
        });

        // Reduce motion toggle
        document.getElementById('reduce-motion-toggle').addEventListener('change', (e) => {
            this.toggleReducedMotion(e.target.checked);
        });

        // Show landmarks button
        document.getElementById('show-landmarks').addEventListener('click', () => {
            this.highlightLandmarks();
        });

        // Show headings button
        document.getElementById('show-headings').addEventListener('click', () => {
            this.highlightHeadings();
        });
    }

    // Utility methods
    skipToMain() {
        const main = document.getElementById('main-content') ||
            document.querySelector('main') ||
            document.querySelector('[role="main"]');
        if (main) {
            main.focus();
            main.scrollIntoView();
        }
    }

    skipToNavigation() {
        const nav = document.getElementById('navigation') ||
            document.querySelector('nav') ||
            document.querySelector('[role="navigation"]');
        if (nav) {
            nav.focus();
            nav.scrollIntoView();
        }
    }

    skipToSearch() {
        const search = document.getElementById('search') ||
            document.querySelector('input[type="search"]') ||
            document.querySelector('[role="search"]');
        if (search) {
            search.focus();
        }
    }

    toggleAccessibilityPanel() {
        const panel = document.getElementById('accessibility-panel');
        panel.classList.toggle('open');

        if (panel.classList.contains('open')) {
            this.focusTracker.returnElement = document.activeElement;
            this.focusTracker.trapElement = panel;
            panel.querySelector('.close-panel').focus();
        } else {
            this.focusTracker.trapElement = null;
            if (this.focusTracker.returnElement) {
                this.focusTracker.returnElement.focus();
            }
        }
    }

    closeAccessibilityPanel() {
        const panel = document.getElementById('accessibility-panel');
        panel.classList.remove('open');
        this.focusTracker.trapElement = null;

        if (this.focusTracker.returnElement) {
            this.focusTracker.returnElement.focus();
        }
    }

    toggleHighContrast(enable = !this.contrastMode) {
        this.contrastMode = enable;
        document.documentElement.classList.toggle('high-contrast', enable);

        // Update checkbox state
        const checkbox = document.getElementById('high-contrast-toggle');
        if (checkbox) checkbox.checked = enable;

        this.announce(`High contrast ${enable ? 'enabled' : 'disabled'}`, 'polite');
    }

    setFontSize(size) {
        document.documentElement.style.fontSize = `${size}px`;
        this.announce(`Font size set to ${size} pixels`, 'polite');
    }

    toggleReducedMotion(enable = !this.reducedMotion) {
        this.reducedMotion = enable;
        document.documentElement.classList.toggle('reduced-motion', enable);

        if (enable) {
            this.disableAnimations();
        }

        this.announce(`Motion ${enable ? 'reduced' : 'restored'}`, 'polite');
    }

    highlightLandmarks() {
        const landmarks = document.querySelectorAll('[role], nav, main, aside, header, footer');
        landmarks.forEach(landmark => {
            landmark.style.outline = '3px solid #ff0000';
            landmark.style.outlineOffset = '2px';
        });

        setTimeout(() => {
            landmarks.forEach(landmark => {
                landmark.style.outline = '';
                landmark.style.outlineOffset = '';
            });
        }, 5000);

        this.announce(`${landmarks.length} landmarks highlighted`, 'polite');
    }

    highlightHeadings() {
        const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
        headings.forEach(heading => {
            heading.style.outline = '3px solid #00ff00';
            heading.style.outlineOffset = '2px';
        });

        setTimeout(() => {
            headings.forEach(heading => {
                heading.style.outline = '';
                heading.style.outlineOffset = '';
            });
        }, 5000);

        this.announce(`${headings.length} headings highlighted`, 'polite');
    }

    generateId(prefix = 'element') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    getFieldLabel(field) {
        const label = this.findFieldLabel(field);
        return label ? label.textContent.trim() : field.name || 'Field';
    }

    // Basic accessibility audit
    auditAccessibility() {
        const issues = [];

        // Check for missing alt text
        document.querySelectorAll('img:not([alt])').forEach(img => {
            issues.push(`Image missing alt text: ${img.src}`);
        });

        // Check for missing form labels
        document.querySelectorAll('input, select, textarea').forEach(field => {
            if (!this.findFieldLabel(field) && !field.getAttribute('aria-label')) {
                issues.push(`Form field missing label: ${field.name || field.id}`);
            }
        });

        // Check for missing heading structure
        const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
        if (headings.length === 0) {
            issues.push('No headings found - page structure unclear');
        }

        // Check for missing main landmark
        if (!document.querySelector('main, [role="main"]')) {
            issues.push('No main landmark found');
        }

        if (issues.length > 0) {
            console.warn('Accessibility issues found:', issues);
        }

        return issues;
    }
}

// Keyboard navigation manager helper class
class KeyboardNavigationManager {
    constructor() {
        this.gridNavigators = new Map();
        this.listNavigators = new Map();
    }

    navigateGrid(e, grid) {
        // Grid navigation implementation
        const currentCell = document.activeElement;
        const allCells = Array.from(grid.querySelectorAll('[role="gridcell"]'));
        const currentIndex = allCells.indexOf(currentCell);

        if (currentIndex === -1) return;

        const cols = parseInt(grid.getAttribute('data-cols')) || this.getGridCols(grid);
        let newIndex = currentIndex;

        switch (e.key) {
            case 'ArrowRight':
                newIndex = Math.min(currentIndex + 1, allCells.length - 1);
                break;
            case 'ArrowLeft':
                newIndex = Math.max(currentIndex - 1, 0);
                break;
            case 'ArrowDown':
                newIndex = Math.min(currentIndex + cols, allCells.length - 1);
                break;
            case 'ArrowUp':
                newIndex = Math.max(currentIndex - cols, 0);
                break;
        }

        if (newIndex !== currentIndex) {
            e.preventDefault();
            allCells[newIndex].focus();
        }
    }

    getGridCols(grid) {
        // Calculate columns based on first row
        const firstRow = grid.querySelector('[role="row"]');
        return firstRow ? firstRow.querySelectorAll('[role="gridcell"]').length : 1;
    }
}

// Initialize accessibility manager
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.accessibilityManager = new AccessibilityManager();
    });
} else {
    window.accessibilityManager = new AccessibilityManager();
}

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AccessibilityManager;
}
