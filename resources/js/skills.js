export default (initialData) => ({
    loading: false,
    search: "",
    searchResults: [],
    suggestions: [],
    activeSkills: initialData.activeSkills || [],
    neutralSkills: initialData.neutralSkills || [],
    refusedSkills: initialData.refusedSkills || [],
    routes: initialData.routes || {},
    csrfToken: initialData.csrfToken || '',
    
    async searchSkills() {
        if (this.search.length < 2) {
            this.searchResults = [];
            return;
        }
        try {
            const res = await fetch(`${this.routes.search}?q=${encodeURIComponent(this.search)}`);
            this.searchResults = await res.json();
            // Filter already present skills
            const currentIds = [...this.activeSkills, ...this.neutralSkills, ...this.refusedSkills].map(s => s.id);
            this.searchResults = this.searchResults.filter(s => !currentIds.includes(s.id));
        } catch (e) { console.error(e); }
    },

    async addFromSearch(skill) {
        await this.setStatus(skill, 'active');
        this.search = "";
        this.searchResults = [];
    },

    async fetchSuggestions() {
        this.loading = true;
        try {
            const res = await fetch(this.routes.suggest, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken }
            });
            const data = await res.json();
            this.suggestions = data.suggestions.map(s => ({ ...s, hidden: false }));
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },

    async setStatus(skill, status) {
        try {
            const res = await fetch(`/profile/skills/${skill.id}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
            });
            const data = await res.json();
            if (data.status === 'success') {
                if (skill.hidden !== undefined) {
                    skill.hidden = true;
                    setTimeout(() => {
                        this.suggestions = this.suggestions.filter(s => s.id !== skill.id);
                        this.updateLocalLists(skill, status);
                    }, 300);
                } else {
                    this.updateLocalLists(skill, status);
                }
            }
        } catch (e) {
            console.error(e);
        }
    },

    async moveTo(skill, status) {
        try {
            const res = await fetch(`/profile/skills/${skill.id}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const oldStatus = this.activeSkills.find(s => s.id === skill.id) ? 'active' : 
                                (this.neutralSkills.find(s => s.id === skill.id) ? 'neutral' : 'refused');

                this.activeSkills = this.activeSkills.filter(s => s.id !== skill.id);
                this.neutralSkills = this.neutralSkills.filter(s => s.id !== skill.id);
                this.refusedSkills = this.refusedSkills.filter(s => s.id !== skill.id);
                
                this.updateLocalLists(skill, status, oldStatus);
            }
        } catch (e) {
            console.error(e);
        }
    },

    updateLocalLists(skill, status, oldStatus = null) {
        if (oldStatus === 'active' && status !== 'active') {
            window.dispatchEvent(new CustomEvent('skill-removed'));
        }
        if (status === 'active' && oldStatus !== 'active') {
            window.dispatchEvent(new CustomEvent('skill-added'));
        }

        if (status === 'active') this.activeSkills.push(skill);
        if (status === 'neutral') this.neutralSkills.push(skill);
        if (status === 'refused') this.refusedSkills.push(skill);
    }
});
