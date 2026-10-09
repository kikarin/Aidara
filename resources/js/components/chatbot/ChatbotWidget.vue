<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    clearChatbotHistory,
    loadChatbotHistory,
    saveChatbotHistory,
    type ChatMessage,
} from '@/composables/useChatbotHistory';
import { renderChatMarkdown } from '@/lib/chatMarkdown';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Bot, Loader2, MessageCircle, Send, Trash2, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const page = usePage();

const isOpen = ref(false);
const input = ref('');
const loading = ref(false);
const messages = ref<ChatMessage[]>([]);
const messagesContainer = ref<HTMLElement | null>(null);

const chatbotEnabled = computed(() => Boolean((page.props as { chatbot?: { enabled?: boolean } }).chatbot?.enabled));
const isAuthenticated = computed(() => Boolean((page.props as { auth?: { user?: unknown } }).auth?.user));
const userId = computed(() => (page.props as { auth?: { user?: { id?: number } } }).auth?.user?.id ?? null);
const hasUserMessages = computed(() => messages.value.some((m) => m.role === 'user'));

const suggestedQuestions = [
    'Bagaimana cara menambah atlet baru?',
    'Apa langkah membuat program latihan?',
    'Di mana menu rekap absen?',
    'Bagaimana mengatur permission role?',
];

/** Maks item riwayat dikirim ke API (sinkron dengan backend) */
const API_HISTORY_MAX_ITEMS = 24;
const API_HISTORY_MAX_CHARS = 1200;

const welcomeMessage: ChatMessage = {
    role: 'assistant',
    content:
        'Halo! Saya asisten bantuan Aplikasi Aidara. Tanyakan cara menggunakan modul seperti Atlet, Program Latihan, Pemeriksaan, Cabor, atau Data Master.',
};

function initMessages() {
    const saved = loadChatbotHistory(userId.value);

    messages.value = saved.length > 0 ? saved : [welcomeMessage];
}

onMounted(() => {
    if (isAuthenticated.value) {
        initMessages();
    }
});

watch(userId, () => {
    initMessages();
});

watch(
    messages,
    (value) => {
        saveChatbotHistory(userId.value, value);
    },
    { deep: true },
);

watch(isOpen, (open) => {
    if (open && messages.value.length === 0) {
        initMessages();
    }
    if (open) {
        scrollToBottom();
    }
});

watch(
    () => [messages.value.length, loading.value] as const,
    async () => {
        await scrollToBottom();
    },
);

async function scrollToBottom() {
    await nextTick();
    const el = messagesContainer.value;
    if (el) {
        el.scrollTop = el.scrollHeight;
    }
}

function togglePanel() {
    isOpen.value = !isOpen.value;
}

function useSuggestion(text: string) {
    input.value = text;
}

function renderMessageHtml(content: string): string {
    return renderChatMarkdown(content);
}

function buildApiHistory(msgs: ChatMessage[]): { role: string; content: string }[] {
    return msgs
        .slice(0, -1)
        .slice(-API_HISTORY_MAX_ITEMS)
        .map((m) => ({
            role: m.role,
            content:
                m.content.length > API_HISTORY_MAX_CHARS
                    ? `${m.content.slice(0, API_HISTORY_MAX_CHARS)}…`
                    : m.content,
        }));
}

async function sendMessage() {
    const text = input.value.trim();
    if (!text || loading.value) {
        return;
    }

    messages.value.push({ role: 'user', content: text });
    input.value = '';
    loading.value = true;

    const history = buildApiHistory(messages.value);

    try {
        const { data } = await axios.post<{
            ok: boolean;
            rejected?: boolean;
            message: string;
        }>('/api/chatbot/message', {
            message: text,
            history,
        });

        messages.value.push({
            role: 'assistant',
            content: data.message,
        });
    } catch (err: unknown) {
        const axiosErr = err as {
            response?: { data?: { message?: string; errors?: Record<string, string[]> } };
        };
        const validationMsg = axiosErr.response?.data?.errors
            ? Object.values(axiosErr.response.data.errors).flat().join(' ')
            : null;
        const fallback =
            validationMsg ??
            axiosErr.response?.data?.message ??
            'Terjadi kesalahan. Silakan coba lagi.';
        messages.value.push({ role: 'assistant', content: fallback });
    } finally {
        loading.value = false;
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function clearHistory() {
    clearChatbotHistory(userId.value);
    messages.value = [welcomeMessage];
}
</script>

<template>
    <div v-if="chatbotEnabled && isAuthenticated" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
        <div
            v-show="isOpen"
            class="flex h-[min(520px,calc(100vh-8rem))] min-h-0 w-[min(400px,calc(100vw-2rem))] flex-col overflow-hidden rounded-xl border bg-background shadow-xl"
        >
            <div class="flex shrink-0 items-center justify-between border-b bg-primary px-4 py-3 text-primary-foreground">
                <div class="flex items-center gap-2">
                    <Bot class="h-5 w-5 shrink-0" />
                    <div>
                        <p class="text-sm font-semibold">Asisten Aplikasi Aidara</p>
                        <p class="text-xs opacity-80">Bantuan modul &amp; cara pakai</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <Button
                        v-if="hasUserMessages"
                        variant="ghost"
                        size="icon"
                        class="text-primary-foreground hover:bg-primary-foreground/10"
                        title="Hapus riwayat chat"
                        @click="clearHistory"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" class="text-primary-foreground hover:bg-primary-foreground/10" @click="togglePanel">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div
                ref="messagesContainer"
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-3"
            >
                <div class="space-y-3">
                    <div
                        v-for="(msg, idx) in messages"
                        :key="idx"
                        class="flex"
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            v-if="msg.role === 'user'"
                            class="max-w-[85%] rounded-lg bg-primary px-3 py-2 text-sm whitespace-pre-wrap text-primary-foreground"
                        >
                            {{ msg.content }}
                        </div>
                        <div
                            v-else
                            class="chat-markdown max-w-[92%] rounded-lg bg-muted px-3 py-2 text-sm text-foreground"
                            v-html="renderMessageHtml(msg.content)"
                        />
                    </div>
                    <div v-if="loading" class="flex justify-start">
                        <div class="flex items-center gap-2 rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                            <Loader2 class="h-4 w-4 animate-spin" />
                            Mengetik...
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!hasUserMessages" class="shrink-0 border-t px-4 py-2">
                <p class="mb-2 text-xs text-muted-foreground">Contoh pertanyaan:</p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="q in suggestedQuestions"
                        :key="q"
                        type="button"
                        class="rounded-full border bg-background px-2.5 py-1 text-xs hover:bg-muted"
                        @click="useSuggestion(q)"
                    >
                        {{ q }}
                    </button>
                </div>
            </div>

            <div class="flex shrink-0 gap-2 border-t p-3">
                <Input
                    v-model="input"
                    placeholder="Tanyakan cara pakai modul Aplikasi..."
                    class="flex-1"
                    :disabled="loading"
                    @keydown="onKeydown"
                />
                <Button size="icon" :disabled="loading || !input.trim()" @click="sendMessage">
                    <Send class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <Button
            size="icon"
            class="h-14 w-14 rounded-full shadow-lg"
            :aria-label="isOpen ? 'Tutup asisten' : 'Buka asisten Aplikasi'"
            @click="togglePanel"
        >
            <MessageCircle v-if="!isOpen" class="h-6 w-6" />
            <X v-else class="h-6 w-6" />
        </Button>
    </div>
</template>

<style scoped>
.chat-markdown :deep(.chat-p),
.chat-markdown :deep(.chat-h3) {
    margin: 0 0 0.5rem;
    line-height: 1.5;
}

.chat-markdown :deep(.chat-h3) {
    font-weight: 600;
}

.chat-markdown :deep(.chat-p:last-child) {
    margin-bottom: 0;
}

.chat-markdown :deep(.chat-ol),
.chat-markdown :deep(.chat-ul) {
    margin: 0.25rem 0 0.5rem;
    padding-left: 1.25rem;
}

.chat-markdown :deep(.chat-ol) {
    list-style-type: decimal;
}

.chat-markdown :deep(.chat-ul) {
    list-style-type: disc;
}

.chat-markdown :deep(li) {
    margin-bottom: 0.35rem;
    line-height: 1.5;
}

.chat-markdown :deep(li:last-child) {
    margin-bottom: 0;
}

.chat-markdown :deep(strong) {
    font-weight: 600;
}

.chat-markdown :deep(.chat-code) {
    border-radius: 0.25rem;
    background-color: color-mix(in oklch, var(--muted-foreground) 12%, transparent);
    padding: 0.1rem 0.35rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.85em;
}

.chat-markdown :deep(.chat-pre) {
    margin: 0.5rem 0;
    overflow-x: auto;
    border-radius: 0.375rem;
    background-color: color-mix(in oklch, var(--muted-foreground) 10%, transparent);
    padding: 0.5rem 0.75rem;
}

.chat-markdown :deep(.chat-pre code) {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8em;
    white-space: pre-wrap;
}
</style>
