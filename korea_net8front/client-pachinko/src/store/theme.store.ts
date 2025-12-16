import { create } from "zustand";
import { persist } from "zustand/middleware";

type Mode = "dark" | "light";

export type ThemeState = {
  mode: Mode;
  setMode: (mode: Mode) => void;
  toggleMode: () => void;
};

function applyTheme(mode: Mode) {
  if (typeof document !== "undefined") {
    document.documentElement.setAttribute("data-theme", mode);
  }
}

export const useThemeState = create(
  persist<ThemeState>(
    (set, get) => ({
      mode: "dark", // default fallback
      setMode: (mode) => {
        applyTheme(mode);
        set({ mode });
      },
      toggleMode: () => {
        const newMode = get().mode === "dark" ? "light" : "dark";
        applyTheme(newMode);
        set({ mode: newMode });
      },
    }),
    {
      name: "mode-storage",
      onRehydrateStorage: () => (state) => {
        if (state?.mode) applyTheme(state.mode);
      },
    }
  )
);