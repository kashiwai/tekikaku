"use client";

import { useEffect, useState } from "react";

import Image from "next/image";
import Link from "next/link";

import { PromotionInfo } from "@/components/cards/promotion/PromotionCard";
import ModalLayout from "@/components/modals/modalLayout";
import { Button } from "@/components/ui/button";
import { ModalControls, useModal } from "@/hooks/useModal";
import { promotionApi } from "@/lib/api/promotion.api";
import { formatDate } from "@/lib/utils";
import { PromotionType } from "@/types/promotion.types";
import { useLocale } from "next-intl";
import { LocaleKey } from "@/i18n/request";

type Props = ModalControls<"promotion">;

export default function PromotionModal({
  isOpen,
  closeWithParams,
  getParam,
}: Props) {
  const locale = useLocale() as LocaleKey;
  const [loading, setLoading] = useState<boolean>(true);
  const handleClose = () => {
    closeWithParams(["promotionId"]);
  };
  const [remainTime, setRemainTime] = useState({
    day: 0,
    hour: 0,
    min: 0,
    sec: 0,
  });
  const [item, setItem] = useState<PromotionType | null>(null);
  const walletModal = useModal("wallet");

  useEffect(() => {
    const getPromotion = async () => {
      const promotionId = getParam("promotionId", "null");
      if (!promotionId || promotionId === "null") return;

      const promotion = await promotionApi.promotionById(promotionId);
      
      setItem(promotion);
      setLoading(false);
    };

    getPromotion();
  }, [getParam]);

  useEffect(() => {
    if (item) {
      const interval = setInterval(() => {
        const currentDate = new Date();
        const endDate = new Date(item.endDate);
        if (endDate <= currentDate) {
          setRemainTime({
            day: 0,
            hour: 0,
            min: 0,
            sec: 0,
          });
        } else {
          const diffInMillis = endDate.getTime() - currentDate.getTime();
          setRemainTime({
            day: Math.floor(diffInMillis / (1000 * 60 * 60 * 24)),
            hour: Math.floor((diffInMillis / (1000 * 60 * 60)) % 24),
            min: Math.floor((diffInMillis / (1000 * 60)) % 60),
            sec: Math.floor((diffInMillis / 1000) % 60),
          });
        }
      }, 1000);
      return () => clearInterval(interval);
    }
  }, [item]);

  return (
    <ModalLayout
      isOpen={isOpen}
      onClose={handleClose}
      ariaLabel="promotion"
      closeBtnClassname="bg-black/60 rounded-full"
      className="!max-w-[596px] p-4"
      loading={loading}
    >
      {item && (
        <div className="space-y-4">
          <div className="space-y-2">
            <div>
              <Image
                src={item.thumbnail}
                alt="promotion"
                width={586}
                height={253}
                className="w-full rounded-2xl"
                style={{ aspectRatio: 586 / 253 }}
                priority={true}
              />
              <PromotionInfo
                title={item.title}
                endDate={formatDate(item.endDate)}
                status={true}
                locale={locale}
              />
              <div className="w-full grid grid-cols-4 py-8">
                <div className="w-full flex flex-col items-center justify-center border-r border-r-foreground/10">
                  <p className="text-xl font-medium">{remainTime.day}</p>
                  <p className="text-sm font-semibold">Day</p>
                </div>
                <div className="w-full flex flex-col items-center justify-center border-r border-r-foreground/10">
                  <p className="text-xl font-medium text-white">
                    {remainTime.hour}
                  </p>
                  <p className="text-sm font-semibold">Hour</p>
                </div>
                <div className="w-full flex flex-col items-center justify-center border-r border-r-foreground/10">
                  <p className="font-500 text-xl font-medium text-white">
                    {remainTime.min}
                  </p>
                  <p className="text-sm font-semibold">Min</p>
                </div>
                <div className="w-full flex flex-col items-center justify-center">
                  <p className="text-xl font-medium text-white">
                    {remainTime.sec}
                  </p>
                  <p className="text-sm font-semibold">Sec</p>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-2">
              <Button
                className="w-full"
                variant={`default`}
                onClick={() => {
                  walletModal.onOpen({ tab: "deposit" });
                }}
              >
                Deposit Now
              </Button>

              <Link href={item.buttonLink}>
                <Button variant={`primary`} className="w-full">
                  {item.buttonName[locale]}
                </Button>
              </Link>
            </div>
          </div>
          <div
            dangerouslySetInnerHTML={{ __html: item.content[locale] ?? '' }}
            className="flex flex-col gap-4 text-[13px] px-1.5"
          ></div>
        </div>
      )}
    </ModalLayout>
  );
}
