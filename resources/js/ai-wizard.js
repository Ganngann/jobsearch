export default (initialData) => ({
    text: '',
    loading: false,
    suggestion: null,
    routes: initialData.routes || {},
    csrfToken: initialData.csrfToken || '',
    
    async analyze() {
        this.loading = true;
        try {
            const response = await fetch(this.routes.analyze, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ text: this.text })
            });
            
            if (!response.ok) throw new Error('Erreur lors de l\'analyse');
            
            this.suggestion = await response.json();
        } catch (error) {
            console.error('Erreur:', error.message);
        } finally {
            this.loading = false;
        }
    },

    applySuggestion() {
        // Update DOM elements directly if they exist
        const fields = ['headline', 'profile_text', 'aspirations'];
        fields.forEach(field => {
            const el = document.getElementById(field);
            if (el && this.suggestion[field]) {
                el.value = this.suggestion[field];
                el.dispatchEvent(new Event('input'));
            }
        });
        
        this.suggestion = null;
        this.text = '';
    }
});
