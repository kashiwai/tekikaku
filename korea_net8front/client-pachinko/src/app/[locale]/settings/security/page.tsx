import { useTranslations } from "next-intl";

import PrivateInfoForm from "@/components/forms/settings/privateInfoForm";
import TwoFactorForm from "@/components/forms/settings/twoFactorForm";
import TabWrapper from "@/components/wrapper/tabWrapper";

export default function Page() {
  const t = useTranslations("SETTINGS.SECURITY")

  return (
    <>
      <TabWrapper
        title={t("TWO_FACTOR")}
        description={t("TWO_FACTOR_DESCRIPTION")}
      >
        <TwoFactorForm />
      </TabWrapper>
    </>
  );
}
