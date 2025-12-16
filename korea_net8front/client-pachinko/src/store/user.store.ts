import { create } from "zustand";

import { User } from "@/types/user.types";

export type UserState = {
    user: User | null;
    setUser: (user: User|null) => void;
    setUserInfo: (info: User["info"]) => void;

    updateUser: (user: Partial<User>) => void;
    updateUserInfo: (userInfo: Partial<User["info"]>) => void;

    clearUser: () => void;
};

export const useUserStore = create<UserState>(
    (set) => ({
        user: null,

        setUser: (user) => {
            set({ user })
        },

        setUserInfo: (userInfo) => {
            set((state) =>
                state.user
                    ? {
                        user: {
                            ...state.user,
                            info: userInfo
                        }
                    }
                    : {}
            );
        },

        updateUser: (partialUser) => {
            set((state) =>
                state.user
                    ? {
                        user: {
                            ...state.user,
                            ...partialUser,
                        },
                    }
                    : {}
            );
        },

        updateUserInfo: (partialInfo) => {
            set((state) =>
                state.user
                    ? {
                        user: {
                            ...state.user,
                            info: {
                                ...state.user.info,
                                ...partialInfo,
                            },
                        },
                    }
                    : {}
            );
        },


        clearUser: () => set({ user: null })
    }),
);

