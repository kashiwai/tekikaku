import { useEffect, useState } from "react";

import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { banklist } from "@/config/bank.config";
import { API_ROUTES } from "@/config/routes.config";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import fetcher from "@/lib/fetcher";
import { AuthSchemas, authSchemas } from "@/validations/auth.schemas";
import { useFormStore } from "@/store/form.store";

export default function RegisterForm() {
  const registerModal = useModal("auth");
  const { loading, startLoading, stopLoading } = useFormStore();
  const [emailTime, setEmailTime] = useState<number>(0);
  const [emailConfirmed, setEmailConfirmed] = useState<boolean>(false);
  const t = useTranslations("REGISTER_FORM");

  const form = useForm<AuthSchemas["register"]>({
    resolver: zodResolver(authSchemas.register),
    defaultValues: {
      email: "",
      email_code: "",
      password: "",
      password_confirmation: "",
      nickname: "",
      subscription_code: "",
      bank: "",
      withdrawal_password: "",
      depositor_name: "",
      account_number: "",
      withdrawal_type: "",
      phone_number: "",
    },
  });

  async function onSubmit(values: AuthSchemas["register"]) {
    if (loading) return;

    if (!emailConfirmed) {
      return toastDanger("Verify your email first");
    }

    startLoading();

    const res = await fetcher(API_ROUTES.AUTH.REGISTER, {
      method: "POST",
      body: JSON.stringify({
        loginId: values.email,
        pw: values.password,
        ...(values.subscription_code.trim().length > 0 && {
          agencyCode: values.subscription_code,
        }),
        info: {
          nickname: values.nickname,
          telecom: "SKT",
          phone: values.phone_number.replaceAll("+", ""),
          level: 1,
          exp: 0,
          transaction: {
            realname: values.depositor_name,
            withdrawalType: values.withdrawal_type,
            bank: values.bank,
            bankNumber: values.account_number,
            pw: values.withdrawal_password,
          },
        },
      }),
    });

    stopLoading();

    if (!res.success) {
      if (res.code === 2007) {
        return form.setError("subscription_code", {
          message: res.message,
        });
      }
      if (res.code === 3002) {
        return form.setError("account_number", {
          message: res.message,
        });
      }
      if (res.code === 3001) {
        return form.setError("nickname", {
          message: res.message,
        });
      }

      return toastDanger(t(res.message));
    }

    toastSuccess(t("REGISTER_SUCCESS_APPLICATION"));
    registerModal.onClose();
  }

  const onSendEmail = async () => {
    const isValid = await form.trigger("email");

    if (loading || !isValid || emailTime > 0 || emailConfirmed) return;

    const email = form.getValues("email");

    startLoading();

    const res = await fetcher(API_ROUTES.AUTH.SEND_EMAIL_CODE, {
      method: "POST",
      body: JSON.stringify({ email, reset: false }),
    });

    stopLoading();

    if (!res.success) {
      toastDanger(t(res.message));
      return;
    }

    toastSuccess(t(res.message));
    setEmailTime(300);
    setEmailConfirmed(false);
  };

  const onSendCode = async () => {
    const isValid = await form.trigger("email_code");

    if (!isValid) return;

    const email = form.getValues("email");
    const code = form.getValues("email_code");

    const res = await fetcher(API_ROUTES.AUTH.VERIFY_EMAIL_CODE, {
      method: "PATCH",
      body: JSON.stringify({ email, code }),
    });

    if (!res.success) {
      return toastDanger(t("EMAIL_VERIFY_FAIL"));
    }

    toastSuccess(t(res.message));
    setEmailConfirmed(true);
  };

  useEffect(() => {
    if (emailTime <= 0) return;

    const interval = setInterval(() => {
      setEmailTime((prev) => {
        if (prev <= 1) {
          clearInterval(interval);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [emailTime]);

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col space-y-4">
          <div className="grid sm:grid-cols-[70%_auto] items-start gap-4">
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("EMAIL")}</FormLabel>
                  <FormControl>
                    <Input
                      placeholder={t("EMAIL_PLACEHOLDER")}
                      className="pr-24"
                      disabled={emailTime > 0 || emailConfirmed}
                      render={
                        <Button
                          type="button"
                          onClick={onSendEmail}
                          disabled={emailTime > 0 || emailConfirmed}
                          variant="success"
                          size={"sm"}
                          className={`${emailConfirmed
                              ? "w-auto !bg-transparent"
                              : "w-[85px]"
                            } rounded-lg text-xs !absolute right-1 top-1/2 -translate-y-1/2 !h-[20px] !min-h-[32px]`}
                        >
                          {emailConfirmed ? (
                            <IconBase
                              icon={ICONS.CHECKMARK}
                              className="text-success size-5"
                            />
                          ) : emailTime > 0 ? (
                            `${Math.floor(emailTime / 60)}:${String(
                              emailTime % 60
                            ).padStart(2, "0")}`
                          ) : (
                            t("SEND_CODE")
                          )}
                        </Button>
                      }
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="email_code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("EMAIL_CODE")}</FormLabel>
                  <FormControl>
                    <Input
                      placeholder={t("EMAIL_CODE_PLACEHOLDER")}
                      disabled={emailTime === 0 || emailConfirmed}
                      maxLength={6}
                      render={
                        emailConfirmed && (
                          <Button
                            onClick={onSendCode}
                            disabled={emailConfirmed}
                            type="button"
                            variant="success"
                            size={"sm"}
                            className={`${emailConfirmed
                                ? "w-auto !bg-transparent"
                                : "w-[85px]"
                              } rounded-lg text-xs !absolute right-1 top-1/2 -translate-y-1/2 !h-[20px] !min-h-[32px]`}
                          >
                            <IconBase
                              icon={ICONS.CHECKMARK}
                              className="text-success size-5"
                            />
                          </Button>
                        )
                      }
                      {...field}
                      onChange={(e) => {
                        field.onChange(e.target.value);
                        if (e.target.value.length === 6) {
                          onSendCode();
                        }
                      }}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
          </div>

          <div className="grid grid-cols-2 items-start gap-4">
            <FormField
              control={form.control}
              name="password"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("PASSWORD")}</FormLabel>

                  <FormControl>
                    <Input
                      type="password"
                      placeholder={t("PASSWORD_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="password_confirmation"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("PASSWORD_CONFIRMATION")}</FormLabel>
                  <FormControl>
                    <Input
                      type="password"
                      placeholder={t("PASSWORD_CONFIRMATION_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
          </div>

          <div className="grid grid-cols-2 items-start gap-4">
            <FormField
              control={form.control}
              name="nickname"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("NICKNAME")}</FormLabel>
                  <FormControl>
                    <Input
                      placeholder={t("NICKNAME_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="subscription_code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("SUBSCRIPTION_CODE")}</FormLabel>
                  <FormControl>
                    <Input
                      placeholder={t("SUBSCRIPTION_CODE_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
          </div>

          <div className="grid grid-cols-2 items-start gap-4">
            <FormField
              control={form.control}
              name="bank"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("BANK")}</FormLabel>
                  <Select onValueChange={field.onChange}>
                    <FormControl>
                      <SelectTrigger disabled={loading}>
                        <SelectValue placeholder={t("BANK_PLACEHOLDER")} />
                      </SelectTrigger>
                    </FormControl>

                    <SelectContent>
                      {Object.entries(banklist).map(([key, value]) => (
                        <SelectItem value={key} key={key}>
                          {value}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="withdrawal_password"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("WITHDRAWAL_PASSWORD")}</FormLabel>
                  <FormControl>
                    <Input
                      type="password"
                      placeholder={t("WITHDRAWAL_PASSWORD_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
          </div>

          <div className="grid grid-cols-2 items-start gap-4">
            <FormField
              control={form.control}
              name="depositor_name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("DEPOSITOR_NAME")}</FormLabel>
                  <FormControl>
                    <Input
                      placeholder={t("DEPOSITOR_NAME_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="account_number"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("ACCOUNT_NUMBER")}</FormLabel>
                  <FormControl>
                    <Input
                      placeholder={t("ACCOUNT_NUMBER_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
          </div>

          <div className="grid grid-cols-2 items-start gap-4">
            <FormField
              control={form.control}
              name="withdrawal_type"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("WITHDRAWAL_TYPE")}</FormLabel>
                  <Select onValueChange={field.onChange}>
                    <FormControl>
                      <SelectTrigger disabled={loading}>
                        <SelectValue
                          placeholder={t("WITHDRAWAL_TYPE_PLACEHOLDER")}
                        />
                      </SelectTrigger>
                    </FormControl>

                    <SelectContent>
                      <SelectItem value="KRW">KRW</SelectItem>
                      <SelectItem value="CRYPTO">Crypto</SelectItem>
                    </SelectContent>
                  </Select>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="phone_number"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("PHONE_NUMBER")}</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      placeholder={t("PHONE_NUMBER_PLACEHOLDER")}
                      disabled={loading}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
          </div>
        </div>
        <Button
          loading={loading}
          type="submit"
          className="w-full rounded-xl"
          variant={"primary"}
          size={"sm"}
        >
          {t("REGISTER")}
        </Button>
      </form>
    </Form>
  );
}
