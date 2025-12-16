"use client";
import { useEffect } from "react";

import { useTranslations } from "next-intl";
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
import { useModal } from "@/hooks/useModal";
import { useUserStore } from "@/store/user.store";

export default function AccountInformationForm() {
  const t = useTranslations("SETTINGS.GENERAL");
  const updatePasswordModal = useModal("update-password");
  const updateWithdrawalPasswordModal = useModal("update-withdrawal-password");

  const user = useUserStore((state) => state.user);
  const form = useForm({
    defaultValues: {
      email: "",
      nickname: "",
      password: "",
      bank: "",
      bankNumber: "",
      withdrawal_password: "",
      phone: "",
    },
  });

  useEffect(() => {
    if (!user) {
      return;
    }
    form.setValue("email", user.loginId);
    form.setValue("nickname", user.info.nickname);
    form.setValue("bank", user.info.transaction.bank);
    form.setValue("bankNumber", user.info.transaction.bankNumber);
    form.setValue("phone", user.info.phone);
  }, [user, form]);

  const onSubmit = (values: any) => {
    return values;
  };

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col space-y-4">
          <div className="grid md:grid-cols-2 gap-4">
            <FormField
              control={form.control}
              name="email"
              disabled
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("EMAIL")}</FormLabel>
                  <FormControl>
                    <Input
                      className="!bg-foreground/5"
                      placeholder="Enter your name"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="nickname"
              disabled
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("NICKNAME")}</FormLabel>
                  <FormControl>
                    <Input
                      className="!bg-foreground/5"
                      placeholder="Enter your name"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>
          <div className="grid md:grid-cols-2 gap-4">
            <FormField
              control={form.control}
              name="bank"
              disabled
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("BANK")}</FormLabel>
                  <FormControl>
                    <Input
                      className="!bg-foreground/5"
                      placeholder="Enter your name"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="password"
              disabled
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("PASSWORD")}</FormLabel>
                  <FormControl>
                    <Input
                      className="bg-foreground/5"
                      placeholder="********"
                      {...field}
                      render={
                        <Button
                          onClick={() => updatePasswordModal.onOpen()}
                          type="button"
                          variant="primary"
                          size={"sm"}
                          className="w-[85px] rounded-lg text-xs !absolute right-1 top-1/2 -translate-y-1/2 !h-[20px] !min-h-[32px]"
                        >
                          {t("CHANGE")}
                        </Button>
                      }
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>
          <div className="grid md:grid-cols-2 gap-4">
            <FormField
              control={form.control}
              name="bankNumber"
              disabled
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("ACCOUNT_NUMBER")}</FormLabel>
                  <FormControl>
                    <Input
                      className="bg-foreground/5"
                      placeholder="Account number"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="withdrawal_password"
              disabled
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("WITHDRAWAL_PASSWORD")}</FormLabel>
                  <FormControl>
                    <Input
                      className="bg-foreground/5"
                      placeholder="********"
                      {...field}
                      render={
                        <Button
                          onClick={() => updateWithdrawalPasswordModal.onOpen()}
                          type="button"
                          variant="primary"
                          size={"sm"}
                          className="w-[85px] rounded-lg text-xs !absolute right-1 top-1/2 -translate-y-1/2 !h-[20px] !min-h-[32px]"
                        >
                          {t("CHANGE")}
                        </Button>
                      }
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <FormField
              control={form.control}
              disabled
              name="phone"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("PHONE_NUMBER")}</FormLabel>
                  <FormControl>
                    <Input
                      className="bg-foreground/5"
                      placeholder="Account number"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>
        </div>
      </form>
    </Form>
  );
}
