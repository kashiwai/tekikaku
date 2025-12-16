"use client";
import dynamic from "next/dynamic";

import Logo from "@/components/common/brand/logo";
import HeaderGuest from "@/components/layout/header/headerGuest";
import HeaderSearchBtn from "@/components/layout/header/headerSearch";
import { useLayoutStore } from "@/store/layout.store";
import { useUserStore } from "@/store/user.store";
import { LogoType } from "@/types/settings.types";

const HeaderLoggedIn = dynamic(
  () => import("@/components/layout/header/headerLoggedIn"),
  {
    ssr: false,
  }
);

export default function Header({ logo }: { logo: LogoType | undefined }) {
  const user = useUserStore((state) => state.user);
  const clearUser = useUserStore((state) => state.clearUser);
  const toggleChat = useLayoutStore((store) => store.toggleChat);
  const setNotificationOpen = useLayoutStore(
    (store) => store.setNotificationOpen
  );

  return (
    <header className="sticky top-0 z-40 w-full min-h-[70px] border-b border-neutral/5 bg-[#f7f7ff] dark:bg-[#0d1623]">
      <div className="container--main flex h-full items-center justify-between">
        {logo && <Logo logo={logo} parentClassName="lg:hidden flex" />}

        <div className="flex items-center gap-x-3 mx-4">
          <HeaderSearchBtn className="hidden lg:flex" />
        </div>

        {user ? (
          <HeaderLoggedIn
            user={user}
            clearUser={clearUser}
            toggleChat={toggleChat}
            setNotificationOpen={setNotificationOpen}
          />
        ) : (
          <HeaderGuest toggleChat={toggleChat} />
        )}
      </div>
    </header>
  );
}
