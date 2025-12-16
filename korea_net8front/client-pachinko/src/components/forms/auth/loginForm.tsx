import { useState } from "react";

import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useForm } from "react-hook-form";

import { login } from "@/actions/auth.actions";
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
import { toastSuccess } from "@/components/ui/sonner";
import { useModal } from "@/hooks/useModal";
import { AuthModalTab } from "@/types/modal.types";
import { AuthSchemas, authSchemas } from "@/validations/auth.schemas";
import { useAsync } from "@/hooks/useAsync";
import { useFormStore } from "@/store/form.store";
import { useUserStore } from "@/store/user.store";

type Props = {
  setActiveTab: (val: AuthModalTab) => void;
};

export default function LoginForm({ setActiveTab }: Props) {
  const t = useTranslations("LOGIN_FORM");
  const loginModal = useModal("auth");

  const { loading, startLoading, stopLoading } = useFormStore();
  const setUser = useUserStore((store) => store.setUser);

  const form = useForm<AuthSchemas["koreaLogin"]>({
    resolver: zodResolver(authSchemas.koreaLogin),
    defaultValues: {
      loginId: "",
      password: "",
    },
  });

  async function onSubmit(values: AuthSchemas["koreaLogin"]) {
    startLoading();
    const res = await login(values, Intl.DateTimeFormat().resolvedOptions().timeZone);
    stopLoading();

    if (!res.success) {
      form.setError("loginId", { message: res.message });
      form.setValue("password", "");
      return;
    }

    setUser(res.data);
    toastSuccess(t("SUCCESS"));
    loginModal.onClose();
  }

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col space-y-4">
          <FormField
            control={form.control}
            name="loginId"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t("EMAIL")}</FormLabel>
                <FormControl>
                  <Input
                    placeholder={t("EMAIL_PLACEHOLDER")}
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
            name="password"
            render={({ field }) => (
              <FormItem>
                <div className="flex items-center justify-between">
                  <FormLabel>{t("PASSWORD")}</FormLabel>
                  <button
                    type="button"
                    onClick={() => setActiveTab("reset-password")}
                    className="cursor-pointer text-xs font-medium underline text-foreground/60 hover:text-foreground"
                    aria-label="Reset password"
                  >
                    {t("FORGOT_PASSWORD")}
                  </button>
                </div>
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
        </div>
        <Button
          className="w-full rounded-xl"
          variant={"primary"}
          size={"sm"}
          loading={loading}
        >
          {t("SIGN_IN")}
        </Button>
      </form>
    </Form>
  );
}
