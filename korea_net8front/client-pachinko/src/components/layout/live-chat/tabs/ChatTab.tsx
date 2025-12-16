// ChatTab.tsx - Fix the loading state logic
"use client";
import {
  ChangeEvent,
  FormEvent,
  useCallback,
  useEffect,
  useRef,
  useState,
} from "react";

import Image from "next/image";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { ActiveChat, useChatStore } from "@/store/chat.store";
import { getSocket } from "@/lib/socket/socket";
import { useUserStore } from "@/store/user.store";
import { ChatMessage } from "@/types/chat.types";
import { format } from "date-fns";
import { chatApi } from "@/lib/api/chat.api";
import { CheckCheck, Loader } from "lucide-react";
import { toastDanger } from "@/components/ui/sonner";
import { AnimatePresence, motion } from "framer-motion";

const isNearBottom = (container: HTMLDivElement, threshold = 120) => {
  const maxScroll = container.scrollHeight - container.clientHeight;
  return maxScroll - container.scrollTop <= threshold;
};

export default function ChatTab({ isPreview }: { isPreview: boolean }) {
  const activeChat = useChatStore((store) => store.activeChat);
  const previewChat = useChatStore((store) => store.previewChat);
  const previewChatLoading = useChatStore((store) => store.previewChatLoading);
  const updateActiveChat = useChatStore((store) => store.updateActiveChat);
  const updatePreviewChat = useChatStore((store) => store.updatePreviewChat);
  const markMessagesAsReadAfter = useChatStore(
    (s) => s.markMessagesAsReadAfter
  );

  const loadMorePreviewMessages = useChatStore(
    (store) => store.loadMorePreviewMessages
  );

  const joinPreviewChat = useChatStore((store) => store.joinPreviewChat);

  const [loadingMessages, setLoadingMessages] = useState<boolean>(false);
  const scrollContainerRef = useRef<HTMLDivElement>(null);
  const typingAuthor = useChatStore((store) => store.typingAuthor);

  const displayChat = isPreview ? previewChat : activeChat;
  const chatStatus = displayChat?.chat.status;

  const initialScroll = useRef<boolean>(true);
  const isLoadingRef = useRef<boolean>(false);
  const scrollDebounceRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * Scroll behavior for chat container:
   * - If there is an unread message, scroll smoothly to the first unread message.
   * - Otherwise, scroll to the bottom of the chat.
   */
  const scrollHandler = useCallback(() => {
    const scrollContainer = scrollContainerRef.current;
    if (!scrollContainer) return;

    let scrollTimeout: NodeJS.Timeout | null = null;

    const firstUnreadMessage = activeChat?.messages.list.find(
      (m) =>
        !m.isRead && m.senderType !== "user" && !m.content.startsWith("system:")
    );

    if (firstUnreadMessage) {
      const messageEl = scrollContainer.querySelector(
        `[data-message-id="${firstUnreadMessage.id}"]`
      ) as HTMLElement;

      if (messageEl) {
        scrollTimeout = setTimeout(() => {
          scrollContainer.scrollTo({
            top: Math.max(0, messageEl.offsetTop - 84),
            behavior: "smooth",
          });
        }, 0);
      }
    } else {
      scrollTimeout = setTimeout(() => {
        if (initialScroll.current) {
          console.log("Initial scroll");
          scrollContainer.scrollTop = scrollContainer.scrollHeight;
          initialScroll.current = false;
        } else if (isNearBottom(scrollContainer)) {
          console.log("Near bottom");
          scrollContainer.scrollTop = scrollContainer.scrollHeight;
        }
      }, 0);
    }

    return () => {
      if (scrollTimeout) clearTimeout(scrollTimeout);
    };
  }, [
    activeChat?.chat.id,
    activeChat?.messages.list.length,
    previewChat?.chat.id,
    previewChat?.messages.list.length,
  ]);

  /**
   * Marks messages as read when they become visible in the chat container.
   *
   * Behavior:
   * - Observes all chat messages inside the scrollable container.
   * - When a message enters the visible area (50% or more visible), it checks for unread messages from the user.
   * - The latest unread message that is currently visible is marked as read:
   *    - Emits a "livechat_read" event via socket for the chat.
   *    - Updates local state via `markAsReadAfter`.
   * - Uses a small debounce (100ms) to avoid rapid firing while scrolling.
   * - Properly cleans up observers and timeouts when component unmounts or dependencies change.
   */
  useEffect(() => {
    const scrollContainer = scrollContainerRef.current;
    if (!scrollContainer || !activeChat) return;

    const socket = getSocket();
    let timeout: NodeJS.Timeout;

    const markVisibleMessagesAsRead = () => {
      const unreadMessages = activeChat.messages.list.filter(
        (m) =>
          !m.isRead &&
          m.senderType !== "user" &&
          !m.content.startsWith("system:")
      );

      if (unreadMessages.length === 0) return;

      const lastVisibleUnread = unreadMessages.find((m) => {
        const el = scrollContainer.querySelector(
          `[data-message-id="${m.id}"]`
        ) as HTMLElement;
        if (!el) return false;

        const containerRect = scrollContainer.getBoundingClientRect();
        const messageRect = el.getBoundingClientRect();

        return (
          containerRect.top < containerRect.bottom &&
          messageRect.bottom > containerRect.top
        );
      });

      if (!lastVisibleUnread) return;

      socket.emit("livechat_read", {
        chatId: activeChat.chat.id,
        senderType: "office",
        after: lastVisibleUnread.createdAt,
      });

      markMessagesAsReadAfter(lastVisibleUnread.createdAt, "office");
    };

    const observer = new IntersectionObserver(
      () => {
        clearTimeout(timeout);
        timeout = setTimeout(markVisibleMessagesAsRead, 100);
      },
      {
        root: scrollContainer,
        threshold: 0.5,
      }
    );

    scrollContainer
      .querySelectorAll("[data-message-id]")
      .forEach((el) => observer.observe(el));

    return () => {
      observer.disconnect();
      clearTimeout(timeout);
    };
  }, [activeChat?.chat.id, activeChat?.messages.list]);

  /**
   * Marks messages as read when they become visible in the chat container.
   *
   * Behavior:
   * if user is typing than it will scroll to bottom
   * if i to much scrol up than not scroll
   */
  useEffect(() => {
    if (!typingAuthor) return;
    const scrollContainer = scrollContainerRef.current;
    if (!scrollContainer) return;

    if (isNearBottom(scrollContainer)) {
      scrollContainer.scrollTo({
        top: scrollContainer.scrollHeight,
        behavior: "smooth",
      });
    }
  }, [typingAuthor]);

  useEffect(() => {
    const cleanup = scrollHandler();
    return cleanup;
  }, [
    scrollHandler,
    activeChat?.messages.list.length,
    previewChat?.messages.list.length,
  ]);

  // Replace your current useEffect with this:
  useEffect(() => {
    const container = scrollContainerRef.current;
    if (!container || !activeChat) return;

    const loadMoreMessages = async () => {
      // Prevent multiple simultaneous requests
      if (isLoadingRef.current || !activeChat.messages.hasMoreBefore) return;

      isLoadingRef.current = true;
      setLoadingMessages(true);

      try {
        const lastMessage = activeChat.messages.list[0];
        const isoCreatedAt = lastMessage.createdAt
          .replace(" ", "T")
          .replace("+00", "Z");

        // Store scroll position before loading
        const previousScrollHeight = container.scrollHeight;
        const scrollTopBefore = container.scrollTop;

        console.log("🔥 Loading older messages...");

        const loadMessages = await chatApi.getMessages({
          page: 1,
          limit: 35,
          chatId: activeChat.chat.id,
          before: isoCreatedAt,
        });
        console.log(loadMessages);

        if (!loadMessages) {
          toastDanger("Messages could not load");
          return;
        }

        // Update store with new messages
        useChatStore.getState().updateActiveChat(loadMessages);

        // Wait for DOM update then restore scroll position
        setTimeout(() => {
          requestAnimationFrame(() => {
            const newScrollHeight = container.scrollHeight;
            // Calculate the exact position to maintain user's view
            container.scrollTop =
              newScrollHeight - previousScrollHeight + scrollTopBefore - 68;
          });
        }, 100);
      } catch (error) {
        console.error("Failed to load messages:", error);
        toastDanger("Failed to load messages");
      } finally {
        setLoadingMessages(false);
        isLoadingRef.current = false;
      }
    };

    const handleScroll = () => {
      const scrollTop = container.scrollTop;
      const nearTop = scrollTop <= 50; // Increased threshold for better UX

      // Clear existing debounce
      if (scrollDebounceRef.current) {
        clearTimeout(scrollDebounceRef.current);
      }

      // Only trigger if near top and has more messages
      if (nearTop && activeChat.messages.hasMoreBefore) {
        scrollDebounceRef.current = setTimeout(() => {
          loadMoreMessages();
        }, 200); // Increased debounce time
      }
    };

    container.addEventListener("scroll", handleScroll);

    return () => {
      container.removeEventListener("scroll", handleScroll);
      if (scrollDebounceRef.current) {
        clearTimeout(scrollDebounceRef.current);
      }
    };
  }, [
    activeChat?.chat.id,
    activeChat?.messages.hasMoreBefore,
    activeChat?.messages.list.length,
  ]);

  useEffect(() => {
    const container = scrollContainerRef.current;

    if (!container || !previewChat) return;
    console.log(previewChat);

    const loadMoreMessages = async () => {
      // Prevent multiple simultaneous requests
      if (isLoadingRef.current || !previewChat.messages.hasMoreBefore) return;

      isLoadingRef.current = true;
      setLoadingMessages(true);

      try {
        const lastMessage = previewChat.messages.list[0];
        const isoCreatedAt = lastMessage.createdAt
          .replace(" ", "T")
          .replace("+00", "Z");

        // Store scroll position before loading
        const previousScrollHeight = container.scrollHeight;
        const scrollTopBefore = container.scrollTop;

        console.log("🔥 Loading older messages...");

        const loadMessages = await chatApi.getMessages({
          page: 1,
          limit: 35,
          chatId: previewChat.chat.id,
          before: isoCreatedAt,
        });
        console.log(loadMessages);

        if (!loadMessages) {
          toastDanger("Messages could not load");
          return;
        }

        // Update store with new messages
        useChatStore.getState().updatePreviewChat(loadMessages);

        // Wait for DOM update then restore scroll position
        setTimeout(() => {
          requestAnimationFrame(() => {
            const newScrollHeight = container.scrollHeight;
            // Calculate the exact position to maintain user's view
            container.scrollTop =
              newScrollHeight - previousScrollHeight + scrollTopBefore - 68;
          });
        }, 100);
      } catch (error) {
        console.error("Failed to load messages:", error);
        toastDanger("Failed to load messages");
      } finally {
        setLoadingMessages(false);
        isLoadingRef.current = false;
      }
    };

    const handleScroll = () => {
      const scrollTop = container.scrollTop;
      const nearTop = scrollTop <= 50; // Increased threshold for better UX

      // Clear existing debounce
      if (scrollDebounceRef.current) {
        clearTimeout(scrollDebounceRef.current);
      }

      // Only trigger if near top and has more messages
      if (nearTop && previewChat.messages.hasMoreBefore) {
        scrollDebounceRef.current = setTimeout(() => {
          loadMoreMessages();
        }, 200); // Increased debounce time
      }
    };

    container.addEventListener("scroll", handleScroll);

    return () => {
      container.removeEventListener("scroll", handleScroll);
      if (scrollDebounceRef.current) {
        clearTimeout(scrollDebounceRef.current);
      }
    };
  }, [
    isPreview,
    previewChat?.chat.id,
    previewChat?.messages.hasMoreBefore,
    previewChat?.messages.list.length,
  ]);

  // Render loading state - ONLY for initial preview chat loading
  if (isPreview && previewChatLoading && !previewChat) {
    return (
      <div className="flex items-center justify-center h-full">
        <Loader className="animate-spin size-8" />
      </div>
    );
  }

  // Render preview mode
  if (isPreview && previewChat) {
    return (
      <>
        {/* Preview Header */}
        <div className="px-6 py-4 border-b border-foreground/10 bg-foreground/5">
          <p className="text-sm font-medium text-foreground/70">
            Previewing chat #{previewChat.chat.id}
          </p>
          <p className="text-xs text-foreground/50 mt-1">
            Scroll up to load older messages
          </p>
        </div>

        {/* Messages with loading indicator */}
        <div
          ref={scrollContainerRef}
          className="relative flex flex-col gap-3 h-full flex-1 overflow-auto px-6 py-4 custom-scrollbar"
        >
          <AnimatePresence>
            {loadingMessages && (
              <motion.div
                key="loader"
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
                transition={{ duration: 0.15 }}
                className="py-4"
              >
                <Loader className="animate-spin m-auto" />
              </motion.div>
            )}
          </AnimatePresence>

          {/* Loading indicator for preview chat - shows when loading more messages */}
          {previewChatLoading && (
            <div className="py-4">
              <Loader className="animate-spin m-auto" />
            </div>
          )}

          {previewChat.messages.list.length > 0 ? (
            previewChat.messages.list.map((message) =>
              message.content === "system:office-joined" ? (
                <AgentJoined
                  key={message.id}
                  id={message.id}
                  activeChat={previewChat}
                  createdAt={message.createdAt}
                />
              ) : message.content === "system:office-leaved" ? (
                <AgentLeaved
                  key={message.id}
                  id={message.id}
                  activeChat={previewChat}
                  createdAt={message.createdAt}
                />
              ) : message.content === "system:user-leaved" ? (
                <UserLeaved
                  key={`${message.id}`}
                  id={message.id}
                  createdAt={message.createdAt}
                />
              ) : (
                <Message key={message.id} {...message} />
              )
            )
          ) : (
            <div className="flex flex-col items-center justify-center h-full text-foreground/50">
              <p>No messages in this chat</p>
            </div>
          )}
        </div>
      </>
    );
  }

  // Render active chat mode
  return (
    <>
      {chatStatus === "waiting" && <WaitingAgent />}
      <div
        ref={scrollContainerRef}
        className="relative flex flex-col gap-3 h-full flex-1 overflow-auto px-6 py-4 custom-scrollbar"
      >
        <AnimatePresence>
          {loadingMessages && (
            <motion.div
              key="loader"
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.15 }}
              className="py-4"
            >
              <Loader className="animate-spin m-auto" />
            </motion.div>
          )}
        </AnimatePresence>

        {displayChat && displayChat.messages.list.length > 0 ? (
          displayChat.messages.list.map((message) =>
            message.content === "system:office-joined" ? (
              <AgentJoined
                key={message.id}
                id={message.id}
                activeChat={displayChat}
                createdAt={message.createdAt}
              />
            ) : message.content === "system:office-leaved" ? (
              <AgentLeaved
                key={message.id}
                id={message.id}
                activeChat={displayChat}
                createdAt={message.createdAt}
              />
            ) : (
              <Message key={message.id} {...message} />
            )
          )
        ) : (
          <div className="flex flex-col items-center justify-center h-full text-foreground/50">
            <p>No messages yet</p>
          </div>
        )}

        <AnimatePresence>
          {typingAuthor && typingAuthor.chatId === activeChat?.chat.id && (
            <motion.div
              key="typing"
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.15 }}
            >
              <TypingIndicator />
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* Only show input for active, non-cancelled chats */}
      {!isPreview && chatStatus !== "cancel" && <ChatInput />}
    </>
  );
}

// ... rest of your components remain the same
export const Message = ({
  attachments,
  id,
  chatId,
  content,
  createdAt,
  isRead,
  readAt,
  senderId,
  senderType,
  sender,
}: ChatMessage) => {
  const messageDate = new Date(createdAt);
  const [timeLabel, setTimeLabel] = useState("");

  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      const msgDate = new Date(createdAt);

      let label = "";
      const diffMinutes = Math.floor(
        (now.getTime() - msgDate.getTime()) / (1000 * 60)
      );
      const diffHours = Math.floor(diffMinutes / 60);
      const diffDays = Math.floor(diffHours / 24);

      if (diffMinutes < 1) {
        // Just now
        label = "now";
      } else if (diffMinutes < 60) {
        // Minutes ago
        label = `${diffMinutes}m`;
      } else if (diffHours < 24) {
        // Hours ago
        label = `${diffHours}h`;
      } else if (diffDays === 1) {
        // Yesterday
        label = "yesterday";
      } else if (diffDays < 7) {
        // Days ago
        label = `${diffDays}d`;
      } else if (diffDays < 365) {
        // Month and day
        label = format(msgDate, "MMM dd");
      } else {
        // Year, month and day
        label = format(msgDate, "MMM dd, yyyy");
      }

      setTimeLabel(label);
    };

    updateTime();
    const interval = setInterval(updateTime, 60 * 1000); // Update every minute
    return () => clearInterval(interval);
  }, [createdAt]);
  return (
    <div className="group flex flex-col" data-message-id={id}>
      <div
        className={`${
          senderType === "office"
            ? "bg-foreground/5 max-w-[90%] rounded-t-2xl rounded-r-2xl"
            : "bg-primary/80 text-white ml-auto max-w-[90%] rounded-t-2xl rounded-l-2xl"
        } flex flex-col gap-2 w-max p-3`}
      >
        {senderType === "office" && (
          <div className="flex items-center gap-2">
            <div className="relative size-9 rounded-full grid place-content-center text-sm bg-background overflow-hidden">
              <Image
                src={`/imgs/avatars/user-avatar-01.svg`}
                alt="avatar"
                fill
              />
            </div>
            <p className="text-sm font-bold">{sender.info.nickname}</p>
            <p className="ml-auto text-xs font-semibold">{timeLabel}</p>
          </div>
        )}
        <div
          className={`${
            senderType === "office" ? "text-foreground/80" : ""
          } flex items-start gap-3 text-[13px] leading-[140%]`}
        >
          <div dangerouslySetInnerHTML={{ __html: content }}></div>
          {senderType === "user" && (
            <CheckCheck
              className={`${
                isRead ? "text-foreground" : "text-foreground/50"
              } size-4`}
            />
          )}
        </div>
      </div>

      <p
        className={`${
          senderType === "user" ? "ml-auto" : ""
        } group-hover:visible invisibl text-xs text-foreground/60`}
      >
        {timeLabel}
      </p>
    </div>
  );
};

const ChatInput = () => {
  const textareaRef = useRef<HTMLTextAreaElement | null>(null);
  const [message, setMessage] = useState<string>("");

  const user = useUserStore((store) => store.user);
  const activeChat = useChatStore((store) => store.activeChat);
  const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const debounceRef = useRef<NodeJS.Timeout | null>(null);

  const emitTyping = (isTyping: boolean) => {
    if (!activeChat || !user) return;
    const socket = getSocket();

    socket.emit("livechat_typing", {
      chatId: activeChat.chat.id,
      participantId: user.id,
      role: activeChat.chat.initiatorType,
      isTyping,
    });
  };

  const handleTyping = () => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      emitTyping(true);
    }, 200);

    if (typingTimeoutRef.current) clearTimeout(typingTimeoutRef.current);
    typingTimeoutRef.current = setTimeout(() => {
      emitTyping(false);
    }, 1200);
  };

  const autoResize = () => {
    const el = textareaRef.current;
    if (el) {
      el.style.height = "auto";
      el.style.height = `${el.scrollHeight}px`;
    }
  };

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!message.trim()) return;
    if (!activeChat) return;
    if (!user) return;

    const socket = getSocket();

    socket.emit("livechat_send_message", {
      chatId: activeChat.chat.id,
      senderType: activeChat.chat.initiatorType,
      senderId: user.id,
      content: message,
      isRead: false,
    });

    emitTyping(false);

    setMessage("");
    if (textareaRef.current) {
      textareaRef.current.style.height = "auto";
    }
  };

  return (
    <form onSubmit={handleSubmit} className="relative flex" id="livechat-form">
      <textarea
        ref={textareaRef}
        value={message}
        onChange={(e: ChangeEvent<HTMLTextAreaElement>) => {
          setMessage(e.target.value);
          autoResize();
          handleTyping();
        }}
        onKeyDown={(e) => {
          if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            (
              document.getElementById("livechat-form") as HTMLFormElement
            )?.requestSubmit();
          }
        }}
        placeholder="Write message..."
        className="w-full min-h-12 max-h-[120px] border border-neutral/10 rounded-none bg-transparent px-3 py-[15px] text-xs font-medium outline-none focus:border-neutral/15 pr-12 text-foreground placeholder:text-foreground/40 resize-none"
        rows={1}
      />
      <Button
        type="submit"
        className="!absolute right-2 top-1/2 -translate-y-1/2 bg-transparent border-none rounded-full w-8 h-8 p-0 min-w-0 min-h-0"
      >
        <IconBase icon={ICONS.SEND_MESSAGE} className="size-4" />
      </Button>
    </form>
  );
};

const AgentJoined = ({
  id,
  activeChat,
  createdAt,
}: {
  id: number;
  activeChat: ActiveChat;
  createdAt: string;
}) => {
  return (
    <div
      className="relative flex flex-col mt-3 w-max mx-auto items-center justify-center gap-2 text-center text-sm text-foreground/80"
      data-message-id={id}
    >
      {/* <Image
        src="/imgs/support.svg"
        alt="support"
        width={50}
        height={50}
        className="w-full max-w-[50px]"
      /> */}

      <div className="grid place-content-center size-6 rounded-full bg-success">
        <IconBase className="size-5 text-black" icon={ICONS.CHECKMARK} />
      </div>
      <p className="font-medium flex items-center gap-1">
        agent:{" "}
        <strong>
          {activeChat?.chat.office?.officeInfo.nickname} Joined Chat
        </strong>
      </p>
      <span className="text-xs">
        {" "}
        {format(new Date(createdAt), "mm/dd/yyyy hh:mm:ss")}
      </span>
    </div>
  );
};

const AgentLeaved = ({
  id,
  activeChat,
  createdAt,
}: {
  id: number;
  activeChat: ActiveChat;
  createdAt: string;
}) => {
  return (
    <div
      className="relative flex flex-col mt-3 w-max mx-auto items-center justify-center gap-2 text-center text-sm text-foreground/80"
      data-message-id={id}
    >
      {/* <Image
        src="/imgs/support.svg"
        alt="support"
        width={50}
        height={50}
        className="w-full max-w-[50px]"
      /> */}

      <div className="grid place-content-center size-6 rounded-full">
        <IconBase className="size-5 text-danger" icon={ICONS.LOGOUT} />
      </div>
      <p className="font-medium flex items-center gap-1">
        agent:{" "}
        <strong>
          {activeChat?.chat.office?.officeInfo.nickname} Left the chat.
        </strong>
      </p>
      <span className="text-xs">
        {" "}
        {format(new Date(createdAt), "mm/dd/yyyy hh:mm:ss")}
      </span>
    </div>
  );
};

const UserLeaved = ({ id, createdAt }: { id: number; createdAt: string }) => {
  return (
    <div
      className="relative flex flex-col mt-3 w-max mx-auto items-center justify-center gap-2 text-center text-sm text-foreground/80"
      data-message-id={id}
    >
      {/* <Image
        src="/imgs/support.svg"
        alt="support"
        width={50}
        height={50}
        className="w-full max-w-[50px]"
      /> */}

      <div className="grid place-content-center size-6 rounded-full">
        <IconBase className="size-5 text-danger" icon={ICONS.LOGOUT} />
      </div>
      <p className="font-medium flex items-center gap-1">You Ended The Chat</p>
      <span className="text-xs">
        {" "}
        {format(new Date(createdAt), "mm/dd/yyyy hh:mm:ss")}
      </span>
    </div>
  );
};

const WaitingAgent = () => {
  return (
    <div className="sticky shadow-2xl top-0 flex flex-col mt-3 pb-2 items-center justify-center gap-2 text-center text-sm text-foreground/80">
      <Image
        src="/imgs/support.svg"
        alt="support"
        width={50}
        height={50}
        className="w-full max-w-[35px]"
      />

      <p className="font-medium flex items-center gap-1">
        Please hold on — an agent will join shortly
      </p>
      <span className="inline-flex items-end mt-3s">
        <span
          className="dot size-2 bg-foreground rounded-full mx-[1px] inline-block"
          style={{ animation: "dot 1.2s infinite ease-in-out" }}
        />
        <span
          className="dot size-2 bg-foreground rounded-full mx-[1px] inline-block"
          style={{
            animation: "dot 1.2s infinite ease-in-out",
            animationDelay: "0.2s",
          }}
        />
        <span
          className="dot size-2 bg-foreground rounded-full mx-[1px] inline-block"
          style={{
            animation: "dot 1.2s infinite ease-in-out",
            animationDelay: "0.4s",
          }}
        />
      </span>
    </div>
  );
};

function TypingIndicator() {
  return (
    <div className="flex items-center gap-3 text-xs font-medium text-foreground/60">
      <span className="inline-flex items-end">
        <span
          className="dot size-2 bg-foreground rounded-full mx-[1px] inline-block"
          style={{ animation: "dot 1.2s infinite ease-in-out" }}
        />
        <span
          className="dot size-2 bg-foreground rounded-full mx-[1px] inline-block"
          style={{
            animation: "dot 1.2s infinite ease-in-out",
            animationDelay: "0.2s",
          }}
        />
        <span
          className="dot size-2 bg-foreground rounded-full mx-[1px] inline-block"
          style={{
            animation: "dot 1.2s infinite ease-in-out",
            animationDelay: "0.4s",
          }}
        />
      </span>
      Typing
    </div>
  );
}
