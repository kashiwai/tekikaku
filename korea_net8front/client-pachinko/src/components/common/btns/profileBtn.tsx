import { useState } from "react";

import Link from "next/link";

import { Loader } from "lucide-react";
import { useTranslations } from "next-intl";

import { logout } from "@/actions/auth.actions";
import IconBase from "@/components/icon/iconBase";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { toastSuccess } from "@/components/ui/sonner";
import { PROFILE_MENU, ProfileMenu } from "@/config/profileMenu.config";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { LayoutState } from "@/store/layout.store";
import { UserState } from "@/store/user.store";
import { User } from "@/types/user.types";
import { getSocket } from "@/lib/socket/socket";
import { useFormStore } from "@/store/form.store";
import { useRouter } from "@/i18n/navigation";
import { ROUTES } from "@/config/routes.config";

type StoreActionProps = Pick<UserState, "clearUser"> &
  Pick<LayoutState, "setNotificationOpen">;

type Props = {
  user: User;
} & StoreActionProps;

export default function ProfileBtn({
  user,
  clearUser,
  setNotificationOpen,
}: Props) {
  const router = useRouter();
  const walletModal = useModal("wallet");
  const profileModal = useModal("profile");
  const { loading, startLoading, stopLoading } = useFormStore();
  const [dropdownIsOpen, setDropdownIsOpen] = useState(false);
  const t = useTranslations("PROFILE_MENU");

  const onLogout = async () => {
    startLoading();
    const res = await logout();
    stopLoading();

    const socket = getSocket();
    if (socket && socket.connected) {
      socket.emit("out_online", { userId: user.id, role: "user" });
      // socket.disconnect();
    }

    clearUser();
    toastSuccess(t(res.message));
    setNotificationOpen(false);
    router.push(ROUTES.HOME)
  };

  const onAction = (action: ProfileMenu["action"]) => {
    if (action) {
      setDropdownIsOpen(false);

      if (action === "wallet-modal") {
        walletModal.onOpen({ tab: "deposit" });
      } else if (action === "profile-modal") {
        profileModal.onOpen({ tab: "profile" });
      }
    }
  };

  return (
    <>
      <DropdownMenu
        open={dropdownIsOpen}
        onOpenChange={(open: boolean) => {
          if (!loading) setDropdownIsOpen(open);
        }}
      >
        <DropdownMenuTrigger>
          <div className="size-[45px] rounded-full border-[1.5px] border-primary p-0.5px flex">
            <span className="m-auto uppercase">
              {user.info.nickname[0]}.
              {user.info.nickname[user.info.nickname.length - 1]}
            </span>
          </div>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          align="center"
          className={`${loading ? "pointer-events-none opacity-90" : ""
            } rounded-xl p-1 linear-background flex flex-col w-[280px] border-neutral/10 shadow-lg mr-4`}
        >
          {PROFILE_MENU.map((menu, index) => (
            <DropdownMenuItem
              key={index}
              asChild
              className={loading ? "pointer-events-none" : ""}
            >
              <Link
                key={index}
                className="flex items-center gap-[6px] p-3 h-[41px] rounded-lg hover:bg-neutral/5 active:scale-95 cursor-pointer transition-all"
                href={menu.href}
                onClick={(e) => {
                  if (menu.action) {
                    e.preventDefault();
                    onAction(menu.action);
                  }
                }}
              >
                <IconBase icon={menu.icon} className="size-4" />
                <span>{t(menu.label)}</span>
              </Link>
            </DropdownMenuItem>
          ))}

          <DropdownMenuSeparator className="bg-neutral/5" />

          <button
            onClick={onLogout}
            className="flex items-center gap-[6px] p-3 h-[41px] rounded-lg hover:bg-danger/10 text-danger/90 active:scale-95 cursor-pointer transition-all"
          >
            {loading ? (
              <Loader className="animate-spin" />
            ) : (
              <IconBase icon={ICONS.LOGOUT} className="size-4" />
            )}
            <span>{t("LOGOUT")}</span>
          </button>
        </DropdownMenuContent>
      </DropdownMenu>
    </>
  );
}
