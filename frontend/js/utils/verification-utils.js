/**
 * BOSTARTER - Verification Utilities
 * Simplified testing utilities for university project
 */

class VerificationUtils {
    static displayResult(sectionId, message, type = 'info') {
        const section = document.getElementById(sectionId);
        if (!section) return;

        const resultDiv = document.createElement('div');
        resultDiv.className = `test-result test-${type}`;
        resultDiv.textContent = message;
        section.appendChild(resultDiv);
    }

    static async testBasicFunctionality() {
        console.log('Running basic functionality tests...');

        // Test 1: Common functions availability
        try {
            if (typeof CommonFunctions !== 'undefined') {
                this.displayResult('basic-tests', '✓ CommonFunctions loaded successfully', 'success');
            } else {
                this.displayResult('basic-tests', '✗ CommonFunctions not available', 'error');
            }
        } catch (error) {
            this.displayResult('basic-tests', '✗ Error testing CommonFunctions: ' + error.message, 'error');
        }

        // Test 2: UI Components availability
        try {
            if (typeof UIComponents !== 'undefined') {
                this.displayResult('basic-tests', '✓ UIComponents loaded successfully', 'success');
            } else {
                this.displayResult('basic-tests', '✗ UIComponents not available', 'error');
            }
        } catch (error) {
            this.displayResult('basic-tests', '✗ Error testing UIComponents: ' + error.message, 'error');
        }

        // Test 3: Service Worker registration
        try {
            if ('serviceWorker' in navigator) {
                this.displayResult('basic-tests', '✓ Service Worker support available', 'success');
            } else {
                this.displayResult('basic-tests', '! Service Worker not supported in this browser', 'warning');
            }
        } catch (error) {
            this.displayResult('basic-tests', '✗ Error checking Service Worker: ' + error.message, 'error');
        }

        // Test 4: Local Storage availability
        try {
            localStorage.setItem('test', 'test');
            localStorage.removeItem('test');
            this.displayResult('basic-tests', '✓ Local Storage is functional', 'success');
        } catch (error) {
            this.displayResult('basic-tests', '✗ Local Storage error: ' + error.message, 'error');
        }
    }

    static async testPerformance() {
        console.log('Running performance tests...');

        const startTime = performance.now();

        // Simple DOM manipulation test
        const testDiv = document.createElement('div');
        testDiv.innerHTML = '<p>Performance test element</p>';
        document.body.appendChild(testDiv);

        const endTime = performance.now();
        const duration = endTime - startTime;

        document.body.removeChild(testDiv);

        this.displayResult('performance-tests', `DOM manipulation: ${duration.toFixed(2)}ms`, 'success');

        // Memory usage (if available)
        if (performance.memory) {
            const memoryMB = (performance.memory.usedJSHeapSize / (1024 * 1024)).toFixed(2);
            this.displayResult('performance-tests', `Memory usage: ${memoryMB} MB`, 'info');
        }
    }

    static runAllTests() {
        console.log('Starting BOSTARTER verification tests...');

        // Clear previous results
        document.querySelectorAll('.test-result').forEach(el => el.remove());

        // Run tests
        this.testBasicFunctionality();
        this.testPerformance();

        console.log('Verification tests completed.');
    }
}

// Auto-run tests when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Add run button functionality
    const runButton = document.getElementById('run-tests');
    if (runButton) {
        runButton.addEventListener('click', () => {
            VerificationUtils.runAllTests();
        });
    }

    // Auto-run basic tests
    setTimeout(() => {
        VerificationUtils.runAllTests();
    }, 1000);
});

// Export for global access
window.VerificationUtils = VerificationUtils;
