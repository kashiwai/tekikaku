import { create } from "zustand";
import { persist } from "zustand/middleware";


import { Locale, locales } from "@/i18n/request";

export type LocaleState = {
  locales: Locale[];
  activeLocale: Locale;
  setLocale: (locale: Locale) => void;
};

export const useLocaleStore = create(
  persist<LocaleState>(
    (set, get) => ({
      locales,
      activeLocale: get()?.activeLocale ?? locales[0],
      setLocale: (locale) => set({ activeLocale: locale }),
    }),
    {
      name: "locale-storage",
    }
  )
)