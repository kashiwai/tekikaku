import Logo from "@/components/common/brand/logo";
import UpdatePasswordForm from "@/components/forms/settings/updatePasswordForm";
import ModalLayout from "@/components/modals/modalLayout";
import { ModalControls } from "@/hooks/useModal";
import { LogoType } from "@/types/settings.types";
import { useTranslations } from "next-intl";

type Props = ModalControls<"update-password"> & {
  logo: LogoType | undefined;
};

export default function UpdatePasswordModal({ logo, isOpen, onClose }: Props) {
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

      <UpdatePasswordForm />
    </ModalLayout>
  );
}
