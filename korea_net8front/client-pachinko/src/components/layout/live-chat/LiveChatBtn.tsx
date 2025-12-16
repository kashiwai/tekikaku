"use client";
import { AnimatePresence } from "framer-motion";

import { useLayoutStore } from "@/store/layout.store";

import LiveChat from "./LiveChat";
import { LogoType } from "@/types/settings.types";
import { useUserStore } from "@/store/user.store";

type Props = {
  logo: LogoType | undefined;
};

export default function LiveChatBtn({ logo }: Props) {
  const user = useUserStore((store) => store.user);
  const isNotificationOpen = useLayoutStore(
    (store) => store.isNotificationOpen
  );
  const isChatOpen = useLayoutStore((store) => store.isChatOpen);
  const toggleChat = useLayoutStore((store) => store.toggleChat);

  return (
    <>
      <AnimatePresence>
        {isChatOpen && user && (
          <LiveChat
            logo={logo}
            key={`chat-iframe`}
            isNotificationOpen={isNotificationOpen}
            toggleChat={toggleChat}
          />
        )}
      </AnimatePresence>
    </>
  );
}
