export type FavoriteItem = {
    id: string;
    gameId: string;
    api: string;
    langs: {
        ko: string;
        en: string;
        ja: string;
        zh: string;
    };
    rank: number;
    thumbnail: string;
    thumbnails: {
        "300x300": string;
    },
    title: string;
    type: string;
    vendor: string;
    exclude: boolean;
    isFavorite: boolean;
    category: string;
    isSiteUse: boolean;
    isUserUse: boolean;
}