"use client";
import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { useLayoutStore } from "@/store/layout.store";

export default function MobileNav() {
  const t = useTranslations("MOBILE");
  const toggleAside = useLayoutStore((store) => store.toggleAside);
  const toggleChat = useLayoutStore((store) => store.toggleChat);
  const toggleNotifications = useLayoutStore(
    (store) => store.toggleNotification
  );

  const searchModal = useModal("search");
  const walletModal = useModal("wallet");

  return (
    <nav
      className="lg:hidden fixed w-full h-[60px] bg-red bottom-0 z-40 bg-white dark:bg-[#0D1521] flex items-center"
      style={{ boxShadow: "0px 0px 15px #00000015" }}
    >
      <Button
        onClick={toggleAside}
        className="flex flex-col gap-0.5 items-center justify-center flex-1 h-full bg-transparent border-transparent hover:text-foreground text-foreground/80 transition-all"
      >
        <IconBase icon={ICONS.MENU_COLLAPSE} className="size-[18px]" />
        <span className="text-xs font-normal">{t("MENU")}</span>
      </Button>
      <Button
        onClick={() => searchModal.onOpen()}
        className="flex flex-col gap-0.5 items-center justify-center flex-1 h-full bg-transparent border-transparent hover:text-foreground text-foreground/80 transition-all"
      >
        <IconBase icon={ICONS.SEARCH} className="size-[18px]" />
        <span className="text-xs font-normal">{t("SEARCH")}</span>
      </Button>
      <div className="flex-1 flex items-center justify-center">
        <Button
          onClick={() => walletModal.onOpen()}
          variant={`primary`}
          size={`icon_default`}
          className="rounded-full"
        >
          <IconBase icon={ICONS.WALLET} className="size-[18px]" />
        </Button>
      </div>
      <Button
        onClick={toggleNotifications}
        className="flex flex-col gap-0.5 items-center justify-center flex-1 h-full bg-transparent border-transparent hover:text-foreground text-foreground/80 transition-all"
      >
        <IconBase icon={ICONS.NOTICE_BELL} className="size-[18px]" />
        <span className="text-xs font-normal">{t("NOTIFICATIONS")}</span>
      </Button>
      <Button
        onClick={toggleChat}
        className="flex flex-col gap-0.5 items-center justify-center flex-1 h-full bg-transparent border-transparent hover:text-foreground text-foreground/80 transition-all"
      >
        <IconBase icon={ICONS.CHAT} className="size-[18px]" />
        <span className="text-xs font-normal">{t("SUPPORT")}</span>
      </Button>
    </nav>
  );
}
