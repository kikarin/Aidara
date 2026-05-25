export interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
}

const STORAGE_PREFIX = 'aidara-chatbot-history';
const MAX_STORED_MESSAGES = 80;

function isValidMessage(item: unknown): item is ChatMessage {
    if (!item || typeof item !== 'object') {
        return false;
    }

    const msg = item as ChatMessage;

    return (msg.role === 'user' || msg.role === 'assistant') && typeof msg.content === 'string' && msg.content.length > 0;
}

export function getChatbotStorageKey(userId: number | string | null | undefined): string | null {
    if (userId === null || userId === undefined || userId === '') {
        return null;
    }

    return `${STORAGE_PREFIX}:${userId}`;
}

export function loadChatbotHistory(userId: number | string | null | undefined): ChatMessage[] {
    const key = getChatbotStorageKey(userId);

    if (!key) {
        return [];
    }

    try {
        const raw = localStorage.getItem(key);

        if (!raw) {
            return [];
        }

        const parsed: unknown = JSON.parse(raw);

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed.filter(isValidMessage).slice(-MAX_STORED_MESSAGES);
    } catch {
        return [];
    }
}

export function saveChatbotHistory(userId: number | string | null | undefined, messages: ChatMessage[]): void {
    const key = getChatbotStorageKey(userId);

    if (!key || messages.length === 0) {
        return;
    }

    try {
        localStorage.setItem(key, JSON.stringify(messages.slice(-MAX_STORED_MESSAGES)));
    } catch {
        // localStorage penuh atau diblokir — abaikan
    }
}

export function clearChatbotHistory(userId: number | string | null | undefined): void {
    const key = getChatbotStorageKey(userId);

    if (key) {
        localStorage.removeItem(key);
    }
}
