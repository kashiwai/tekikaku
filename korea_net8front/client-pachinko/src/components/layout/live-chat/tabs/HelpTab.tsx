import { useState, useMemo, useCallback } from "react";

import SearchInput from "@/components/filter/searchInput";
import IconBase from "@/components/icon/iconBase";
import ContentLoader from "@/components/loader/contentLoader";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { groupLabels, useChatStore } from "@/store/chat.store";
import { LayoutState } from "@/store/layout.store";
import { ChatGroupFaqs } from "@/types/chat.types";
import { FAQType } from "@/types/faq";
import { useLocale } from "next-intl";
import { LocaleKey } from "@/i18n/request";

type TabView = "list" | "details";
type ActiveFaqTab = keyof ChatGroupFaqs["grouped"] | null;

interface HelpTabProps {
  activeFaqTab: ActiveFaqTab;
  setActiveFaqTab: (tab: ActiveFaqTab) => void;
  toggleChat: LayoutState["toggleChat"];
}

interface FaqItemProps {
  title: string;
  count?: number;
  onClick?: () => void;
  className?: string;
}

/**
 * HelpTab component - A comprehensive FAQ help center with search and navigation
 */
export default function HelpTab({
  activeFaqTab,
  setActiveFaqTab,
  toggleChat,
}: HelpTabProps) {
  const [searchQuery, setSearchQuery] = useState("");
  const [currentView, setCurrentView] = useState<TabView>("list");
  const [activeFaqId, setActiveFaqId] = useState<string | null>(null);

  const chatGroupFaqs = useChatStore((store) => store.chatGroupFaqs);
  const isLoading = !chatGroupFaqs;

  // Memoize filtered groups to avoid unnecessary recalculations
  const filteredGroups = useMemo(() => {
    if (!chatGroupFaqs?.grouped) return null;

    const lowerSearch = searchQuery.toLowerCase().trim();

    if (!lowerSearch) return chatGroupFaqs.grouped;

    return Object.entries(chatGroupFaqs.grouped).reduce((acc, [key, faqs]) => {
      const label = groupLabels[key as keyof typeof groupLabels]?.toLowerCase();

      const matchesKey = key.toLowerCase().includes(lowerSearch);
      const matchesLabel = label?.includes(lowerSearch);

      if (matchesKey || matchesLabel) {
        acc[key as keyof ChatGroupFaqs["grouped"]] = faqs;
      }

      return acc;
    }, {} as ChatGroupFaqs["grouped"]);
  }, [chatGroupFaqs, searchQuery]);

  const hasSearchResults =
    filteredGroups && Object.keys(filteredGroups).length > 0;
  const currentFaqs =
    activeFaqTab && chatGroupFaqs
      ? chatGroupFaqs.grouped[activeFaqTab].list
      : null;
  const activeFaq = currentFaqs?.find((faq) => faq.faqId === activeFaqId);

  // Navigation handlers
  const handleBackToList = useCallback(() => {
    setCurrentView("list");
    setActiveFaqTab(null);
    setActiveFaqId(null);
    setSearchQuery("");
  }, [setActiveFaqTab]);

  const handleBackToCategories = useCallback(() => {
    setCurrentView("list");
    setActiveFaqId(null);
    setSearchQuery("");
  }, []);

  const handleSearch = useCallback((value: string) => {
    setSearchQuery(value);
  }, []);

  // Render different views based on current state
  const renderView = () => {
    if (isLoading) {
      return <ContentLoader className="h-[120px]" />;
    }

    switch (currentView) {
      case "details":
        return activeFaq ? (
          <DetailView
            faq={activeFaq}
            onBack={handleBackToCategories}
            onClose={toggleChat}
          />
        ) : (
          <EmptyState message="FAQ not found" onBack={handleBackToList} />
        );

      case "list":
        if (activeFaqTab) {
          return (
            <CategoryView
              title={groupLabels[activeFaqTab]}
              faqs={currentFaqs}
              searchQuery={searchQuery}
              onSearch={handleSearch}
              onBack={handleBackToList}
              onClose={toggleChat}
              onFaqSelect={(id) => {
                setCurrentView("details");
                setActiveFaqId(id);
              }}
            />
          );
        }

        return (
          <MainView
            groups={filteredGroups}
            hasResults={hasSearchResults ? true : false}
            searchQuery={searchQuery}
            onSearch={handleSearch}
            onClose={toggleChat}
            onCategorySelect={(key) => {
              setActiveFaqTab(key);
            }}
          />
        );

      default:
        return null;
    }
  };

  return (
    <div className="flex flex-col h-full overflow-hidden">{renderView()}</div>
  );
}

/**
 * Main categories view
 */
const MainView = ({
  groups,
  hasResults,
  searchQuery,
  onSearch,
  onClose,
  onCategorySelect,
}: {
  groups: ChatGroupFaqs["grouped"] | null;
  hasResults: boolean;
  searchQuery: string;
  onSearch: (value: string) => void;
  onClose: () => void;
  onCategorySelect: (key: keyof ChatGroupFaqs["grouped"]) => void;
}) => (
  <>
    <Header title="Help Center" onClose={onClose} />

    <div className="flex flex-col h-full overflow-auto px-6 py-4 custom-scrollbar">
      <SearchInput
        placeholder="What do you need help with?"
        value={searchQuery}
        onValueChange={onSearch}
      />
      <div className="flex flex-col mt-2">
        {groups ? (
          hasResults ? (
            Object.entries(groups).map(([key, data]) => (
              <FaqItem
                key={key}
                title={groupLabels[key as keyof typeof groupLabels]}
                count={data.total}
                onClick={() =>
                  onCategorySelect(key as keyof ChatGroupFaqs["grouped"])
                }
                className="hover:bg-accent/50 transition-colors"
              />
            ))
          ) : (
            <EmptyState message="No results found" />
          )
        ) : (
          <ContentLoader className="h-[120px]" />
        )}
      </div>
    </div>
  </>
);

/**
 * Category-specific FAQ list view
 */
const CategoryView = ({
  title,
  faqs,
  searchQuery,
  onSearch,
  onBack,
  onClose,
  onFaqSelect,
}: {
  title: string;
  faqs: FAQType[] | null;
  searchQuery: string;
  onSearch: (value: string) => void;
  onBack: () => void;
  onClose: () => void;
  onFaqSelect: (id: string) => void;
}) => {
  const locale = useLocale() as LocaleKey;
  const filteredFaqs = useMemo(() => {
    if (!faqs) return null;

    const lowerSearch = searchQuery.toLowerCase().trim();
    if (!lowerSearch) return faqs;

    return faqs.filter((faq) =>
      faq?.title?.en.toLowerCase().includes(lowerSearch)
    );
  }, [faqs, searchQuery]);

  return (
    <>
      <Header title={title} onBack={onBack} onClose={onClose} showBackButton />

      <div className="flex flex-col h-full overflow-auto px-6 py-4 custom-scrollbar">
        <SearchInput
          placeholder="Search in this category..."
          value={searchQuery}
          onValueChange={onSearch}
        />

        <div className="flex flex-col mt-2">
          {filteredFaqs ? (
            filteredFaqs.length > 0 ? (
              filteredFaqs.map((faq) => (
                <FaqItem
                  key={faq.faqId}
                  title={faq.title[locale]}
                  onClick={() => onFaqSelect(faq.faqId)}
                  className="hover:bg-accent/50 transition-colors"
                />
              ))
            ) : (
              <EmptyState message="No matching FAQs found" />
            )
          ) : (
            <ContentLoader className="h-[120px]" />
          )}
        </div>
      </div>
    </>
  );
};

/**
 * FAQ detail view
 */
const DetailView = ({
  faq,
  onBack,
  onClose,
}: {
  faq: FAQType;
  onBack: () => void;
  onClose: () => void;
}) => {
  const locale = useLocale() as LocaleKey;
  return (
    <>
      <Header
        title={faq.title[locale]}
        onBack={onBack}
        onClose={onClose}
        showBackButton
        className="truncate"
      />

      <div className="flex flex-col h-full overflow-auto px-6 py-4 custom-scrollbar">
        <div className="prose prose-sm text-sm text-foreground/90 leading-5.5 max-w-none">
          <div dangerouslySetInnerHTML={{ __html: faq.content[locale] }} />
        </div>
      </div>
    </>
  );
};

/**
 * Reusable header component
 */
const Header = ({
  title,
  showBackButton = false,
  onBack,
  onClose,
  className = "",
}: {
  title: string;
  showBackButton?: boolean;
  onBack?: () => void;
  onClose: () => void;
  className?: string;
}) => (
  <div
    className={`${
      showBackButton && onBack ? "pl-2 pr-6" : "px-6"
    } w-full flex items-center justify-between pb-6 border-b border-foreground/10 sticky top-0 bg-background z-10`}
  >
    <div className="grid grid-cols-[auto_1fr] items-center gap-2">
      {showBackButton && onBack && (
        <Button
          onClick={onBack}
          size="icon_sm"
          variant="default"
          className="bg-transparent border-transparent"
          aria-label="Go back"
        >
          <IconBase icon={ICONS.CHEVRON_LEFT} className="size-6" />
        </Button>
      )}
      <h1 className={`text-base font-semibold truncate ${className}`}>
        {title}
      </h1>
    </div>

    <Button
      onClick={onClose}
      size="icon_sm"
      variant="default"
      className="rounded-full"
      aria-label="Close help"
    >
      <IconBase icon={ICONS.CLOSE_X} className="size-4" />
    </Button>
  </div>
);

/**
 * FAQ list item component
 */
export const FaqItem = ({
  title,
  count,
  onClick,
  className = "",
}: FaqItemProps) => {
  const locale = useLocale() as LocaleKey;

  return (
    <button
      onClick={onClick}
      className={`${className} group flex items-center py-2 justify-between px-2 opacity-80 hover:opacity-100 cursor-pointer`}
      aria-label={title}
    >
      <p className="text-sm font-medium truncate">
        {title} {count && `(${count})`}
      </p>
      <IconBase
        icon={ICONS.CHEVRON_RIGHT}
        className="text-foreground group-hover:text-success size-4"
      />
    </button>
  );
};

/**
 * Empty state component
 */
const EmptyState = ({
  message,
  onBack,
}: {
  message: string;
  onBack?: () => void;
}) => (
  <div className="flex flex-col items-center justify-center py-8 text-center">
    <IconBase
      icon={ICONS.SEARCH}
      className="size-8 text-muted-foreground mb-2"
    />
    <p className="text-sm text-muted-foreground">{message}</p>
    {onBack && (
      <Button variant="default" size="sm" onClick={onBack} className="mt-4">
        Back to all categories
      </Button>
    )}
  </div>
);
