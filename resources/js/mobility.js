export default (initialData) => ({
    zip_code: initialData.zip_code || '',
    radius: initialData.radius || 20,
    permits: initialData.permits || [],
    nonePermitId: initialData.nonePermitId || 0,
    routes: initialData.routes || {},
    csrfToken: initialData.csrfToken || '',
    isSaving: false,
    showSuccess: false,

    togglePermit(id) {
        if (id === this.nonePermitId) {
            this.permits = this.permits.includes(id) ? [] : [id];
        } else {
            if (this.permits.includes(id)) {
                this.permits = this.permits.filter(p => p !== id);
            } else {
                this.permits = this.permits.filter(p => p !== this.nonePermitId);
                this.permits.push(id);
            }
        }
        this.save();
    },

    async save() {
        this.isSaving = true;
        try {
            const response = await fetch(this.routes.update, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'PATCH',
                    zip_code: this.zip_code,
                    radius: this.radius,
                    permits: this.permits
                })
            });
            if (!response.ok) throw new Error('Erreur');
            
            window.dispatchEvent(new CustomEvent('mobility-updated'));
            this.showSuccess = true;
            setTimeout(() => { this.showSuccess = false; }, 3000);
        } catch (e) {
            console.error(e);
        } finally {
            setTimeout(() => { this.isSaving = false; }, 600);
        }
    }
});
