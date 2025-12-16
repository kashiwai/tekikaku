import { useEffect, useState } from "react";

import { useForm } from "react-hook-form";

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
import { useUserStore } from "@/store/user.store";
import { zodResolver } from "@hookform/resolvers/zod";
import {
  SettingsSchemas,
  settingsSchemas,
} from "@/validations/settings.schema";
import fetcher from "@/lib/fetcher";
import { API_ROUTES } from "@/config/routes.config";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { useTranslations } from "next-intl";
import IconBase from "@/components/icon/iconBase";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { useFormStore } from "@/store/form.store";

export default function UpdateWithdrawalPasswordForm() {
  const updatePasswordModal = useModal("update-withdrawal-password");
  const user = useUserStore((state) => state.user);
  const { loading, startLoading, stopLoading } = useFormStore();
  const [emailTime, setEmailTime] = useState<number>(0);
  const [emailConfirmed, setEmailConfirmed] = useState<boolean>(false);
  // GIGI
  const t = useTranslations("SETTINGS.SECURITY");

  const form = useForm<SettingsSchemas["updatePassword"]>({
    resolver: zodResolver(settingsSchemas.updatePassword),
    defaultValues: {
      email: "",
      email_code: "",
      password: "",
      password_confirmation: "",
    },
  });

  useEffect(() => {
    if (!user) {
      return;
    }

    form.setValue("email", user.loginId);
  }, [user, form]);

  const onSubmit = async (values: SettingsSchemas["updatePassword"]) => {
    if (loading) return;

    startLoading();

    const res = await fetcher(API_ROUTES.AUTH.CHANGE_WITHDRAWL_PASSWORD, {
      method: "PATCH",
      body: JSON.stringify({
        email: user?.loginId ?? values.email,
        code: values.email_code,
        newPw: values.password,
      }),
    });

    stopLoading();

    if (!res.success) {
      return toastDanger(t(res.message));
    }

    toastSuccess(t(res.message));
    updatePasswordModal.onClose();
  };

  const onSendEmail = async () => {
    const isValid = await form.trigger("email");

    if (loading || !isValid || emailTime > 0 || emailConfirmed) return;

    const email = form.getValues("email");

    startLoading();

    const res = await fetcher(API_ROUTES.AUTH.SEND_EMAIL_CODE, {
      method: "POST",
      body: JSON.stringify({ email, reset: true }),
    });

    stopLoading()

    if (!res.success) {
      toastDanger(t(res.message));
      return;
    }

    toastSuccess(t(res.message));
    setEmailTime(300); // 5 minutes
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
          <FormField
            control={form.control}
            name="email"
            disabled
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t("EMAIL")}</FormLabel>
                <FormControl>
                  <Input
                    placeholder="example@gmail.com"
                    className="pr-24"
                    render={
                      <Button
                        type="button"
                        onClick={onSendEmail}
                        disabled={emailTime > 0 || emailConfirmed}
                        variant="success"
                        size={"sm"}
                        className="w-[85px] rounded-lg text-xs !absolute right-1 top-1/2 -translate-y-1/2 !h-[20px] !min-h-[32px]"
                      >
                        {emailTime > 0
                          ? `${Math.floor(emailTime / 60)}:${String(
                            emailTime % 60
                          ).padStart(2, "0")}`
                          : t("SEND_CODE")}{" "}
                      </Button>
                    }
                    {...field}
                  />
                </FormControl>
                <FormMessage />
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

          <FormField
            control={form.control}
            name="password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t("NEW_PASSWORD")}</FormLabel>

                <FormControl>
                  <Input type="password" placeholder="********" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          <FormField
            control={form.control}
            name="password_confirmation"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t("CONFIRM_NEW_PASSWORD")}</FormLabel>
                <FormControl>
                  <Input type="password" placeholder="********" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>
        <Button
          loading={loading}
          className="w-full rounded-xl"
          variant={"primary"}
          size={"sm"}
        >
          {t("UPDATE")}
        </Button>
      </form>
    </Form>
  );
}
