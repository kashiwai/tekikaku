"use client";

import { HTMLAttributes, useState } from "react";

import Image from "next/image";

import { Loader } from "lucide-react";

import IconBase from "@/components/icon/iconBase";
import { toastDanger } from "@/components/ui/sonner";
import { ICONS } from "@/constants/icons";
import Link from "next/link";
import fetcher from "@/lib/fetcher";
import { useUserStore } from "@/store/user.store";
import { useModal } from "@/hooks/useModal";
import { useLocale, useTranslations } from "next-intl";
import { GameItem } from "@/types/game.types";
import { useFormStore } from "@/store/form.store";

type Props = GameItem &
  HTMLAttributes<HTMLDivElement> & { priority: boolean; href: string };

export default function Card({
  gameId,
  thumbnail,
  href,
  title,
  langs,
  priority = false,
  isFavorite = false,
  isSiteUse,
  isUserUse,
  ...props
}: Props) {
  const authModal = useModal("auth");
  const locale = useLocale();
  const [flag, setFlag] = useState<boolean>(isFavorite);
  const [loading, setLoading] = useState<boolean>(false);
  const user = useUserStore((state) => state.user);
  const t = useTranslations("GAME");

  const updateFavorite = async () => {
    if (!user) {
      return toastDanger("Please log in or register to access all features.");
    }

    setLoading(true);

    const res = await fetcher("v1/game/favorite", {
      method: "PATCH",
      body: JSON.stringify({
        gameId: gameId,
      }),
    });

    setLoading(false);

    if (res.success) {
      setFlag(!flag);
    }
  };

  return (
    <div>
      <Link
        onClick={(e) => {
          if (!user) {
            e.preventDefault();
            return authModal.onOpen({ tab: "login" });
          }

          if (!isSiteUse || !isUserUse) {
            e.preventDefault();
            if (!isSiteUse) {
              return toastDanger(t("GAME_PROHIBITED", { type: t("SLOT") }));
            }

            if (!isUserUse) {
              return toastDanger(
                t("GAME_PROHIBITED_USER", { type: t("SLOT") })
              );
            }
          }
        }}
        href={href}
        className="w-full flex flex-col"
      >
        <div
          className="group relative w-full rounded-t-2xl overflow-hidden"
          style={{
            aspectRatio: props.style?.aspectRatio
              ? props.style.aspectRatio
              : 150 / 185,
          }}
        >
          <Image
            src={thumbnail}
            alt={title}
            width={150}
            height={185}
            priority={priority}
            className="relative z-10 w-full h-full object-cover group-hover:scale-105 duration-400 transition-all"
          />

          <div className="absolute top-0 left-0 w-full h-full flex items-center justify-center">
            <IconBase icon={ICONS.SPINNER} className="animate-spin" />
          </div>

          <div className="absolute top-0 left-0 w-full h-full bg-black/0 group-hover:bg-black/70 duration-400 grid place-content-center transition-all">
            <div className="size-11 grid place-content-center bg-primary/50 rounded-full group-hover:scale-100 scale-0 transition-all">
              <IconBase icon={ICONS.PLAY} className="size-6 text-white" />
            </div>
          </div>
        </div>
      </Link>
      <div className="flex items-center gap-2.5 p-2 bg-foreground/5 justify-between">
        <h6 className="truncate text-xs font-medium">
          {langs[locale as keyof GameItem["langs"]] ?? title}
        </h6>
        <button
          onClick={async () => {
            await updateFavorite();
          }}
          className="active:scale-95 transition-all"
        >
          {loading ? (
            <Loader className="animate-spin size-4.5" />
          ) : (
            <IconBase
              icon={ICONS.HEART}
              className={`size-4.5 text-foreground/60 cursor-pointer ${flag ? "fill-red-500 text-red-500" : ""
                }`}
            />
          )}
        </button>
      </div>
    </div>
  );
}
