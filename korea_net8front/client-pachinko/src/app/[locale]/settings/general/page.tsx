import { useTranslations } from "next-intl";

import AccountInformationForm from "@/components/forms/settings/accountInformationForm";
import TabWrapper from "@/components/wrapper/tabWrapper";

export default function Page() {
  const t = useTranslations("SETTINGS.GENERAL")

  return (
    <TabWrapper title={t("ACCOUNT_INFORMATION")}>
      <AccountInformationForm />
    </TabWrapper>
  );
}
