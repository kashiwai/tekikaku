"use client";
import { useCallback, useMemo, useState } from "react";

import Image from "next/image";

import { useTranslations } from "next-intl";
import { Wheel } from "react-custom-roulette";

import StatInfoCard from "@/components/cards/info/statInfoCard";
import { Button } from "@/components/ui/button";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { API_ROUTES } from "@/config/routes.config";
import { useModal } from "@/hooks/useModal";
import fetcher from "@/lib/fetcher";
import { spinRoulette } from "@/lib/utils";
import { useUserStore } from "@/store/user.store";
import { SettingsType } from "@/types/settings.types";

type Props = {
  data: SettingsType["roulette"];
};

type RoulleteResponse = {
  id: number;
  siteId: number;
  title: string;
  odds: number;
  bonus: number;
  isLock: boolean;
  rand: number;
  message: string;
};

export default function LuckyWheel({ data }: Props) {
  const user = useUserStore((state) => state.user);
  const setUser = useUserStore((state) => state.setUser);
  const authModal = useModal("auth");

  const [mustSpin, setMustSpin] = useState(false);
  const [prizeNumber, setPrizeNumber] = useState(1);
  const t = useTranslations("WHEEL");
  const [loading, setLoading] = useState<boolean>(false);

  const wheelOptions = useMemo(
    () =>
      data.map((option) => ({
        id: option.id,
        option: option.title,
        bonus: option.bonus,
        isLock: option.isLock,
      })),
    [data]
  );

  const handleSpinClick = useCallback(async () => {
    if (mustSpin || loading) return;
    if (!user) return toastDanger(t("LOGIN_FIRST"));
    if (user.roulette.count === 0) return toastDanger(t("NO_SPIN"));

    setLoading(true);
    const res = await fetcher<RoulleteResponse>(API_ROUTES.WHEEL.ROULLETE);
    if (!res.success) {
      setLoading(false);
      toastDanger(t(res.message));
      return;
    }

    const seedData = res.data;
    const spinResult = spinRoulette(data, seedData.rand);
    if (!spinResult) {
      setLoading(false);
      return;
    }

    const optionIndex = wheelOptions.findIndex((o) => o.id === spinResult.id);
    if (optionIndex === -1) {
      setLoading(false);
      return;
    }

    setPrizeNumber(optionIndex);
    setMustSpin(true);

    // setUser({
    //   ...user,
    //   roulette: {
    //     ...user.roulette,
    //     count: user.roulette.count - 1,
    //   },
    // });
  }, [data, loading, mustSpin, t, wheelOptions]);

  const onStopSpinning = useCallback(() => {
    setMustSpin(false);

    if (user) {
      toastSuccess(
        t("BONUS_WIN", {
          bonus: wheelOptions[prizeNumber].bonus,
          lockStatus: wheelOptions[prizeNumber].isLock ? t("LOCKED") : t("UNLOCKED"),
        })
      );
      setUser({
        ...user,
        roulette: {
          ...user.roulette,
          total: user.roulette.total + wheelOptions[prizeNumber].bonus,
          count: user.roulette.count - 1,
        },
        bonus: {
          unlocked:
            user.bonus.unlocked +
            (wheelOptions[prizeNumber].isLock
              ? 0
              : wheelOptions[prizeNumber].bonus),
          locked:
            user.bonus.locked +
            (wheelOptions[prizeNumber].isLock
              ? wheelOptions[prizeNumber].bonus
              : 0),
        },
      });
      setLoading(false);
    }
  }, [prizeNumber, t, user, wheelOptions]);

  return (
    <div className="flex flex-col gap-6 w-full overflow-hidden">
      <div className="relative  m-auto flex items-center justify-center w-[90vw] max-w-[412px] max-h-[412px] h-[90vw] rounded-full">
        <Image
          src={`/imgs/wheels/wheel-new.svg`}
          alt="wheel"
          fill
          priority={true}
          className="!w-[100%] !h-[100%] !left-1/2 !top-1/2 -translate-y-1/2 -translate-x-1/2 z-[9]"
        />

        <div
          className="relative w-[76%] max-w-[404px] h-[76%] max-h-[404px] m-auto z-[9]"
          style={{ transform: `rotate(-45deg)` }}
        >
          {wheelOptions.length > 0 && (
            <Wheel
              mustStartSpinning={mustSpin}
              prizeNumber={prizeNumber}
              onStopSpinning={onStopSpinning}
              backgroundColors={["#0d0d0d", "#e50914", "#ffae00", "#1f1f1f", "#2b00ff", "#9900ff", "#00c2ff"]}
              textColors={["#FFFFFF", "#FFD700"]}
              data={wheelOptions}
              outerBorderWidth={0}
              innerRadius={16}
              radiusLineColor="red"
              pointerProps={{
                src:
                  "https://new.goodfriendsgaming.com" +
                  "/imgs/wheels/arrow.svg",
                style: {
                  width: "13%",
                  top: "14.8%",
                  right: "14%",
                  transform: "rotate(45deg)",
                },
              }}
              radiusLineWidth={0}
            />
          )}
        </div>
      </div>

      {user && (
        <div className="grid sm:grid-cols-2 gap-3">
          <StatInfoCard
            title={t("TOTAL_SPIN_BONUS")}
            description={`${user.roulette.total}`}
          />
          <StatInfoCard
            title={t("NUMBER_SPINS_REMAIN")}
            description={user.roulette.count.toString()}
          />
        </div>
      )}

      {user ? (
        <>
          <Button
            onClick={handleSpinClick}
            className="w-full rounded-xl"
            variant={"primary"}
            size={"default"}
            disabled={mustSpin || loading}
            loading={mustSpin || loading}
          >
            {t("SPIN_NOW")}
          </Button>
        </>
      ) : (
        <Button
          onClick={() => authModal.onOpen({ tab: "login" })}
          className="w-full rounded-xl"
          variant={"danger_ghost"}
          size={"default"}
        >
          {t("LOGIN_TO_SPIN")}
        </Button>
      )}
    </div>
  );
}
