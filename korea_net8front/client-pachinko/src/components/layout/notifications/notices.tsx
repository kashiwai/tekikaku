"use client";
import { useRef } from "react";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { LayoutState } from "@/store/layout.store";
import { useNoticeStore } from "@/store/notice.store";
import NoticeMessage from "./noticeMessage";
import Link from "next/link";
import { ROUTES } from "@/config/routes.config";
import { ArrowRight } from "lucide-react";
import { useLocale, useTranslations } from "next-intl";
import { LocaleKey } from "@/i18n/request";

type Props = Pick<LayoutState, "toggleNotification">;

export default function Notices({ toggleNotification }: Props) {
  const notices = useNoticeStore((store) => store.notices);
  const removeNotice = useNoticeStore((store) => store.removeNotice);
  const locale = useLocale() as LocaleKey;
  const scrollContainerRef = useRef<HTMLDivElement | null>(null);
  const t = useTranslations("NOTICE");

  return (
    <aside className="flex flex-col w-full shrink-0 ">
      <div className="w-full h-[70px] border-b border-neutral/10 flex items-center justify-between pl-4 pr-2">
        <div className="flex flex-col">
          <p className="text-base font-semibold">{t("NOTICE")}</p>
          <span className="text-xs font-medium opacity-80">{notices.length} {t("NEW")}</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="flex items-center gap-1"></div>
          <Button
            onClick={toggleNotification}
            size={`icon_sm`}
            className="rounded-full border-none"
          >
            <IconBase icon={ICONS.CLOSE_X} />
          </Button>
        </div>
      </div>
      <div className="flex flex-col gap-3 flex-1 overflow-auto custom-scrollbar p-2" ref={scrollContainerRef}>
        {notices.length > 0 ? (
          notices.map((notice, index: number) => (
            <NoticeMessage
              key={index}
              notice={notice}
              removeNotice={removeNotice}
              locale={locale}
            />
          ))
        ) : (
          <div className="h-full flex items-center justify-center">
            <span className="text-foreground/50 m-auto">
              {t("NO_NOTICE_YET")}
            </span>
          </div>
        )}
      </div>
      <div className="p-4 flex items-center">
        <Link href={ROUTES.NOTICE} className="ml-auto text-foreground hover:text-primary flex items-center gap-1.5 text-sm font-semibold">{t("VIEW_MORE")} <ArrowRight className="size-4.5" /></Link>
      </div>
    </aside>
  );
}
