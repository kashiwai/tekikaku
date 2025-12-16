"use client";
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

export default function WelcomeOfferForm() {
  const form = useForm({
    defaultValues: {
      code: "",
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
              name="code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Code</FormLabel>
                  <FormControl>
                    <Input
                      className="!bg-foreground/5"
                      placeholder="Write code here"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <Button className="w-full md:w-max md:ml-auto" variant={`primary`}>
              Submit
            </Button>
          </div>
        </form>
      </Form>
    </>
  );
}
