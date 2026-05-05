/**
 * Profile Edit Forms Logic
 */

const mobilityForm = (initialData) => ({
    zip_code: initialData.zip_code || '',
    radius: initialData.radius || 20,
    isSaving: false,
    csrfToken: initialData.csrfToken || '',
    route: initialData.route || '',

    async save() {
        this.isSaving = true;
        try {
            const response = await fetch(this.route, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    zip_code: this.zip_code,
                    radius: this.radius
                })
            });
            if (!response.ok) throw new Error("Erreur");
            window.dispatchEvent(new CustomEvent('mobility-updated'));
        } catch (e) {
            console.error(e);
        } finally {
            setTimeout(() => { this.isSaving = false; }, 600);
        }
    }
});

const languagesForm = (initialData) => ({
    search: "",
    isSaving: false,
    selectedItems: initialData.selectedItems || [],
    allAvailable: initialData.allAvailable || [],
    csrfToken: initialData.csrfToken || '',
    route: initialData.route || '',

    get filteredAvailable() {
        const query = this.search.toLowerCase();
        return this.allAvailable.filter(item => {
            const matchesSearch = item.label.toLowerCase().includes(query) || (item.code && item.code.toLowerCase().includes(query));
            const notSelected = !this.selectedItems.find(s => s.id === item.id);
            return matchesSearch && notSelected;
        });
    },

    async save() {
        this.isSaving = true;
        try {
            const response = await fetch(this.route, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    languages: this.selectedItems.map(i => i.id),
                    levels: this.selectedItems.reduce((acc, i) => {
                        acc[i.id] = i.level;
                        return acc;
                    }, {})
                })
            });
            if (!response.ok) throw new Error("Erreur");
        } catch (e) {
            console.error(e);
        } finally {
            setTimeout(() => { this.isSaving = false; }, 600);
        }
    },

    addItem(item) {
        this.selectedItems.push({ ...item, level: "" });
        this.save();
        this.search = "";
    },

    removeItem(id) {
        this.selectedItems = this.selectedItems.filter(i => i.id !== id);
        this.save();
    }
});

const permitsForm = (initialData) => ({
    search: "",
    isSaving: false,
    selectedItems: initialData.selectedItems || [],
    allAvailable: initialData.allAvailable || [],
    csrfToken: initialData.csrfToken || '',
    route: initialData.route || '',

    get filteredAvailable() {
        const query = this.search.toLowerCase();
        return this.allAvailable.filter(item => {
            const matchesSearch = item.label.toLowerCase().includes(query);
            const notSelected = !this.selectedItems.find(s => s.id === item.id);
            return matchesSearch && notSelected;
        });
    },

    async save() {
        this.isSaving = true;
        try {
            const response = await fetch(this.route, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    permits: this.selectedItems.map(i => i.id)
                })
            });
            if (!response.ok) throw new Error("Erreur");
        } catch (e) {
            console.error(e);
        } finally {
            setTimeout(() => { this.isSaving = false; }, 600);
        }
    },

    addItem(item) {
        this.selectedItems.push(item);
        this.save();
        this.search = "";
    },

    removeItem(id) {
        this.selectedItems = this.selectedItems.filter(i => i.id !== id);
        this.save();
    }
});

export { mobilityForm, languagesForm, permitsForm };
