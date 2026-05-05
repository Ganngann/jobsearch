export default (initialData) => ({
    search: "",
    isSaving: false,
    isSyncing: false,
    selectedSkills: initialData.selectedSkills || [],
    allAvailable: initialData.allAvailable || [],
    blacklistedSkills: initialData.blacklistedSkills || [],
    routes: initialData.routes || {},
    csrfToken: initialData.csrfToken || '',

    get filteredAvailable() {
        if (this.search.length < 2) return [];
        const query = this.search.toLowerCase();
        return this.allAvailable.filter(skill => {
            const matchesSearch = skill.label.toLowerCase().includes(query);
            const notSelected = !this.selectedSkills.find(s => s.id === skill.id);
            const notBlacklisted = !this.blacklistedSkills.find(s => s.id === skill.id);
            return matchesSearch && notSelected && notBlacklisted;
        }).slice(0, 8);
    },

    addSkill(skill) {
        this.selectedSkills.push({
            id: skill.id,
            label: skill.label,
            level: 'beginner',
            type: skill.type,
            sources: []
        });
        this.search = "";
    },

    removeSkill(id) {
        this.selectedSkills = this.selectedSkills.filter(s => s.id !== id);
    },

    notify(message, type = 'success') {
        window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
    },

    async syncSkills() {
        if (this.isSyncing) return;
        this.isSyncing = true;
        try {
            const response = await fetch(this.routes.sync, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": this.csrfToken, "Accept": "application/json" }
            });
            const data = await response.json();
            if (data.success) {
                this.notify(data.message);
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (e) { this.notify('Erreur lors de la synchronisation', 'error'); }
        finally { this.isSyncing = false; }
    },

    async saveAll() {
        this.isSaving = true;
        try {
            const response = await fetch(this.routes.update, {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": this.csrfToken, "Accept": "application/json" },
                body: JSON.stringify({
                    skills: this.selectedSkills.map(s => s.id),
                    levels: this.selectedSkills.reduce((acc, s) => ({ ...acc, [s.id]: s.level }), {})
                })
            });
            if (response.ok) {
                this.notify('Compétences enregistrées');
            }
        } catch (e) { this.notify('Erreur lors de l\'enregistrement', 'error'); }
        finally { this.isSaving = false; }
    },

    async blacklist(skill) {
        window.dispatchEvent(new CustomEvent('confirm', { 
            detail: { 
                title: `Blacklister '${skill.label}' ?`, 
                message: 'Elle sera retirée de votre profil et ne sera plus jamais suggérée par l\'IA.',
                callback: async () => {
                    try {
                        const response = await fetch(`/profile/skills/${skill.id}/blacklist`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                        });
                        if (response.ok) {
                            this.selectedSkills = this.selectedSkills.filter(s => s.id !== skill.id);
                            this.blacklistedSkills.push({ id: skill.id, label: skill.label });
                            this.notify(`'${skill.label}' a été blacklistée`);
                        }
                    } catch (e) { this.notify('Erreur lors du blacklistage', 'error'); }
                }
            } 
        }));
    },

    async unblacklist(skill) {
        try {
            const response = await fetch(`/profile/skills/${skill.id}/blacklist`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
            });
            if (response.ok) {
                this.blacklistedSkills = this.blacklistedSkills.filter(s => s.id !== skill.id);
                this.notify(`'${skill.label}' a été retirée de la blacklist`);
            }
        } catch (e) { this.notify('Erreur lors de l\'opération', 'error'); }
    }
});
