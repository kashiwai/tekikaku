"use client";
import { useUserStore } from "@/store/user.store";
import Link from "next/link";
import { toastDanger } from "../ui/sonner";
import Image from "next/image";
import { cn } from "@/lib/utils";
import { useModal } from "@/hooks/useModal";
import { guardUtils } from "@/utils/guard.utils";
import { useSettingsStore } from "@/store/siteSettings.store";
import { BanType } from "@/types/user.types";
import { useTranslations } from "next-intl";

type CasinoListCardProps = {
  href: string;
  title: string;
  logo: { src: string; alt: string };
  additionalImg: { src: string; alt: string };
  logoClassName?: string;
  additionalImgClassName?: string;
};

export default function CasinoListCard({
  href,
  title,
  logo,
  additionalImg,
  logoClassName = "",
  additionalImgClassName = "",
}: CasinoListCardProps) {
  const authModal = useModal("auth");
  const settings = useSettingsStore((state) => state.settings);
  const user = useUserStore((store) => store.user);
  const type = "casino";
  const isGameBanned = guardUtils.isGameBanned(settings, type as BanType, user);
  const t = useTranslations("GAME");

  return (
    <Link
      href={href}
      onClick={(e) => {
        if (!user) {
          e.preventDefault();
          return authModal.onOpen({ tab: "login" });
        }

        if (isGameBanned.site || isGameBanned.user) {
          e.preventDefault();
          if (isGameBanned.site) {
            return toastDanger(
              t("GAME_PROHIBITED", { type: t(type) })
            );
          }

          if (isGameBanned.user) {
            return toastDanger(
              t("GAME_PROHIBITED_USER", { type: t(type) })
            );
          }
        }
      }}
      className="group hover:opacity-90 scale-95 transition-all relative w-full rounded-2xl"
      style={{
        aspectRatio: 217 / 120,
        background: "linear-gradient(180deg, #7E2CFF, #625DF9)",
      }}
    >
      <div className="flex flex-col gap-[2px] absolute z-10 bottom-2 sm:bottom-[24px] left-[14px]">
        <h6 className="text-[15px] md:text-[15px] font-semibold text-white">
          {title}
        </h6>
      </div>

      <Image
        src={logo.src}
        alt={logo.alt}
        width={100}
        height={46}
        className={cn(
          "absolute top-2 left-2 w-full max-w-[100px] z-10",
          logoClassName
        )}
      />

      <Image
        src={additionalImg.src}
        alt={additionalImg.alt}
        width={146}
        height={132}
        className={cn(
          "group-hover:w-[61%] duration-400 transition-all absolute w-[52%] md:w-[56%] h-auto bottom-[0px] right-[-10px] object-cover",
          additionalImgClassName
        )}
      />
    </Link>
  );
}
