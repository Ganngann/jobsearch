export default (initialData) => ({
    messages: initialData.messages || [],
    facts: initialData.facts || [],
    projects: initialData.projects || [],
    certifications: initialData.certifications || [],
    interests: initialData.interests || [],
    volunteer_experiences: initialData.volunteer_experiences || [],
    all_experiences: initialData.all_experiences || [],
    all_educations: initialData.all_educations || [],
    languages: initialData.languages || [],
    activeSessions: initialData.activeSessions || [],
    archivedSessions: initialData.archivedSessions || [],
    currentSessionId: initialData.currentSessionId,
    user: initialData.user,
    stats: initialData.stats || {},
    skills: initialData.skills || [],
    allAvailableLanguages: initialData.allAvailableLanguages || [],
    routes: initialData.routes || {},
    
    newMessage: '',
    isTyping: false,
    isSyncing: false,
    showAllFacts: false,
    showArchives: false,
    editingItem: { type: null, id: null },
    editingData: {},

    get filteredFacts() {
        if (this.showAllFacts) return this.facts;
        return this.facts.filter(f => f.status === 'validated' || f.proposed_action || f.session_id === this.currentSessionId || f._isNew);
    },

    get pendingChangesCount() {
        let count = 0;
        const collections = [
            this.facts, this.all_experiences, this.all_educations, 
            this.projects, this.certifications, this.volunteer_experiences,
            this.interests, this.languages
        ];
        collections.forEach(col => {
            if (!col) return;
            count += col.filter(item => item.proposed_action || item.status === 'proposed' || item.status === 'draft').length;
        });
        return count;
    },

    init() {
        this.scrollToBottom();
        this.$nextTick(() => this.$refs.messageInput.focus());
    },

    scrollToBottom() {
        setTimeout(() => {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        }, 100);
    },

    scrollToFirstSuggestion() {
        setTimeout(() => {
            const firstSuggestion = document.querySelector('.cv-item-draft, .animate-pulse-update');
            if (firstSuggestion) {
                firstSuggestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 300);
    },

    async sendMessage() {
        const message = this.newMessage.trim();
        if (!message) return;

        this.newMessage = '';
        this.messages.push({ id: Date.now(), role: 'user', content: message });
        this.scrollToBottom();
        this.isTyping = true;

        try {
            const response = await fetch(this.routes.message, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            this.messages.push({ id: Date.now(), role: 'assistant', content: data.reply });

            this.updateAllData(data);
            
            this.scrollToBottom();
            if (this.pendingChangesCount > 0) {
                this.scrollToFirstSuggestion();
            }
        } catch (error) {
            console.error('Error:', error);
            this.messages.push({ 
                id: Date.now(), 
                role: 'assistant', 
                content: "Désolé, j'ai rencontré un petit problème technique en traitant ta demande. Peux-tu réessayer dans un instant ?" 
            });
        } finally {
            this.isTyping = false;
            this.$nextTick(() => this.$refs.messageInput.focus());
        }
    },

    async uploadDocument(event) {
        const file = event.target.files[0];
        if (!file) return;

        this.isTyping = true;
        this.messages.push({ id: Date.now(), role: 'user', content: `Envoi du document : ${file.name}...` });
        this.scrollToBottom();

        const formData = new FormData();
        formData.append('document', file);

        try {
            const response = await fetch(this.routes.upload, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await response.json();
            if (data.error) {
                this.messages.push({ id: Date.now(), role: 'assistant', content: `Erreur : ${data.error}` });
            } else {
                this.messages.push({ id: Date.now(), role: 'assistant', content: data.reply });

                this.updateAllData(data);
            }
            
            this.scrollToBottom();
            if (this.pendingChangesCount > 0) {
                this.scrollToFirstSuggestion();
            }
        } catch (error) {
            console.error('Error:', error);
            this.messages.push({ id: Date.now(), role: 'assistant', content: "Désolé, une erreur est survenue lors de l'envoi du document." });
        } finally {
            this.isTyping = false;
            event.target.value = ''; // Reset input
        }
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR');
    },

    formatDateForInput(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    },

    renderDiff(oldText, newText) {
        if (!oldText) return `<span class="diff-added">${newText || ''}</span>`;
        if (!newText) return `<span class="diff-deleted">${oldText || ''}</span>`;
        if (oldText === newText) return oldText;

        const words1 = (oldText || '').toString().split(/\s+/).filter(w => w.length > 0);
        const words2 = (newText || '').toString().split(/\s+/).filter(w => w.length > 0);
        let i = 0, j = 0;
        let html = '';

        while (i < words1.length || j < words2.length) {
            if (i < words1.length && j < words2.length && words1[i] === words2[j]) {
                html += words1[i] + ' ';
                i++; j++;
            } else {
                let foundIn2 = -1;
                for (let k = j + 1; k < Math.min(j + 10, words2.length); k++) {
                    if (i < words1.length && words1[i] === words2[k]) {
                        foundIn2 = k;
                        break;
                    }
                }
                
                if (foundIn2 !== -1) {
                    while (j < foundIn2) {
                        html += `<span class="diff-added">${words2[j]}</span> `;
                        j++;
                    }
                } else if (i < words1.length) {
                    html += `<span class="diff-deleted">${words1[i]}</span> `;
                    i++;
                } else if (j < words2.length) {
                    html += `<span class="diff-added">${words2[j]}</span> `;
                    j++;
                }
            }
        }
        return html;
    },

    async acceptFact(id) {
        try {
            const response = await fetch(`/profile/builder/facts/${id}/accept`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) {
                this.updateAllData(data);
            }
        } catch (error) { console.error(error); }
    },

    async rejectFact(id) {
        try {
            const response = await fetch(`/profile/builder/facts/${id}/reject`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) {
                 this.updateAllData(data);
            }
        } catch (error) { console.error(error); }
    },

    async rejectItem(type, id) {
        try {
            const response = await fetch(`/profile/builder/item/${type}/${id}/reject`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            if ((await response.json()).success) {
                let list = this.getItemList(type);
                const index = list.findIndex(i => i.id === id);
                if (index !== -1) {
                    if (list[index].proposed_action === 'add') {
                        this.setItemList(type, list.filter(i => i.id !== id));
                    } else {
                        list[index].proposed_action = null;
                        list[index].proposed_data = null;
                    }
                }
            }
        } catch (error) { console.error(error); }
    },

    async acceptItem(type, id) {
        try {
            const response = await fetch(`/profile/builder/item/${type}/${id}/accept`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await response.json();
            if (data.success) {
                this.updateAllData(data);
            }
        } catch (error) { console.error(error); }
    },

    getItemList(type) {
        if (type === 'experience') return this.all_experiences;
        if (type === 'education') return this.all_educations;
        if (type === 'project') return this.projects;
        if (type === 'certification') return this.certifications;
        if (type === 'volunteer') return this.volunteer_experiences;
        if (type === 'interest') return this.interests;
        if (type === 'language') return this.languages;
        return [];
    },

    setItemList(type, newList) {
        if (type === 'experience') this.all_experiences = newList;
        else if (type === 'education') this.all_educations = newList;
        else if (type === 'project') this.projects = newList;
        else if (type === 'certification') this.certifications = newList;
        else if (type === 'volunteer') this.volunteer_experiences = newList;
        else if (type === 'interest') this.interests = newList;
        else if (type === 'language') this.languages = newList;
    },

    async deleteItem(type, id) {
        try {
            const response = await fetch(`/profile/builder/item/${type}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            if (data.success) {
                this.removeLocalItem(type, id);
            }
        } catch (error) { console.error(error); }
    },

    refreshLocalItemStatus(type, id, status) {
        const arrays = {
            'experience': 'all_experiences',
            'education': 'all_educations',
            'project': 'projects',
            'interest': 'interests',
            'certification': 'certifications',
            'volunteer': 'volunteer_experiences',
            'skill': 'skills',
            'fact': 'facts',
            'language': 'languages'
        };
        const arrayName = arrays[type];
        if (arrayName) {
            this[arrayName] = this[arrayName].map(item => item.id === id ? { ...item, status } : item);
        }
    },

    removeLocalItem(type, id) {
        const arrays = {
            'experience': 'all_experiences',
            'education': 'all_educations',
            'project': 'projects',
            'interest': 'interests',
            'certification': 'certifications',
            'volunteer': 'volunteer_experiences',
            'skill': 'skills',
            'fact': 'facts',
            'language': 'languages'
        };
        const arrayName = arrays[type];
        if (arrayName) {
            this[arrayName] = this[arrayName].filter(item => item.id !== id);
        }
    },

    async toggleArchive(sessionId) {
        try {
            const response = await fetch(`/profile/builder/sessions/${sessionId}/archive`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            this.activeSessions = data.activeSessions;
            this.archivedSessions = data.archivedSessions;
        } catch (error) { console.error(error); }
    },

    startEditing(type, item) {
        this.editingItem = { type, id: item.id };
        this.editingData = { ...item };
        
        for (const key in this.editingData) {
            if (key.endsWith('_date') && this.editingData[key]) {
                this.editingData[key] = this.formatDateForInput(this.editingData[key]);
            }
        }
    },

    startEditingUser() {
        this.editingItem = { type: 'user', id: this.user.id };
        this.editingData = { ...this.user, links: [...(this.user.links || [])] };
        if (this.editingData.birth_date) {
            this.editingData.birth_date = this.formatDateForInput(this.editingData.birth_date);
        }
    },

    addLink() {
        if (!this.editingData.links) this.editingData.links = [];
        this.editingData.links.push({ label: '', url: '' });
    },

    removeLink(index) {
        this.editingData.links.splice(index, 1);
    },

    async saveUserEdit() {
        try {
            const response = await fetch(`/profile/builder/item/user/${this.user.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.editingData)
            });
            const data = await response.json();
            if (data.success) {
                this.user = data.item;
                this.editingItem = { type: null, id: null };
            }
        } catch (error) { console.error(error); }
    },

    addLocalItem(type, newItem) {
        const arrays = {
            'experience': 'all_experiences',
            'education': 'all_educations',
            'project': 'projects',
            'interest': 'interests',
            'certification': 'certifications',
            'volunteer': 'volunteer_experiences',
            'skill': 'skills',
            'fact': 'facts',
            'language': 'languages'
        };
        const arrayName = arrays[type];
        if (arrayName) {
            this[arrayName].push(newItem);
        }
    },

    startCreating(type) {
        const newItem = { id: 'new', _isNew: true };
        if (type === 'fact') {
            newItem.category = 'VALEURS';
        } else if (type === 'language') {
            newItem.label = 'Français';
            newItem.level = 'Intermédiaire';
        }
        this.addLocalItem(type, newItem);
        this.editingItem = { type, id: 'new' };
        this.editingData = { ...newItem };
    },

    cancelEdit() {
        if (this.editingItem.id === 'new') {
            this.removeLocalItem(this.editingItem.type, 'new');
        }
        this.editingItem = { type: null, id: null };
    },

    async saveManualEdit() {
        const { type, id } = this.editingItem;
        const isNew = id === 'new';
        const method = isNew ? 'POST' : 'PATCH';
        const url = isNew ? `/profile/builder/item/${type}` : `/profile/builder/item/${type}/${id}`;
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.editingData)
            });
            const data = await response.json();
            if (data.success) {
                if (isNew) {
                    this.removeLocalItem(type, 'new');
                    this.addLocalItem(type, data.item);
                } else {
                    this.refreshLocalItem(type, id, data.item);
                }
                this.editingItem = { type: null, id: null };
            }
        } catch (error) { console.error(error); }
    },

    refreshLocalItem(type, id, newItem) {
        const arrays = {
            'experience': 'all_experiences',
            'education': 'all_educations',
            'project': 'projects',
            'interest': 'interests',
            'certification': 'certifications',
            'volunteer': 'volunteer_experiences',
            'skill': 'skills',
            'fact': 'facts',
            'language': 'languages'
        };
        const arrayName = arrays[type];
        if (arrayName) {
            this[arrayName] = this[arrayName].map(item => item.id === id ? newItem : item);
        }
    },

    async syncSkills() {
        this.isSyncing = true;
        try {
            const response = await fetch(this.routes.syncSkills, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            this.facts = data.facts;
        } catch (error) { console.error(error); } finally {
            this.isSyncing = false;
        }
    },

    async embedProfile() {
        this.isTyping = true;
        try {
            const response = await fetch('/profile/embed', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Erreur serveur');
            }

            // Succès
        } catch (error) {
            console.error('Embedding failed', error);
            // Erreur silencieuse
        } finally {
            this.isTyping = false;
        }
    },

    async acceptProposal(fact) {
        this.acceptFact(fact.id);
    },

    async rejectProposal(fact) {
        this.rejectFact(fact.id);
    },

    updateAllData(data) {
        if (!data) return;
        console.log("Updating profile data...", data.stats);
        this.facts = data.facts || this.facts;
        this.projects = data.projects || this.projects;
        this.certifications = data.certifications || this.certifications;
        this.interests = data.interests || this.interests;
        this.volunteer_experiences = data.volunteer_experiences || this.volunteer_experiences;
        this.all_experiences = data.all_experiences || this.all_experiences;
        this.all_educations = data.all_educations || this.all_educations;
        this.languages = data.languages || this.languages;
        this.skills = data.skills || this.skills;
        this.user = data.user || this.user;
        this.stats = data.stats || this.stats;
        this.activeSessions = data.activeSessions || this.activeSessions;
        this.archivedSessions = data.archivedSessions || this.archivedSessions;
    }
});
