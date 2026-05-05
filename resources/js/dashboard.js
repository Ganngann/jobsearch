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
                    ia: el.dataset.aiScore === '' || el.dataset.aiScore === undefined ? null : el.dataset.aiScore
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

        updateOfferScore(offerId, score, isBlacklisted) {
            if (!this.scores[offerId]) {
                this.scores[offerId] = { data: score, ia: null };
            } else {
                this.scores[offerId].data = score;
            }
            
            const el = document.querySelector(`[data-offer-id="${offerId}"]`);
            if (el) el.dataset.preScore = score;
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
