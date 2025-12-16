import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { ICONS } from "@/constants/icons";
import { LayoutState } from "@/store/layout.store";
import { ThemeState, useThemeState } from "@/store/theme.store";

type Props = Pick<LayoutState, "isAsideOpen">;

export default function ThemeSwitcher({ isAsideOpen }: Props) {
  const t = useTranslations("THEME");
  const mode = useThemeState((store) => store.mode);
  const setMode = useThemeState((store) => store.setMode);
  const toggleMode = useThemeState((store) => store.toggleMode);

  const onChange = (mode: ThemeState["mode"]) => {
    if (!isAsideOpen) {
      return toggleMode();
    }
    setMode(mode);
  };

  return (
    <div className="flex items-center p-1 bg-foreground/5 rounded-full overflow-hidden">
      <button
        onClick={() => onChange("dark")}
        className={`${
          mode === "dark"
            ? "bg-foreground/5 text-foreground font-bold"
            : "text-foreground/50 hover:text-foreground/80 font-light"
        } ${
          !isAsideOpen && mode !== "dark" ? "hidden" : "flex"
        }  gap-1.5 items-center justify-center flex-1 cursor-pointer p-2 rounded-full transition-all`}
        aria-label="dark-mode"
      >
        <IconBase icon={ICONS.HALF_MOON} className="size-4" />
        <span className={`${isAsideOpen ? "" : "opacity-0 absolute"} text-xs`}>
          {t("DARK")}
        </span>
      </button>

      <button
        onClick={() => onChange("light")}
        className={`${
          mode === "light"
            ? "bg-foreground/5 text-foreground font-bold"
            : "text-foreground/50 hover:text-foreground/80 font-light"
        } ${
          !isAsideOpen && mode !== "light" ? "hidden" : "flex"
        } gap-1.5 items-center justify-center flex-1 cursor-pointer p-2 rounded-full transition-all`}
        aria-label="light-mode"
      >
        <IconBase icon={ICONS.SUN} className="size-4" />
        <span className={`${isAsideOpen ? "" : "opacity-0 absolute"} text-xs`}>
          {t("LIGHT")}
        </span>
      </button>
    </div>
  );
}
