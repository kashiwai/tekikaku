"use client";
import {
  FC,
  useEffect,
  useState,
  useCallback,
  memo,
  useMemo,
  useRef,
} from "react";

import Image from "next/image";
import { useSearchParams } from "next/navigation";

import { useTranslations } from "next-intl";

import Logo from "@/components/common/brand/logo";
import LanguageSwitcher from "@/components/common/languageSwitcher";
import ThemeSwitcher from "@/components/common/themeSwitcher";
import IconBase from "@/components/icon/iconBase";
import AsideLink from "@/components/layout/aside/asideLink";
import AsideNav from "@/components/layout/aside/asideNav";
import { Button } from "@/components/ui/button";
import { ASIDE_MENU, AsideNavType } from "@/config/aside.config";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { LayoutState, useLayoutStore } from "@/store/layout.store";
import { usePathname } from "@/i18n/navigation";
import { useUserStore } from "@/store/user.store";
import { SettingsType } from "@/types/settings.types";
import { toastDanger } from "@/components/ui/sonner";

type AsideTogglerProps = Pick<LayoutState, "isAsideOpen" | "toggleAside">;

// Memoize the toggler component
const AsideToggler: FC<AsideTogglerProps> = memo(
  ({ isAsideOpen, toggleAside }) => {
    return (
      <button
        aria-label={isAsideOpen ? "collapse aside menu" : "expand aside menu"}
        className="absolute z-10 top-[87px] -right-4 w-8 h-8 rounded-full bg-background shadow-md border border-neutral/5 hidden lg:grid place-content-center cursor-pointer hover:opacity-80 transition-all"
        onClick={toggleAside}
      >
        <IconBase
          icon={ICONS.MENU_COLLAPSE}
          className={`${isAsideOpen ? "" : "rotate-180"} size-4.5`}
        />
      </button>
    );
  }
);

AsideToggler.displayName = "AsideToggler";

// Memoize the main component
function Aside({ logo }: { logo: SettingsType["site"]["logo"] | undefined }) {
  const t = useTranslations("ASIDEMENU.MAIN");
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const wheelModal = useModal("wheel");
  const attendanceModal = useModal("attendance");
  const isAsideOpen = useLayoutStore((store) => store.isAsideOpen);
  const toggleAside = useLayoutStore((store) => store.toggleAside);
  const toggleChat = useLayoutStore((store) => store.toggleChat);
  const isChatOpen = useLayoutStore((store) => store.isChatOpen);
  const user = useUserStore((store) => store.user);

  const [isMobile, setIsMobile] = useState(false);

  const isMobileRef = useRef<boolean>(false);

  useEffect(() => {
    const checkMobile = () => {
      const mobile = window.innerWidth < 1024;
      if (mobile !== isMobileRef.current) {
        isMobileRef.current = mobile;
        setIsMobile(mobile);
      }
    };

    checkMobile(); // Initial check

    // Throttle resize events
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let resizeTimeout: any;
    const handleResize = () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(checkMobile, 100);
    };

    window.addEventListener("resize", handleResize);
    return () => {
      window.removeEventListener("resize", handleResize);
      clearTimeout(resizeTimeout);
    };
  }, []);

  // Memoize the action handler
  const onAction = useCallback(
    (action: AsideNavType["action"]) => {
      if (action === "chat") {
        if (!user)
          return toastDanger("Please login live chat");
        toggleChat();
      }
    },
    [toggleChat]
  );

  // Memoize modal handlers
  const handleWheelModal = useCallback(() => {
    if (isMobile) {
      toggleAside();
    }
    wheelModal.onOpen({ tab: "wheel" });
  }, [isMobile, toggleAside, wheelModal]);

  const handleAttendanceModal = useCallback(() => {
    if (isMobile) {
      toggleAside();
    }
    attendanceModal.onOpen();
  }, [isMobile, toggleAside, attendanceModal]);

  // Memoize the navigation items to prevent re-renders on every render
  const navItems = useMemo(() => {
    return ASIDE_MENU.map((menu, index) => {
      // フォールバック翻訳を提供
      const getTitle = (title: string) => {
        try {
          return t(title);
        } catch {
          return title === "GAMES" ? "ゲーム" : title === "MAIN" ? "メイン" : title;
        }
      };
      
      return (
        <AsideNav title={getTitle(menu.title)} key={index}>
        {menu.nav.map((navItem, navItemIndex) => {
          // hide items that require auth if user is null
          if (navItem.requiresAuth && !user) return null;

          return (
            <AsideLink
              key={navItemIndex}
              nav={navItem}
              isActive={
                navItem.action === "chat"
                  ? isChatOpen
                  : pathname.startsWith("/games") &&
                    navItem.identifier &&
                    navItem.identifier === searchParams.get("game")
                  ? true
                  : pathname === navItem.href
              }
              isAsideOpen={isAsideOpen}
              toggleAside={toggleAside}
              isMobile={isMobile}
              onAction={onAction}
              t={t}
            />
          );
        })}
        </AsideNav>
      )
    });
  }, [
    t,
    isChatOpen,
    pathname,
    searchParams,
    isAsideOpen,
    toggleAside,
    isMobile,
    onAction,
    user,
  ]);

  // Memoize the aside classnames
  const asideClassName = useMemo(
    () =>
      `${
        isAsideOpen
          ? "w-full max-w-full lg:max-w-[240px] border-r-neutral/5"
          : "max-w-0 lg:max-w-[74px] border-r-transparent lg:border-r-neutral/5"
      } fixed lg:sticky top-0 flex flex-col py-4 h-full flex-1 shrink-0 border-r bg-[#f5f4ff] dark:bg-background transition-all z-50`,
    [isAsideOpen]
  );

  // Memoize button classnames
  const wheelButtonClassName = useMemo(
    () =>
      `${
        isAsideOpen ? "" : "px-2.5"
      } justify-start hover:bg-primary hover:text-white border-neutral/5`,
    [isAsideOpen]
  );

  const attendanceButtonClassName = useMemo(
    () =>
      `${
        isAsideOpen ? "" : "px-2.5"
      } hover:bg-primary hover:text-white justify-start`,
    [isAsideOpen]
  );

  return (
    <aside className={asideClassName}>
      <AsideToggler isAsideOpen={isAsideOpen} toggleAside={toggleAside} />

      <div className="w-full flex-1 flex flex-col gap-4 overflow-hidden">
        <div className="flex lg:flex-col justify-between py-2 px-4">
          {logo && <Logo withTitle={isAsideOpen} logo={logo} />}
          <Button
            onClick={toggleAside}
            variant={`default`}
            size={`icon_xs`}
            className="border-transparent rounded-full lg:hidden flex"
          >
            <IconBase icon={ICONS.CLOSE_X} className="size-4" />
          </Button>
        </div>

        <div className="w-full grid grid-cols-2 lg:flex lg:flex-col gap-2 px-4">
          <Button
            onClick={handleWheelModal}
            variant={`default`}
            className={wheelButtonClassName}
          >
            <Image
              src={`/imgs/wheel-icon.svg`}
              alt=""
              width={20}
              height={20}
              className="w-full max-w-[20px]"
            />
            {isAsideOpen && (
              <span className="text-[13px] font-medium">{t("SPIN_NOW")}</span>
            )}
          </Button>
          <Button
            onClick={handleAttendanceModal}
            variant={`default`}
            className={attendanceButtonClassName}
          >
            <Image
              src={`/imgs/todolist-icon.svg`}
              alt=""
              width={20}
              height={20}
              className="w-full max-w-[20px]"
            />
            {isAsideOpen && (
              <span className="text-[13px] font-medium">{t("ATTENDANCE")}</span>
            )}
          </Button>
        </div>

        <div className="flex-1 overflow-auto custom-scrollbar px-4 h-[calc(100vh-200px)]">
          <div className="flex flex-col space-y-4 divide-y divide-neutral/5 border-b border-neutral/5">
            {navItems}
          </div>
        </div>

        <div className="px-4 flex flex-col gap-2">
          <ThemeSwitcher isAsideOpen={isAsideOpen} />
          <LanguageSwitcher isAsideOpen={isAsideOpen} />
        </div>
      </div>
    </aside>
  );
}

export default memo(Aside);
