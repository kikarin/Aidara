function escapeHtml(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

type ListState = {
    ol: boolean;
    ul: boolean;
    olLiOpen: boolean;
    nestedUl: boolean;
};

function closeOlItem(state: ListState, out: string[]): void {
    if (state.ul && state.nestedUl) {
        out.push('</ul>');
        state.ul = false;
        state.nestedUl = false;
    }

    if (state.olLiOpen) {
        out.push('</li>');
        state.olLiOpen = false;
    }
}

function closeAllLists(state: ListState, out: string[]): void {
    closeOlItem(state, out);

    if (state.ol) {
        out.push('</ol>');
        state.ol = false;
    }

    if (state.ul) {
        out.push('</ul>');
        state.ul = false;
        state.nestedUl = false;
    }
}

function appendOlItem(state: ListState, out: string[], content: string): void {
    if (!state.ol) {
        closeAllLists(state, out);
        out.push('<ol class="chat-ol">');
        state.ol = true;
    } else {
        closeOlItem(state, out);
    }

    out.push(`<li>${content}`);
    state.olLiOpen = true;
}

/**
 * Render markdown ringan (bold, italic, code, list, heading) untuk balasan chatbot.
 */
export function renderChatMarkdown(raw: string): string {
    let text = escapeHtml(raw.trim());

    text = text.replace(/```([\s\S]*?)```/g, '<pre class="chat-pre"><code>$1</code></pre>');
    text = text.replace(/`([^`\n]+)`/g, '<code class="chat-code">$1</code>');
    text = text.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/(?<!\*)\*([^*\n]+)\*(?!\*)/g, '<em>$1</em>');

    const lines = text.split('\n');
    const out: string[] = [];
    const state: ListState = { ol: false, ul: false, olLiOpen: false, nestedUl: false };

    for (const line of lines) {
        const trimmed = line.trim();

        const headingOlMatch = trimmed.match(/^#{1,3}\s+(\d+)\.\s+(.+)$/);
        if (headingOlMatch) {
            appendOlItem(state, out, headingOlMatch[2]);
            continue;
        }

        const headingMatch = trimmed.match(/^#{1,3}\s+(.+)$/);
        if (headingMatch) {
            closeAllLists(state, out);
            out.push(`<p class="chat-h3">${headingMatch[1]}</p>`);
            continue;
        }

        const olMatch = trimmed.match(/^(\d+)\.\s+(.+)$/);
        const ulMatch = trimmed.match(/^[*\-]\s+(.+)$/);

        if (olMatch) {
            appendOlItem(state, out, olMatch[2]);
            continue;
        }

        if (ulMatch) {
            if (state.olLiOpen) {
                if (!state.ul) {
                    out.push('<ul class="chat-ul">');
                    state.ul = true;
                    state.nestedUl = true;
                }

                out.push(`<li>${ulMatch[1]}</li>`);
                continue;
            }

            if (!state.ul) {
                closeAllLists(state, out);
                out.push('<ul class="chat-ul">');
                state.ul = true;
            }

            out.push(`<li>${ulMatch[1]}</li>`);
            continue;
        }

        if (trimmed === '') {
            continue;
        }

        closeAllLists(state, out);
        out.push(`<p class="chat-p">${trimmed}</p>`);
    }

    closeAllLists(state, out);

    return out.join('');
}
