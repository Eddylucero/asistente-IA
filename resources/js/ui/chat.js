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

function isTableSeparator(line) {
    return /^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)+\|?\s*$/.test(line);
}

function parseTableRow(line) {
    return line.trim().replace(/^\||\|$/g, '').split('|').map((cell) => cell.trim());
}

function formatMarkdown(value) {
    const output = [];
    let listType = null;
    const lines = escapeHtml(value).split('\n');

    const closeList = () => {
        if (listType) {
            output.push(`</${listType}>`);
            listType = null;
        }
    };

    for (let index = 0; index < lines.length; index += 1) {
        const line = lines[index];

        if (lines[index + 1] && line.includes('|') && isTableSeparator(lines[index + 1])) {
            closeList();
            const headers = parseTableRow(line);
            const rows = [];
            index += 2;

            while (index < lines.length && lines[index].includes('|') && lines[index].trim()) {
                rows.push(parseTableRow(lines[index]));
                index += 1;
            }

            output.push(`<div class="message-table-wrapper"><table class="message-table"><thead><tr>${headers.map((header) => `<th>${formatInline(header)}</th>`).join('')}</tr></thead><tbody>${rows.map((row) => `<tr>${headers.map((_, cellIndex) => `<td>${formatInline(row[cellIndex] || '')}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`);
            index -= 1;
            continue;
        }

        const ordered = line.match(/^\s*\d+[.)]\s+(.+)$/);
        const unordered = line.match(/^\s*[-*]\s+(.+)$/);

        if (ordered || unordered) {
            const nextType = ordered ? 'ol' : 'ul';

            if (listType !== nextType) {
                closeList();
                output.push(`<${nextType} class="message-list ${nextType === 'ol' ? 'message-list--ordered' : 'message-list--unordered'}">`);
                listType = nextType;
            }

            output.push(`<li>${formatInline((ordered || unordered)[1])}</li>`);

            continue;
        }

        closeList();

        if (line.trim()) {
            output.push(`<p>${formatInline(line)}</p>`);
        }
    }

    closeList();

    return output.join('');
}

function createTypingIndicator() {
    const wrapper = document.createElement('div');

    wrapper.className = 'flex gap-3 self-start max-w-[85%]';
    wrapper.dataset.typingIndicator = '';
    wrapper.setAttribute('aria-label', 'Mente está escribiendo');
    wrapper.innerHTML = `
        <div class="hidden md:flex w-8 h-8 rounded-full bg-primary-container text-on-primary-container items-center justify-center shrink-0 mt-1">
            <span class="material-symbols-outlined text-[18px]">psychiatry</span>
        </div>
        <div class="bg-surface-container text-on-surface rounded-2xl rounded-tl-sm shadow-sm px-4 py-3 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-primary/60 animate-bounce"></span>
            <span class="w-2 h-2 rounded-full bg-primary/60 animate-bounce [animation-delay:150ms]"></span>
            <span class="w-2 h-2 rounded-full bg-primary/60 animate-bounce [animation-delay:300ms]"></span>
        </div>`;

    return wrapper;
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
    let conversationId = conversation;
    let isNewConversation = root.dataset.startNewConversation === 'true';
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
        const typingIndicator = createTypingIndicator();
        list.appendChild(typingIndicator);
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
                body: JSON.stringify({
                    content,
                    conversation_id: Number.parseInt(conversationId || '0', 10) || null,
                    new_conversation: isNewConversation,
                }),
            });

            const data = await response.json();

            if (! response.ok) {
                throw new Error(data.message || 'No se pudo enviar el mensaje.');
            }

            if (data.conversation) {
                conversationId = String(data.conversation.id);
                isNewConversation = false;
                root.dataset.conversation = conversationId;
                root.dataset.startNewConversation = 'false';

                const title = root.querySelector('[data-chat-title]');
                if (title && data.conversation.title) {
                    title.textContent = data.conversation.title;
                }
            }

            data.messages
                .filter((message) => message.role === 'assistant')
                .forEach(append);
        } catch (failure) {
            append({ content: failure.message, role: 'assistant', time: now() });
        } finally {
            typingIndicator.remove();
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

    const suggestionToggle = root.querySelector('[data-chat-suggestion-toggle]');
    const suggestionList = root.querySelector('[data-chat-suggestion-list]');

    const setSuggestionsState = (expanded) => {
        if (! suggestionToggle || ! suggestionList) {
            return;
        }

        suggestionToggle.dataset.expanded = String(expanded);
        suggestionToggle.setAttribute('aria-expanded', String(expanded));
        suggestionToggle.style.display = expanded ? 'none' : '';
        suggestionList.classList.toggle('hidden', ! expanded);
        suggestionList.classList.toggle('flex', expanded);
    };

    const toggleSuggestions = () => {
        const expanded = suggestionToggle?.dataset.expanded === 'true';
        setSuggestionsState(! expanded);
    };

    if (suggestionToggle) {
        suggestionToggle.setAttribute('aria-expanded', 'false');
        suggestionToggle.addEventListener('click', toggleSuggestions);
    }

    document.addEventListener('click', (event) => {
        if (suggestionToggle?.dataset.expanded !== 'true') {
            return;
        }

        if (! suggestionToggle.contains(event.target) && ! suggestionList?.contains(event.target)) {
            setSuggestionsState(false);
        }
    });

    root.querySelectorAll('[data-chat-suggestion]').forEach((suggestion) => {
        suggestion.addEventListener('click', async () => {
            setSuggestionsState(false);
            await send(suggestion.textContent.trim());
        });
    });

    textarea.addEventListener('input', resize);

    resize();
    scrollToEnd();
}
