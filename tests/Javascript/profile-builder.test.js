import { describe, it, expect, vi, beforeEach } from 'vitest';
import profileBuilder from '../../resources/js/profile-builder';

// Mock fetch
global.fetch = vi.fn();

describe('Profile Builder Alpine Component', () => {
    let component;
    const initialData = {
        messages: [],
        facts: [],
        all_experiences: [],
        user: { id: 1, name: 'Test User' },
        routes: {
            message: '/profile/builder/message',
            upload: '/profile/builder/upload'
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        
        // Use a fresh copy of initialData for each test
        const testData = JSON.parse(JSON.stringify(initialData));
        
        // Mock DOM elements
        document.body.innerHTML = `
            <div id="chat-messages"></div>
            <meta name="csrf-token" content="test-token">
            <input id="message-input" />
        `;
        
        component = profileBuilder(testData);
        // Mock $refs and $nextTick
        component.$refs = { messageInput: document.getElementById('message-input') };
        component.$nextTick = (cb) => cb();
        
        // Mock scrollIntoView
        window.HTMLElement.prototype.scrollIntoView = vi.fn();
    });

    it('initializes with provided data', () => {
        expect(component.user.name).toBe('Test User');
        expect(component.messages).toEqual([]);
        expect(component.isTyping).toBe(false);
    });

    it('calculates pending changes count correctly', () => {
        component.facts = [{ id: 1, status: 'proposed' }];
        component.all_experiences = [{ id: 2, status: 'validated', proposed_action: 'update' }];
        component.all_educations = [{ id: 3, status: 'validated' }];
        
        expect(component.pendingChangesCount).toBe(2);
    });

    it('filters facts correctly', () => {
        component.currentSessionId = 1;
        component.facts = [
            { id: 1, status: 'validated' },
            { id: 2, status: 'proposed' },
            { id: 3, status: 'proposed', session_id: 1 },
            { id: 4, status: 'proposed', proposed_action: 'add' }
        ];
        
        component.showAllFacts = false;
        expect(component.filteredFacts).toHaveLength(3); // 1, 3, 4
        
        component.showAllFacts = true;
        expect(component.filteredFacts).toHaveLength(4);
    });

    it('sends message and handles response', async () => {
        const mockReply = "Hello! I updated your profile.";
        fetch.mockResolvedValueOnce({
            json: () => Promise.resolve({ 
                reply: mockReply,
                facts: [{ id: 10, content: 'New fact' }]
            })
        });

        component.newMessage = "My new message";
        await component.sendMessage();

        expect(component.messages).toHaveLength(2);
        expect(component.messages[0].content).toBe("My new message");
        expect(component.messages[1].content).toBe(mockReply);
        expect(component.facts).toHaveLength(1);
        expect(component.isTyping).toBe(false);
    });

    it('handles message error', async () => {
        fetch.mockRejectedValueOnce(new Error('API Fail'));
        
        component.newMessage = "Error test";
        await component.sendMessage();

        expect(component.messages).toHaveLength(2);
        expect(component.messages[1].content).toContain("problème technique");
        expect(component.isTyping).toBe(false);
    });

    it('formats dates correctly', () => {
        expect(component.formatDate('2024-05-01')).toBe('01/05/2024');
        expect(component.formatDateForInput('2024-05-01')).toBe('2024-05-01');
    });

    it('renders diff correctly', () => {
        const oldText = "I love cats";
        const newText = "I love dogs";
        const diff = component.renderDiff(oldText, newText);
        
        expect(diff).toContain('<span class="diff-deleted">cats</span>');
        expect(diff).toContain('<span class="diff-added">dogs</span>');
        expect(diff).toContain('I love');
    });

    it('manages local items (add/remove/update)', () => {
        // Add
        component.addLocalItem('experience', { id: 1, title: 'Dev' });
        expect(component.all_experiences).toHaveLength(1);

        // Update status
        component.refreshLocalItemStatus('experience', 1, 'validated');
        expect(component.all_experiences[0].status).toBe('validated');

        // Refresh item
        component.refreshLocalItem('experience', 1, { id: 1, title: 'Senior Dev' });
        expect(component.all_experiences[0].title).toBe('Senior Dev');

        // Remove
        component.removeLocalItem('experience', 1);
        expect(component.all_experiences).toHaveLength(0);
    });

    it('starts editing an item', () => {
        const item = { id: 1, title: 'Test', start_date: '2020-01-01' };
        component.startEditing('experience', item);
        
        expect(component.editingItem.id).toBe(1);
        expect(component.editingData.title).toBe('Test');
        expect(component.editingData.start_date).toBe('2020-01-01');
    });

    it('starts creating a new fact', () => {
        component.facts = [];
        component.startCreating('fact');
        
        expect(component.facts).toHaveLength(1);
        expect(component.facts[0].id).toBe('new');
        expect(component.editingItem.id).toBe('new');
        expect(component.editingData.category).toBe('VALEURS');
    });

    it('cancels editing and removes new item', () => {
        component.startCreating('fact');
        expect(component.facts).toHaveLength(1);
        
        component.cancelEdit();
        expect(component.facts).toHaveLength(0);
        expect(component.editingItem.id).toBeNull();
    });

    it('updates all data', () => {
        const newData = {
            facts: [{ id: 1 }],
            all_experiences: [{ id: 2 }],
            stats: { completion: 50 }
        };
        component.updateAllData(newData);
        expect(component.facts).toEqual(newData.facts);
        expect(component.all_experiences).toEqual(newData.all_experiences);
        expect(component.stats.completion).toBe(50);
    });
});
