import { describe, it, expect, vi, beforeEach } from 'vitest';
import feedbackSystem from '../../resources/js/feedback';

global.fetch = vi.fn();
// Mock localStorage
const localStorageMock = (() => {
    let store = {};
    return {
        getItem: vi.fn(key => store[key] || null),
        setItem: vi.fn((key, value) => { store[key] = value.toString(); }),
        clear: vi.fn(() => { store = {}; })
    };
})();
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

describe('Feedback System Alpine Component', () => {
    let component;
    const initialData = {
        csrfToken: 'test-token',
        routes: { store: '/feedback' }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        localStorageMock.clear();
        component = feedbackSystem(initialData);
    });

    it('initializes correctly', () => {
        expect(component.open).toBe(false);
        expect(component.type).toBe('feedback');
    });

    it('sends feedback successfully', async () => {
        fetch.mockResolvedValueOnce({
            json: () => Promise.resolve({ status: 'success' })
        });

        component.message = 'Great app!';
        await component.sendFeedback();

        expect(component.loading).toBe(false);
        expect(component.sent).toBe(true);
        expect(component.message).toBe('');
        expect(localStorageMock.setItem).toHaveBeenCalledWith('feedback_interacted', 'true');
    });

    it('handles send error', async () => {
        fetch.mockRejectedValueOnce(new Error('Network error'));
        window.alert = vi.fn();

        component.message = 'Bug report';
        await component.sendFeedback();

        expect(window.alert).toHaveBeenCalled();
        expect(component.loading).toBe(false);
    });
});
