import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { ICONS } from "@/constants/icons";
import { WheelModalTab } from "@/types/modal.types";
import { SettingsType } from "@/types/settings.types";

type Props = {
  setActiveTab: (tab: WheelModalTab) => void;
  data: SettingsType["roulette"];
};

export default function WheelDetails({ setActiveTab, data }: Props) {
  const t = useTranslations("WHEEL_DETAIL");

  return (
    <>
      <div className="px-2 overflow-auto custom-scrollbar max-h-[calc(100vh-500px)]">
        <div className="flex flex-col">

          <div className="">
            <Table>
              <TableHeader>
                <TableRow className="!bg-transparent !shadow-none">
                  <TableHead>{t("TITLE")}</TableHead>
                  <TableHead>{t("TYPE")}</TableHead>
                  <TableHead className="w-[110px]">
                    {t("POSSIBILITY")}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.map(
                  (
                    item: { title: string; isLock: boolean; odds: number },
                    index: number
                  ) => (
                    <TableRow key={index}>
                      <TableCell>{item.title}</TableCell>
                      <TableCell variant={item.isLock ? "danger" : "success"}>
                        {item.isLock ? t("LOCKED") : t("UNLOCKED")}
                      </TableCell>
                      <TableCell>{item.odds.toFixed(2)}%</TableCell>
                    </TableRow>
                  )
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </div>
      <Button
        onClick={() => setActiveTab("wheel")}
        type="button"
        className="w-full border-transparent rounded-xl"
        variant={"default"}
        size={"default"}
      >
        <IconBase icon={ICONS.ARROW_RIGHT} className="rotate-180 size-4" />
        {t("GO_BACK")}
      </Button>
    </>
  );
}
