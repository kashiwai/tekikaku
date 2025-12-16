"use client";
import ChatBtn from "@/components/common/btns/chatBtn";
import NoticeBtn from "@/components/common/btns/noticeBtn";
import ProfileBtn from "@/components/common/btns/profileBtn";
import WalletBtn from "@/components/common/btns/walletBtn";
import { LayoutState } from "@/store/layout.store";
import { UserState } from "@/store/user.store";
import { User } from "@/types/user.types";

type StoreActionProps = Pick<UserState, "clearUser"> &
  Pick<LayoutState, "setNotificationOpen" | "toggleChat">;

type Props = {
  user: User;
} & StoreActionProps;

export default function HeaderLoggedIn({
  user,
  clearUser,
  setNotificationOpen,
  toggleChat,
}: Props) {

  return (
    <div className="flex items-center space-x-2">
      <WalletBtn />
      <ChatBtn onClick={toggleChat} className="md:flex hidden" />
      <NoticeBtn className="relative md:flex hidden" />
      <ProfileBtn
        user={user}
        clearUser={clearUser}
        setNotificationOpen={setNotificationOpen}
      />
    </div>
  );
}
