import Image from "next/image";

import { useTranslations } from "next-intl";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { useUserStore } from "@/store/user.store";
import { useRouter } from "next/navigation";
import { ROUTES } from "@/config/routes.config";
import numeral from "numeral";

export default function WalletBtn() {
  const router = useRouter();
  const t = useTranslations("HEADER");
  const user = useUserStore((state) => state.user);
  const unlockWalletModal = useModal("unlock");

  return (
    user && (
      <>
        <div className="group flex items-center justify-between bg-neutral/5 border-neutral/5 rounded-2xl h-[42px] max-w-[320px] pl-0 hover:bg-primary p-1 transition-all">
          <Button
            onClick={() => router.push(ROUTES.ACCOUNT.BALANCE)}
            className="h-[36px] flex items-center gap-9 outline-none cursor-pointer pl-3 bg-transparent border-transparent pr-2"
          >
            <div className="flex items-center gap-2">
              <Image
                src={`/imgs/coins/btc.svg`}
                alt="btc"
                width={40}
                height={40}
                className="w-6 h-6 min-w-6"
              />
              <span className="text-xs font-medium group-hover:text-white transition-all">
                {t("USD")}
              </span>
            </div>
            <span className="text-xs font-medium ml-auto group-hover:text-white transition-all">
              {numeral(user.wallets.money).format("0,0.00")}
            </span>
          </Button>
        </div>
        <Button
          onClick={() => unlockWalletModal.onOpen()}
          variant={"default"}
          className="hover:bg-primary hover:text-white hidden md:flex"
        >
          <div className="md:flex items-center gap-1">
            <IconBase icon={ICONS.UNLOCKED} className="!size-4" />
            <span className="text-xs font-medium">
              {numeral(user.bonus.unlocked).format("0,0.00")}
            </span>
          </div>
          <div className="flex items-center gap-1">
            <IconBase icon={ICONS.LOCKED} className="!size-4" />
            <span className="text-xs font-medium md:flex hidden">
              {numeral(user.bonus.locked).format("0,0.00")}
            </span>
          </div>
        </Button>
      </>
    )
  );
}
