// Classe per la gestione delle performance
class PerformanceManager {
    constructor() {
        this.metrics = {};
        this.marks = {};
        this.measures = {};
        this.init();
    }

    // Inizializzazione
    init() {
        if (window.performance && window.performance.mark) {
            this.observePerformance();
            this.observeResources();
            this.observeLongTasks();
        }
    }

    // Osserva le metriche di performance
    observePerformance() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.metrics[entry.name] = entry;
                }
            });

            observer.observe({ entryTypes: ['measure', 'resource', 'paint', 'largest-contentful-paint', 'first-input', 'layout-shift'] });
        }
    }

    // Osserva le risorse
    observeResources() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.initiatorType === 'script' || entry.initiatorType === 'css') {
                        this.metrics[entry.name] = entry;
                    }
                }
            });

            observer.observe({ entryTypes: ['resource'] });
        }
    }

    // Osserva i task lunghi
    observeLongTasks() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.duration > 50) {
                        console.warn('Task lungo rilevato:', entry);
                    }
                }
            });

            observer.observe({ entryTypes: ['longtask'] });
        }
    }

    // Marca un punto nel tempo
    mark(name) {
        if (window.performance && window.performance.mark) {
            window.performance.mark(name);
            this.marks[name] = performance.now();
        }
    }

    // Misura il tempo tra due marcature
    measure(name, startMark, endMark) {
        if (window.performance && window.performance.measure) {
            window.performance.measure(name, startMark, endMark);
            const measure = performance.getEntriesByName(name).pop();
            this.measures[name] = measure;
        }
    }

    // Ottiene le metriche di caricamento
    getLoadMetrics() {
        const timing = performance.timing;
        return {
            dns: timing.domainLookupEnd - timing.domainLookupStart,
            tcp: timing.connectEnd - timing.connectStart,
            request: timing.responseEnd - timing.requestStart,
            dom: timing.domComplete - timing.domLoading,
            load: timing.loadEventEnd - timing.navigationStart
        };
    }

    // Ottiene le metriche di First Paint
    getPaintMetrics() {
        const paint = performance.getEntriesByType('paint');
        return {
            firstPaint: paint.find(entry => entry.name === 'first-paint')?.startTime,
            firstContentfulPaint: paint.find(entry => entry.name === 'first-contentful-paint')?.startTime
        };
    }

    // Ottiene le metriche di Largest Contentful Paint
    getLCP() {
        const lcp = performance.getEntriesByType('largest-contentful-paint');
        return lcp[lcp.length - 1]?.startTime;
    }

    // Ottiene le metriche di First Input Delay
    getFID() {
        const fid = performance.getEntriesByType('first-input');
        return fid[0]?.duration;
    }

    // Ottiene le metriche di Cumulative Layout Shift
    getCLS() {
        const cls = performance.getEntriesByType('layout-shift');
        return cls.reduce((sum, entry) => sum + entry.value, 0);
    }

    // Ottiene le metriche di Time to Interactive
    getTTI() {
        const longTasks = performance.getEntriesByType('longtask');
        const lastLongTask = longTasks[longTasks.length - 1];
        return lastLongTask ? lastLongTask.startTime + lastLongTask.duration : 0;
    }

    // Ottiene le metriche di Speed Index
    getSpeedIndex() {
        const paint = performance.getEntriesByType('paint');
        const firstPaint = paint.find(entry => entry.name === 'first-paint')?.startTime;
        const lastPaint = paint[paint.length - 1]?.startTime;
        return lastPaint - firstPaint;
    }

    // Ottiene le metriche di Time to First Byte
    getTTFB() {
        const timing = performance.timing;
        return timing.responseStart - timing.navigationStart;
    }

    // Ottiene le metriche di First Meaningful Paint
    getFMP() {
        const paint = performance.getEntriesByType('paint');
        return paint.find(entry => entry.name === 'first-contentful-paint')?.startTime;
    }

    // Ottiene tutte le metriche
    getAllMetrics() {
        return {
            load: this.getLoadMetrics(),
            paint: this.getPaintMetrics(),
            lcp: this.getLCP(),
            fid: this.getFID(),
            cls: this.getCLS(),
            tti: this.getTTI(),
            speedIndex: this.getSpeedIndex(),
            ttfb: this.getTTFB(),
            fmp: this.getFMP()
        };
    }

    // Invia le metriche al server
    async sendMetrics() {
        const metrics = this.getAllMetrics();
        try {
            await fetch('/api/metrics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(metrics)
            });
        } catch (error) {
            console.error('Errore nell\'invio delle metriche:', error);
        }
    }

    // Pulisce le metriche
    clearMetrics() {
        this.metrics = {};
        this.marks = {};
        this.measures = {};
        if (window.performance && window.performance.clearMarks) {
            window.performance.clearMarks();
            window.performance.clearMeasures();
        }
    }
}

// Crea un'istanza globale del gestore performance
const performanceManager = new PerformanceManager();

// Esporta l'istanza e la classe
export { performanceManager, PerformanceManager }; 