import React from "react";

import { AnimatePresence, motion } from "framer-motion";

import Logo from "@/components/common/brand/logo";
import IconBase from "@/components/icon/iconBase";
import ModalLayout from "@/components/modals/modalLayout";
import {
  Carousel,
  CarouselApi,
  CarouselContent,
  CarouselIndicator,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from "@/components/ui/carousel";
import { ICONS } from "@/constants/icons";
import { noticeHelpers } from "@/helpers/notice.helpers";
import { ModalControls } from "@/hooks/useModal";
import { useNoticeStore } from "@/store/notice.store";
import { LogoType } from "@/types/settings.types";
import { useLocale } from "next-intl";
import { PageNoticeItem } from "@/types/notice.types";
import { LocaleKey } from "@/i18n/request";

type Props = ModalControls<"notices"> & {
  logo: LogoType | undefined;
};

const Notice = ({
  title,
  description,
  locale,
}: {
  title: PageNoticeItem["title"];
  description: PageNoticeItem["content"];
  locale: LocaleKey;
}) => {
  return (
    <div className="flex flex-col gap-2 w-full overflow-hidden">
      <h6 className="text-sm md:text-lg font-semibold text-foreground/90">
        {title[locale]}
      </h6>
      <div
        dangerouslySetInnerHTML={{ __html: description[locale] }}
        className="notification-block flex flex-col gap-2 text-xs font-normal text-foreground/70 break-words"
      />
    </div>
  );
};

export default function NoticesModal({ logo, isOpen, onClose }: Props) {
  const locale = useLocale() as LocaleKey;
  const [api, setApi] = React.useState<CarouselApi | null>(null);
  const [currentIndex, setCurrentIndex] = React.useState(0);
  const [markedIds, setMarkedIds] = React.useState<number[]>([]);
  const [pendingIds, setPendingIds] = React.useState<number[]>([]);
  const [isLastNotice, setIsLastNotice] = React.useState(false);
  const notices = useNoticeStore((store) => store.notices);
  const updatedNotices = notices.filter(
    (n) => !noticeHelpers.shouldHideNotice(n.id)
  );

  React.useEffect(() => {
    if (updatedNotices.length === 0) {
      onClose();
    }
  }, [updatedNotices, onClose]);

  React.useEffect(() => {
    if (!api) return;

    const onSelect = () => {
      const newIndex = api.selectedScrollSnap();
      setCurrentIndex(newIndex);
      setIsLastNotice(newIndex === updatedNotices.length - 1);
    };

    onSelect();
    api.on("select", onSelect);
    api.on("slidesChanged", onSelect);

    return () => {
      api.off("select", onSelect);
      api.off("slidesChanged", onSelect);
    };
  }, [api, updatedNotices.length]);

  const onMarkAndDontShow = () => {
    const currentId = updatedNotices[currentIndex]?.id;

    if (!currentId) return;

    if (!pendingIds.includes(currentId)) {
      setPendingIds([...pendingIds, currentId]);
    } else {
      // Remove currentId from pendingIds
      setPendingIds(pendingIds.filter((id) => id !== currentId));
    }
  };

  const handleModalClose = () => {
    // Merge pending into marked and persist
    const newMarked = [...markedIds, ...pendingIds];
    newMarked.forEach((id) => {
      noticeHelpers.addDontShowNotice(id);
    });

    setMarkedIds(newMarked);
    setPendingIds([]);

    sessionStorage.setItem("modalClosedInSession", "true");
    onClose();
  };

  return (
    updatedNotices.length > 0 && (
      <ModalLayout
        isOpen={isOpen}
        onClose={handleModalClose}
        closeBtnClassname={
          !isLastNotice ? "rounded-full" : "rounded-full shine"
        }
        ariaLabel={"Notice"}
        className="pb-0 max-h-[560px]"
      >
        <div className="flex flex-col items-center gap-1 px-4">
          <div className="flex flex-col items-center gap-2">
            {logo && (
              <Logo
                logo={logo}
                withTitle={false}
                className="w-[40px] h-[35px]"
              />
            )}
          </div>
        </div>

        <Carousel setApi={setApi}>
          <CarouselContent>
            <AnimatePresence mode="wait">
              {updatedNotices.map((notification, i) => (
                <CarouselItem key={notification.id} className="overflow-hidden">
                  <motion.div
                    initial={{ opacity: 1 }}
                    animate={{ height: currentIndex === i ? "auto" : "100px" }}
                    transition={{ duration: 0.3, ease: "easeInOut" }}
                    className="overflow-hidden grid"
                  >
                    <Notice
                      title={notification.title}
                      description={notification.content}
                      locale={locale}
                    />
                  </motion.div>
                </CarouselItem>
              ))}
            </AnimatePresence>
          </CarouselContent>

          <div className="linear-background flex items-center justify-between mt-4 bg-background sticky bottom-0 pb-4 pt-2">
            <button
              onClick={onMarkAndDontShow}
              className={`${
                updatedNotices[currentIndex]?.id &&
                pendingIds.includes(updatedNotices[currentIndex].id)
                  ? "text-success"
                  : "text-foreground/80"
              } flex items-center gap-1 cursor-pointer active:scale-95 transition-all`}
            >
              <IconBase
                icon={ICONS.DOUBLE_CHECK}
                className={
                  pendingIds?.includes(updatedNotices[currentIndex].id)
                    ? "opacity-100"
                    : "opacity-0"
                }
              />

              <span className="text-xs font-medium">
                Don’t show again in 24hr - ({currentIndex + 1})
              </span>
            </button>

            <div className="flex items-center gap-3">
              <CarouselIndicator />
              <div className="flex items-center gap-3">
                <CarouselPrevious className="relative translate-0 left-0 size-8">
                  <IconBase icon={ICONS.CHEVRON_LEFT} className="size-4" />
                </CarouselPrevious>
                <CarouselNext className="relative translate-0 left-0 size-8">
                  <IconBase
                    icon={ICONS.CHEVRON_LEFT}
                    className="size-4 rotate-180"
                  />
                </CarouselNext>
              </div>
            </div>
          </div>
        </Carousel>
      </ModalLayout>
    )
  );
}
