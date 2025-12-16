"use client";

import { useEffect, useState } from "react";
import InfoCard from "@/components/cards/info/statInfoCard";
import Logo from "@/components/common/brand/logo";
import ModalLayout from "@/components/modals/modalLayout";
import { ModalControls } from "@/hooks/useModal";
import { useUserStore } from "@/store/user.store";
import { bettingApi } from "@/lib/api/betting.api";
import { BetInfo } from "@/types/bethistory";
import { Loader } from "lucide-react";
import { format } from "date-fns";
import { useTranslations } from "next-intl";
import { LogoType } from "@/types/settings.types";

type Props = ModalControls<"betInfo"> & { logo: LogoType | undefined };

export default function BettingInfoModal({
  logo,
  isOpen,
  onClose,
  getParam,
}: Props) {
  const user = useUserStore((store) => store.user);
  const [info, setInfo] = useState<BetInfo | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const t = useTranslations("BET_HISTORY.INFO");

  useEffect(() => {
    const getData = async () => {
      const id = getParam("id", "null");
      if (!id || id === "null") return;

      const res = await bettingApi.betInfo({ id, ssr: false });
      setInfo(res);
      setLoading(false);
    };

    getData();
  }, [getParam]);

  return (
    <ModalLayout isOpen={isOpen} onClose={onClose} ariaLabel="Bet Info">
      <div className="flex flex-col items-center gap-2 mb-4">
        {logo && (
          <Logo logo={logo} withTitle={false} className="w-[40px] h-[35px]" />
        )}
        <h6 className="text-xl font-semibold text-foreground">{t("TITLE")}</h6>
      </div>

      {loading ? (
        <div className="w-full h-[240px] grid place-content-center">
          <Loader className="size-5 animate-spin" />
        </div>
      ) : (
        info && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <InfoCard
              title={t("GAME_TYPE")}
              description={info.type}
              className="h-[90px]"
            />
            <InfoCard
              title={t("GAME_NAME")}
              description={info.gameName}
              className="h-[90px]"
            />
            <InfoCard
              title={t("USER_NAME")}
              description={info.userInfo.nickname}
              className="h-[90px]"
            />
            <InfoCard
              title={t("BET")}
              description={info.bet ? info.bet.toFixed(2) : 0}
              className="h-[90px]"
            />
            <InfoCard
              title={t("WIN")}
              description={info.win ? info.win.toFixed(2) : 0}
              className="h-[90px]"
            />
            <InfoCard
              title={t("MULTIPLIER")}
              description={`${
                info.multiplier ? info.multiplier.toFixed(2) : 0
              }x`}
              className="h-[90px]"
            />
            <InfoCard title={t("STATUS")} description={t(info.status)} />
            <InfoCard
              title={t("CREATED_AT")}
              description={format(info.createdAt, "dd/mmm/yyyy hh:mm")}
              className="h-[90px]"
            />
          </div>
        )
      )}
    </ModalLayout>
  );
}
