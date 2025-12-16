import { API_ROUTES } from "@/config/routes.config";
import fetcher from "@/lib/fetcher";
import { Chat, ChatGroupFaqs, ChatMessage, ChatStatus } from "@/types/chat.types";
import { searchParamUtils } from "@/utils/searchparam.utils";


export const chatApi = {
    groupedChatFaqs: async (): Promise<ChatGroupFaqs | null> => {
        const url = `${API_ROUTES.FAQ.GROUPED_FAQ}`
        const res = await fetcher<ChatGroupFaqs>(url)

        if (!res.success) return null;
        return res.data
    },
    getChats: async ({ page, limit, status, userId }: { page: number, limit: number, status?: ChatStatus, userId: number }): Promise<{ list: Chat[], total: string }> => {
        const query = searchParamUtils.buildSearchParams({
            page: page.toString(),
            limit: limit.toString(),
            userId: userId.toString(),
            ...(status && { status: status }),
        });

        const res = await fetcher<{ list: Chat[], total: string }>(`${API_ROUTES.LIVE_CHAT.getChats}?${query.toString()}`, {
            method: "GET",
        })

        if (!res.success) return { list: [], total: '0' };
        return res.data
    },
    getInitedChat: async ({ page = 1, limit = 20, userId }: { page: number, limit: number, userId: number }): Promise<{ chat: Chat, messages: { list: ChatMessage[], total: number } } | null> => {
        const query = searchParamUtils.buildSearchParams({
            page: page.toString(),
            limit: limit.toString(),
            userId: userId.toString()
        });

        const res = await fetcher<{ chat: Chat, messages: { list: ChatMessage[], total: number } }>(`${API_ROUTES.LIVE_CHAT.getInitedChat}?${query.toString()}`, {
            method: "GET",
        })

        if (!res.success) return null;

        return res.data
    },
    getMessages: async ({ page = 1, limit = 20, before, after, chatId }: { page: number, limit: number, before?: string, after?: string, chatId: number }): Promise<{
        chat: Chat,
        messages: {
            list: ChatMessage[];
            total: number;
            hasMoreBefore: boolean;
            hasMoreAfter: boolean;
        }
    } | null> => {
        const query = searchParamUtils.buildSearchParams({
            page: page.toString(),
            limit: limit.toString(),
            ...(before && { before }),
            ...(after && { after })
        });
        
        const res = await fetcher<{
            chat: Chat, messages: {
                list: ChatMessage[],
                total: number,
                hasMoreBefore: boolean,
                hasMoreAfter: boolean
            }
        }>(`${API_ROUTES.LIVE_CHAT.getMessages(chatId)}?${query.toString()}`, {
            method: "GET",
        })

        if (!res.success) return null;

        return res.data
    },
}