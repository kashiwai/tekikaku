export type BannerData = {
    category: CategoryItem[];
    game: GameItem[];
    logout: LogoutItem[];
};

export type CategoryItem = {
    id: number;
    siteId: number;
    thumbnail: string;
    title: string;
    link: string | null;
    category: "category";
    isUse: boolean;
    mobileThumbnail: string | null;
};

export type GameItem = {
    id: number;
    siteId: number;
    thumbnail: string;
    title: string;
    link: string | null;
    category: "game";
    isUse: boolean;
    mobileThumbnail: string | null;
};

export type LogoutItem = {
    id: number;
    siteId: number;
    thumbnail: string;
    title: string | null;
    link: string | null;
    category: "logout";
    isUse: boolean;
    mobileThumbnail: string | null;
};