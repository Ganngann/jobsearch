import { describe, it, expect, vi, beforeEach } from 'vitest';
import discovery from '../../resources/js/discovery';

// Mock Alpine
global.Alpine = {
    store: vi.fn()
};

// Mock fetch
global.fetch = vi.fn();

describe('Discovery Alpine Store', () => {
    let store;

    beforeEach(() => {
        vi.clearAllMocks();
        discovery();
        // Get the store object passed to Alpine.store('discovery', { ... })
        store = global.Alpine.store.mock.calls[0][1];
    });

    it('initializes with correct default values', () => {
        expect(store.suggestions).toEqual([]);
        expect(store.savedMetiers).toEqual([]);
        expect(store.loading).toBe(false);
    });

    it('sets data correctly', () => {
        const mockData = {
            suggestions: [{ code: 'A123', label: 'Test Suggestion' }],
            savedMetiers: [{ id: 1, type: 'specific' }],
            config: { csrfToken: 'token' }
        };
        store.setData(mockData);
        expect(store.suggestions).toEqual(mockData.suggestions);
        expect(store.savedMetiers).toEqual(mockData.savedMetiers);
        expect(store.config).toEqual(mockData.config);
    });

    it('adds a saved specific metier', () => {
        store.savedMetiers = [];
        const item = { id: 1, type: 'specific', status: 'interested' };
        store.addSaved(item);
        expect(store.savedMetiers).toHaveLength(1);
        expect(store.savedMetiers[0]).toEqual(item);
    });

    it('updates status of an already saved metier', () => {
        const item = { id: 1, type: 'specific', status: 'interested' };
        store.savedMetiers = [item];
        const updatedItem = { id: 1, type: 'specific', status: 'validated' };
        store.addSaved(updatedItem);
        expect(store.savedMetiers).toHaveLength(1);
        expect(store.savedMetiers[0].status).toBe('validated');
    });

    it('removes a saved specific metier', () => {
        const item = { id: 1, type: 'specific' };
        store.savedMetiers = [item, { id: 2, type: 'specific' }];
        store.removeSaved(item);
        expect(store.savedMetiers).toHaveLength(1);
        expect(store.savedMetiers[0].id).toBe(2);
    });

    it('updates suggestion status for families and variants', () => {
        store.suggestions = [
            { 
                code: 'F1', 
                status: 'proposed', 
                variants: [
                    { id: 1, status: 'proposed' },
                    { id: 2, status: 'proposed' }
                ] 
            }
        ];

        // Update family status
        store.updateSuggestionStatus({ code: 'F1' }, 'interested');
        expect(store.suggestions[0].status).toBe('interested');
        expect(store.suggestions[0].variants[0].status).toBe('interested');
        expect(store.suggestions[0].variants[1].status).toBe('interested');

        // Update specific variant status
        store.updateSuggestionStatus({ id: 1 }, 'validated');
        expect(store.suggestions[0].variants[0].status).toBe('validated');
        expect(store.suggestions[0].variants[1].status).toBe('interested'); // Should remain same
    });

    it('handles successful API get', async () => {
        const mockResponse = { data: 'test' };
        fetch.mockResolvedValueOnce({
            json: () => Promise.resolve(mockResponse)
        });

        const result = await store.get('/test-url');
        expect(fetch).toHaveBeenCalledWith('/test-url', expect.any(Object));
        expect(result).toEqual(mockResponse);
    });

    it('handles API error gracefully', async () => {
        fetch.mockRejectedValueOnce(new Error('Network error'));
        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

        const result = await store.get('/test-url');
        expect(result).toBeNull();
        expect(consoleSpy).toHaveBeenCalled();
        consoleSpy.mockRestore();
    });
});
