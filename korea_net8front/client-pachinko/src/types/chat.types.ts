import { User } from "@/types/user.types";

import { FAQType } from "./faq";


export type ChatGroupFaqs = {
    grouped: {
        auth: { list: FAQType[], total: number };
        transaction: { list: FAQType[], total: number };
        betting: { list: FAQType[], total: number };
        bonus: { list: FAQType[], total: number };
    },
    total: number
}

export type ChatUser = {
    userId: number;
    userLoginId: string;
    userInfo: {
        exp: number;
        level: number;
        phone: string;
        nickname: string;
        transaction: {
            pw: string;
            bank: string;
            realname: string;
            bankNumber: string;
        }
    }
}

export type ChatOffice = {
    officeId: number;
    officeInfo: { nickname: string };
    officeLoginId: string;
};

export type ParticipantData = {
    chatId: number;
    participant: {
        id: number;
        info: { nickname: string };
        loginId: string;
    };
    role: "user" | "office";
}

export type Initiator = "user" | "office";
export type ChatStatus = "waiting" | "active" | "cancel";

export type Chat = {
    id: number;
    siteId: number;
    user: ChatUser | null;
    office: ChatOffice | null;
    initiatorType: Initiator;
    status: ChatStatus;
    createdAt: string;
    closedAt: string | null;
    lastMessageAt: string | null;
    lastMessage: string | null;
    unreadCount: string;
    lastMessageAuthor: "user" | "office" | null,
}

export type ChatMessage = {
    id: number;
    chatId: number;
    senderId: number;
    senderType: "user" | "office";
    sender: {
        id: number;
        info: { nickname: string }
    }
    content: string | "system:office-joined" | "system:office-leaved";
    attachments: string[] | null;
    isRead: boolean;
    readAt: string | null;
    createdAt: string;
}