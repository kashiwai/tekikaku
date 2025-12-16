import { useEffect, useState } from "react";

import { useTranslations } from "next-intl";

import Logo from "@/components/common/brand/logo";
import TabFilter from "@/components/filter/tabFilter";
import LoginForm from "@/components/forms/auth/loginForm";
import RegisterForm from "@/components/forms/auth/registerForm";
import ResetPasswordForm from "@/components/forms/auth/resetPassword";
import VerifyEmailForm from "@/components/forms/auth/verifyEmailForm";
import ModalLayout from "@/components/modals/modalLayout";
import { ModalControls } from "@/hooks/useModal";
import { AuthModalTab } from "@/types/modal.types";
import { LogoType } from "@/types/settings.types";

type Props = ModalControls<"auth"> & { logo: LogoType | undefined };

export default function AuthModal({
  logo,
  isOpen,
  onClose,
  getParam,
  setParam,
}: Props) {
  const t = useTranslations("AUTH");

  const [activeTab, setActiveTab] = useState<AuthModalTab>(
    getParam("tab", "login")
  );

  useEffect(() => {
    setActiveTab(getParam("tab", "login"));
  }, [getParam]);

  const onTabChange = (tab: AuthModalTab) => {
    setParam("tab", tab);
  };

  const getTitle = () => {
    switch (activeTab) {
      case "login":
        return t("WELCOME_TO_GOODFRIENDS");
      case "register":
        return t("WELCOME_TO_GOODFRIENDS");
      case "reset-password":
        return t("RESET_PASSWORD");
      // case "verify-email":
      // return "Please Verify Email";
      default:
        return t("WELCOME_TO_GOODFRIENDS"); // Default fallback title
    }
  };

  const getDescription = () => {
    switch (activeTab) {
      case "login":
        return t("LOGIN_DESCRIPTION");
      case "register":
        return t("REGISTER_DESCRIPTION");
      case "reset-password":
        return t("RESET_PASSWORD_DESCRIPTION");
      // case "verify-email":
      // return `We Send 6 digit code on your email <br /> <span class="font-bold text-foreground">${verifyEmail}</span>`;
      default:
        return t("WELCOME_TO_GOODFRIENDS"); // Default fallback title
    }
  };

  return (
    <ModalLayout
      size={activeTab === "register" ? "md" : "default"}
      className="w-full max-w-[600px]"
      isOpen={isOpen}
      onClose={onClose}
      ariaLabel={activeTab}
    >
      <div className="flex flex-col items-center gap-1">
        <div className="flex flex-col items-center gap-2">
          {logo && (
            <Logo logo={logo} withTitle={false} className="w-[40px] h-[35px]" />
          )}
          <h6 className="text-xl font-semibold text-foreground">
            {getTitle()}
          </h6>
        </div>
        <div
          className="text-xs text-foreground/70 leading-[150%]"
          dangerouslySetInnerHTML={{ __html: getDescription() }}
        ></div>
      </div>

      {(activeTab === "login" || activeTab === "register") && (
        <TabFilter
          value={activeTab}
          onValueChange={onTabChange}
          tabs={["login", "register"]}
          pageName="AUTH"
        />
      )}

      {activeTab === "login" ? (
        <LoginForm setActiveTab={onTabChange} />
      ) : activeTab === "register" ? (
        <RegisterForm />
      ) : activeTab === "verify-email" ? (
        <VerifyEmailForm setActiveTab={onTabChange} />
      ) : (
        <ResetPasswordForm setActiveTab={onTabChange} />
      )}

      {/* {activeTab === "login" ||
        (activeTab === "register" && (
          <>
            <div className="flex items-center gap-[10px] justify-center">
              <span className="w-full max-w-[100px] h-[1px] bg-foreground/5"></span>
              <span className="text-xs font-medium text-foreground/60">
                or continue with
              </span>
              <span className="w-full max-w-[100px] h-[1px] bg-foreground/5"></span>
            </div>

            <div className="flex items-center justify-center gap-9">
              <button
                type="button"
                className="cursor-pointer hover:opacity-80 active:scale-95 active:opacity-100 transition-all"
              >
                <Image
                  src={"/imgs/social-platform-logos/google.svg"}
                  alt="google"
                  width={32}
                  height={32}
                  className="w-[32px]"
                />
              </button>
              <button
                type="button"
                className="cursor-pointer hover:opacity-80 active:scale-95 active:opacity-100 transition-all"
              >
                <Image
                  src={"/imgs/social-platform-logos/facebook.svg"}
                  alt="facebook"
                  width={32}
                  height={32}
                  className="w-[32px]"
                />
              </button>
              <button
                type="button"
                className="cursor-pointer hover:opacity-80 active:scale-95 active:opacity-100 transition-all"
              >
                <Image
                  src={"/imgs/social-platform-logos/apple.svg"}
                  alt="apple"
                  width={32}
                  height={32}
                  className="w-[32px]"
                />
              </button>
            </div>
          </>
        ))} */}
    </ModalLayout>
  );
}
