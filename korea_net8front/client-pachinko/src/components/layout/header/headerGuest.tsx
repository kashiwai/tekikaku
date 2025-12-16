"use client";

import { FC } from "react";
import ChatBtn from "@/components/common/btns/chatBtn";
import { Button } from "@/components/ui/button";
import { useModal } from "@/hooks/useModal";
import { LayoutState } from "@/store/layout.store";
import { useTranslations } from "next-intl";
import { useUserStore } from "@/store/user.store";
import { toastDanger } from "@/components/ui/sonner";
import KoreaLoginButton from "@/components/auth/KoreaLoginButton";

type StoreActionProps = Pick<LayoutState, "toggleChat">;

type Props = StoreActionProps;

const HeaderGuest: FC<Props> = ({ toggleChat }) => {
  const authModal = useModal("auth");
  const user = useUserStore((store) => store.user);
  const t = useTranslations("HEADER");

  const openAuthModal = (tab: "login" | "register") => {
    authModal.onOpen({ tab });
  };

  const handleChat = () => {
    if(!user) return toastDanger("Please login live chat");

    toggleChat();
  };

  return (
    <div className="flex items-center gap-x-2">
      <KoreaLoginButton />
      <Button
        onClick={() => openAuthModal("login")}
        aria-label="Open login modal"
        title="Login"
      >
        {t("LOGIN")}
      </Button>
      <Button
        onClick={() => openAuthModal("register")}
        variant="primary"
        aria-label="Open registration modal"
        title="Register"
      >
        {t("REGISTER")}
      </Button>
      <ChatBtn onClick={handleChat} />
    </div>
  );
};

export default HeaderGuest;
