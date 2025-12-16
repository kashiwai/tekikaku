"use client";
import { useEffect, useState } from "react";

import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { useUserStore } from "@/store/user.store";

export default function GameDetails({
  iframeUrl,
}: {
  iframeUrl: string | null;
}) {
  const authModal = useModal("auth");
  const [isFullScreen, setIsFullScreen] = useState(false);
  const [ifrUrl, setIfrUrl] = useState<string | null>(iframeUrl);
  const user = useUserStore((state) => state.user);
  const t = useTranslations('GAME_DETAIL');

  useEffect(() => {
    setIfrUrl(iframeUrl)
  }, [iframeUrl]);

  return (
      <div
        className={`${isFullScreen
          ? "fixed top-0 left-0 w-full flex flex-col h-screen z-[999999] rounded-none"
          : "rounded-3xl"
          } overflow-hidden bg-[#1a2332]`}
      >
        {!user ? (
          <div
            className="flex flex-col justify-center items-center gap-1 py-[18px] mt-2"
            style={{ aspectRatio: 1152 / 726 }}
          >
            <Button onClick={() => authModal.onOpen({ tab: "login" })}>
              {t('LOGIN_TO_CONTINUE')}
            </Button>
          </div>
        ) : (
          <div
            className={`${isFullScreen ? "!h-full" : "h-[calc(75svh)]"} relative`}

          >
            {ifrUrl && ifrUrl != "" && user && (
              <iframe
                src={ifrUrl}
                style={{ width: "100%", height: "100%" }}
              ></iframe>
            )}
          </div>
        )}
        <div className="flex w-full bg-foreground/5 h-[55px] md:h-[65px] px-3">
          <div className="ml-auto flex items-center gap-2.5">
            <Button
              onClick={() => setIsFullScreen((state) => !state)}
              variant={`default`}
              size={`icon_sm`}
              className="rounded-xl border-transparent"
            >
              <IconBase icon={ICONS.FULLSCREEN} className="size-4" />
            </Button>
          </div>
        </div>
      </div>
  );
}
