export default (initialData) => ({
    messages: initialData.messages || [],
    facts: initialData.facts || [],
    projects: initialData.projects || [],
    certifications: initialData.certifications || [],
    interests: initialData.interests || [],
    volunteer_experiences: initialData.volunteer_experiences || [],
    all_experiences: initialData.all_experiences || [],
    all_educations: initialData.all_educations || [],
    activeSessions: initialData.activeSessions || [],
    archivedSessions: initialData.archivedSessions || [],
    currentSessionId: initialData.currentSessionId,
    user: initialData.user,
    skills: initialData.skills || [],
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
        return this.facts.filter(f => f.status === 'validated' || f.proposed_action || f.session_id === this.currentSessionId);
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

            // Update all data arrays
            this.facts = data.facts;
            this.projects = data.projects;
            this.certifications = data.certifications;
            this.interests = data.interests;
            this.volunteer_experiences = data.volunteer_experiences;
            this.all_experiences = data.all_experiences;
            this.all_educations = data.all_educations;
            this.skills = data.skills;
            this.user = data.user;
            this.activeSessions = data.activeSessions;
            this.archivedSessions = data.archivedSessions;
            
            this.scrollToBottom();
        } catch (error) {
            console.error('Error:', error);
        } finally {
            this.isTyping = false;
            this.$nextTick(() => this.$refs.messageInput.focus());
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
                const fact = this.facts.find(f => f.id === id);
                if (fact) {
                    if (fact.proposed_action === 'update') {
                        fact.content = fact.proposed_content;
                    }
                    if (fact.proposed_action === 'delete') {
                        this.facts = this.facts.filter(f => f.id !== id);
                    } else {
                        fact.proposed_action = null;
                        fact.proposed_content = null;
                        fact.status = 'validated';
                    }
                }
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
                 const fact = this.facts.find(f => f.id === id);
                 if (fact && fact.proposed_action === 'add') {
                     this.facts = this.facts.filter(f => f.id !== id);
                 } else if (fact) {
                     fact.proposed_action = null;
                     fact.proposed_content = null;
                 }
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
            if ((await response.json()).success) {
                let list = this.getItemList(type);
                const item = list.find(i => i.id === id);
                if (item) {
                    if (item.proposed_action === 'update' && item.proposed_data) {
                        Object.assign(item, item.proposed_data);
                    } else if (item.proposed_action === 'delete') {
                        this.setItemList(type, list.filter(i => i.id !== id));
                        return;
                    }
                    item.status = 'validated';
                    item.proposed_action = null;
                    item.proposed_data = null;
                }
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
        return [];
    },

    setItemList(type, newList) {
        if (type === 'experience') this.all_experiences = newList;
        else if (type === 'education') this.all_educations = newList;
        else if (type === 'project') this.projects = newList;
        else if (type === 'certification') this.certifications = newList;
        else if (type === 'volunteer') this.volunteer_experiences = newList;
        else if (type === 'interest') this.interests = newList;
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
            'fact': 'facts'
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
            'fact': 'facts'
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
            'fact': 'facts'
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
            'fact': 'facts'
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

    async acceptProposal(fact) {
        this.acceptFact(fact.id);
    },

    async rejectProposal(fact) {
        this.rejectFact(fact.id);
    }
});
