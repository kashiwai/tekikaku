import { create } from "zustand";

import { Notice } from "@/types/notice.types";

export type NoticeState = {
    notices: Notice[];
    setNotices: (notices: Notice[]) => void;
    addNotice: (notification: Notice) => void;
    removeNotice: (notificationId: Notice["id"]) => void;
    clearNotice: () => void;
};

export const useNoticeStore = create<NoticeState>((set) => ({
    notices: [],

    addNotice: (notice) => {
        set((state) => ({
            notices: [...state.notices, notice],
        }));
    },

    setNotices: (notices) => {
        set(() => ({ notices }));
    },

    removeNotice: (noticeId) => {
        set((state) => ({
            notices: state.notices.filter(
                (notice) => notice.id !== noticeId
            ),
        }));
    },

    clearNotice: () => set({ notices: [] }),
}));