// Cache configuration
const CACHE_CONFIG = {
    topCreators: {
        key: 'cache_top_creators',
        ttl: 300000 // 5 minutes
    },
    nearCompletionProjects: {
        key: 'cache_near_completion_projects',
        ttl: 300000
    },
    topFunders: {
        key: 'cache_top_funders',
        ttl: 300000
    },
    featuredProjects: {
        key: 'cache_featured_projects',
        ttl: 600000 // 10 minutes
    }
};

// Cache manager class
class CacheManager {
    static get(key) {
        try {
            const cached = localStorage.getItem(key);
            if (!cached) return null;

            const { data, timestamp } = JSON.parse(cached);
            const config = Object.values(CACHE_CONFIG).find(c => c.key === key);

            if (!config || Date.now() - timestamp > config.ttl) {
                localStorage.removeItem(key);
                return null;
            }

            return data;
        } catch (error) {
            console.error('Cache read error:', error);
            return null;
        }
    }

    static set(key, data) {
        try {
            const cacheData = {
                data,
                timestamp: Date.now()
            };
            localStorage.setItem(key, JSON.stringify(cacheData));
        } catch (error) {
            console.error('Cache write error:', error);
        }
    }

    static clear(key) {
        try {
            if (key) {
                localStorage.removeItem(key);
            } else {
                Object.values(CACHE_CONFIG).forEach(config => {
                    localStorage.removeItem(config.key);
                });
            }
        } catch (error) {
            console.error('Cache clear error:', error);
        }
    }
}

// Enhanced API functions with caching
const CachedAPI = {
    async getTopCreators() {
        const cached = CacheManager.get(CACHE_CONFIG.topCreators.key);
        if (cached) return cached;

        try {
            // Use API instead of StatsAPI
            const data = await API.getTopCreators(); // Assuming API.getTopCreators exists or will be added
            CacheManager.set(CACHE_CONFIG.topCreators.key, data);
            return data;
        } catch (error) {
            console.error('Error fetching top creators:', error);
            throw error;
        }
    },

    async getNearCompletionProjects() {
        const cached = CacheManager.get(CACHE_CONFIG.nearCompletionProjects.key);
        if (cached) return cached;

        try {
            // Use API instead of StatsAPI
            const data = await API.getNearCompletionProjects(); // Assuming API.getNearCompletionProjects exists
            CacheManager.set(CACHE_CONFIG.nearCompletionProjects.key, data);
            return data;
        } catch (error) {
            console.error('Error fetching near completion projects:', error);
            throw error;
        }
    },

    async getTopFunders() {
        const cached = CacheManager.get(CACHE_CONFIG.topFunders.key);
        if (cached) return cached;

        try {
            // Use API instead of StatsAPI
            const data = await API.getTopFunders(); // Assuming API.getTopFunders exists
            CacheManager.set(CACHE_CONFIG.topFunders.key, data);
            return data;
        } catch (error) {
            console.error('Error fetching top funders:', error);
            throw error;
        }
    },

    async getFeaturedProjects() {
        const cached = CacheManager.get(CACHE_CONFIG.featuredProjects.key);
        if (cached) return cached;

        try {
            // Use API instead of ProjectsAPI, assuming getProjects supports a 'featured' filter
            const data = await API.getProjects(1, 10, { featured: true }); // Adjust params as needed
            CacheManager.set(CACHE_CONFIG.featuredProjects.key, data);
            return data;
        } catch (error) {
            console.error('Error fetching featured projects:', error);
            throw error;
        }
    }
};

// Make CachedAPI globally available if needed, or export if using modules
// window.CachedAPI = CachedAPI; 
// export { CachedAPI }; // Uncomment if using modules and cache.js is imported elsewhere