import { useEffect, useState } from "react";

import Image from "next/image";

import InfoCard from "@/components/cards/info/statInfoCard";
import Logo from "@/components/common/brand/logo";
import TabFilter from "@/components/filter/tabFilter";
import CropEditor from "@/components/forms/profile/avatarCrop";
import IconBase from "@/components/icon/iconBase";
import ModalLayout from "@/components/modals/modalLayout";
import ProgressBar from "@/components/progress/ProgressBar";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import CardWrapper from "@/components/wrapper/cardWrapper";
import { ICONS } from "@/constants/icons";
import { ModalControls } from "@/hooks/useModal";
import { urlToFile } from "@/lib/utils";
import { useUserStore } from "@/store/user.store";
import { ProfileModalTab } from "@/types/modal.types";
import { User } from "@/types/user.types";
import { LogoType } from "@/types/settings.types";

type Props = ModalControls<"profile"> & {
  logo: LogoType | undefined;
};

export default function ProfileModal({
  logo,
  isOpen,
  onClose,
  getParam,
  setParam,
}: Props) {
  const user = useUserStore((state) => state.user);
  const [uploadedFile, setUploadedFile] = useState<File | null>(null);
  const [activeTab, setActiveTab] = useState<ProfileModalTab>(
    getParam("tab", "profile")
  );

  useEffect(() => {
    setActiveTab(getParam("tab", "profile"));
  }, [getParam]);

  const onTabChange = (tab: ProfileModalTab) => {
    setParam("tab", tab);
  };

  useEffect(() => {
    if (!user) {
      return;
    }

    const prepareForm = async () => {
      const file = await urlToFile("/");
      setUploadedFile(file);
    };

    prepareForm();
  }, [user]);

  const getTitle = () => {
    switch (activeTab) {
      case "profile":
        return "Manage Your Profile";
      case "vip":
        return "Manage Your Profile";
      case "edit":
        return "Edit Your Image";
      default:
        return "Welcome to GoodFriends";
    }
  };

  return (
    user && (
      <ModalLayout
        className="w-full max-w-[600px]"
        isOpen={isOpen}
        onClose={onClose}
        ariaLabel={activeTab}
        hasPrevBtn={activeTab === "edit"}
        onPrevBtn={() => onTabChange("profile")}
      >
        <div className="flex flex-col items-center gap-1">
          <div className="flex flex-col items-center gap-2">
            {logo && (
              <Logo
                logo={logo}
                withTitle={false}
                className="w-[40px] h-[35px]"
              />
            )}
            <h6 className="text-xl font-semibold text-foreground">
              {getTitle()}
            </h6>
          </div>
        </div>

        {(activeTab === "profile" || activeTab === "vip") && (
          <TabFilter
            value={activeTab}
            onValueChange={onTabChange}
            tabs={["profile", "vip"]}
          />
        )}

        {activeTab === "profile" ? (
          // uploadedFile && (
          //   <>
          //     <ProfileForm
          //       initialData={{
          //         name: user.info.nickname,
          //         avatar: uploadedFile,
          //       }}
          //       onEditStart={(file) => {
          //         setUploadedFile(file);
          //         onTabChange("edit");
          //       }}
          //     />

          //   </>
          // )
          <ProfileInfo user={user} />
        ) : activeTab === "vip" ? (
          <VipTab />
        ) : (
          uploadedFile && (
            <CropEditor
              file={uploadedFile}
              onSave={(croppedFile) => {
                setUploadedFile(croppedFile);
                onTabChange("profile");
              }}
            />
          )
        )}
      </ModalLayout>
    )
  );
}

const ProfileInfo = ({ user }: { user: User }) => {
  return (
    <div className="space-y-3">
      <div className="grid grid-cols-3 gap-2">
        <InfoCard
          icon={ICONS.BITCOIN_BAG}
          title="Total Wins"
          description="0"
          className="items-center border border-foreground/5"
          titleClassName="text-center"
        />
        <InfoCard
          icon={ICONS.POKER_CHIP}
          title="Total Bets"
          description="0"
          className="items-center border border-foreground/5"
          titleClassName="text-center"
        />
        <InfoCard
          icon={ICONS.STAKE}
          title="Total Wagered"
          description="NOK 0.00"
          className="items-center border border-foreground/5"
          titleClassName="text-center"
        />
      </div>

      <CardWrapper
        title="Progress"
        description="Earn bonuses and spin rewards as you level up. Plus, the higher your
            level, the more bonus rewards you can get!"
      >
        <ProgressBar
          value={Number(((user.info.exp / user.info.needExp) * 100).toFixed(2))}
          header={{
            leftText: "Your Progress",
            rightText: `${Number(
              ((user.info.exp / user.info.needExp) * 100).toFixed(2)
            )} %`,
          }}
          footer={{
            leftText: `Level ${user.info.level}`,
            rightText: `Level ${user.info.level + 1}`,
          }}
        />
      </CardWrapper>
    </div>
  );
};

const VipTab = () => {
  return (
    <>
      {/* <CardWrapper title="VIP Progress">
        <ProgressBar
          value={60}
          header={{ leftText: "0 Exp", rightText: "100 Exp" }}
          footer={{ rightText: "Level 1 - Level 2" }}
        />
      </CardWrapper> */}

      <Accordion type="single" collapsible className="gap-4 flex flex-col">
        <AccordionItem
          value="item-1"
          className="!border border-foreground/10 rounded-2xl p-4"
        >
          <AccordionTrigger className="p-0">
            <h6>VIP Benefits</h6>
            <IconBase
              icon={ICONS.CHEVRON_LEFT}
              className="-rotate-90 group-data-[state=open]:!rotate-90 size-5"
            />
          </AccordionTrigger>
          <AccordionContent className="p-0 mt-4 flex flex-col gap-3">
            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-1.5">
                <Image
                  src={`/imgs/medals/bronze.svg`}
                  alt="bronze"
                  width={20}
                  height={20}
                  className="size-5"
                />
                <p style={{ color: "#C4A490" }}>Bronze</p>
                <span className="text-foreground">(Level 10)</span>
              </div>
              <ul>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Bonus from Support in currency of your choice
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Rakeback enabled
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Weekly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Monthly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  VIP Telegram channel access
                </li>
              </ul>
            </div>

            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-1.5">
                <Image
                  src={`/imgs/medals/silver.svg`}
                  alt="bronze"
                  width={20}
                  height={20}
                  className="size-5"
                />
                <p style={{ color: "#B2CCCC" }}>Silver</p>
                <span className="text-foreground">(Level 11)</span>
              </div>
              <ul>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Bonus from Support in currency of your choice
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Rakeback enabled
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Weekly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Monthly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  VIP Telegram channel access
                </li>
              </ul>
            </div>

            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-1.5">
                <Image
                  src={`/imgs/medals/gold.svg`}
                  alt="bronze"
                  width={20}
                  height={20}
                  className="size-5"
                />
                <p style={{ color: "#B2CCCC" }}>Gold</p>
                <span className="text-foreground">(Level 12)</span>
              </div>
              <ul>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Bonus from Support in currency of your choice
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Rakeback enabled
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Weekly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Monthly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  VIP Telegram channel access
                </li>
              </ul>
            </div>

            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-1.5">
                <Image
                  src={`/imgs/medals/platinum-1.svg`}
                  alt="bronze"
                  width={20}
                  height={20}
                  className="size-5"
                />
                <p>Platinum I-III</p>
                <span className="text-foreground">(Level 13)</span>
              </div>
              <ul>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Bonus from Support in currency of your choice
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Rakeback enabled
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Weekly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Monthly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  VIP Telegram channel access
                </li>
              </ul>
            </div>

            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-1.5">
                <Image
                  src={`/imgs/medals/platinum-4.svg`}
                  alt="bronze"
                  width={20}
                  height={20}
                  className="size-5"
                />
                <p>Platinum IV-VI</p>
                <span className="text-foreground">(Level 14)</span>
              </div>
              <ul>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Bonus from Support in currency of your choice
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Rakeback enabled
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Weekly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  Monthly bonuses
                </li>
                <li className="text-xs font-medium text-foreground/60 flex items-center gap-2 pl-2">
                  <span className="flex w-0.5 h-0.5 bg-foreground/60"></span>
                  VIP Telegram channel access
                </li>
              </ul>
            </div>
          </AccordionContent>
        </AccordionItem>

        <AccordionItem
          value="item-2"
          className="!border border-foreground/10 rounded-2xl p-4"
        >
          <AccordionTrigger className="p-0 w-full flex flex-col gap-1">
            <div className="flex w-full items-center justify-between">
              <h6>VIP Hosts</h6>
              <IconBase
                icon={ICONS.CHEVRON_LEFT}
                className="-rotate-90 group-data-[state=open]:!rotate-90 size-5"
              />
            </div>
            <p className="text-foreground/60 text-xs">
              Reach Platinum IV or above and receive your own dedicated VIP host
              who will support and cater to your betting needs.
            </p>
          </AccordionTrigger>
          <AccordionContent className="p-0 mt-4 flex flex-col gap-3">
            <div className="flex flex-col gap-1">
              <ul className="flex flex-col gap-1">
                <li className="flex items-center gap-1">
                  <IconBase
                    icon={ICONS.OUTLINE_CHECK}
                    className="size-4 text-success/20"
                  />
                  <span className="text-xs font-medium text-foreground/80">
                    Bonus from Support in currency of your choice
                  </span>
                </li>
                <li className="flex items-center gap-1">
                  <IconBase
                    icon={ICONS.OUTLINE_CHECK}
                    className="size-4 text-success/20"
                  />
                  <span className="text-xs font-medium text-foreground/80">
                    Tailored bonuses and sports betting limits
                  </span>
                </li>
                <li className="flex items-center gap-1">
                  <IconBase
                    icon={ICONS.OUTLINE_CHECK}
                    className="size-4 text-success/20"
                  />
                  <span className="text-xs font-medium text-foreground/80">
                    Insight into gameplay statistics
                  </span>
                </li>
                <li className="flex items-center gap-1">
                  <IconBase
                    icon={ICONS.OUTLINE_CHECK}
                    className="size-4 text-success/20"
                  />
                  <span className="text-xs font-medium text-foreground/80">
                    Exclusive promotions and events
                  </span>
                </li>
              </ul>
            </div>
          </AccordionContent>
        </AccordionItem>
      </Accordion>
    </>
  );
};
