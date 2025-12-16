import Image from "next/image";

import LiveUserStats from "@/components/banners/LiveUserStats";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselPagination,
} from "@/components/ui/carousel";
import { BannerData } from "@/types/banner.types";

type Props = {
  banners: BannerData['logout'];
};

export default function GuestBanner({ banners }: Props) {
  return (
    <div className="relative w-full flex flex-col gap-6">
      <div className="rounded-[36px] overflow-hidden bg-neutral/5">
        <Carousel>
          <CarouselContent>
            {banners.map((banner) => (
              <CarouselItem key={banner.id}>
                <div className="rounded-[36px] h-full overflow-hidden">
                  <picture>
                    <source
                      srcSet={banner.thumbnail}
                      media="(min-width: 768px)"
                    />
                    <Image
                      src={banner.mobileThumbnail || banner.thumbnail}
                      alt={banner.title || `hero-banner-${banner.id}`}
                      width={500}
                      height={300}
                      priority
                      className="w-full h-full"
                      sizes="100vw"
                    />
                  </picture>
                </div>
              </CarouselItem>
            ))}
          </CarouselContent>

          <CarouselPagination
            dotClassname="size-2.5 border border-foreground/10"
            className="absolute bottom-[25%] left-1/2 -translate-x-1/2 gap-[5px]"
          />
        </Carousel>
      </div>

      <LiveUserStats />
    </div>
  );
}
