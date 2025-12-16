import { useEffect } from "react";

import { zodResolver } from "@hookform/resolvers/zod";
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
import { ProfileSchemas, profileSchemas } from "@/validations/profile.schema";

import AvatarUploader from "./avatarUploader";

type Props = {
  initialData: ProfileSchemas['editProfile'];
  onEditStart: (file: File) => void;
};

export default function ProfileForm({ initialData, onEditStart }: Props) {
  const form = useForm<ProfileSchemas['editProfile']>({
    resolver: zodResolver(profileSchemas.editProfile),
    defaultValues: {
      name: "",
      avatar: undefined,
    },
  });

  useEffect(() => {
    form.setValue("name", initialData.name);
    form.setValue("avatar", initialData.avatar);
  }, [form, initialData]);

  const onSubmit = (values: ProfileSchemas['editProfile']) => {
    return values;
  };

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
        <div className={`grid grid-cols-[95px_auto] gap-6 items-end`}>
          <FormField
            control={form.control}
            name="avatar"
            render={({ field }) => (
              <FormItem>
                <FormControl>
                  <AvatarUploader
                    initialFile={form.getValues("avatar")}
                    onChange={(file) => {
                      field.onChange(file);
                      onEditStart(file);
                    }}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          <FormField
            control={form.control}
            name="name"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Name</FormLabel>
                <FormControl>
                  <Input
                    className="bg-foreground/5"
                    placeholder="Nick Jonson"
                    {...field}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>

        <Button className="w-full rounded-xl" variant={"primary"} size={"sm"}>
          Update
        </Button>
      </form>
    </Form>
  );
}
