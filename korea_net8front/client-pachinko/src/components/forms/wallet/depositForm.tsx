"use client";
import { useEffect, useState } from "react";
import { format, toZonedTime } from "date-fns-tz";
import { parse } from "date-fns";
import Image from "next/image";

import { zodResolver } from "@hookform/resolvers/zod";
import { Loader } from "lucide-react";
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
import { Input, InputControls } from "@/components/ui/input";
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
import { copyOnClipboard } from "@/utils/clipboard.utils";
import { WalletSchemas, walletSchemas } from "@/validations/wallet.schema";
import { useUserStore } from "@/store/user.store";
import { useTranslations } from "next-intl";
import { deposit } from "@/actions/wallet.actions";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { useModal } from "@/hooks/useModal";
import { walletApi } from "@/lib/api/wallet.api";

const DepositCrypto = () => {
  const [copied, setCopied] = useState(false);
  const currencySelect = useSelect();
  const networkSelect = useSelect();

  const form = useForm<WalletSchemas["depositCrypto"]>({
    resolver: zodResolver(walletSchemas.depositCrypto),
    defaultValues: {
      network: "",
      currency: "",
    },
  });

  const onSubmit = (values: WalletSchemas["depositCrypto"]) => {
    return values;
  };

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
        
      </form>
    </Form>
  );
};

const DepositFiat = ({ col = true }: { col: boolean }) => {
  const [address, setAddress] = useState<null | string>(null);
  const [loading, setLoading] = useState<boolean>(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const user = useUserStore((store) => store.user);
  const form = useForm<WalletSchemas["depositFiat"]>({
    reValidateMode: "onChange",
    resolver: zodResolver(walletSchemas.depositFiat),
    defaultValues: {
      amount: "",
      depositor_name: "",
    },
  });
  const [bankAccountId, setBankAccountId] = useState<null | number>(null);
  const t = useTranslations("WALLET");

  useEffect(() => {
    if (user) {
      form.setValue("depositor_name", user?.info.transaction.realname ?? "");
    }
  }, [user]);

  const amount = form.watch("amount");

  const onSubmit = async (values: WalletSchemas["depositFiat"]) => {
    if (!address || !bankAccountId) return;
    setLoading(true);
    const res = await deposit({
      amount: values.amount,
      bankAccountId,
      ssr: false,
    });

    setLoading(false);

    if (!res.success) {
      if (res.data && res.data.start_at && res.data.end_at) {
        const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const today = new Date();
        const todayStr = format(today, "yyyy-MM-dd");

        const startDateTimeStr = `${todayStr} ${res.data.start_at}`;
        const endDateTimeStr = `${todayStr} ${res.data.end_at}`;

        const startDate = parse(
          startDateTimeStr,
          "yyyy-MM-dd hh:mm a",
          new Date()
        );
        const endDate = parse(endDateTimeStr, "yyyy-MM-dd hh:mm a", new Date());
        const startInUserTZ = toZonedTime(startDate, userTimezone);
        const endInUserTZ = toZonedTime(endDate, userTimezone);

        return toastDanger(
          `Time restriction from ${format(
            startInUserTZ,
            "hh:mm a"
          )} to ${format(endInUserTZ, "hh:mm a")}`
        );
      } else {
        return toastDanger(res.message);
      }
    }

    toastSuccess(t("SUCCESS"));
    // setAddress(null);
    // walletModal.onClose()
  };

  const handleAddress = async () => {
    if (loading || address) return;
    setLoading(true);
    const depositAddressResponse = await walletApi.deposit.address({
      ssr: false,
    });

    setLoading(false);

    if (depositAddressResponse?.type == "duplicate") {
      toastDanger(t("ALREADY_REQUESTED"));
      return;
    } else if (depositAddressResponse?.type == "request") {
      toastSuccess(t("REQUEST_SUCCESS"));
    } else if (depositAddressResponse?.type == "approve") {
      setAddress(depositAddressResponse.address ?? "");
      setBankAccountId(depositAddressResponse.bankAccountId ?? null);
    }
  };

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
      </form>
    </Form>
  );
};

export default function DepositForm({ col = true }: { col?: boolean }) {
  const depositType = useSelect();
  const [type, setType] = useState<"fiat" | "crypto">("fiat");
  const t = useTranslations("WALLET");

  return (
    <>
      <div className="flex flex-col gap-2">
        <Label className="text-sm text-foreground/80">
          {t("DEPOSIT_TYPE")}
        </Label>
        <Select
          {...depositType}
          onValueChange={(val) => setType(val as "fiat" | "crypto")}
          defaultValue={"fiat"}
        >
          <SelectTrigger>
            <SelectValue placeholder={t("SELECT_DEPOSIT")} />
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

      {type === "crypto" ? <DepositCrypto /> : <DepositFiat col={col} />}
    </>
  );
}
