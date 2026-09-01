const ESCAPES = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#039;',
    '"': '&quot;',
};

function escapeHtml(value) {
    return value.replace(/[&<>'"]/g, (character) => ESCAPES[character]);
}

function formatInline(value) {
    return value.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
}

function formatMarkdown(value) {
    const output = [];
    let listType = null;

    const closeList = () => {
        if (listType) {
            output.push(`</${listType}>`);
            listType = null;
        }
    };

    escapeHtml(value).split('\n').forEach((line) => {
        const ordered = line.match(/^\s*\d+[.)]\s+(.+)$/);
        const unordered = line.match(/^\s*[-*]\s+(.+)$/);

        if (ordered || unordered) {
            const nextType = ordered ? 'ol' : 'ul';

            if (listType !== nextType) {
                closeList();
                output.push(`<${nextType}>`);
                listType = nextType;
            }

            output.push(`<li>${formatInline((ordered || unordered)[1])}</li>`);

            return;
        }

        closeList();

        if (line.trim()) {
            output.push(`<p>${formatInline(line)}</p>`);
        }
    });

    closeList();

    return output.join('');
}

function now() {
    return new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

export function initChat() {
    const root = document.querySelector('[data-chat]');

    if (! root) {
        return;
    }

    const { endpoint, conversation, initials } = root.dataset;
    const list = root.querySelector('[data-chat-messages]');
    const textarea = root.querySelector('[data-chat-input]');
    const sendButton = root.querySelector('[data-chat-send]');

    const resize = () => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.value === '' ? '48px' : `${textarea.scrollHeight}px`;
    };

    const scrollToEnd = () => {
        list.scrollTop = list.scrollHeight;
    };

    const append = ({ content, role, time }) => {
        const isUser = role === 'user';
        const wrapper = document.createElement('div');

        wrapper.className = `flex gap-3 max-w-[85%] ${isUser ? 'self-end flex-row-reverse' : 'self-start'}`;
        wrapper.innerHTML = `
            <div class="hidden md:flex w-8 h-8 rounded-full ${isUser ? 'bg-secondary-container text-on-secondary-container text-label-sm font-label-sm' : 'bg-primary-container text-on-primary-container'} items-center justify-center shrink-0 mt-1">
                ${isUser ? escapeHtml(initials) : '<span class="material-symbols-outlined text-[18px]">psychiatry</span>'}
            </div>
            <div class="flex flex-col gap-1 ${isUser ? 'items-end' : ''}">
                <div class="${isUser ? 'bg-primary text-on-primary rounded-tr-sm shadow-md shadow-primary/20' : 'bg-surface-container text-on-surface rounded-tl-sm shadow-sm'} p-4 rounded-2xl">
                    <div class="message-content text-body-md font-body-md"></div>
                </div>
                <span class="text-label-sm font-label-sm text-on-surface-variant ${isUser ? 'mr-1' : 'ml-1'}">${escapeHtml(time)}</span>
            </div>`;

        wrapper.querySelector('.message-content').innerHTML = isUser
            ? escapeHtml(content).replace(/\n/g, '<br>')
            : formatMarkdown(content);

        list.appendChild(wrapper);
    };

    const send = async (suggested = null) => {
        const content = (suggested ?? textarea.value).trim();

        if (! content || sendButton.disabled) {
            return;
        }

        sendButton.disabled = true;
        textarea.disabled = true;
        append({ content, role: 'user', time: now() });
        textarea.value = '';
        resize();
        scrollToEnd();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ content, conversation_id: Number(conversation) }),
            });

            const data = await response.json();

            if (! response.ok) {
                throw new Error(data.message || 'No se pudo enviar el mensaje.');
            }

            data.messages
                .filter((message) => message.role === 'assistant')
                .forEach(append);
        } catch (failure) {
            append({ content: failure.message, role: 'assistant', time: now() });
        } finally {
            textarea.disabled = false;
            sendButton.disabled = false;
            textarea.focus();
            scrollToEnd();
        }
    };

    root.querySelectorAll('[data-message-role="assistant"] .message-content').forEach((node) => {
        node.innerHTML = formatMarkdown(node.textContent);
    });

    textarea.addEventListener('input', resize);
    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && ! event.shiftKey) {
            event.preventDefault();
            send();
        }
    });

    sendButton.addEventListener('click', () => send());

    root.querySelectorAll('[data-chat-suggestion]').forEach((suggestion) => {
        suggestion.addEventListener('click', () => {
            suggestion.remove();
            send(suggestion.textContent.trim());
        });
    });

    resize();
    scrollToEnd();
}
