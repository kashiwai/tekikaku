"use client";
import Image from "next/image";
import Link from "next/link";

import LiveUserStats from "@/components/banners/LiveUserStats";
import HeroCarouselItem from "@/components/common/containers/heroCarouselItem";
import {
  Carousel,
  CarouselContent,
  CarouselPagination,
} from "@/components/ui/carousel";
import { PromotionType } from "@/types/promotion.types";
import { getLocale } from "next-intl/server";
import { LocaleKey } from "@/i18n/request";
import { useLocale } from "next-intl";

type Props = {
  banners: PromotionType[];
  searchParams: URLSearchParams;
};

const PromotionBanner = ({
  href,
  src,
  title,
  description,
}: {
  href: string;
  src: string;
  title: string;
  description: string;
}) => {
  return (
    <Link
      href={href}
      className="group relative w-full rounded-2xl md:rounded-[36px] h-full overflow-hidden flex aspect-[355/233] md:aspect-[595/274]"
    >
      <Image
        className="group-hover:scale-105 w-full h-auto object-cover rounded-2xl md:rounded-[36px] transition-all duration-500"
        src={src}
        alt={title}
        width={438}
        height={274}
        sizes="(max-width: 640px) 100vw, (max-width: 2048px) 600px"
        priority={true}
      />
      <div
        className="absolute w-full h-[78%] bottom-0 left-0"
        style={{ background: "linear-gradient(0deg, #6710B0, #6710b000)" }}
      ></div>
      <div className="absolute bottom-6 md:bottom-8 left-4 md:left-8 flex flex-col">
        <h6 className="text-xl font-semibold text-white">{title}</h6>
        {/* <div className="text-xs md:text-sm font-medium text-white line-clamp-3 pr-4" dangerouslySetInnerHTML={{__html: description}} /> */}
      </div>
    </Link>
  );
};

export default function PromotionBanners({
  searchParams,
  banners,
}: Props) {
  const locale = useLocale() as LocaleKey;
  return (
    <div className="relative w-full flex flex-col gap-6">
      <Carousel
        className="w-full rounded-3xl overflow-hidden"
        opts={{ align: "start" }}
      >
        <CarouselContent className="-ml-4">
          {banners.map((banner) => {
            const params = new URLSearchParams(searchParams);
            params.set("promotionId", String(banner.id));
            params.set("modal", "promotion");
            const href = `?${params.toString()}`;
            return (
              <HeroCarouselItem key={banner.id}>
                <PromotionBanner
                  href={href}
                  src={banner.thumbnail}
                  title={banner.title[locale] ?? ""}
                  description={banner.content[locale] ?? ""}
                />
              </HeroCarouselItem>
            );
          })}
        </CarouselContent>

        <CarouselPagination
          dotClassname="size-2.5 border border-foreground/10"
          className="absolute bottom-[4%] md:bottom-[10%] left-1/2 -translate-x-1/2 gap-[5px]"
        />
      </Carousel>
      <LiveUserStats />
    </div>
  );
}
