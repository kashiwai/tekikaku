import { useEffect, useState } from "react";

import Image from "next/image";

import Logo from "@/components/common/brand/logo";
import TabFilter from "@/components/filter/tabFilter";
import DepositForm from "@/components/forms/wallet/depositForm";
import WithdrawalForm from "@/components/forms/wallet/withdrawalForm";
import ModalLayout from "@/components/modals/modalLayout";
import ProgressBar from "@/components/progress/ProgressBar";
import { ModalControls } from "@/hooks/useModal";
import { WalletModalTab } from "@/types/modal.types";
import { LogoType } from "@/types/settings.types";

type Props = ModalControls<"wallet"> & {
  logo: LogoType | undefined;
};

const CurrentRolling = () => {
  return (
    <div className="flex flex-col gap-3">
      <div className="flex items-center justify-between">
        <span>Currency:</span>
        <div className="relative flex items-center gap-[6px]">
          <Image
            src={`/imgs/coins/btc.svg`}
            alt="btc"
            width={24}
            height={24}
            className="size-6 rounded-full"
          />
          <span className="text-foreground text-base font-medium">
            BTC (Bitcoin)
          </span>
        </div>
      </div>
      <div className="flex flex-col gap-6 pr-1 max-h-[calc(100vh-300px)] overflow-auto custom-scrollbar">
        <div className="p-4 flex flex-col gap-4 bg-foreground/5 rounded-xl">
          <div className="flex flex-col text-sm gap-0.5">
            <p className="font-bold text-foreground/70">
              Deposit Money: <span className="text-foreground">10,000</span>
            </p>
            <p className="font-bold text-foreground/70">
              Creation Date:{" "}
              <span className="text-foreground">2024-02-10 10:12:51</span>
            </p>
            <p className="font-bold text-foreground/70">
              Withdrawable: <span className="text-foreground">595</span>
            </p>
          </div>

          <div className="flex flex-col gap-4">
            <ProgressBar
              value={60}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Sports Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={10}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Casino Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={10}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Slot Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={100}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Hold'em Betting",
              }}
              footer={{
                rightText: "50,000 - 50,000",
              }}
            />
            <ProgressBar
              value={100}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Minigame Betting",
              }}
              footer={{
                rightText: "50,000 - 50,000",
              }}
            />
          </div>
        </div>

        <div className="p-4 flex flex-col gap-4 bg-foreground/5 rounded-xl">
          <div className="flex flex-col text-sm gap-0.5">
            <p className="font-bold text-foreground/70">
              Deposit Money: <span className="text-foreground">10,000</span>
            </p>
            <p className="font-bold text-foreground/70">
              Creation Date:{" "}
              <span className="text-foreground">2024-02-10 10:12:51</span>
            </p>
            <p className="font-bold text-foreground/70">
              Withdrawable: <span className="text-foreground">595</span>
            </p>
          </div>

          <div className="flex flex-col gap-4">
            <ProgressBar
              value={60}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Sports Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={10}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Casino Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={10}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Slot Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={100}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Hold'em Betting",
              }}
              footer={{
                rightText: "50,000 - 50,000",
              }}
            />
            <ProgressBar
              value={100}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Minigame Betting",
              }}
              footer={{
                rightText: "50,000 - 50,000",
              }}
            />
          </div>
        </div>

        <div className="p-4 flex flex-col gap-4 bg-foreground/5 rounded-xl">
          <div className="flex flex-col text-sm gap-0.5">
            <p className="font-bold text-foreground/70">
              Deposit Money: <span className="text-foreground">10,000</span>
            </p>
            <p className="font-bold text-foreground/70">
              Creation Date:{" "}
              <span className="text-foreground">2024-02-10 10:12:51</span>
            </p>
            <p className="font-bold text-foreground/70">
              Withdrawable: <span className="text-foreground">595</span>
            </p>
          </div>

          <div className="flex flex-col gap-4">
            <ProgressBar
              value={60}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Sports Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={10}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Casino Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={10}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Slot Betting",
              }}
              footer={{
                rightText: "0 - 50,000",
              }}
            />
            <ProgressBar
              value={100}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Hold'em Betting",
              }}
              footer={{
                rightText: "50,000 - 50,000",
              }}
            />
            <ProgressBar
              value={100}
              header={{
                leftText: "Set Rolling: 500%",
                rightText: "Minigame Betting",
              }}
              footer={{
                rightText: "50,000 - 50,000",
              }}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default function WalletModal({
  logo,
  isOpen,
  onClose,
  setParam,
  getParam,
}: Props) {
  const [activeTab, setActiveTab] = useState<WalletModalTab>(
    getParam("tab", "deposit")
  );

  useEffect(() => {
    setActiveTab(getParam("tab", "deposit"));
  }, [getParam]);

  const onTabChange = (tab: WalletModalTab) => {
    setParam("tab", tab);
  };
  const getTitle = () => {
    switch (activeTab) {
      case "deposit":
        return "Manage Your Wallet";
      case "withdraw":
        return "Manage Your Wallet";
      default:
        return "Current Rolling";
    }
  };

  return (
    <ModalLayout
      isOpen={isOpen}
      onClose={onClose}
      hasPrevBtn={activeTab === "current-rolling"}
      onPrevBtn={() => onTabChange("withdraw")}
      ariaLabel={activeTab}
    >
      <div className="flex flex-col items-center gap-1">
        <div className="flex flex-col items-center gap-2">
          {logo && (
            <Logo logo={logo} withTitle={false} className="w-[40px] h-[35px]" />
          )}
          <h6 className="text-xl font-semibold text-foreground">
            {getTitle()}
          </h6>
        </div>
      </div>

      {(activeTab === "deposit" || activeTab === "withdraw") && (
        <TabFilter
          value={activeTab}
          onValueChange={onTabChange}
          tabs={["deposit", "withdraw"]}
          pageName="WALLET"
        />
      )}

      {activeTab === "deposit" ? (
        <DepositForm />
      ) : activeTab === "withdraw" ? (
        <WithdrawalForm setActiveTab={onTabChange} />
      ) : (
        <CurrentRolling />
      )}
    </ModalLayout>
  );
}
