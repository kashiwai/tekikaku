"use server";
import { API_ROUTES } from "@/config/routes.config";
import fetcher from "@/lib/fetcher";
import { getCookieHeader } from "./cookie.actions";
import { Chat, ChatMessage } from "@/types/chat.types";


export async function initChat({ userId }: { userId: number }) {
    const body = {
        userId: userId,
        initiatorType: "user"
    }
    const res = await fetcher<{
        chat: Chat, messages: {
            list: ChatMessage[],
            total: number,
            hasMoreBefore: boolean,
            hasMoreAfter: boolean
        }
    }>(API_ROUTES.LIVE_CHAT.initChat, {
        method: "POST",
        headers: {
            Cookie: await getCookieHeader(),
        },
        body: JSON.stringify(body)
    })

    return res;
}