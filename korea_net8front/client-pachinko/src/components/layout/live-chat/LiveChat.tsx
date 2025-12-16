import { useCallback, useEffect, useState } from "react";

import { motion } from "framer-motion";
import { createPortal } from "react-dom";

import Logo from "@/components/common/brand/logo";
import IconBase from "@/components/icon/iconBase";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { chatApi } from "@/lib/api/chat.api";
import { useChatStore } from "@/store/chat.store";
import { LayoutState } from "@/store/layout.store";
import { Chat, ChatGroupFaqs } from "@/types/chat.types";

import ChatTab from "./tabs/ChatTab";
import HelpTab from "./tabs/HelpTab";
import HomeTab from "./tabs/HomeTab";
import MessagesTab from "./tabs/MessagesTab";
import { LogoType } from "@/types/settings.types";
import { useUserStore } from "@/store/user.store";
import { toastDanger, toastSuccess } from "@/components/ui/sonner";
import { initChat } from "@/actions/chat.actions";
import { useFormStore } from "@/store/form.store";
import { getSocket } from "@/lib/socket/socket";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Loader } from "lucide-react";
import { useTranslations } from "next-intl";

type ActiveFaqTab = keyof ChatGroupFaqs["grouped"] | null;
type ActiveTab = "home" | "messages" | "chat" | "help";

export default function LiveChat({
  logo,
  isNotificationOpen,
  toggleChat,
}: {
  logo: LogoType | undefined;
  isNotificationOpen: LayoutState["isNotificationOpen"];
  toggleChat: LayoutState["toggleChat"];
}) {
  const initialLoading = useChatStore((store) => store.initialLoading);
  const activeChatLoading = useChatStore((store) => store.activeChatLoading);
  const user = useUserStore((store) => store.user);
  const [chatType, setChatType] = useState<"preview" | "current" | null>(null);
  const { startDataLoading, dataLoading, stopDataLoading } = useFormStore();
  const setPreviewChat = useChatStore((store) => store.setPreviewChat);

  const activeChat = useChatStore((store) => store.activeChat);
  const setChats = useChatStore((store) => store.setChats);
  const setActiveChat = useChatStore((store) => store.setActiveChat);
  const chatGroupFaqs = useChatStore((store) => store.chatGroupFaqs);
  const setChatGroupFaqs = useChatStore((store) => store.setChatGroupFaqs);

  const [tab, setTab] = useState<ActiveTab>("home");
  const [mounted, setMounted] = useState<boolean>(false);
  const [activeFaqTab, setActiveFaqTab] = useState<ActiveFaqTab>(null);

  // Memoized handlers to prevent unnecessary re-renders
  const onMessageClick = useCallback(
    async (chat: Chat) => {
      if (!user) return;

      setTab("chat");

      if (chat.id === activeChat?.chat.id) {
        // Clicking on already active chat
        setPreviewChat(null);
        setChatType("current");
      } else {
        // Preview mode - load messages for this chat
        setChatType("preview");
        setPreviewChat(null);
        try {
          useChatStore.getState().setPreviewChatLoading(true)
          const loadMessages = await chatApi.getMessages({
            page: 1,
            limit: 35,
            chatId: chat.id,
          });
          useChatStore.getState().setPreviewChatLoading(false)
          useChatStore.getState().setPreviewChat(loadMessages)
        } catch (error) {
          console.error("Failed to load preview chat messages:", error);
          toastDanger("Failed to load chat messages");
        }
      }
    },
    [user, activeChat?.chat.id, setPreviewChat]
  );

  const handleEndChat = useCallback(async () => {
    if (!user || !activeChat) return;

    try {
      const socket = getSocket();
      socket.emit("livechat_leave", {
        chatId: activeChat.chat.id,
        participantId: user.id,
        role: "user",
      });

      setActiveChat(null);
      setTab("messages");

      startDataLoading();
      const chats = await chatApi.getChats({
        page: 1,
        limit: 35,
        userId: user.id,
      });
      setChats(chats);
    } catch (error) {
      console.error("Failed to end chat:", error);
      toastDanger("Failed to end chat. Please try again.");
    } finally {
      stopDataLoading();
    }
  }, [
    user,
    activeChat,
    setActiveChat,
    setChats,
    startDataLoading,
    stopDataLoading,
  ]);

  useEffect(() => {
    setMounted(true);
  }, []);

  useEffect(() => {
    let isMounted = true;

    const getFaqs = async () => {
      if (!chatGroupFaqs) {
        try {
          const groupedFaqs = await chatApi.groupedChatFaqs();
          if (isMounted && groupedFaqs) {
            setChatGroupFaqs(groupedFaqs);
          }
        } catch (error) {
          console.error("Failed to load FAQs:", error);
        }
      }
    };

    getFaqs();

    return () => {
      isMounted = false;
    };
  }, [chatGroupFaqs, setChatGroupFaqs]);

  const handleChatInit = useCallback(async () => {
    if (!user) return;

    try {
      startDataLoading();
      const res = await initChat({ userId: user.id });

      if (!res.success) {
        toastDanger(res.message);
        return;
      }

      const socket = getSocket();
      socket.emit("livechat_join", {
        chatId: res.data.chat.id,
        participantId: user.id,
        role: "user",
      });

      setActiveChat(res.data);
      setTab("chat");
      setChatType("current");
      toastSuccess("Chat started successfully");
    } catch (error) {
      console.error("Failed to initialize chat:", error);
      toastDanger("Failed to start chat. Please try again.");
    } finally {
      stopDataLoading();
    }
  }, [user, setActiveChat, startDataLoading, stopDataLoading]);

  // Memoized tab change handlers
  const handleTabChangeToHome = useCallback(() => setTab("home"), []);
  const handleTabChangeToMessages = useCallback(() => setTab("messages"), []);
  const handleTabChangeToHelp = useCallback(() => setTab("help"), []);
  const t = useTranslations("LIVE_CHAT");
  
  if (!mounted || typeof window === "undefined") return null;

  return createPortal(
    <motion.div
      initial={{ opacity: 0, y: 100 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: 100 }}
      transition={{ duration: 0.2, ease: "linear" }}
      className={`${
        isNotificationOpen ? "lg:right-[280px]" : "lg:right-4"
      } fixed bottom-0 lg:bottom-4 w-full lg:w-[500px] lg:max-w-[90vw] h-svh lg:h-[calc(100%-100px)] overflow-hidden bg-background border border-foreground/5 shadow-2xl lg:rounded-2xl pt-4 z-50`}
    >
      {activeChatLoading && (
        <div className="grid place-content-center absolute top-0 left-0 w-full h-full z-10 bg-black/40">
          <Loader className="animate-spin" />
        </div>
      )}
      {tab !== "messages" && tab !== "chat" && tab !== "help" && (
        <LinearBackground />
      )}

      <div className="flex flex-col flex-1 h-full">
        {tab === "messages" ? (
          <MessagesHeader toggleChat={toggleChat} />
        ) : tab === "chat" ? (
          <ChatHeader
            logo={logo}
            onTabChange={handleTabChangeToMessages}
            toggleChat={toggleChat}
            onEndChat={handleEndChat}
            isPreview={chatType === "preview"}
          />
        ) : tab === "help" ? null : (
          <BaseHeader logo={logo} toggleChat={toggleChat} />
        )}

        {tab === "home" ? (
          <HomeTab
            onTabChange={handleTabChangeToHelp}
            onStartChat={onMessageClick}
            setActiveFaqTab={setActiveFaqTab}
          />
        ) : tab === "messages" ? (
          <MessagesTab loading={dataLoading} onClick={onMessageClick} />
        ) : tab === "chat" ? (
          <ChatTab isPreview={chatType === "preview"} />
        ) : (
          <HelpTab
            activeFaqTab={activeFaqTab}
            setActiveFaqTab={setActiveFaqTab}
            toggleChat={toggleChat}
          />
        )}

        {!activeChat && !activeChatLoading && tab !== "chat" && (
          <Button
            onClick={handleChatInit}
            variant={`primary`}
            className="max-w-[90%] mx-auto w-full mb-4"
            loading={dataLoading}
          >
            <p className="text-sm font-medium text-white">
              {t("START_NEW_CHAT")}
            </p>
            <IconBase
              icon={ICONS.CHEVRON_RIGHT}
              className="size-5 text-white"
            />
          </Button>
        )}
        <div className="w-full flex items-center bg-foreground/5">
          <Button
            onClick={handleTabChangeToHome}
            className={`${
              tab === "home" ? "text-primary bg-primary/5" : "bg-transparent"
            } flex flex-col flex-1 max-h-max h-max py-3 gap-1 border-transparent rounded-none`}
          >
            <IconBase icon={ICONS.HOME} className="size-4.5" />
            <p className="text-[13px] font-semibold">{t("HOME")}</p>
          </Button>
          <Button
            onClick={handleTabChangeToMessages}
            className={`${
              tab === "messages" || tab === "chat"
                ? "text-primary bg-primary/5"
                : "bg-transparent"
            } flex flex-col flex-1 max-h-max h-max py-3 gap-1 border-y-0 border-x-foreground/5 rounded-none opacity-80`}
          >
            <IconBase icon={ICONS.CHAT} className="size-4.5" />
            <p className="text-[13px] font-semibold">{t("MESSAGES")}</p>
          </Button>
          <Button
            onClick={handleTabChangeToHelp}
            className={`${
              tab === "help" ? "text-primary bg-primary/5" : "bg-transparent"
            } flex flex-col flex-1 max-h-max h-max py-3 gap-1 border-transparent rounded-none`}
          >
            <IconBase icon={ICONS.HELP_CENTER} className="size-4.5" />
            <p className="text-[13px] font-semibold">{t("HELP")}</p>
          </Button>
        </div>
      </div>
    </motion.div>,
    document.body
  );
}

const LinearBackground = () => {
  const t = useTranslations("LIVE_CHAT");
  
  return (
    <div className="absolute top-0 left-0 w-full h-1/3 bg-linear-to-b from-[#dfcbff] dark:from-primary/60 to-background/0 z-0"></div>
  );
};

const BaseHeader = ({
  logo,
  toggleChat,
}: {
  logo: LogoType | undefined;
  toggleChat: LayoutState["toggleChat"];
}) => {
  const t = useTranslations("LIVE_CHAT");
  
  return (
    <div className="flex h-max flex-col px-6 z-10">
      <div className="flex items-center justify-between py-2 ">
        {logo && <Logo logo={logo} withTitle className="" />}
        <div className="flex items-center gap-3">
          <div className="flex -space-x-2 *:data-[slot=avatar]:ring-2 *:data-[slot=avatar]:ring-primary/60 *:data-[slot=avatar]:bg-background">
            <Avatar>
              <AvatarImage
                src="/imgs/avatars/user-avatar-02.svg"
                alt="@leerob"
              />
              <AvatarFallback>LR</AvatarFallback>
            </Avatar>
            <Avatar>
              <AvatarImage
                src="/imgs/avatars/user-avatar-01.svg"
                alt="@shadcn"
              />
              <AvatarFallback>CN</AvatarFallback>
            </Avatar>
            <Avatar>
              <AvatarImage
                src="/imgs/avatars/user-avatar-02.svg"
                alt="@leerob"
              />
              <AvatarFallback>LR</AvatarFallback>
            </Avatar>
            <Avatar>
              <AvatarImage
                src="/imgs/avatars/user-avatar-03.svg"
                alt="@evilrabbit"
              />
              <AvatarFallback>ER</AvatarFallback>
            </Avatar>
          </div>
          <Button
            onClick={toggleChat}
            size={`icon_sm`}
            className="rounded-full bg-[#e9e9e9] dark:bg-foreground/10"
          >
            <IconBase icon={ICONS.CLOSE_X} className="size-4" />
          </Button>
        </div>
      </div>
      <div className="flex py-4 flex-col">
        <p className="text-2xl font-medium opacity-70">{t("HEY_BROWN_LIN")} 👋</p>
        <p className="text-xl font-semibold">{t("HOW_WE_CAN_HELP_YOU")}</p>
      </div>
    </div>
  );
};

const MessagesHeader = ({
  toggleChat,
}: {
  toggleChat: LayoutState["toggleChat"];
}) => {
  const t = useTranslations("LIVE_CHAT");
  
  return (
    <div className="w-full flex items-center justify-between px-6 pb-6 border-b border-foreground/10">
      <h6 className="text-xl">{t("MESSAGES")}</h6>
      <Button onClick={toggleChat} size={`icon_sm`} className="rounded-full">
        <IconBase icon={ICONS.CLOSE_X} className="size-4" />
      </Button>
    </div>
  );
};

const ChatHeader = ({
  logo,
  onTabChange,
  toggleChat,
  onEndChat,
  isPreview,
}: {
  logo: LogoType | undefined;
  onTabChange: () => void;
  toggleChat: LayoutState["toggleChat"];
  onEndChat: () => void;
  isPreview: boolean;
}) => {
  const activeChat = useChatStore((store) => store.activeChat);
  const t = useTranslations("LIVE_CHAT");
  
  return (
    <div className="w-full flex items-center justify-between px-6 pb-6 border-b border-foreground/10">
      <div className="flex items-center gap-1">
        <Button
          onClick={onTabChange}
          size={`icon_sm`}
          className="bg-transparent border-transparent rounded-xl"
        >
          <IconBase icon={ICONS.CHEVRON_LEFT} className="size-5" />
        </Button>
        <div className="flex items-center gap-1.5">
          {logo && <Logo logo={logo} hasHref={false} />}
          <div className="flex flex-col">
            <p className="text-base font-semibold">{t("GOODFRIEND_SUPPORT")}</p>
            <span className="text-[13px] font-medium opacity-70">
              {t("THE_TEAM_CAN_ALSO_HELP")}
            </span>
          </div>
        </div>
      </div>

      <div className="flex items-center gap-3">
        {activeChat?.chat.status !== "cancel" && !isPreview && (
          <Dialog>
            <DialogTrigger asChild>
              <Button
                size={`icon_sm`}
                variant={`danger_ghost`}
                className="rounded-full mt-2 border border-danger/10"
              >
                <IconBase icon={ICONS.LOGOUT} className="size-4" />
              </Button>
            </DialogTrigger>
            <DialogContent className="!max-w-[480px] border border-foreground/10 rounded-xl">
              <DialogTitle className="sr-only">{""}</DialogTitle>
              <div className="mt-12"></div>
              <div className="size-20 bg-danger/10 mx-auto grid place-content-center rounded-full">
                <IconBase
                  icon={ICONS.WARNING}
                  className="size-12 mx-auto text-danger"
                />
              </div>
              <h2 className="text-sm text-foreground/80 font-medium max-w-[320px] m-auto text-center">
                {t("REALLY_WANT_TO_END_THIS_CHAT")}
              </h2>
              <div className="grid grid-cols-2 items-center gap-3 mt-16">
                <DialogClose asChild>
                  <Button variant={`default`} className="w-full !text-xs">
                    {t("CANCEL")}
                  </Button>
                </DialogClose>
                <DialogClose asChild>
                  <Button
                    onClick={onEndChat}
                    variant={`primary`}
                    className="w-full !text-xs"
                  >
                    {t("END_THE_CHAT")}
                  </Button>
                </DialogClose>
              </div>
            </DialogContent>
          </Dialog>
        )}
        <Button
          onClick={toggleChat}
          size={`icon_sm`}
          className="rounded-full mt-2"
        >
          <IconBase icon={ICONS.CLOSE_X} className="size-4" />
        </Button>
      </div>
    </div>
  );
};
