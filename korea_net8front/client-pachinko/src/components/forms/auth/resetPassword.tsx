import { useEffect, useState } from "react";

import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useForm } from "react-hook-form";

import { resetPassword, sendAuthCode } from "@/actions/auth.actions";
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
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { API_ROUTES } from "@/config/routes.config";
import { ICONS } from "@/constants/icons";
import fetcher from "@/lib/fetcher";
import { AuthModalTab } from "@/types/modal.types";
import { AuthSchemas, authSchemas } from "@/validations/auth.schemas";
import { useFormStore } from "@/store/form.store";

type Props = {
  setActiveTab: (val: AuthModalTab) => void;
};

export default function ResetPasswordForm({ setActiveTab }: Props) {
  const t = useTranslations("RESET_PASSWORD");
  const { loading, startLoading, stopLoading } = useFormStore();
  const [emailTime, setEmailTime] = useState<number>(0);
  const [emailConfirmed, setEmailConfirmed] = useState<boolean>(false);

  const form = useForm<AuthSchemas["resetPassword"]>({
    resolver: zodResolver(authSchemas.resetPassword),
    defaultValues: {
      email: "",
      email_code: "",
      new_password: "",
      password_confirmation: "",
    },
  });

  const onSubmit = async (values: AuthSchemas["resetPassword"]) => {
    startLoading();

    const res = await resetPassword(values);

    stopLoading();

    if (!res.success) {
      return toastDanger(t(res.message));
    }

    toastSuccess(t(res.message));
    setActiveTab("login");
  };

  const onSendEmail = async () => {
    const isValid = await form.trigger("email");

    if (loading || !isValid || emailTime > 0 || emailConfirmed) return;

    const email = form.getValues("email");

    startLoading();
    const res = await sendAuthCode(email, true);
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
                        className={`${
                          emailConfirmed ? "w-auto !bg-transparent" : "w-[85px]"
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
                          className={`${
                            emailConfirmed
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
            name="new_password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t("NEW_PASSWORD")}</FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder={t("NEW_PASSWORD_PLACEHOLDER")}
                    disabled={!emailConfirmed}
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
                <FormLabel>{t("CONFIRM_PASSWORD")}</FormLabel>
                <FormControl>
                  <Input
                    type="password"
                    placeholder={t("CONFIRM_PASSWORD_PLACEHOLDER")}
                    disabled={!emailConfirmed}
                    {...field}
                  />
                </FormControl>
                <FormMessage t={t} />
              </FormItem>
            )}
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <Button
            onClick={() => setActiveTab("login")}
            type="button"
            className="w-full border-transparent rounded-xl"
            variant={"default"}
            size={"default"}
            disabled={loading}
          >
            <IconBase icon={ICONS.ARROW_RIGHT} className="rotate-180 size-4" />
            {t("BACK_TO_LOGIN")}
          </Button>
          <Button
            className="w-full rounded-xl"
            variant={"primary"}
            size={"default"}
            disabled={!emailConfirmed}
          >
            {t("RESET")}
          </Button>
        </div>
      </form>
    </Form>
  );
}
