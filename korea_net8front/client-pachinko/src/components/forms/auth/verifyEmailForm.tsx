import { zodResolver } from "@hookform/resolvers/zod";
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
import { AuthModalTab } from "@/types/modal.types";
import { AuthSchemas, authSchemas } from "@/validations/auth.schemas";
import { useFormStore } from "@/store/form.store";

type Props = {
  setActiveTab: (val: AuthModalTab) => void;
};

export default function VerifyEmailForm({ setActiveTab }: Props) {
  const { loading, startLoading, stopLoading } = useFormStore();
  const form = useForm<AuthSchemas["verifyEmail"]>({
    resolver: zodResolver(authSchemas.verifyEmail),
    defaultValues: {
      code: "",
    },
  });

  const onSubmit = (values: AuthSchemas["verifyEmail"]) => {
    return values;
  };

  return (
    <Form {...form}>
      <form className="space-y-6" onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col space-y-4">
          <FormField
            control={form.control}
            name="code"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Verification Code</FormLabel>
                <FormControl>
                  <Input placeholder="000000" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>
        <div className="grid grid-cols-2 gap-3">
          <Button
            onClick={() => setActiveTab("register")}
            type="button"
            className="w-full border-transparent rounded-xl"
            variant={"default"}
            size={"default"}
          >
            <IconBase icon={ICONS.ARROW_RIGHT} className="rotate-180 size-4" />
            Go back
          </Button>
          <Button
            className="w-full rounded-xl"
            variant={"primary"}
            size={"default"}
          >
            Verify
          </Button>
        </div>
      </form>
    </Form>
  );
}
