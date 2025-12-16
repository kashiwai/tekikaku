export const ROUTES = {
    HOME: "/",
    BONUS: "/bonus",
    PROMOTIONS: "/promotions",
    NOTICE: "/notice",
    SUPPORT: "/support",
    PROFILE: "/profile",
    PAYMENT: "/payment",
    CASINO: "/games/casino",
    SLOT: "/games/slot",
    SLOT_LIST: "/games",
    HOLDEM: "/games?game=holdem",
    MINIGAME: "/games?game=minigame",
    VIRTUAL: "/games?game=virtual",
    SPORTS: "/games?game=sports",
    FAVORITES: "/favorites",
    // NET8 パチンコ・スロット
    PACHINKO: "/pachinko",
    PACHINKO_PLAY: "/pachinko/play",
    SETTINGS: {
        GENERAL: "/settings/general",
        SECURITY: "/settings/security",
        OFFERS: "/settings/offers",
    },
    ACCOUNT: {
        BALANCE: "/account/balance",
        TRANSACTIONS: "/account/transactions",
        BET_HISTORY: "/account/bet-history",
        DEPOSIT: "/account/deposit",
        WITHDRAWAL: "/account/withdraw",
    },
    HELP_CENTER: {
        BONUS: "/help-center/bonus",
        BETTING: "/help-center/betting",
        SUPPORT: "/help-center/support",
        TRANSACTIONS: "/help-center/transactions",
        REGISTRATION_LOGIN: "/help-center/registration-and-login"
    },
    MAINTENANCE: "/maintenance"
} as const

export const API_ROUTES = {
    AUTH: {
        LOGIN: "v1/account/sign-in",
        LOGOUT: "v1/account/sign-out",
        REGISTER: "",
        CHANGE_PASSWORD: "",
        CHANGE_WITHDRAWL_PASSWORD: "",
        RESET_PASSWORD: "",
        SEND_EMAIL_CODE: "",
        VERIFY_EMAIL_CODE: "",
        LOGIN_SUCCESS: "v1/account/sign-in/success",
        LOGIN_CHECK: "v1/account/sign-in/check",
        REFRESH: "v1/account/sign-in/refresh",
    },
    BONUS: {
        CLAIM_BONUS: "",
        DETAIL: "",
        PUBLIC_DETAIL: "",
    },
    ATTENDANCE: "",
    WHEEL: {
        ROULLETE: ''
    },
    GAME: {
        LIST: '',
        LAUNCH: ''
    },
    SITE: {
        INFO: "/",
        SETTINGS: "v1/api/site"
    },
    BANNERS: "v1/api/banner",
    TRANSACTIONS: {
        LIST: ""
    },
    BETHISTORY: {
        LIST: "",
        BET: ""
    },
    FAVORITE: {
        LIST: ""
    },
    PROMOTION: {
        LIST: "",
        SINGLE: ""
    },
    NOTICE: {
        LIST: ""
    },
    FAQ: {
        LIST: "",
        GROUPED_FAQ: ""
    },
    REFERRAL: "",
    COUPON: "",
    WALLET: {
        WITHDRAWAL: {
            WITHDRAWAL: "", // POST
            INFO: "" // GET
        },
        DEPOSIT: {
            DEPOSIT: "",
            ADDRESS: ""
        }
    },
    LIVE_CHAT: {
        initChat: "v1/livechat/chat",
        getChats: "v1/livechat/chats",
        getInitedChat: "v1/livechat/active-chat",
        getMessages: (chatId: number) => `v1/livechat/chat/${chatId}/messages`
    }
}

type Flatten<T> = T extends string ? T :
    T extends Record<string, infer V> ? Flatten<V> : never;

export type RoutePath = Flatten<typeof ROUTES>;
