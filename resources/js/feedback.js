export default (initialData) => ({
    open: false,
    message: '',
    type: 'feedback',
    loading: false,
    sent: false,
    hasInteracted: localStorage.getItem('feedback_interacted') === 'true',
    routes: initialData.routes || {},
    csrfToken: initialData.csrfToken || '',

    sendFeedback() {
        this.loading = true;
        this.hasInteracted = true;
        localStorage.setItem('feedback_interacted', 'true');

        return fetch(this.routes.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                message: this.message,
                type: this.type,
                page_url: window.location.href
            })
        })
        .then(res => res.json())
        .then(data => {
            this.sent = true;
            this.message = '';
        })
        .catch(err => {
            console.error('Feedback send error', err);
            // On pourrait ajouter un this.error = true ici si le template le supporte
        })
        .finally(() => {
            this.loading = false;
        });
    }
});
