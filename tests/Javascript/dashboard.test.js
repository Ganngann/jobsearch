import { describe, it, expect, vi, beforeEach } from 'vitest';
import dashboardApp from '../../resources/js/dashboard';

// Mock fetch
global.fetch = vi.fn();

describe('Dashboard Alpine Component', () => {
    let component;

    beforeEach(() => {
        vi.clearAllMocks();
        component = dashboardApp({
            initialSelectedId: '123',
            csrfToken: 'test-token',
            filters: {
                sort: 'recent',
                min_score: 50
            }
        });
        
        // Mock DOM elements needed by the component
        document.body.innerHTML = `
            <div id="offers-container"></div>
            <div id="offers-scroll-container"></div>
            <div data-offer-id="123" data-pre-score="80" data-ai-score="75"></div>
        `;
    });

    it('initializes with correct default values', () => {
        expect(component.selectedId).toBe('123');
        expect(component.filters.sort).toBe('recent');
        expect(component.filters.min_score).toBe(50);
        expect(component.previewLoading).toBe(false);
    });

    it('initializes scores from DOM', () => {
        component.initializeScores();
        expect(component.scores['123']).toBeDefined();
        expect(component.scores['123'].data).toBe('80');
        expect(component.scores['123'].ia).toBe('75');
    });

    it('selects an offer and fetches preview', async () => {
        const mockResponse = {
            text: () => Promise.resolve('<div>Preview Content</div>')
        };
        fetch.mockResolvedValue(mockResponse);

        await component.selectOffer('456');

        expect(component.selectedId).toBe('456');
        expect(fetch).toHaveBeenCalledWith('/jobs/456/preview');
        expect(component.previewHtml).toBe('<div>Preview Content</div>');
    });

    it('updates filters and refreshes list', () => {
        // Mock URL and history
        global.window = {
            location: {
                origin: 'http://localhost',
                pathname: '/dashboard',
                href: 'http://localhost/dashboard'
            },
            history: {
                pushState: vi.fn()
            }
        };

        component.setMetier(10);
        
        expect(component.filters.metier_id).toBe(10);
        expect(component.filters.employer_id).toBe(null);
        expect(component.page).toBe(1);
    });

    it('handles AI analysis start', async () => {
        const mockResponse = {
            ok: true,
            json: () => Promise.resolve({ status: 'started' })
        };
        fetch.mockResolvedValueOnce(mockResponse); // startAiAnalysis
        fetch.mockResolvedValueOnce({ text: () => Promise.resolve('Analyse IA...') }); // selectOffer

        await component.startAiAnalysis('123');

        expect(fetch).toHaveBeenCalledWith('/jobs/123/match', expect.objectContaining({
            method: 'POST',
            headers: expect.objectContaining({
                'X-CSRF-TOKEN': 'test-token'
            })
        }));
        expect(component.previewLoading).toBe(false);
    });
});
