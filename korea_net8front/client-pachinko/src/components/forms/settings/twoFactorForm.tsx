"use client";
import { useState } from "react";

import Image from "next/image";

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
import { ICONS } from "@/constants/icons";
import { copyOnClipboard } from "@/utils/clipboard.utils";

export default function TwoFactorForm() {
  const [copied, setCopied] = useState(false);

  const form = useForm({
    defaultValues: {
      auth_app_code: "NMVGIYLOK5OX24RSMY4T6PROGJASQKBTMMSUWYTGOMSCM3Z6FZWA",
      two_fa_code: "",
    },
  });

  const onSubmit = () => {};

  return (
    <>
      <Form {...form}>
        <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
          <div className="flex flex-col space-y-4">
            <FormField
              control={form.control}
              name="auth_app_code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>
                    Copy this code to your authenticator app
                  </FormLabel>
                  <FormControl>
                    <Input
                      placeholder="0.000000"
                      {...field}
                      render={
                        <Button
                          type="button"
                          variant={`success`}
                          size={`icon_sm`}
                          className="right-1 text-black rounded-lg bg-[#00FF86] !absolute top-1 cursor-pointer"
                          onClick={() =>
                            copyOnClipboard(
                              form.getValues("auth_app_code"),
                              setCopied
                            )
                          }
                        >
                          {copied ? (
                            <IconBase
                              icon={ICONS.CHECKMARK}
                              className="size-4"
                            />
                          ) : (
                            <IconBase icon={ICONS.COPY} className="size-4" />
                          )}
                        </Button>
                      }
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <span className="text-danger/80 text-sm font-normal">
              Don’t let anyone see this
            </span>

            <div className="">
              <Image
                src={`/imgs/qr.jpg`}
                alt="qr"
                width={75}
                height={75}
                className="m-auto md:m-0 w-[154px] rounded-xl invert-100 border"
              />
            </div>

            <FormField
              control={form.control}
              name="two_fa_code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Two Factor Code</FormLabel>
                  <FormControl>
                    <Input
                      className="!bg-foreground/5"
                      placeholder="Enter two factor code"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <Button variant={`primary`} className="w-full md:w-max md:ml-auto">
              <Image
                src={"/imgs/social-platform-logos/google.svg"}
                alt="google"
                width={32}
                height={32}
                className="w-[16px]"
              />
              Re-verify with Google
            </Button>
          </div>
        </form>
      </Form>
    </>
  );
}
