import { useTranslations } from "next-intl";

import ClaimBonusDropForm from "@/components/forms/settings/claimCouponDropForm";
import WelcomeOfferForm from "@/components/forms/settings/welcomeReferralForm";
import TabWrapper from "@/components/wrapper/tabWrapper";

export default function Page() {
  const t = useTranslations("SETTINGS.OFFER")

  return (
    <>
      <TabWrapper title={t("REFERRER")} description={t("REFERRER_DESCRIPTION")}>
        <WelcomeOfferForm />
      </TabWrapper>

      <TabWrapper
        title={t("COUPON_CODE")}
        description={t("COUPON_DESCRIPTION")}
      >
        <ClaimBonusDropForm />
      </TabWrapper>
    </>
  );
}
