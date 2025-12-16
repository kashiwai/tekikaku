"use client";
import { useEffect, useState } from "react";

import Image from "next/image";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { ICONS } from "@/constants/icons";
import { useSelect } from "@/hooks/useSelect";
import { WalletModalTab } from "@/types/modal.types";
import { copyOnClipboard } from "@/utils/clipboard.utils";
import { walletSchemas, WalletSchemas } from "@/validations/wallet.schema";
import { useUserStore } from "@/store/user.store";
import { useTranslations } from "next-intl";
import { walletApi } from "@/lib/api/wallet.api";
import { WithdrawResponse } from "@/types/wallet.types";
import { formatMinutes } from "@/utils/time.utils";
import { useCountdown } from "@/hooks/useCountdown";
import { deposit, withdraw } from "@/actions/wallet.actions";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { useModal } from "@/hooks/useModal";
import numeral from "numeral";
import { useFormStore } from "@/store/form.store";

type Props = {
  col?: boolean;
  setActiveTab?: (val: WalletModalTab) => void;
};

const WithdrawalCrypto = ({ col }: { col: boolean }) => {
  const [copied, setCopied] = useState(false);
  const currencySelect = useSelect();
  const networkSelect = useSelect();

  const form = useForm<WalletSchemas["withdarawlCrypto"]>({
    resolver: zodResolver(walletSchemas.withdrawalCrypto),
    defaultValues: {
      network: "",
      currency: "",
      amount: "",
    },
  });

  const onSubmit = (values: WalletSchemas["withdarawlCrypto"]) => {
    return values;
  };

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
      </form>
    </Form>
  );
};

const WithdrawalFiat = ({
  withdrawalInfo,
  loading,
  col,
  setActiveTab,
}: Props & { loading: boolean; withdrawalInfo: WithdrawResponse | null }) => {
  const currencySelect = useSelect();
  const user = useUserStore((store) => store.user);
  const [formLoading, setLoading] = useState<boolean>(false);
  const walletModal = useModal("wallet");

  const form = useForm<WalletSchemas["withdrawalFiat"]>({
    resolver: zodResolver(walletSchemas.withdrawalFiat),
    defaultValues: {
      currency: "btc",
      bank: "",
      account_number: "",
      withdrawal_name: "",
      withdrawal_password: "",
      amount: "",
    },
  });
  const t = useTranslations("WALLET");
  const { remaining, done } = useCountdown(withdrawalInfo?.possibleWithdraw);

  useEffect(() => {
    if (user) {
      form.setValue("bank", user.info.transaction.bank);
      form.setValue("account_number", user.info.transaction.bankNumber);
      form.setValue("withdrawal_name", user.info.transaction.realname);
    }
  }, [user]);

  const onSubmit = async (values: WalletSchemas["withdrawalFiat"]) => {
    setLoading(true);
    const res = await withdraw({
      amount: values.amount,
      pw: values.withdrawal_password,
      ssr: false,
    });
    setLoading(false);

    if (!res.success) {
      return toastDanger(t(res.message));
    }

    toastSuccess(t("SUCCESS"));
    form.setValue("amount", "");
    form.setValue("withdrawal_password", "");
    walletModal.onClose();
  };

  return (
    <Form {...form}>
      <form
        className={`${
          loading ? "pointer-events-none opacity-60" : ""
        } space-y-6`}
        onSubmit={form.handleSubmit(onSubmit)}
      >
      </form>
    </Form>
  );
};

export default function WithdrawalForm({ col = true, setActiveTab }: Props) {
  const withdrawalType = useSelect();
  const { loading, startLoading, stopLoading } = useFormStore();
  const [type, setType] = useState<"fiat" | "crypto">("fiat");
  const t = useTranslations("WALLET");
  const [withdrawalInfo, setWithdrawalInfo] = useState<WithdrawResponse | null>(
    null
  );

  useEffect(() => {
    const getData = async () => {
      startLoading();
      const data = await walletApi.withdrawal.get({ ssr: false });
      stopLoading();
      setWithdrawalInfo(data);
    };
    getData();
  }, []);

  return (
    <>
      <div className="flex flex-col gap-2">
        <Label className="text-sm text-foreground/80">
          {t("WITHDRAW_TYPE")}
        </Label>
        <Select
          {...withdrawalType}
          onValueChange={(val) => setType(val as "fiat" | "crypto")}
          defaultValue={"fiat"}
        >
          <SelectTrigger>
            <SelectValue placeholder={t("SELECT_WITHDRAW")} />
          </SelectTrigger>

          <SelectContent className="max-w-full">
            <SelectItem
              value="crypto"
              className="flex items-center gap-[6px]"
              disabled
            >
              <span>{t("CRYPTO")}</span>
            </SelectItem>
            <SelectItem value="fiat" className="flex items-center gap-[6px]">
              <span>{t("FIAT")}</span>
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      {type === "crypto" ? (
        <WithdrawalCrypto col={col} />
      ) : (
        <WithdrawalFiat
          withdrawalInfo={withdrawalInfo}
          loading={loading}
          col={col}
          setActiveTab={setActiveTab}
        />
      )}
    </>
  );
}
