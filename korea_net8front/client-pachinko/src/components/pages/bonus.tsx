"use client";
import Image from "next/image";
import Link from "next/link";

import { useTranslations } from "next-intl";

import { Button } from "@/components/ui/button";
import { ROUTES } from "@/config/routes.config";
import { fixedDecimal } from "@/lib/utils";
import { useUserStore } from "@/store/user.store";
import { BonusResponse } from "@/types/bonus.types";

export default function Bonus({ data }: { data: BonusResponse | null }) {
  const t = useTranslations("BONUS");
  const user = useUserStore((state) => state.user);

  return (
    <>
      {user && (
        <div className="relative w-full h-[207px] md:h-[280px] rounded-3xl overflow-hidden md:overflow-visible bg-linear-to-r from-primary dark:from-primary/40 to-primary/20 dark:to-primary/10">
          <Image
            src={`/imgs/bonuses.svg`}
            alt="bonuses"
            width={250}
            height={370}
            className="absolute left-0 bottom-0 w-full opacity-70 md:opacity-100 max-w-[100px] md:max-w-[250px]"
          />
          <div className="absolute left-[8%] md:left-[38%] top-1/2 -translate-y-1/2 z-10">
            <div className="flex flex-col gap-6">
              <div className="grid grid-cols-2 gap-x-8 gap-y-4">
                <div className="flex flex-col">
                  <span className="text-xs md:text-sm text-white/80 dark:text-foreground/60 font-medium">
                    {t("TOTAL_BONUS_CLAIMED")}
                  </span>
                  <h6 className="font-extrabold text-base md:text-2xl text-white dark:text-foreground">
                    {fixedDecimal(data?.totalBonusClaim)}
                  </h6>
                </div>
                <div className="flex flex-col">
                  <span className="text-xs md:text-sm text-white/80 dark:text-foreground/60 font-medium">
                    {t("TOTAL_BONUS_REWARD")}
                  </span>
                  <h6 className="font-extrabold text-base md:text-2xl text-white dark:text-foreground">
                    {fixedDecimal(data?.totalBonusReward)}
                  </h6>
                </div>
                <div className="flex flex-col">
                  <span className="text-xs md:text-sm text-white/80 dark:text-foreground/60 font-medium">
                    {t("CURRENT_LOCKED")}
                  </span>
                  <h6 className="font-extrabold text-base md:text-2xl text-white dark:text-foreground">
                    {user ? fixedDecimal(user.bonus.locked) : 0}
                  </h6>
                </div>
                <div className="flex flex-col">
                  <span className="text-xs md:text-sm text-white/80 dark:text-foreground/60 font-medium">
                    {t("CURRENT_UNLOCKED")}
                  </span>
                  <h6 className="font-extrabold text-base md:text-2xl text-white dark:text-foreground">
                    {user ? fixedDecimal(user.bonus.unlocked) : 0}
                  </h6>
                </div>
              </div>
              <Link href={ROUTES.ACCOUNT.TRANSACTIONS}>
                <Button variant={`primary`} className="w-max">
                  {t("VIEW_TRANSACTIONS")}
                </Button>
              </Link>
            </div>
          </div>
          <Image
            src={`/imgs/gift.svg`}
            alt="gift"
            width={286}
            height={161}
            className="absolute right-0 bottom-0 w-full max-w-[100px] md:max-w-[186px] opacity-70 md:opacity-100"
          />
        </div>
      )}
    </>
  );
}
