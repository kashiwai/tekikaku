import { create } from "zustand";

import { Notification } from "@/types/notification.types";

export type NotificationState = {
    notifications: Notification[];
    addNotification: (notification: Notification) => void;
    removeNotification: (notificationId: Notification['id']) => void;
    clearNotifications: () => void;
};

export const useNotificationStore = create<NotificationState>((set) => ({
    notifications: [],

    addNotification: (notification) => {
        set((state) => ({
            notifications: [...state.notifications, notification],
        }));
    },

    removeNotification: (notificationId) => {
        set((state) => ({
            notifications: state.notifications.filter(
                (notification) => notification.id !== notificationId
            ),
        }));
    },

    clearNotifications: () => set({ notifications: [] }),
}));