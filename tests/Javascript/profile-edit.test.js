import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mobilityForm, languagesForm, permitsForm } from '../../resources/js/profile-edit.js';

// Mock fetch and dispatchEvent
global.fetch = vi.fn();
window.dispatchEvent = vi.fn();

describe('Profile Edit Forms Logic', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('mobilityForm', () => {
        it('initializes with default data', () => {
            const form = mobilityForm({});
            expect(form.zip_code).toBe('');
            expect(form.radius).toBe(20);
            expect(form.isSaving).toBe(false);
            expect(form.csrfToken).toBe('');
            expect(form.route).toBe('');
        });

        it('initializes with custom data', () => {
            const form = mobilityForm({
                zip_code: '75001',
                radius: 50,
                csrfToken: 'test-token',
                route: '/api/mobility'
            });
            expect(form.zip_code).toBe('75001');
            expect(form.radius).toBe(50);
            expect(form.csrfToken).toBe('test-token');
            expect(form.route).toBe('/api/mobility');
        });

        it('saves successfully', async () => {
            const mockResponse = { ok: true };
            fetch.mockResolvedValue(mockResponse);

            const form = mobilityForm({
                zip_code: '75001',
                radius: 50,
                csrfToken: 'test-token',
                route: '/api/mobility'
            });

            const savePromise = form.save();
            expect(form.isSaving).toBe(true);

            await savePromise;

            expect(fetch).toHaveBeenCalledWith('/api/mobility', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": 'test-token',
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    zip_code: '75001',
                    radius: 50
                })
            });

            expect(window.dispatchEvent).toHaveBeenCalledWith(expect.any(CustomEvent));
            expect(window.dispatchEvent.mock.calls[0][0].type).toBe('mobility-updated');

            vi.advanceTimersByTime(600);
            expect(form.isSaving).toBe(false);
        });

        it('handles save error', async () => {
            fetch.mockRejectedValue(new Error('Network error'));
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            const form = mobilityForm({});
            await form.save();

            expect(consoleSpy).toHaveBeenCalled();
            consoleSpy.mockRestore();

            vi.advanceTimersByTime(600);
            expect(form.isSaving).toBe(false);
        });

        it('handles response not ok', async () => {
            const mockResponse = { ok: false };
            fetch.mockResolvedValue(mockResponse);
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            const form = mobilityForm({});
            await form.save();

            expect(consoleSpy).toHaveBeenCalled();
            expect(consoleSpy.mock.calls[0][0].message).toBe("Erreur");
            consoleSpy.mockRestore();
        });
    });

    describe('languagesForm', () => {
        const getInitialData = () => ({
            selectedItems: [{ id: 1, label: 'English', code: 'en', level: 'native' }],
            allAvailable: [
                { id: 1, label: 'English', code: 'en' },
                { id: 2, label: 'French', code: 'fr' },
                { id: 3, label: 'Spanish', code: 'es' }
            ],
            csrfToken: 'test-token',
            route: '/api/languages'
        });

        it('initializes correctly', () => {
            const form = languagesForm(getInitialData());
            expect(form.search).toBe("");
            expect(form.isSaving).toBe(false);
            expect(form.selectedItems.length).toBe(1);
            expect(form.allAvailable.length).toBe(3);
        });

        it('filters available languages by search query', () => {
            const form = languagesForm(getInitialData());

            // Empty search, should return all except selected
            expect(form.filteredAvailable.length).toBe(2);
            expect(form.filteredAvailable.map(i => i.id)).toEqual([2, 3]);

            // Search by label
            form.search = "Fren";
            expect(form.filteredAvailable.length).toBe(1);
            expect(form.filteredAvailable[0].id).toBe(2);

            // Search by code
            form.search = "es";
            expect(form.filteredAvailable.length).toBe(1);
            expect(form.filteredAvailable[0].id).toBe(3);
        });

        it('adds an item', () => {
            const form = languagesForm(getInitialData());
            form.search = "fr";

            // Mock save to not actually do fetch logic for this test
            vi.spyOn(form, 'save').mockImplementation(() => {});

            form.addItem({ id: 2, label: 'French', code: 'fr' });

            expect(form.selectedItems.length).toBe(2);
            expect(form.selectedItems[1].id).toBe(2);
            expect(form.selectedItems[1].level).toBe("");
            expect(form.search).toBe("");
            expect(form.save).toHaveBeenCalled();
        });

        it('removes an item', () => {
            const form = languagesForm(getInitialData());
            vi.spyOn(form, 'save').mockImplementation(() => {});

            form.removeItem(1);

            expect(form.selectedItems.length).toBe(0);
            expect(form.save).toHaveBeenCalled();
        });

        it('saves successfully', async () => {
            const mockResponse = { ok: true };
            fetch.mockResolvedValue(mockResponse);

            const form = languagesForm(getInitialData());
            const savePromise = form.save();
            expect(form.isSaving).toBe(true);

            await savePromise;

            expect(fetch).toHaveBeenCalledWith('/api/languages', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": 'test-token',
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    languages: [1],
                    levels: { "1": "native" }
                })
            });

            vi.advanceTimersByTime(600);
            expect(form.isSaving).toBe(false);
        });

        it('handles save error', async () => {
            fetch.mockRejectedValue(new Error('Network error'));
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            const form = languagesForm({});
            await form.save();

            expect(consoleSpy).toHaveBeenCalled();
            consoleSpy.mockRestore();

            vi.advanceTimersByTime(600);
            expect(form.isSaving).toBe(false);
        });
    });

    describe('permitsForm', () => {
        const getInitialData = () => ({
            selectedItems: [{ id: 1, label: 'B' }],
            allAvailable: [
                { id: 1, label: 'B' },
                { id: 2, label: 'A' },
                { id: 3, label: 'C' }
            ],
            csrfToken: 'test-token',
            route: '/api/permits'
        });

        it('initializes correctly', () => {
            const form = permitsForm(getInitialData());
            expect(form.search).toBe("");
            expect(form.isSaving).toBe(false);
            expect(form.selectedItems.length).toBe(1);
            expect(form.allAvailable.length).toBe(3);
        });

        it('filters available permits by search query', () => {
            const form = permitsForm(getInitialData());

            // Empty search, should return all except selected
            expect(form.filteredAvailable.length).toBe(2);
            expect(form.filteredAvailable.map(i => i.id)).toEqual([2, 3]);

            // Search by label
            form.search = "a";
            expect(form.filteredAvailable.length).toBe(1);
            expect(form.filteredAvailable[0].id).toBe(2);
        });

        it('adds an item', () => {
            const form = permitsForm(getInitialData());
            form.search = "a";

            vi.spyOn(form, 'save').mockImplementation(() => {});

            form.addItem({ id: 2, label: 'A' });

            expect(form.selectedItems.length).toBe(2);
            expect(form.selectedItems[1].id).toBe(2);
            expect(form.search).toBe("");
            expect(form.save).toHaveBeenCalled();
        });

        it('removes an item', () => {
            const form = permitsForm(getInitialData());
            vi.spyOn(form, 'save').mockImplementation(() => {});

            form.removeItem(1);

            expect(form.selectedItems.length).toBe(0);
            expect(form.save).toHaveBeenCalled();
        });

        it('saves successfully', async () => {
            const mockResponse = { ok: true };
            fetch.mockResolvedValue(mockResponse);

            const form = permitsForm(getInitialData());
            const savePromise = form.save();
            expect(form.isSaving).toBe(true);

            await savePromise;

            expect(fetch).toHaveBeenCalledWith('/api/permits', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": 'test-token',
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    _method: "PATCH",
                    permits: [1]
                })
            });

            vi.advanceTimersByTime(600);
            expect(form.isSaving).toBe(false);
        });

        it('handles save error', async () => {
            fetch.mockRejectedValue(new Error('Network error'));
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            const form = permitsForm({});
            await form.save();

            expect(consoleSpy).toHaveBeenCalled();
            consoleSpy.mockRestore();

            vi.advanceTimersByTime(600);
            expect(form.isSaving).toBe(false);
        });
    });
});
