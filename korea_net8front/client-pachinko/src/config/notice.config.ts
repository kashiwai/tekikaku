import { PageFaqItem } from "@/types/faq";
import { PageNoticeItem } from "@/types/notice.types";

export type NoticeListResponse = { list: PageNoticeItem[], total: number };
export type FaqListResponse = { list: PageFaqItem[], total: number };
export type DontShowNoticesResponse = { id: number; expiresAt: number }[];

export const noticeConfig = {
    limitPerPage: 5,
    localstorageKey: "notices_dont_show_24hr",
    pagination: {
        limit: 16
    }
}