import { useEffect, useState } from "react";

import { useTranslations } from "next-intl";

import Logo from "@/components/common/brand/logo";
import IconBase from "@/components/icon/iconBase";
import ModalLayout from "@/components/modals/modalLayout";
import LuckyWheel from "@/components/modals/wheel/wheel";
import WheelDetails from "@/components/modals/wheel/wheelDetails";
import { ICONS } from "@/constants/icons";
import { ModalControls } from "@/hooks/useModal";
import { WheelModalTab } from "@/types/modal.types";
import { LogoType, SettingsType } from "@/types/settings.types";

type Props = ModalControls<"wheel"> & {
  settings: SettingsType;
  logo: LogoType | undefined;
};

export default function WheelModal({
  logo,
  isOpen,
  onClose,
  setParam,
  getParam,
  settings,
}: Props) {
  const t = useTranslations("WHEEL");

  const [activeTab, setActiveTab] = useState<WheelModalTab>(
    getParam("tab", "wheel")
  );

  useEffect(() => {
    setActiveTab(getParam("tab", "wheel"));
  }, [getParam]);

  const onTabChange = (tab: WheelModalTab) => {
    setParam("tab", tab);
  };

  const getTitle = () => {
    switch (activeTab) {
      case "wheel":
        return t("LUCKY_WHEEL");
      case "details":
        return t("WHEEL_DETAILS");
      default:
        return ""; // Default fallback title
    }
  };

  return (
    <ModalLayout
      isOpen={isOpen}
      onClose={onClose}
      ariaLabel={activeTab}
      hasPrevBtn={activeTab !== "wheel"}
      onPrevBtn={() => onTabChange("wheel")}
    >
      <div className="relative flex flex-col items-center gap-1">
        <div className=" flex flex-col items-center gap-2">
          {logo && (
            <Logo logo={logo} withTitle={false} className="w-[40px] h-[35px]" />
          )}
          <h6 className="text-xl font-semibold text-foreground">
            {getTitle()}
          </h6>
          {activeTab === "wheel" && (
            <button
              onClick={() => onTabChange("details")}
              className="absolute -bottom-2 z-10 cursor-pointer -right-1 flex items-center gap-1 text-foreground px-3 py-2 rounded-[18px] bg-foreground/5"
            >
              <IconBase icon={ICONS.HELP_CIRCLE} className="sizr-4" />
              <span className="text-xs font-normal">{t("DETAILS")}</span>
            </button>
          )}
        </div>
      </div>

      {activeTab === "wheel" ? (
        <LuckyWheel data={settings.roulette} />
      ) : (
        <WheelDetails setActiveTab={onTabChange} data={settings.roulette} />
      )}
    </ModalLayout>
  );
}
