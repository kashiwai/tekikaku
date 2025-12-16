export type Notice = PageNoticeItem;

export type PageNoticeType = {
  list: PageNoticeItem[];
  total: number;
}

export type PageNoticeItem = {
  thumbnail: string | null;
  content: { en: string, ja: string, ko: string, zh: string };
  endDate: string | null;
  id: number;
  isUse: boolean;
  siteId: number;
  startDate: string;
  title: { en: string, ja: string, ko: string, zh: string };
  views: number;
};
