import { describe, it, expect, vi, beforeEach } from 'vitest';
import mobilityApp from '../../resources/js/mobility';

// Mock fetch
global.fetch = vi.fn();

describe('Mobility Alpine Component', () => {
    let component;
    const initialData = {
        zip_code: '5000',
        radius: 30,
        permits: [1],
        nonePermitId: 99,
        csrfToken: 'test-token',
        routes: {
            update: '/profile/mobility'
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        // Use a fresh copy for each test
        const testData = JSON.parse(JSON.stringify(initialData));
        component = mobilityApp(testData);
        
        // Mock window.dispatchEvent
        window.dispatchEvent = vi.fn();
        
        // Mock setTimeout
        vi.useFakeTimers();
    });

    it('initializes with correct data', () => {
        expect(component.zip_code).toBe('5000');
        expect(component.radius).toBe(30);
        expect(component.permits).toEqual([1]);
    });

    it('toggles non-NONE permit', async () => {
        const mockResponse = { ok: true };
        fetch.mockResolvedValue(mockResponse);

        // Remove existing
        await component.togglePermit(1);
        expect(component.permits).toEqual([]);
        
        // Add new
        await component.togglePermit(2);
        expect(component.permits).toEqual([2]);
        expect(fetch).toHaveBeenCalled();
    });

    it('toggles NONE permit clears others', async () => {
        const mockResponse = { ok: true };
        fetch.mockResolvedValue(mockResponse);

        component.permits = [1, 2];
        await component.togglePermit(99); // NONE
        expect(component.permits).toEqual([99]);
        
        await component.togglePermit(99); // Toggle NONE off
        expect(component.permits).toEqual([]);
    });

    it('adding other permit clears NONE', async () => {
        const mockResponse = { ok: true };
        fetch.mockResolvedValue(mockResponse);

        component.permits = [99];
        await component.togglePermit(1);
        expect(component.permits).toEqual([1]);
    });

    it('saves data and shows success message', async () => {
        const mockResponse = { ok: true };
        fetch.mockResolvedValue(mockResponse);

        await component.save();

        expect(component.isSaving).toBe(true);
        expect(fetch).toHaveBeenCalledWith('/profile/mobility', expect.objectContaining({
            method: 'POST',
            body: expect.stringContaining('"zip_code":"5000"')
        }));

        vi.advanceTimersByTime(600);
        expect(component.isSaving).toBe(false);
        expect(component.showSuccess).toBe(true);
        expect(window.dispatchEvent).toHaveBeenCalledWith(expect.any(CustomEvent));

        vi.advanceTimersByTime(3000);
        expect(component.showSuccess).toBe(false);
    });

    it('handles save error', async () => {
        fetch.mockRejectedValue(new Error('API Fail'));
        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

        await component.save();

        expect(consoleSpy).toHaveBeenCalled();
        consoleSpy.mockRestore();
    });
});
