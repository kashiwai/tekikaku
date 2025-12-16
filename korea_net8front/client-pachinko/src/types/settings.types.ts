import { BanType } from "./user.types";

export type SettingsType = {
  roulette: {
    id: number;
    title: string;
    odds: number;
    bonus: number;
    isLock: boolean;
  }[];
  banners: BannerType[],
  attendance: {
    id: number;
    days: number;
    bonus: number;
    isLock: boolean;
  }[];
  design: {
    logo: string;
  };
  unlockRate: number;
  site: {
    minBonusConvert: number;
    unlockBonus: number;
    bettingAttendance: number;
    logo: LogoType;
    favicon: string | null;
    info: { domain: string, nickname: string }
  },
  games: { id: number, siteId: number, type: BanType, isUse: boolean }[],
};

export type LogoType = { dark: { icon: string, title: string }, light: { icon: string, title: string } };
export type BannerType = {
  id: number;
  siteId: number;
  thumbnail: string;
  title: string | null;
  mobileThumbnail: string | null;
  link: string | null;
  page: "main-unauth" | "main-auth";
  inUse: boolean;
}