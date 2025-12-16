"use client";
import { useEffect } from "react";
import { useUserStore } from "@/store/user.store";
import { TypingAuthor, useChatStore } from "@/store/chat.store";
import { chatApi } from "@/lib/api/chat.api";
import { getSocket } from "@/lib/socket/socket";
import { ChatMessage, ChatOffice, ParticipantData } from "@/types/chat.types";

export default function HydrateChat() {
  const user = useUserStore((store) => store.user);
  const setInitialLoading = useChatStore((store) => store.setInitialLoading);

  const activeChat = useChatStore((store) => store.activeChat);
  const setActiveChat = useChatStore((store) => store.setActiveChat);
  const updateActiveChatPartial = useChatStore(
    (store) => store.updateActiveChatPartial
  );
  const setChats = useChatStore((store) => store.setChats);
  const setActiveChatLoading = useChatStore(
    (store) => store.setActiveChatLoading
  );
  const setChatsLoading = useChatStore((store) => store.setChatsLoading);
  const updateActiveChatMessage = useChatStore(
    (store) => store.updateActiveChatMessage
  );

  const setTypingAuthor = useChatStore((store) => store.setTypingAuthor);

  useEffect(() => {
    if (!user) return;

    const socket = getSocket();

    const onMessageSent = (message: ChatMessage) => {
      updateActiveChatMessage(message);
    };

    const onNewMessage = (message: ChatMessage) => {
      console.log(message);
      updateActiveChatMessage(message, true);
    };

    const onLiveChatJoined = (data: ChatOffice) => {
      // User joined room successfully
    };

    const onLiveChatParticipantJoined = (data: ParticipantData) => {
      console.log("Office joined");
      console.log(data);

      if (data.role === "office") {
        updateActiveChatPartial({
          office: {
            officeId: data.participant.id,
            officeInfo: { nickname: data.participant.info.nickname },
            officeLoginId: data.participant.loginId,
          },
          status: "active",
        });

        socket.emit("livechat_send_message", {
          chatId: data.chatId,
          senderType: "office",
          senderId: data.participant.id,
          content: "system:office-joined",
          isRead: true,
        });

        socket.emit("livechat_send_message", {
          chatId: data.chatId,
          senderType: "office",
          senderId: data.participant.id,
          content: `Hello! I'm ${data.participant.info.nickname} from the support team. How can I assist you today?`,
          isRead: false,
        });
      }
    };

    const onLiveChatParticipantLeft = (data: ParticipantData) => {
      // Handle participant left if needed
      if (
        data.role === "user" &&
        activeChat?.chat.office?.officeId === data.participant.id
      ) {
        updateActiveChatPartial({
          office: null,
        });
      }
    };

    const onLiveChatLeave = (data: ParticipantData) => {
      if (data.role === "user") {
        updateActiveChatPartial({
          status: "cancel",
        });
      }
    };

    const onLiveChatTyping = (data: TypingAuthor) => {
      if (!data.isTyping) {
        setTypingAuthor(null);
      } else {
        setTypingAuthor(data);
      }
    };

    const onLiveChatRead = (data: { after: string }) => {
      console.log("Live chat read", data);
      useChatStore.getState().markMessagesAsReadAfter(data.after, "user");
    };

    socket.on("livechat_message_sent", onMessageSent);
    socket.on("livechat_new_message", onNewMessage);
    socket.on("livechat_joined", onLiveChatJoined);
    socket.on("livechat_participant_joined", onLiveChatParticipantJoined);
    socket.on("livechat_participant_left", onLiveChatParticipantLeft);
    socket.on("livechat_leave", onLiveChatLeave);
    socket.on("livechat_typing", onLiveChatTyping);
    socket.on("livechat_read", onLiveChatRead);

    return () => {
      socket.off("livechat_message_sent", onMessageSent);
      socket.off("livechat_new_message", onNewMessage);
      socket.off("livechat_joined", onLiveChatJoined);
      socket.off("livechat_participant_joined", onLiveChatParticipantJoined);
      socket.off("livechat_participant_left", onLiveChatParticipantLeft);
      socket.off("livechat_leave", onLiveChatLeave);
      socket.off("livechat_typing", onLiveChatTyping);
      socket.off("livechat_read", onLiveChatRead);
    };
  }, [user?.id]);

  useEffect(() => {
    if (!user) return;

    const promises: Promise<any>[] = [
      chatApi.getChats({
        page: 1,
        limit: 35,
        userId: user.id,
      }),
    ];

    if (!activeChat) {
      promises.push(
        chatApi.getInitedChat({
          page: 1,
          limit: 35,
          userId: user.id,
        })
      );
    }

    const getData = async () => {
      try {
        setInitialLoading(true);
        setChatsLoading(true);
        if (!activeChat) {
          setActiveChatLoading(true);
        }
        const [chats, activeChatData] = await Promise.all(promises);
        setChats(chats);

        if (activeChatData) {
          setActiveChat(activeChatData);
          const socket = getSocket();
          socket.emit("livechat_join", {
            chatId: activeChatData.chat.id,
            participantId: user.id,
            role: "user",
          });
        }
      } catch (error) {
        console.error("Failed to load chat data:", error);
      } finally {
        setInitialLoading(false);
        setChatsLoading(false);
        setActiveChatLoading(false);
      }
    };

    getData();
  }, [
    user?.id,
    activeChat,
    setChats,
    setActiveChat,
    setChatsLoading,
    setActiveChatLoading,
  ]);

  return null;
}
