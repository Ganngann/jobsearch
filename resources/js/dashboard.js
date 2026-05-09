/**
 * Dashboard Alpine.js Component
 */
export default function dashboardApp(config = {}) {
    return {
        selectedId: config.initialSelectedId || null,
        previewLoading: false,
        previewHtml: '',
        filters: {
            sort: config.filters?.sort || 'score_desc',
            min_score: config.filters?.min_score || 0,
            metier_id: config.filters?.metier_id || null,
            employer_id: config.filters?.employer_id || null,
            rome: config.filters?.rome || null,
            q: config.filters?.q || ''
        },
        scores: {},
        page: 1,
        loadingMore: false,
        noMoreData: false,
        csrfToken: config.csrfToken || '',

        init() {
            window.dashboard = this;
            this.initializeScores();
            if (this.selectedId) {
                this.selectOffer(this.selectedId);
            }
        },

        initializeScores() {
            document.querySelectorAll('[data-offer-id]').forEach(el => {
                const id = el.dataset.offerId;
                this.scores[id] = {
                    data: el.dataset.preScore,
                    ia: el.dataset.aiScore === '' || el.dataset.aiScore === undefined ? null : el.dataset.aiScore,
                    vector: el.dataset.vectorScore === '' || el.dataset.vectorScore === undefined ? null : el.dataset.vectorScore
                };
            });
        },

        async selectOffer(id) {
            if (!id) return;
            this.selectedId = id;
            this.previewLoading = true;
            try {
                const res = await fetch(`/jobs/${id}/preview`);
                this.previewHtml = await res.text();
                
                // Si l'offre chargée est déjà en cours d'analyse, on relance le polling
                if (this.previewHtml.includes('Analyse IA...')) {
                    this.pollAiStatus(id);
                }
            } catch (e) {
                console.error('Failed to select offer', e);
            } finally {
                this.previewLoading = false;
            }
        },

        async startAiAnalysis(offerId) {
            console.log('Starting AI analysis for:', offerId);
            this.previewLoading = true;
            
            try {
                const response = await fetch(`/jobs/${offerId}/match`, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': this.csrfToken, 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }

                const data = await response.json();
                console.log('Analysis started:', data);

                await this.selectOffer(offerId);
                this.pollAiStatus(offerId);
            } catch (e) {
                this.previewLoading = false;
                console.error('AI Analysis failed to start', e);
                alert('Erreur lors du lancement de l\'analyse IA. Veuillez réessayer.');
            }
        },

        pollAiStatus(offerId) {
            if (this.pollInterval) clearInterval(this.pollInterval);
            
            this.pollInterval = setInterval(async () => {
                try {
                    const res = await fetch(`/jobs/${offerId}/preview?check=1`);
                    const html = await res.text();
                    
                    if (html.includes('id="ai-result-ready"') || html.includes('id="ai-result-failed"')) {
                        clearInterval(this.pollInterval);
                        console.log('AI Analysis finished for:', offerId);
                        
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const scoreEl = tempDiv.querySelector('#ai-result-ready');
                        if (scoreEl && this.scores[offerId]) {
                            this.scores[offerId].ia = scoreEl.dataset.score;
                        }

                        if (this.selectedId == offerId) {
                            this.selectOffer(offerId);
                        }
                    }
                } catch (e) {
                    console.error('Polling failed', e);
                    clearInterval(this.pollInterval);
                }
            }, 3000);
        },

        async embedJob(offerId) {
            this.previewLoading = true;
            try {
                const res = await fetch(`/jobs/${offerId}/embed`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                // Mettre à jour le score dans la liste si présent
                if (data.score !== undefined && this.scores[offerId]) {
                    this.scores[offerId].vector = data.score;
                }

                await this.selectOffer(offerId);
            } catch (e) {
                console.error('Embedding failed', e);
                // Notification supprimée (alert banni)

            } finally {
                this.previewLoading = false;
            }
        },

        async triggerTopAi() {
            this.previewLoading = true;
            try {
                const res = await fetch(`/matching/top-ai-sync`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                this.showToast(data.message, 'success');
                
                // On peut rafraîchir la liste après un court délai pour voir les premiers résultats IA s'il y en a déjà
                setTimeout(() => this.refreshList(), 5000);

            } catch (e) {
                console.error('Top AI trigger failed', e);
                this.showToast('Erreur lors du lancement de l\'IA.', 'error');
            } finally {
                this.previewLoading = false;
            }
        },

        async syncSimilarities() {
            this.previewLoading = true;
            try {
                const res = await fetch(`/matching/vector-sync`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (!res.ok) {
                    throw new Error(data.error || 'Erreur serveur');
                }

                this.refreshList();
                // Succès silencieux (la liste se rafraîchit)

            } catch (e) {
                console.error('Sync failed', e);
                // Erreur logguée en console uniquement

            } finally {
                this.previewLoading = false;
            }
        },

        updateOfferScore(offerId, score, isBlacklisted) {
            if (!this.scores[offerId]) {
                this.scores[offerId] = { data: score, ia: null };
            } else {
                this.scores[offerId].data = score;
            }
            
            const el = document.querySelector(`[data-offer-id="${offerId}"]`);
            if (el) {
                el.dataset.preScore = score;
                // Déclencher l'animation (Level 8 doc)
                const scoreDisplay = el.querySelector('.score-confort');
                if (scoreDisplay) {
                    scoreDisplay.classList.remove('animate-score-change');
                    void scoreDisplay.offsetWidth; // Force reflow
                    scoreDisplay.classList.add('animate-score-change');
                }
            }
        },

        showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border animate-toast-in ${
                type === 'success' ? 'bg-emerald-900 border-emerald-500/30 text-emerald-100' : 'bg-slate-900 border-slate-700 text-slate-100'
            }`;
            
            const icon = type === 'success' 
                ? '<svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                : '<svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

            toast.innerHTML = `
                ${icon}
                <span class="text-xs font-black uppercase tracking-widest">${message}</span>
            `;

            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-10');
                toast.style.transition = 'all 0.5s ease';
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        },

        setMetier(id) {
            this.filters.metier_id = id;
            this.filters.employer_id = null;
            this.filters.rome = null;
            this.refreshList();
        },


        setEmployer(id) {
            this.filters.employer_id = id;
            this.filters.metier_id = null;
            this.filters.rome = null;
            this.refreshList();
        },

        refreshList() {
            this.page = 1;
            this.noMoreData = false;
            this.updateUrl();
            
            const url = new URL(window.location.origin + window.location.pathname);
            Object.keys(this.filters).forEach(key => {
                const val = this.filters[key];
                if (val !== null && val !== '' && val !== 0 && val !== '0') {
                    url.searchParams.append(key, val);
                }
            });
            url.searchParams.append('partial', '1');

            fetch(url.toString())
                .then(res => res.text())
                .then(html => {
                    const container = document.getElementById('offers-container');
                    if (container) {
                        container.innerHTML = html;
                        document.getElementById('offers-scroll-container').scrollTop = 0;
                        this.initializeScores();
                    }
                });
        },

        loadMore() {
            if (this.loadingMore || this.noMoreData) return;
            
            this.loadingMore = true;
            this.page++;
            
            const url = new URL(window.location.href);
            url.searchParams.set('page', this.page);
            url.searchParams.set('partial', '1');

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                if (!html.trim()) {
                    this.noMoreData = true;
                } else {
                    const container = document.getElementById('offers-container');
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    
                    const newElements = Array.from(temp.children);
                    newElements.forEach(el => {
                        container.appendChild(el);
                        if (window.Alpine) {
                            window.Alpine.initTree(el);
                        }
                    });
                    
                    this.initializeScores();
                }
                this.loadingMore = false;
            })
            .catch(err => {
                console.error('Load more failed', err);
                this.loadingMore = false;
            });
        },

        updateUrl() {
            const url = new URL(window.location.origin + window.location.pathname);
            Object.keys(this.filters).forEach(key => {
                const val = this.filters[key];
                if (val !== null && val !== '' && val !== 0 && val !== '0') {
                    url.searchParams.append(key, val);
                }
            });
            window.history.pushState({}, '', url.toString());
        }
    };
}
