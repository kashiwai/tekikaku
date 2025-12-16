import { create } from "zustand";

import { SiteSettingsResponse } from "@/config/settings.config";

export type SiteSettingsState = {
    settings: SiteSettingsResponse | null;
    setSettings: (user: SiteSettingsResponse | null) => void;
};

export const useSettingsStore = create<SiteSettingsState>(
    (set) => ({
        settings: null,

        setSettings: (settings) => {
            set({ settings })
        },
    }),
);

