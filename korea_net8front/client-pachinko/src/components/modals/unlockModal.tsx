import { useState } from "react";

import Image from "next/image";
import Link from "next/link";

import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useForm } from "react-hook-form";

import { claimUnlockBonus } from "@/actions/api.actions";
import Logo from "@/components/common/brand/logo";
import IconBase from "@/components/icon/iconBase";
import ModalLayout from "@/components/modals/modalLayout";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { ROUTES } from "@/config/routes.config";
import { ICONS } from "@/constants/icons";
import { ModalControls } from "@/hooks/useModal";
import { useUserStore } from "@/store/user.store";
import { LogoType, SettingsType } from "@/types/settings.types";
import { bonusSchemas, BonusSchemas } from "@/validations/bonus.schema";

type Props = ModalControls<"unlock"> & {
  settings: SettingsType;
  logo: LogoType | undefined;
};

export default function UnlockBonusModal({logo, isOpen, onClose, settings }: Props) {
  const t = useTranslations("UNLOCK");
  const user = useUserStore((state) => state.user);
  const setUser = useUserStore((state) => state.setUser);

  const [loading, setLoading] = useState<boolean>(false);

  const form = useForm<BonusSchemas["claimBonus"]>({
    resolver: zodResolver(bonusSchemas.claimBonus),
    defaultValues: {
      amount: "",
    },
  });

  async function onSubmit(values: BonusSchemas["claimBonus"]) {
    if (!user) return;

    if (Number(values.amount) < Number(settings.site.minBonusConvert)) {
      form.setError("amount", { message: "MIN_BONUS" });
      return;
    }

    if (Number(values.amount) > Number(user?.bonus.unlocked)) {
      form.setError("amount", { message: "LACK_UNLOCK_BONUS" });
      return;
    }

    setLoading(true);
    /* eslint-disable @typescript-eslint/no-explicit-any */
    const res: any = await claimUnlockBonus(values);

    if (!res.success) {
      setLoading(false);
      return toastDanger(t(res.message));
    }

    setUser({
      ...user,
      bonus: {
        unlocked: res.data.unlocked,
        locked: user.bonus.locked,
      },
      wallets: {
        ...user.wallets,
        money: res.data.money,
      },
    });

    setLoading(false);
    toastSuccess(t("UNLOCK_BONUS_SUCCESS", { bonus: Number(values.amount) }));
  }

  return (
    user && (
      <ModalLayout
        isOpen={isOpen}
        onClose={onClose}
        ariaLabel={`${loading ? "loading" : "unlock-bonus"}`}
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
              {t("UNLOCK_BONUS")}
            </h6>
          </div>
        </div>
      </ModalLayout>
    )
  );
}
