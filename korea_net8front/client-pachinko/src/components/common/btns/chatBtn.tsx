"use client";
import { forwardRef, HTMLAttributes } from "react";
import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { useLayoutStore } from "@/store/layout.store";
import { cn } from "@/lib/utils";
import { useChatStore } from "@/store/chat.store";

type ChatBtnProps = HTMLAttributes<HTMLButtonElement>;

const ChatBtn = forwardRef<HTMLButtonElement, ChatBtnProps>(
  ({ className, ...props }, ref) => {
    const isChatOpen = useLayoutStore((store) => store.isChatOpen);
    const activeChat = useChatStore((store) => store.activeChat);
    return (
      <div className="relative">
        {activeChat && Number(activeChat.chat.unreadCount) !== 0 && (
          <div className="absolute -top-1 -right-1 z-10 flex items-center justify-center text-xs font-semibold w-4.5 h-4.5 rounded-full bg-danger text-white">
            {Number(activeChat.chat.unreadCount) > 9
              ? "9+"
              : activeChat.chat.unreadCount}
          </div>
        )}
        <Button
          ref={ref}
          size="icon_default"
          aria-label={isChatOpen ? "Close chat panel" : "Open chat panel"}
          aria-pressed={isChatOpen}
          variant={isChatOpen ? "primary_bordered" : "default"}
          className={cn(
            "transition-colors hover:bg-primary hover:text-white",
            className
          )}
          {...props}
        >
          <IconBase icon={ICONS.HEADPHONES} />
        </Button>
      </div>
    );
  }
);

ChatBtn.displayName = "ChatBtn";

export default ChatBtn;
