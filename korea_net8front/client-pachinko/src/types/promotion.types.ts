export type PromotionType = {
    id: number;
    siteId: number;
    thumbnail: string;
    title: Record<string, string | null>;
    content: Record<string, string | null>;
    views: number;
    buttonName: Record<string, string | null>;
    buttonLink: string;
    startDate: string;
    endDate: string;
    isUse: boolean;
    isShow: boolean;
};