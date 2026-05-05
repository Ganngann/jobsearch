import { describe, it, expect, vi, beforeEach } from 'vitest';
import aiWizard from '../../resources/js/ai-wizard';

global.fetch = vi.fn();

describe('AI Wizard Alpine Component', () => {
    let component;
    const initialData = {
        csrfToken: 'test-token',
        routes: { analyze: '/analyze' }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        component = aiWizard(initialData);
        
        // Mock DOM for applySuggestion
        document.body.innerHTML = `
            <input id="headline" />
            <textarea id="profile_text"></textarea>
            <textarea id="aspirations"></textarea>
        `;
    });

    it('analyzes text and gets suggestions', async () => {
        const mockSuggestion = { headline: 'Dev', profile_text: 'Bio', aspirations: 'Job', skills: ['PHP'] };
        fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve(mockSuggestion)
        });

        component.text = 'My long resume text here...';
        await component.analyze();

        expect(component.loading).toBe(false);
        expect(component.suggestion).toEqual(mockSuggestion);
    });

    it('applies suggestions to DOM', () => {
        component.suggestion = { headline: 'Dev', profile_text: 'Bio', aspirations: 'Job' };
        
        component.applySuggestion();
        
        expect(document.getElementById('headline').value).toBe('Dev');
        expect(document.getElementById('profile_text').value).toBe('Bio');
        expect(document.getElementById('aspirations').value).toBe('Job');
        expect(component.suggestion).toBeNull();
    });
});
