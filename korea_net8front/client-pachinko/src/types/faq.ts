export type FAQType = PageFaqItem;

export type PageNoticeType = {
    list: PageFaqItem[];
    total: number;
}

export type PageFaqItem = {
    title: Record<string, string>;
    content: Record<string, string>;
    createdAt: string;
    isUse: boolean,
    noticeId: string;
    faqId: string;
};
