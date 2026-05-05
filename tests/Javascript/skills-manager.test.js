import { describe, it, expect, vi, beforeEach } from 'vitest';
import skillsManager from '../../resources/js/skills-manager';

global.fetch = vi.fn();

describe('Skills Manager Alpine Component', () => {
    let component;
    const initialData = {
        selectedSkills: [{ id: 1, label: 'PHP', level: 'expert', type: 'hard', sources: ['Experience 1'] }],
        allAvailable: [{ id: 1, label: 'PHP', type: 'hard' }, { id: 2, label: 'Laravel', type: 'hard' }],
        blacklistedSkills: [],
        csrfToken: 'test-token',
        routes: {
            sync: '/sync',
            update: '/update'
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        component = skillsManager(JSON.parse(JSON.stringify(initialData)));
        window.dispatchEvent = vi.fn();
        vi.useFakeTimers();
    });

    it('filters available skills correctly', () => {
        component.search = 'Lara';
        expect(component.filteredAvailable).toHaveLength(1);
        expect(component.filteredAvailable[0].id).toBe(2);
        
        // PHP is already selected, should not appear
        component.search = 'PHP';
        expect(component.filteredAvailable).toHaveLength(0);
    });

    it('adds a skill', () => {
        component.addSkill({ id: 2, label: 'Laravel', type: 'hard' });
        expect(component.selectedSkills).toHaveLength(2);
        expect(component.selectedSkills[1].label).toBe('Laravel');
        expect(component.search).toBe('');
    });

    it('removes a skill', () => {
        component.removeSkill(1);
        expect(component.selectedSkills).toHaveLength(0);
    });

    it('saves all skills', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        await component.saveAll();
        expect(fetch).toHaveBeenCalledWith('/update', expect.objectContaining({
            method: 'POST',
            body: expect.stringContaining('"skills":[1]')
        }));
    });

    it('blacklists a skill with confirmation', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        
        // Mock the confirm callback
        let confirmCallback;
        window.dispatchEvent.mockImplementation((e) => {
            if (e.type === 'confirm') confirmCallback = e.detail.callback;
        });

        const skill = component.selectedSkills[0];
        await component.blacklist(skill);
        
        expect(window.dispatchEvent).toHaveBeenCalledWith(expect.objectContaining({ type: 'confirm' }));
        
        await confirmCallback();
        
        expect(component.selectedSkills).toHaveLength(0);
        expect(component.blacklistedSkills).toHaveLength(1);
        expect(component.blacklistedSkills[0].id).toBe(1);
    });
});
