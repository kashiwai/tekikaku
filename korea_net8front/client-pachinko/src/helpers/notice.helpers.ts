import { DontShowNoticesResponse, noticeConfig } from "@/config/notice.config";

export const noticeHelpers = {
    getDontShowNotices: (): DontShowNoticesResponse => {
        const raw = localStorage.getItem(noticeConfig.localstorageKey);
        try {
            return raw ? JSON.parse(raw) as DontShowNoticesResponse : [];
        } catch {
            return []
        }
    },

    addDontShowNotice: (id: number) => {
        const current = noticeHelpers.getDontShowNotices().filter(
            (n) => n.expiresAt > Date.now()
        );

        const newEntry = { id, expiresAt: Date.now() + 24 * 60 * 60 * 1000 }; // 24hr

        const updated = [...current, newEntry];
        localStorage.setItem(noticeConfig.localstorageKey, JSON.stringify(updated));
    },

    shouldHideNotice: (id: number): boolean => {
        const entries = noticeHelpers.getDontShowNotices();
        return entries.some((n) => n.id === id && n.expiresAt > Date.now());
    }
}