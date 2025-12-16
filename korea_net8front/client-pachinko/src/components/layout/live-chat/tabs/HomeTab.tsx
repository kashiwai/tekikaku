import { useState } from "react";

import SearchInput from "@/components/filter/searchInput";
import { groupLabels, useChatStore } from "@/store/chat.store";
import { Chat, ChatGroupFaqs } from "@/types/chat.types";

import { FaqItem } from "./HelpTab";
import { MessageFrame } from "./MessagesTab";
import { useUserStore } from "@/store/user.store";
import { Loading01Icon } from "@hugeicons/core-free-icons";
import IconBase from "@/components/icon/iconBase";
import { ICONS } from "@/constants/icons";
import { Loader } from "lucide-react";
import { useTranslations } from "next-intl";

export default function HomeTab({
  setActiveFaqTab,
  onTabChange,
  onStartChat,
}: {
  setActiveFaqTab: (faqTab: keyof ChatGroupFaqs["grouped"]) => void;
  onTabChange: () => void;
  onStartChat: (chat: Chat) => void;
}) {
  const user = useUserStore((store) => store.user);
  const [showForm, setShowForm] = useState<boolean>(false);
  const [search, setSearch] = useState<string>("");
  const chatGroupFaqs = useChatStore((store) => store.chatGroupFaqs);
  const activeChat = useChatStore((store) => store.activeChat);
  const activeChatLoading = useChatStore((store) => store.activeChatLoading);
  const t = useTranslations("LIVE_CHAT");

  const filteredGroups = (() => {
    if (!chatGroupFaqs?.grouped) return null;

    const lowerSearch = search.toLowerCase();

    return Object.entries(chatGroupFaqs.grouped).reduce((acc, [key, faqs]) => {
      const label = groupLabels[key as keyof typeof groupLabels]?.toLowerCase();

      if (
        key.toLowerCase().includes(lowerSearch) ||
        label?.includes(lowerSearch)
      ) {
        acc[key as keyof ChatGroupFaqs["grouped"]] = faqs;
      }

      return acc;
    }, {} as ChatGroupFaqs["grouped"]);
  })();

  const hasResults = filteredGroups && Object.keys(filteredGroups).length > 0;

  const handleFaq = (key: keyof ChatGroupFaqs["grouped"]) => {
    setActiveFaqTab(key);
    onTabChange();
  };

  return (
    <div className="relative flex flex-col h-full flex-1 overflow-auto px-6 py-4 custom-scrollbar">
      <div className="flex flex-col gap-2 w-full max-w-[94%] mx-auto">
        {/* {activeChatLoading ? (
          <div className="group flex flex-col items-start w-full p-3 bg-[#e9e9e9] dark:bg-[#172235] dark:hover:bg-[#17223580] border border-neutral/10 rounded-2xl dark:shadow-xl dark:shadow-gray-700/5 transition-all cursor-pointer">
            <p className="text-xs font-bold">Current Chat</p>
            <Loader className="animate-spin my-4 mx-auto" />
          </div>
        ) : ( */}
        {activeChat && (
          <div className="group flex flex-col items-start w-full p-3 bg-[#e9e9e9] dark:bg-[#172235] dark:hover:bg-[#17223580] border border-neutral/10 rounded-2xl dark:shadow-xl dark:shadow-gray-700/5 transition-all cursor-pointer">
            <p className="text-xs font-bold">{t("CURRENT_CHAT")}</p>
            <MessageFrame
              hasActiveChat={!!activeChat}
              {...activeChat.chat}
              onClick={() => onStartChat(activeChat.chat)}
            />
          </div>
        )}
        {/* )} */}
      </div>

      <div className="flex flex-col gap-2 w-full max-w-[94%] mx-auto mt-4 p-2 bg-[#e9e9e9] dark:bg-[#172235] rounded-2xl border border-neutral/10">
        <SearchInput
          placeholder={t("WHAT_YOU_NEED")}
          value={search}
          onValueChange={setSearch}
        />
        <div className="flex flex-col">
          {
            chatGroupFaqs && chatGroupFaqs.grouped ? (
              hasResults ? (
                Object.entries(filteredGroups).map(([key, data]) => {
                  const label =
                    groupLabels[key as keyof ChatGroupFaqs["grouped"]];
                  return (
                    <FaqItem
                      key={key}
                      title={`${label}`}
                      count={data.total}
                      onClick={() =>
                        handleFaq(key as keyof ChatGroupFaqs["grouped"])
                      }
                    />
                  );
                })
              ) : (
                <p className="text-sm text-muted-foreground text-center py-4">
                  {t("NO_RESULTS_FOUND")}
                </p>
              )
            ) : null
            // <ContentLoader className="h-[120px]" />
          }
        </div>
      </div>
    </div>
  );
}
