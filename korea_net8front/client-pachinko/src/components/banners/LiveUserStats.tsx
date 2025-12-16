"use client";
import Image from "next/image";

import { useTranslations } from "next-intl";

import LiveStatCarouselItem from "@/components/common/containers/liveStatCarouselItem";
import SectionTitle from "@/components/sections/sectionTitle";
import { Carousel, CarouselContent } from "@/components/ui/carousel";
import { type BetItem } from "@/types/bethistory";
import { useBetHistoryStore } from "@/store/bets.store";
import { useRouter } from "next/navigation";

const LiveUserStatCard = ({
  src,
  alt,
  username,
  win,
  multipler
}: {
  src: string;
  alt: string;
  username: string;
  win: number;
  multipler: number;
}) => {
  const t = useTranslations("COMMON")

  return (
    <div className="relative flex flex-col gap-1">
      <Image
        src={src}
        alt={alt}
        width={80}
        height={80}
        className="w-full h-auto object-cover rounded-2xl"
        style={{ aspectRatio: 71 / 74 }}
      />
      <div className="relative grid gap-[2px] overflow-hidden">
        <h6 className="text-xs font-medium truncate">{username}</h6>
        <span className="text-xs font-normal text-success truncate">
          {win} {t("USD")}
        </span>
        <span className="text-xs text-success">X {multipler?.toFixed(2)}</span>
      </div>
    </div>
  );
};

export default function LiveUserStats() {
  const t = useTranslations();
  const bets = useBetHistoryStore((store) => store.betsData)

  return (
    <div className="relative w-full flex flex-col gap-3">
      <div className="w-full flex items-center gap-1.5">
        <div className="size-[5px] rounded-full bg-success"></div>
        <SectionTitle>{t("RECENT_BET_WINS")}</SectionTitle>
      </div>
      <div className="flex bg-white/50 border border-foreground/5 dark:bg-[#12002F]/70 p-4 rounded-3xl backdrop-blur-sm shadow-sm dark:shadow-2xl ">
        <Carousel
          opts={{ align: "start" }}
          className="w-full cursor-grab active:cursor-grabbing select-none"
        >
          <CarouselContent className="-ml-4">
            {bets.winlist.slice(0, 15).map((bet: BetItem, index: number) => (
              <LiveStatCarouselItem key={index}>
                <LiveUserStatCard
                  src={bet.gameThumbnail}
                  alt={bet.gameTitle}
                  username={bet.userName}
                  win={bet.win}
                  multipler={bet.multipler}
                />
              </LiveStatCarouselItem>
            ))}
          </CarouselContent>
        </Carousel>
      </div>
    </div>
  );
}
