export default (initialData) => ({
    csrfToken: initialData.csrfToken || '',
    
    async addMetier(id) {
        try {
            const response = await fetch(`/discovery/metiers/${id}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: 'favorite' })
            });
            if (response.ok) {
                window.location.reload();
            }
        } catch (e) { console.error(e); }
    },

    async refuseMetier(id) {
        try {
            const response = await fetch(`/discovery/metiers/${id}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: 'refused' })
            });
            if (response.ok) {
                window.location.href = '/dashboard';
            }
        } catch (e) { console.error(e); }
    },

    async handleSkill(skillId, status) {
        try {
            const response = await fetch(`/profile/skills/${skillId}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            if (response.ok) {
                window.location.reload();
            }
        } catch (e) { console.error(e); }
    }
});
