"use client";
import IconBase from "@/components/icon/iconBase";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { cn } from "@/lib/utils";
import { useChatStore } from "@/store/chat.store";
import { Chat } from "@/types/chat.types";
import Image from "next/image";
import { useTranslations } from "next-intl";

export default function MessagesTab({
  loading,
  onClick,
}: {
  loading: boolean;
  onClick: (chat: Chat) => void;
}) {
  const activeChat = useChatStore((store) => store.activeChat);
  const activeChatLoading = useChatStore((store) => store.activeChatLoading);
  const chats = useChatStore((store) => store.chats);
  const filteredChats = chats?.list.filter(
    (chat) => chat.id !== activeChat?.chat.id
  );
  const t = useTranslations("LIVE_CHAT");

  return (
    <>
      <div className="relative flex flex-col h-full flex-1 overflow-auto px-3 py-4 custom-scrollbar">
        {activeChat && (
          <div className="flex flex-col gap-1 border-b border-foreground/10 pb-4">
            <h2 className="font-semibold text-sm">{t("CURRENT_CHAT")}</h2>
            <MessageFrame
              {...activeChat.chat}
              hasActiveChat={!!activeChat}
              onClick={() => onClick(activeChat.chat)}
            />
          </div>
        )}

        {filteredChats && filteredChats.length > 0 && (
          <div className="flex flex-col gap-1 pt-4">
            <h2 className="font-semibold text-sm px-3">{t("OLD_CHATS")}</h2>
            {filteredChats.map((chat) => (
              <MessageFrame
                key={chat.id}
                hasActiveChat={false}
                {...chat}
                onClick={() => onClick(chat)}
              />
            ))}
          </div>
        )}
      </div>
    </>
  );
}

export const MessageFrame = ({
  id,
  user,
  office,
  lastMessage,
  unreadCount,
  lastMessageAuthor,
  onClick,
  hasActiveChat,
}: {
  onClick?: () => void;
  hasActiveChat: boolean;
} & Chat) => {
  const t = useTranslations("LIVE_CHAT");
  return !hasActiveChat ? (
    <button
      onClick={() => {
        onClick?.();
      }}
      className={cn(
        "w-full flex gap-1 items-center justify-between border-b py-2 border-foreground/10 hover:bg-foreground/5 px-3 cursor-pointer"
      )}
    >
      <div className="flex gap-1 items-start">
        <div className="relative grid place-content-center size-9 border border-neutral/10 rounded-full bg-black mt-1 overflow-hidden">
          <Image src={`/imgs/avatars/user-avatar-01.svg`} alt="avatar" fill />
        </div>
        <div className="flex flex-col items-start gap-0.5 flex-1 p-1 overflow-hidden">
          <div className="flex items-center flex-1 gap-2">
            <h6 className="truncate flex-1 text-sm font-medium">
              {lastMessage === "system:office-leaved"
                ? t("AGENT")
                : lastMessageAuthor === "user"
                  ? user?.userInfo.nickname
                  : ""}
            </h6>
          </div>
          <div
            className="text-start text-[13px] line-clamp-2 text-foreground/90 leading-5"
            dangerouslySetInnerHTML={{
              __html:
                lastMessage === "system:office-leaved"
                  ? t("AGENT_HAS_LEFTED_THE_CHAT")
                  : lastMessage ?? t("NO_MESSAGES_YET"),
            }}
          ></div>
        </div>
      </div>
      <IconBase
        icon={ICONS.CHEVRON_RIGHT}
        className="size-5 group-hover:opacity-100 opacity-60 transition-all"
      />
    </button>
  ) : (
    <Button
      onClick={() => onClick?.()}
      variant="primary"
      className="mt-2 w-full justify-between"
    >
      <div className="flex items-center gap-1.5">
        <span>{t("JOIN_ACTIVE_CHAT")}</span>
        {Number(unreadCount) !== 0 && (
          <div className="flex items-center justify-center text-xs font-semibold w-4.5 h-4.5 rounded-full bg-danger text-white">
            {Number(unreadCount) > 9 ? "9+" : unreadCount}
          </div>
        )}
      </div>
      <IconBase
        icon={ICONS.CHEVRON_RIGHT}
        className="size-5 group-hover:opacity-100 opacity-60 transition-all"
      />
    </Button>
  );
};
