export default (initialData) => ({
    facts: initialData.facts || [],
    editingId: null,
    editContent: '',
    csrfToken: initialData.csrfToken || '',

    editFact(fact) {
        this.editingId = fact.id;
        this.editContent = fact.content;
    },

    notify(message, type = 'success') {
        window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
    },

    async confirmDelete(id) {
        window.dispatchEvent(new CustomEvent('confirm', { 
            detail: { 
                title: 'Supprimer ce récit ?', 
                message: 'Cette action est irréversible et retirera les compétences associées.',
                callback: () => this.deleteFact(id)
            } 
        }));
    },

    async updateFact(fact) {
        try {
            const response = await fetch(`/profile/builder/facts/${fact.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ content: this.editContent })
            });
            if (response.ok) {
                fact.content = this.editContent;
                this.editingId = null;
                this.notify('Récit mis à jour');
            }
        } catch (e) { this.notify('Erreur lors de la mise à jour', 'error'); }
    },

    async deleteFact(id) {
        try {
            const response = await fetch(`/profile/builder/facts/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
            });
            if (response.ok) {
                this.facts = this.facts.filter(f => f.id !== id);
                this.notify('Récit supprimé');
            }
        } catch (e) { this.notify('Erreur lors de la suppression', 'error'); }
    },
});
