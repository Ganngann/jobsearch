import { describe, it, expect, vi, beforeEach } from 'vitest';
import manageFacts from '../../resources/js/manage-facts';

global.fetch = vi.fn();

describe('Manage Facts Alpine Component', () => {
    let component;
    const initialData = {
        facts: [{ id: 1, content: 'Test fact', category: 'Exp' }],
        csrfToken: 'test-token'
    };

    beforeEach(() => {
        vi.clearAllMocks();
        component = manageFacts(JSON.parse(JSON.stringify(initialData)));
        window.dispatchEvent = vi.fn();
    });

    it('sets up editing state', () => {
        component.editFact(component.facts[0]);
        expect(component.editingId).toBe(1);
        expect(component.editContent).toBe('Test fact');
    });

    it('updates a fact', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        component.editFact(component.facts[0]);
        component.editContent = 'Updated content';
        
        await component.updateFact(component.facts[0]);
        
        expect(component.facts[0].content).toBe('Updated content');
        expect(component.editingId).toBeNull();
        expect(window.dispatchEvent).toHaveBeenCalledWith(expect.any(CustomEvent));
    });

    it('deletes a fact after confirmation', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        
        let confirmCallback;
        window.dispatchEvent.mockImplementation((e) => {
            if (e.type === 'confirm') confirmCallback = e.detail.callback;
        });

        await component.confirmDelete(1);
        await confirmCallback();
        
        expect(component.facts).toHaveLength(0);
        expect(fetch).toHaveBeenCalledWith('/profile/builder/facts/1', expect.objectContaining({
            method: 'DELETE'
        }));
    });
});
