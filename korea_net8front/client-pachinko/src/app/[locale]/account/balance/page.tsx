"use client";
import Link from "next/link";

import { useTranslations } from "next-intl";

import InfoCard from "@/components/cards/info/statInfoCard";
import { Button } from "@/components/ui/button";
import TabWrapper from "@/components/wrapper/tabWrapper";
import { ROUTES } from "@/config/routes.config";
import { useUserStore } from "@/store/user.store";
import ProgressBar from "@/components/progress/ProgressBar";
import numeral from "numeral";

export default function Page() {
  const user = useUserStore((state) => state.user);
  const t = useTranslations("ACCOUNT.BALANCE");

  return (
    <>
      {user && (
        <>
          <TabWrapper className="grid md:grid-cols-2 lg:grid-cols-2 gap-3 space-y-0">
            <div className="md:col-span-2 lg:col-span-3 grid lg:grid-cols-2 gap-3">
              <InfoCard
                title={t("AMOUNT")}
                className="h-[120px] md:h-[146px] justify-between"
              >
                <div className="h-11 flex items-center justify-between">
                  <p className="text-sm font-normal leading-[130%]">
                    {t("BALANCE")}
                  </p>
                  <h6 className="text-lg font-bold">
                    {numeral(user.wallets.money).format("0,0.00")}
                  </h6>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <Link href={ROUTES.ACCOUNT.DEPOSIT}>
                    <Button
                      className="w-full rounded-xl hover:bg-primary hover:text-white"
                      variant={"default"}
                      size={"sm"}
                    >
                      {t("GO_TO_DEPOSIT")}
                    </Button>
                  </Link>

                  <Link href={ROUTES.ACCOUNT.WITHDRAWAL}>
                    <Button
                      className="w-full rounded-xl hover:bg-primary hover:text-white"
                      variant={"default"}
                      size={"sm"}
                    >
                      {t("GO_TO_WITHDRAW")}
                    </Button>
                  </Link>
                </div>
              </InfoCard>

              <InfoCard
                title={t("BONUS")}
                className="h-[120px] md:h-[146px] justify-between"
              >
                <div className="w-full">
                  <div className="w-full flex items-center justify-between">
                    <p className="text-danger text-sm">{t("LOCKED")}</p>
                    <h6 className="font-bold">
                      {numeral(user.bonus.locked).format("0,0.00")}
                    </h6>
                  </div>
                  <div className="w-full flex items-center justify-between">
                    <p className="text-success text-sm">{t("UNLOCKED")}</p>
                    <h6 className="font-bold">
                      {numeral(user.bonus.unlocked).format("0,0.00")}
                    </h6>
                  </div>
                </div>
              </InfoCard>
            </div>

            <div className="md:col-span-2 lg:col-span-3 grid lg:grid-cols-2 gap-3">
              <InfoCard
                title={t("LEVEL_UP_EVENT")}
                className="h-[120px] md:h-[146px] justify-between"
              >
                <div className="h-11 flex items-center justify-between">
                  <p className="text-sm font-normal leading-[130%]">
                    {t("NEXT_LEVEL_REWARD_BONUS")}
                  </p>
                  <h6 className="text-lg font-bold">
                    {user.info.nextLevelData?.bonus ?? 0}
                  </h6>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <Link href={ROUTES.CASINO}>
                    <Button
                      className="w-full rounded-xl hover:bg-primary hover:text-white"
                      variant={"default"}
                      size={"sm"}
                    >
                      {t("GO_TO_CASINO")}
                    </Button>
                  </Link>
                  <Link href={ROUTES.SPORTS}>
                    <Button
                      className="w-full rounded-xl hover:bg-primary hover:text-white"
                      variant={"default"}
                      size={"sm"}
                    >
                      {t("GO_TO_SPORTS")}
                    </Button>
                  </Link>
                </div>
              </InfoCard>

              <InfoCard title={t("PROGRESS")} titleDesc={t("DESCRIOPTION")}>
                <ProgressBar
                  value={
                    user.info.nextLevelData
                      ? Number(
                        (
                          ((user.info.exp - user.info.curLevelData?.needExp) / (user.info.nextLevelData?.needExp - user.info.curLevelData?.needExp)) *
                          100
                        ).toFixed(2)
                      )
                      : 100
                  }
                  header={{
                    leftText: t("YOUR_PROGRESS"),
                    rightText: `${user.info.nextLevelData
                      ? ((user.info.exp - user.info.curLevelData?.needExp) / (user.info.nextLevelData?.needExp - user.info.curLevelData?.needExp)) *
                        100 >
                        100
                        ? 100
                        : (
                          ((user.info.exp - user.info.curLevelData?.needExp) /
                            (user.info.nextLevelData?.needExp - user.info.curLevelData?.needExp)) *
                          100
                        ).toFixed(2)
                      : 100
                      } %`,
                  }}
                  footer={{
                    leftText: `${user.info.curLevelData?.name ?? ""}`,
                    rightText: `${user.info.nextLevelData?.name ?? ""}`,
                  }}
                />
              </InfoCard>
            </div>
          </TabWrapper>
        </>
      )}
    </>
  );
}
