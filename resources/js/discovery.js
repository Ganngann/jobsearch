export default () => {
     Alpine.store('discovery', {
        suggestions: [],
        savedMetiers: [],
        config: {},
        loading: false,
        loadingMessage: '',
        errorMessage: '',
        
        setData(data) {
            if (!data) return;
            this.suggestions = data.suggestions || [];
            this.savedMetiers = data.savedMetiers || [];
            this.config = data.config || {};
        },

        // API Helpers
        async post(url, body = {}) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                return await res.json();
            } catch (e) {
                console.error(`API Post Error (${url}):`, e);
                return null;
            }
        },

        async get(url) {
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                return await res.json();
            } catch (e) {
                console.error(`API Get Error (${url}):`, e);
                return null;
            }
        },

        // State Management
        addSaved(item) {
            const index = item.type === 'specific' 
                ? this.savedMetiers.findIndex(m => m.id === item.id && m.type === 'specific')
                : this.savedMetiers.findIndex(m => m.code === item.code && m.type === 'family');

            if (index === -1) {
                this.savedMetiers = [...this.savedMetiers, item];
            } else {
                // Update status if it changed
                this.savedMetiers[index].status = item.status;
            }
        },

        removeSaved(item) {
            this.savedMetiers = this.savedMetiers.filter(m => {
                if (item.type === 'specific') return !(m.id === item.id && m.type === 'specific');
                return !(m.code === item.code && m.type === 'family');
            });
        },

        updateSuggestionStatus(item, status) {
            // Update main suggestions
            this.suggestions.forEach(s => {
                if (s.code === item.code) {
                    s.status = status;
                    if (s.variants) s.variants.forEach(v => v.status = status);
                }
                // Check variants
                if (s.variants) {
                    const v = s.variants.find(varItem => varItem.id === item.id);
                    if (v) v.status = status;
                }
            });
        }
    });
}
