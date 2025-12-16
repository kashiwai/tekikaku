import Image from "next/image";
import Link from "next/link";

import { useTranslations } from "next-intl";

import CategoryBannersContainer from "@/components/common/containers/cateogoryBannersContainer";
import { ROUTES } from "@/config/routes.config";
import { BannerData } from "@/types/banner.types";

type CategoryBannerItemProps = {
  href: string;
  label: string;
  imageSrc: string;
  alt: string;
  titleClassName: string;
  imageSizes: string;
};

function CategoryBannerItem({
  href,
  label,
  imageSrc,
  alt,
  titleClassName,
  imageSizes,
}: CategoryBannerItemProps) {
  return (
    <Link
      href={href}
      className="group relative rounded-2xl md:rounded-3xl overflow-hidden"
    >
      {/* Gradient overlay */}
      <div
        className="absolute top-0 left-0 w-full h-1/2 z-10 opacity-30"
        style={{
          background: "linear-gradient(180deg, #7d2dff94, #4b00f400)",
        }}
      />
      {/* Title */}
      <h6 className={titleClassName}>{label}</h6>
      {/* Background image */}
      <Image
        src={imageSrc}
        alt={alt}
        fill
        priority
        className="object-cover group-hover:scale-110 transition-all duration-500"
        sizes={imageSizes}
      />
    </Link>
  );
}

export default function CategoryBanners({
  banners,
}: {
  banners: BannerData["category"];
}) {
  const t = useTranslations("CATEGORY");

  return (
    <CategoryBannersContainer>
      {/* First row: Casino + Slot */}
      <div className="w-full h-full grid grid-cols-2 gap-2 md:gap-3">
        {banners.filter((banner) => banner.title === "casino" || banner.title === "slot").map((banner) => (
          <CategoryBannerItem
            key={banner.id}
            href={banner.link ?? "#"}
            label={t(banner.title.toUpperCase())}
            alt={banner.title}
            imageSrc={banner.thumbnail}
            titleClassName="absolute top-2.5 left-2.5 md:top-4 md:left-4 text-shadow-lg text-shadow-black/60 dark:text-shadow-black text-white z-10 font-bold text-lg md:text-2xl"
            imageSizes="(max-width: 640px) 150px, (max-width: 2048px) 300px"
          />
        ))}
      </div>

      {/* Second row: Minigame + Sports + Virtual + Holdem */}
      <div className="w-full h-full grid grid-cols-4 md:grid-cols-2 gap-2 md:gap-3">
        {banners.filter((banner) => banner.title !== "casino" && banner.title !== "slot").map((banner) => (
          <CategoryBannerItem
            key={banner.id}
            href={banner.link ?? "#"}
            label={t(banner.title.toUpperCase())}
            alt={banner.title}
            imageSrc={banner.thumbnail}
            titleClassName="absolute top-2.5 left-2.5 md:top-3 md:left-3 text-shadow-lg text-shadow-black/60 dark:text-shadow-black text-white z-10 font-bold text-xs sm:text-sm md:text-xl"
            imageSizes="150px"
          />
        ))}

      </div>
    </CategoryBannersContainer>
  );
}
