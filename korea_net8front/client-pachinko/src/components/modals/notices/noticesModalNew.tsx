import React, { useRef } from "react";

import { AnimatePresence, motion } from "framer-motion";

import Logo from "@/components/common/brand/logo";
import IconBase from "@/components/icon/iconBase";
import ModalLayout from "@/components/modals/modalLayout";
import { ICONS } from "@/constants/icons";
import { noticeHelpers } from "@/helpers/notice.helpers";
import { ModalControls } from "@/hooks/useModal";
import { useNoticeStore } from "@/store/notice.store";
import { LogoType } from "@/types/settings.types";
import Image from "next/image";
import { type Notice } from "@/types/notice.types";
import { useLocale } from "next-intl";
import { Swiper, SwiperSlide } from "swiper/react";
import { Autoplay, EffectCoverflow } from "swiper/modules";
import "swiper/css";
import "swiper/css/effect-coverflow";
import "swiper/css/autoplay";
import type SwiperType from "swiper";

type Props = ModalControls<"notices"> & {
  logo: LogoType | undefined;
};

const NoticeItem = ({
  title,
  description,
  thumbnail,
  noticeId,
  isPending,
  onTogglePending,
  onRemove,
  showRemoveButton = true,
}: {
  title: Notice["title"];
  description: Notice["content"];
  thumbnail: string | null;
  noticeId: number;
  isPending: boolean;
  onTogglePending: (id: number) => void;
  onRemove?: () => void;
  showRemoveButton?: boolean;
}) => {
  const locale = useLocale() as keyof typeof title;

  return (
    <div className="group flex flex-col gap-2 w-full overflow-hidden h-[640px] shadow-2xl relative !outline-none">
      <Image
        className="rounded-3xl w-full object-cover h-[640px] border border-foreground/10 !outline-none"
        src={thumbnail ?? ""}
        alt=""
        width={400}
        height={400}
      />

      {/* Remove button */}
      {showRemoveButton && onRemove && (
        <motion.button
          onClick={onRemove}
          className={`absolute top-3 right-3 z-50 size-8 flex items-center justify-center rounded-full bg-background/80 backdrop-blur-sm border border-foreground/10 hover:bg-background transition-colors ${
            false ? "shine" : "" // You can keep your original condition here
          }`}
          aria-label="Remove this notice"
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.9 }}
        >
          <IconBase icon={ICONS.CLOSE_X} className="size-4" />
        </motion.button>
      )}

      <div className="absolute top-0 p-12 left-0 w-full h-1/2 !pb-12 !pr-8 rounded-t-2xl">
        <h1 className="text-xl font-bold">{title[locale]}</h1>
        <div
          className="text-sm font-medium"
          dangerouslySetInnerHTML={{ __html: description[locale] }}
        ></div>
      </div>

      <div className="select-none absolute flex items-end justify-end group-hover:bottom-0 group-hover:opacity-100 opacity-0 -bottom-24 left-0 w-full h-1/2 pb-4 pr-6 rounded-b-2xl bg-linear-to-t from-black/70 to-black/0 transition-all duration-300">
        <button
          onClick={() => onTogglePending(noticeId)}
          className={`flex items-center gap-1 cursor-pointer active:scale-95 transition-all ${
            isPending ? "text-success" : "text-foreground/80"
          }`}
        >
          <IconBase
            icon={ICONS.DOUBLE_CHECK}
            className={`transition-opacity ${
              isPending ? "opacity-100" : "opacity-0"
            }`}
          />
          <span className="text-sm font-medium">Don't show again in 24hr</span>
        </button>
      </div>
    </div>
  );
};

export default function NoticesModalNew({ logo, isOpen, onClose }: Props) {
  const [currentIndex, setCurrentIndex] = React.useState(0);
  const [markedIds, setMarkedIds] = React.useState<number[]>([]);
  const [pendingIds, setPendingIds] = React.useState<number[]>([]);
  const [removingNoticeId, setRemovingNoticeId] = React.useState<number | null>(
    null
  );

  const [isMobile, setIsMobile] = React.useState(false);

  React.useEffect(() => {
    const check = () => setIsMobile(window.innerWidth < 768);
    check();
    window.addEventListener("resize", check);
    return () => window.removeEventListener("resize", check);
  }, [768]);

  const notices = useNoticeStore((store) => store.notices);
  const [visibleNotices, setVisibleNotices] = React.useState(
    notices.filter((n) => !noticeHelpers.shouldHideNotice(n.id))
  );

  const swiperRef = useRef<any>(null);

  React.useEffect(() => {
    if (visibleNotices.length === 0) {
      onClose();
    }
  }, [visibleNotices, onClose]);

  const onMarkAndDontShow = () => {
    const currentId = visibleNotices[currentIndex]?.id;
    if (!currentId) return;

    if (!pendingIds.includes(currentId)) {
      setPendingIds([...pendingIds, currentId]);
    } else {
      setPendingIds(pendingIds.filter((id) => id !== currentId));
    }
  };

  const handleRemoveNotice = (id: number) => {
    setRemovingNoticeId(id);

    setTimeout(() => {
      setVisibleNotices((prev) => prev.filter((notice) => notice.id !== id));
      setRemovingNoticeId(null);

      // If we're on the last slide and we remove it, swiper will handle the transition automatically due to loop
    }, 300);
  };

  const handleModalClose = () => {
    const newMarked = [...markedIds, ...pendingIds];
    newMarked.forEach((id) => {
      noticeHelpers.addDontShowNotice(id);
    });

    setMarkedIds(newMarked);
    setPendingIds([]);

    sessionStorage.setItem("modalClosedInSession", "true");
    onClose();
  };

  const handleSlideChange = (swiper: SwiperType) => {
    setCurrentIndex(swiper.realIndex);
  };

  return (
    visibleNotices.length > 0 && (
      <ModalLayout
        isOpen={isOpen}
        onClose={handleModalClose}
        closeBtnClassname="!hidden"
        ariaLabel="Notice"
        className="!p-0 pb-0 overflow-visible !outline-none"
        overlayClassName="overflow-hidden"
        size="lg"
        bg="transparent"
      >
        <div className="relative w-full sm:max-w-[90%] h-full mx-auto overflow-hidden">
          <AnimatePresence>
            {isOpen && (
              <motion.div
                key="notice-modal"
                initial={{ opacity: 0, scale: 0.95, y: 30 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                transition={{
                  duration: 0.35,
                  ease: [0.22, 1, 0.36, 1],
                }}
                className="relative outline-none"
              >
                <Swiper
                  ref={swiperRef}
                  effect={"coverflow"}
                  grabCursor={true}
                  centeredSlides={true}
                  slidesPerView={"auto"}
                  coverflowEffect={{
                    rotate: -50,
                    stretch: 0,
                    depth: 400,
                    modifier: 1,
                    slideShadows: true,
                  }}
                  loop={true}
                  autoplay={
                    isMobile
                      ? {
                          delay: 3000,
                          disableOnInteraction: true,
                          pauseOnMouseEnter: false,
                        }
                      : {
                          delay: 3000,
                          disableOnInteraction: false,
                          pauseOnMouseEnter: true,
                        }
                  }
                  modules={[EffectCoverflow, Autoplay]}
                  className="mySwiper !overflow-visible"
                  onSlideChange={handleSlideChange}
                  onSwiper={(swiper) => {
                    swiperRef.current = swiper;
                    setCurrentIndex(swiper.realIndex);
                  }}
                >
                  <AnimatePresence mode="popLayout">
                    {visibleNotices.map((notice, index) => (
                      <SwiperSlide
                        key={notice.id}
                        className="sm:max-w-[460px] !h-[640px] bg-background rounded-3xl shadow-xl relative overflow-hidden !outline-none"
                      >
                        <NoticeItem
                          title={notice.title}
                          description={notice.content}
                          thumbnail={notice.thumbnail}
                          noticeId={notice.id}
                          isPending={pendingIds.includes(notice.id)}
                          onTogglePending={(id) => {
                            setPendingIds((prev) =>
                              prev.includes(id)
                                ? prev.filter((x) => x !== id)
                                : [...prev, id]
                            );
                          }}
                          onRemove={() => handleRemoveNotice(notice.id)}
                          showRemoveButton={true}
                        />
                      </SwiperSlide>
                    ))}
                  </AnimatePresence>
                </Swiper>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </ModalLayout>
    )
  );
}
