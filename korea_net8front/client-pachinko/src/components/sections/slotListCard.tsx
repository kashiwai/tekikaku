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

type SlotListCardProps = {
  href: string;
  title: string;
  logo: { src: string; alt: string };
  additionalImg: { src: string; alt: string };
  logoClassName?: string;
  additionalImgClassName?: string;
};

export default function SlotListCard({
  href,
  title,
  logo,
  additionalImg,
  logoClassName = "",
  additionalImgClassName = "",
}: SlotListCardProps) {

  return (
    <Link
      href={href}
      className="group relative hover:opacity-90 transition-all w-full scale-95 rounded-2xl"
      style={{
        aspectRatio: 217 / 120,
        background: "linear-gradient(180deg, #7E2CFF, #625DF9)",
      }}
    >
      <div className="flex flex-col gap-[2px] absolute z-10 bottom-3 sm:bottom-[24px] left-[14px]">
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
          "absolute top-2 left-2 w-full max-w-[80px] md:max-w-[100px] z-10",
          logoClassName
        )}
      />

      <Image
        src={additionalImg.src}
        alt={additionalImg.alt}
        width={146}
        height={132}
        className={cn(
          "absolute group-hover:w-[65%] md:group-hover:w-[70%] transition-all duration-400 w-[55%] md:w-[65%] h-auto max-h-[200px] bottom-[0px] right-[-10px] object-cover",
          additionalImgClassName
        )}
      />
    </Link>
  );
}
