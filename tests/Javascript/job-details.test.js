import { describe, it, expect, vi, beforeEach } from 'vitest';
import jobDetails from '../../resources/js/job-details';

global.fetch = vi.fn();
// Mock window.location
delete window.location;
window.location = { reload: vi.fn(), href: '' };

describe('Job Details Alpine Component', () => {
    let component;
    const initialData = {
        csrfToken: 'test-token'
    };

    beforeEach(() => {
        vi.clearAllMocks();
        component = jobDetails(initialData);
    });

    it('adds metier to favorites', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        
        await component.addMetier(123);
        
        expect(fetch).toHaveBeenCalledWith('/discovery/metiers/123/status', expect.objectContaining({
            method: 'POST',
            body: expect.stringContaining('"status":"favorite"')
        }));
        expect(window.location.reload).toHaveBeenCalled();
    });

    it('refuses metier', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        
        await component.refuseMetier(123);
        
        expect(fetch).toHaveBeenCalledWith('/discovery/metiers/123/status', expect.objectContaining({
            method: 'POST',
            body: expect.stringContaining('"status":"refused"')
        }));
        expect(window.location.href).toBe('/dashboard');
    });
});
