// chat.store.tsx
import { create } from "zustand";

import { Chat, ChatGroupFaqs, ChatMessage } from "@/types/chat.types";
import { chatApi } from "@/lib/api/chat.api";
import { getSocket } from "@/lib/socket/socket";
import { useUserStore } from "./user.store";

export const groupLabels: Record<keyof ChatGroupFaqs["grouped"], string> = {
    auth: "Authentication",
    transaction: "Transactions",
    betting: "Betting",
    bonus: "Bonus",
};

export type ActiveChat = {
    chat: Chat,
    messages: {
        list: ChatMessage[],
        total: number,
        hasMoreBefore: boolean,
        hasMoreAfter: boolean
    }
} | null;

export type TypingAuthor = { chatId: number, isTyping: boolean, participantId: number, role: "user" | "office" }
export type ChatState = {
    initialLoading: boolean;
    setInitialLoading: (state: boolean) => void;
    // chat
    chatsLoading: boolean;
    setChatsLoading: (loading: boolean) => void;
    chats: { list: Chat[], total: string } | null;
    setChats: (chats: { list: Chat[], total: string }) => void;

    activeChatLoading: boolean;
    setActiveChatLoading: (loading: boolean) => void;
    activeChat: ActiveChat;
    setActiveChat: (chat: ActiveChat) => void;
    updateActiveChatMessage: (message: ChatMessage, addUnread?: boolean) => void;
    updateActiveChat: (data: {
        chat: Chat, messages: {
            list: ChatMessage[], total: number, hasMoreBefore: boolean,
            hasMoreAfter: boolean
        }
    }) => void;
    updateActiveChatPartial: (partialChat: Partial<Chat>) => void;

    previewChatLoading: boolean;
    setPreviewChatLoading: (loading: boolean) => void;
    previewChat: ActiveChat;
    setPreviewChat: (chat: ActiveChat) => void;

    messages: ChatMessage[];
    chatGroupFaqs: ChatGroupFaqs | null;
    setChatGroupFaqs: (data: ChatGroupFaqs | null) => void;
    addMessage: (message: ChatMessage) => void;
    setMessages: (messages: ChatMessage[]) => void;
    updateMessage: (messageId: number, newContent: Partial<ChatMessage>) => void;
    clearMessages: () => void;

    typingAuthor: TypingAuthor | null;
    setTypingAuthor: (typingAuthor: TypingAuthor | null) => void;

    markMessagesAsReadBefore: (timestamp: string) => void;
    markMessagesAsReadAfter: (timestamp: string, senderType: "user" | "office" | "both") => void;

    loadPreviewChatMessages: (chatId: number) => Promise<void>;
    clearPreviewChat: () => void;
    joinPreviewChat: () => void;
    loadMorePreviewMessages: ({ before, after }: { before?: string, after?: string }) => Promise<void>;
    updatePreviewChat: (data: {
        chat: Chat, messages: {
            list: ChatMessage[], total: number, hasMoreBefore: boolean,
            hasMoreAfter: boolean
        }
    }) => void;
};

export const useChatStore = create<ChatState>(
    (set, get) => ({
        initialLoading: false,
        setInitialLoading: (loading) => set({ initialLoading: loading }),

        chatsLoading: false,
        setChatsLoading: (loading) => set({ chatsLoading: loading }),
        chats: null,
        setChats: (chats) => set({ chats }),

        activeChatLoading: false,
        setActiveChatLoading: (loading) => set({ activeChatLoading: loading }),
        activeChat: null,
        setActiveChat: (chat) => {
            if (!chat) {
                set({ activeChat: null });
                return;
            }

            set({
                activeChat: {
                    chat: { ...chat.chat },
                    messages: {
                        list: [...(chat.messages.list || [])],
                        total: Number(chat.messages.total || 0),
                        hasMoreBefore: chat.messages.hasMoreBefore,
                        hasMoreAfter: chat.messages.hasMoreAfter
                    },
                },
            });
        },
        updateActiveChatMessage: (message, addUnread = false) => {
            set((state) => {
                if (!state.activeChat) return {};

                const updatedActiveChat: ActiveChat = {
                    chat: {
                        ...state.activeChat.chat,
                        lastMessage: message.content,
                        lastMessageAt: message.createdAt,
                        lastMessageAuthor: message.senderType,
                        ...(addUnread && {
                            unreadCount: (Number(state.activeChat.chat.unreadCount) + 1).toString()
                        })

                    },
                    messages: {
                        ...state.activeChat.messages,
                        list: [...state.activeChat.messages.list, message],
                        total: Number(state.activeChat.messages.total) + 1,
                    },
                };

                return { activeChat: updatedActiveChat };
            });
        },
        updateActiveChat: (data) => {
            set((state) => {
                if (!state.activeChat) return {};

                const updatedActiveChat: ActiveChat = {
                    chat: {
                        ...state.activeChat.chat,
                        ...data.chat,
                    },
                    messages: {
                        ...state.activeChat.messages,
                        list: [...data.messages.list, ...state.activeChat.messages.list],
                        total: Number(data.messages.total),
                        hasMoreAfter: data.messages.hasMoreAfter,
                        hasMoreBefore: data.messages.hasMoreBefore
                    },
                };

                return { activeChat: updatedActiveChat };
            });
        },
        updateActiveChatPartial: (partialChat: Partial<Chat>) => {
            set((state) => {
                if (!state.activeChat) return {};

                return {
                    activeChat: {
                        ...state.activeChat,
                        chat: {
                            ...state.activeChat.chat,
                            ...partialChat,
                        },
                    },
                };
            });
        },

        previewChatLoading: false,
        setPreviewChatLoading: (loading) => set({ previewChatLoading: loading }), // Fixed: was setting activeChatLoading
        previewChat: null,
        setPreviewChat: (chat) => set({ previewChat: chat }),

        messages: [],
        chatGroupFaqs: null,
        setChatGroupFaqs: (chatGroupFaqs) => {
            set({ chatGroupFaqs });
        },

        addMessage: (message) => {
            set((state) => ({
                messages: [...state.messages, message]
            }))
        },
        setMessages: (messages) => {
            set({ messages })
        },
        updateMessage: (messageId, newContent) => {
            set((state) => ({
                messages: state.messages.map((message) => message.id === messageId ? { ...message, ...newContent } : message)
            }))
        },
        clearMessages: () => set({ messages: [] }),

        typingAuthor: null,
        setTypingAuthor: (typingAuthor) => set({ typingAuthor }),

        markMessagesAsReadBefore: (timestamp: string) => {
            set((state) => {
                if (!state.activeChat) return state;

                return {
                    activeChat: {
                        ...state.activeChat,
                        messages: {
                            ...state.activeChat.messages,
                            list: state.activeChat.messages.list.map(msg =>
                                !msg.isRead &&
                                    msg.senderType !== "user" &&
                                    new Date(msg.createdAt) <= new Date(timestamp)
                                    ? { ...msg, isRead: true }
                                    : msg
                            )
                        }
                    }
                };
            });
        },

        markMessagesAsReadAfter: (
            timestamp: string,
            senderType: "user" | "office" | "both" = "office"
        ) => {
            set((state) => {
                const chat = state.activeChat;
                if (!chat) return state;

                let unreadToMark = 0;
                const ts = new Date(timestamp);

                const updatedList = chat.messages.list.map((msg) => {
                    const shouldMark =
                        !msg.isRead &&
                        !msg.content.startsWith("system:") &&
                        new Date(msg.createdAt) >= ts &&
                        (senderType === "both" || msg.senderType === senderType);

                    if (shouldMark) unreadToMark++;

                    return shouldMark ? { ...msg, isRead: true } : msg;
                });

                return {
                    activeChat: {
                        chat: {
                            ...chat.chat,
                            unreadCount: Math.max(
                                0,
                                Number(chat.chat.unreadCount) - unreadToMark
                            ).toString(),
                        },
                        messages: {
                            ...chat.messages,
                            list: updatedList,
                        },
                    },
                };
            });
        },

        clearPreviewChat: () => {
            set({ previewChat: null });
        },

        joinPreviewChat: () => {
            const { previewChat, activeChat } = get();
            if (!previewChat) return;

            const socket = getSocket();
            const user = useUserStore.getState().user;

            if (!user) return;

            // Join the chat via socket
            socket.emit("livechat_join", {
                chatId: previewChat.chat.id,
                participantId: user.id,
                role: "user",
            });

            // Set as active chat and clear preview
            set({
                activeChat: previewChat,
                previewChat: null
            });
        },

        loadPreviewChatMessages: async (chatId: number) => {
            try {
                set({ previewChatLoading: true });
                const messages = await chatApi.getMessages({
                    chatId,
                    page: 1,
                    limit: 35,
                });

                if (messages) {
                    set({
                        previewChat: messages,
                        previewChatLoading: false
                    });
                }
            } catch (error) {
                console.error("Failed to load preview messages:", error);
                set({ previewChatLoading: false });
            }
        },

        loadMorePreviewMessages: async ({ before, after }: { before?: string, after?: string }) => {
            const { previewChat } = get();
            if (!previewChat) return;

            const { list, total } = previewChat.messages;
            if (list.length >= total) return;

            let lastMessage;
            if (before) {
                lastMessage = list[0]
            } else {
                lastMessage = list[list.length - 1];
            }

            const isoCreatedAt = lastMessage.createdAt
                .replace(" ", "T")
                .replace("+00", "Z");

            try {
                set({ previewChatLoading: true });
                const loadMessages = await chatApi.getMessages({
                    page: 1,
                    limit: 35,
                    chatId: previewChat.chat.id,
                    ...(before && { before: isoCreatedAt }),
                    ...(after && { after: isoCreatedAt }),
                });

                set({ previewChatLoading: false });

                if (loadMessages) {
                    get().updatePreviewChat(loadMessages);
                }
            } catch (error) {
                console.error("Failed to load more preview messages:", error);
                set({ previewChatLoading: false });
            }
        },

        updatePreviewChat: (data) => {
            set((state) => {
                if (!state.previewChat) return {};

                const updatedPreviewChat: ActiveChat = {
                    chat: {
                        ...state.previewChat.chat,
                        ...data.chat,
                    },
                    messages: {
                        ...state.previewChat.messages,
                        list: [...data.messages.list, ...state.previewChat.messages.list],
                        total: Number(data.messages.total),
                        hasMoreAfter: data.messages.hasMoreAfter,
                        hasMoreBefore: data.messages.hasMoreBefore
                    },
                };

                return { previewChat: updatedPreviewChat };
            });
        },


    })
);