"use client";
import { useState } from "react";

import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useForm } from "react-hook-form";

import { getCouponBonus } from "@/actions/api.actions";
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
import { useUserStore } from "@/store/user.store";
import { User } from "@/types/user.types";
import { OfferSchema, offerSchema } from "@/validations/offer.schema";
import { useFormStore } from "@/store/form.store";

export default function ClaimBonusDropForm() {
  const t = useTranslations("SETTINGS.OFFER")
  const user = useUserStore((state) => state.user);
  const setUser = useUserStore((state) => state.setUser);
  const { loading, startLoading, stopLoading } = useFormStore();

  const form = useForm<OfferSchema>({
    resolver: zodResolver(offerSchema),
    defaultValues: {
      code: "",
    },
  });

  const onSubmit = async (values: OfferSchema) => {
    if (!user) return;

    startLoading();
    const response = await getCouponBonus(values.code)
    stopLoading();

    if (!response.success) return toastDanger(t(response.message));

    const { user: updatedUser, bonus, isLock } = response.data as { user: User, bonus: number, isLock: boolean }
    setUser(updatedUser)
    toastSuccess(t("GET_BONUS_SUCCESS", { bonus, lockStatus: isLock ? t("LOCKED") : t("UNLOCKED") }))
  };

  return (
    <>
      <Form {...form}>
        <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
          <div className="flex flex-col space-y-4">
            <FormField
              control={form.control}
              name="code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>{t("CODE")}</FormLabel>
                  <FormControl>
                    <Input
                      className="!bg-foreground/5"
                      placeholder="Write code here"
                      maxLength={6}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage t={t} />
                </FormItem>
              )}
            />
            <Button className="w-full md:w-max md:ml-auto" variant={`primary`} loading={loading}>
              {t("SUBMIT")}
            </Button>
          </div>
        </form>
      </Form>
    </>
  );
}
