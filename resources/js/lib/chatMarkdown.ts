function escapeHtml(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function closeLists(state: { ol: boolean; ul: boolean }, out: string[]): void {
    if (state.ol) {
        out.push('</ol>');
        state.ol = false;
    }
    if (state.ul) {
        out.push('</ul>');
        state.ul = false;
    }
}

function openOl(state: { ol: boolean; ul: boolean }, out: string[]): void {
    if (!state.ol) {
        closeLists(state, out);
        out.push('<ol class="chat-ol">');
        state.ol = true;
    }
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
    const state = { ol: false, ul: false };

    for (const line of lines) {
        const trimmed = line.trim();

        const headingOlMatch = trimmed.match(/^#{1,3}\s+(\d+)\.\s+(.+)$/);
        if (headingOlMatch) {
            openOl(state, out);
            out.push(`<li>${headingOlMatch[2]}</li>`);
            continue;
        }

        const headingMatch = trimmed.match(/^#{1,3}\s+(.+)$/);
        if (headingMatch) {
            closeLists(state, out);
            out.push(`<p class="chat-h3">${headingMatch[1]}</p>`);
            continue;
        }

        const olMatch = trimmed.match(/^(\d+)\.\s+(.+)$/);
        const ulMatch = trimmed.match(/^[*\-]\s+(.+)$/);

        if (olMatch) {
            openOl(state, out);
            out.push(`<li>${olMatch[2]}</li>`);
            continue;
        }

        if (ulMatch) {
            if (!state.ul) {
                closeLists(state, out);
                out.push('<ul class="chat-ul">');
                state.ul = true;
            }
            out.push(`<li>${ulMatch[1]}</li>`);
            continue;
        }

        closeLists(state, out);

        if (trimmed === '') {
            continue;
        }

        out.push(`<p class="chat-p">${trimmed}</p>`);
    }

    closeLists(state, out);

    return out.join('');
}
