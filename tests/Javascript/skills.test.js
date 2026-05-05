import { describe, it, expect, vi, beforeEach } from 'vitest';
import skillApp from '../../resources/js/skills';

// Mock fetch
global.fetch = vi.fn();

describe('Skill Atelier Alpine Component', () => {
    let component;
    const initialData = {
        activeSkills: [{ id: 1, label: 'PHP' }],
        neutralSkills: [],
        refusedSkills: [],
        csrfToken: 'test-token',
        routes: {
            search: '/api/skills/search',
            suggest: '/profile/skills/suggest'
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        const testData = JSON.parse(JSON.stringify(initialData));
        component = skillApp(testData);
        
        window.dispatchEvent = vi.fn();
        vi.useFakeTimers();
    });

    it('initializes with correct data', () => {
        expect(component.activeSkills).toHaveLength(1);
        expect(component.activeSkills[0].label).toBe('PHP');
    });

    it('searches skills and filters duplicates', async () => {
        const mockResults = [
            { id: 1, label: 'PHP' }, // Duplicate
            { id: 2, label: 'Laravel' }
        ];
        fetch.mockResolvedValueOnce({
            json: () => Promise.resolve(mockResults)
        });

        component.search = 'Lara';
        await component.searchSkills();

        expect(component.searchResults).toHaveLength(1);
        expect(component.searchResults[0].id).toBe(2);
    });

    it('fetches suggestions', async () => {
        const mockSuggestions = [
            { id: 10, label: 'Vue.js', reason: 'Common with PHP' }
        ];
        fetch.mockResolvedValueOnce({
            json: () => Promise.resolve({ suggestions: mockSuggestions })
        });

        await component.fetchSuggestions();

        expect(component.loading).toBe(false);
        expect(component.suggestions).toHaveLength(1);
        expect(component.suggestions[0].label).toBe('Vue.js');
    });

    it('sets status for a suggested skill', async () => {
        fetch.mockResolvedValue({
            json: () => Promise.resolve({ status: 'success' })
        });

        const skill = { id: 10, label: 'Vue.js', hidden: false };
        component.suggestions = [skill];
        
        await component.setStatus(skill, 'active');
        
        expect(skill.hidden).toBe(true);
        vi.advanceTimersByTime(300);
        
        expect(component.suggestions).toHaveLength(0);
        expect(component.activeSkills.find(s => s.id === 10)).toBeDefined();
        expect(window.dispatchEvent).toHaveBeenCalledWith(expect.any(CustomEvent));
    });

    it('moves skill between lists', async () => {
        fetch.mockResolvedValue({
            json: () => Promise.resolve({ status: 'success' })
        });

        const skill = component.activeSkills[0]; // PHP
        await component.moveTo(skill, 'neutral');

        expect(component.activeSkills).toHaveLength(0);
        expect(component.neutralSkills).toHaveLength(1);
        expect(component.neutralSkills[0].id).toBe(1);
        expect(window.dispatchEvent).toHaveBeenCalled(); // skill-removed
    });

    it('handles search error', async () => {
        fetch.mockRejectedValue(new Error('API Fail'));
        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

        component.search = 'error';
        await component.searchSkills();

        expect(consoleSpy).toHaveBeenCalled();
        consoleSpy.mockRestore();
    });
});
