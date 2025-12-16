import { create } from "zustand";
import { persist } from "zustand/middleware";

export type LayoutState = {
    isAsideOpen: boolean;
    setAsideOpen: (state: boolean) => void;
    toggleAside: () => void;

    isNotificationOpen: boolean;
    setNotificationOpen: (state: boolean) => void;
    toggleNotification: () => void;

    isChatOpen: boolean;
    setChatOpen: (state: boolean) => void;
    toggleChat: () => void;
}

export const useLayoutStore = create(
    persist<LayoutState>(
        (set) => ({
            isAsideOpen: false,
            setAsideOpen: (state) => set({ isAsideOpen: state }),
            toggleAside: () => {
                set((state) => ({ isAsideOpen: !state.isAsideOpen }));
            },

            isNotificationOpen: false,
            setNotificationOpen: (state) => set({ isNotificationOpen: state }),
            toggleNotification: () => {
                set((state) => {
                    // If notification is opened, close chat
                    return { isNotificationOpen: !state.isNotificationOpen };
                });
            },

            isChatOpen: false,
            setChatOpen: (state) => set({ isChatOpen: state }),
            toggleChat: () => {
                set((state) => {
                    // If notification is opened, close chat
                    return { isChatOpen: !state.isChatOpen };
                });
            }
        }),
        {
            name: "layout-storage"
        }
    )
);