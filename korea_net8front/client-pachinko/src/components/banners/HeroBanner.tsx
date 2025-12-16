"use client";
import GuestBanner from "@/components/banners/GuestBanner";

import PromotionBanners from "./PromotionBanners";
import { BannerData } from "@/types/banner.types";
import fetcher from "@/lib/fetcher";
import { promotionApi } from "@/lib/api/promotion.api";
import { useEffect, useState } from "react";
import { PromotionType } from "@/types/promotion.types";
import { useUserStore } from "@/store/user.store";

type Props = {
  searchParams: URLSearchParams;
  banners: BannerData["logout"];
  promotions: PromotionType[];
};

export default function HeroBanner({ searchParams, banners, promotions }: Props) {
  const user = useUserStore((store) => store.user);

  return user ? (
    <PromotionBanners searchParams={searchParams} banners={promotions} />
  ) : (
    <GuestBanner banners={banners} />
  );
}
