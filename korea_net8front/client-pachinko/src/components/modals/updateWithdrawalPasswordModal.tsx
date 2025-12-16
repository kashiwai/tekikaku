import Logo from "@/components/common/brand/logo";
import ModalLayout from "@/components/modals/modalLayout";
import { ModalControls } from "@/hooks/useModal";
import UpdateWithdrawalPasswordForm from "../forms/settings/updateWithdrawalForm";
import { useTranslations } from "next-intl";
import { LogoType } from "@/types/settings.types";

type Props = ModalControls<"update-withdrawal-password"> & {
  logo: LogoType | undefined;
};

export default function UpdateWithdrawalPasswordModal({
  logo,
  isOpen,
  onClose,
}: Props) {
  const t = useTranslations("SETTINGS.SECURITY");
  return (
    <ModalLayout
      isOpen={isOpen}
      onClose={onClose}
      ariaLabel={`Update password`}
    >
      <div className="flex flex-col items-center gap-1">
        <div className="flex flex-col items-center gap-2">
          {logo && (
            <Logo logo={logo} withTitle={false} className="w-[40px] h-[35px]" />
          )}
          <h6 className="text-xl font-semibold text-foreground">
            {t("UPDATE_PASSWORD")}
          </h6>
        </div>
      </div>

      <UpdateWithdrawalPasswordForm />
    </ModalLayout>
  );
}
