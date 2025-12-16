import type { IconSvgObject } from '@/components/icon/iconBase';
import { RoutePath, ROUTES } from "@/config/routes.config";
import { ICONS } from '@/constants/icons';

export type Action = "deposit-modal" | "withdraw-modal" | "chat";

export type AsideNavType = {
    label: string;
    icon: IconSvgObject;
    href: RoutePath;
    action?: Action;
    identifier?: string;
    requiresAuth: boolean;
}

type AsideMenu = {
    title: string;
    nav: AsideNavType[];
};

export const ASIDE_MENU: AsideMenu[] = [
    {
        title: "GAMES",
        nav: [
            {
                href: ROUTES.PACHINKO,
                identifier: "pachinko",
                icon: ICONS.GAME_CONSOLE,
                label: "PACHINKO",
                requiresAuth: false,
            },
            {
                href: ROUTES.CASINO,
                identifier: "casino",
                icon: ICONS.LIVE_STREAMING,
                label: "LIVE",
                requiresAuth: false,
            },
            {
                href: ROUTES.SLOT,
                identifier: "slot",
                icon: ICONS.CHERRY,
                label: "SLOT",
                requiresAuth: false,
            },
            {
                href: ROUTES.HOLDEM,
                identifier: "holdem",
                icon: ICONS.POKER_CHIP,
                label: "HOLDEM",
                requiresAuth: false,
            },
            {
                href: ROUTES.MINIGAME,
                identifier: "minigame",
                icon: ICONS.GAME_CONSOLE,
                label: "MINIGAME",
                requiresAuth: false,
            },
            {
                href: ROUTES.VIRTUAL,
                identifier: "virtual",
                icon: ICONS.VR_GLASSES,
                label: "VIRTUAL",
                requiresAuth: false,
            },
            {
                href: ROUTES.SPORTS,
                identifier: "sports",
                icon: ICONS.FOOTBALL_BALL,
                label: "SPORTS",
                requiresAuth: false,
            },
            {
                href: ROUTES.FAVORITES,
                icon: ICONS.HEART,
                label: "FAVORITES",
                requiresAuth: true,
            },
        ]
    },
    {
        title: "MAIN",
        nav: [
            {
                label: "DEPOSIT",
                icon: ICONS.WALLET_ADD,
                href: ROUTES.ACCOUNT.DEPOSIT,
                requiresAuth: true,
            },
            {
                label: "WITHDRAW",
                icon: ICONS.BANK,
                href: ROUTES.ACCOUNT.WITHDRAWAL,
                requiresAuth: true,
            },
            {
                href: ROUTES.BONUS,
                icon: ICONS.GIFT,
                label: "BONUS",
                requiresAuth: false,
            },
            {
                href: ROUTES.PROMOTIONS,
                icon: ICONS.MEGAPHONE,
                label: "PROMOTIONS",
                requiresAuth: false,
            },
            {
                href: ROUTES.NOTICE,
                icon: ICONS.NOTICE_BELL,
                label: "NOTICE",
                requiresAuth: false,
            },
            {
                label: "BALANCE",
                icon: ICONS.WALLET,
                href: ROUTES.ACCOUNT.BALANCE,
                requiresAuth: true,
            },
            {
                label: "BET_HISTORY",
                icon: ICONS.HISTORY,
                href: ROUTES.ACCOUNT.BET_HISTORY,
                requiresAuth: true,
            },
            {
                label: "TRANSACTIONS",
                icon: ICONS.BTC_TRANSACTION,
                href: ROUTES.ACCOUNT.TRANSACTIONS,
                requiresAuth: true,
            },
            {
                href: ROUTES.SETTINGS.GENERAL,
                icon: ICONS.SETTINGS,
                label: "SETTINGS",
                requiresAuth: true,
            },
            {
                href: ROUTES.HELP_CENTER.BONUS,
                icon: ICONS.HELP_CENTER,
                label: "Q&A",
                requiresAuth: false,
            },
            {
                href: `#` as any,
                icon: ICONS.HEADPHONES,
                label: "SUPPORT",
                action: "chat",
                requiresAuth: false,
            },
        ]
    },
];

export const SETTINGS_MENU: AsideNavType[] = [
    {
        label: "GENERAL",
        icon: ICONS.USER,
        href: ROUTES.SETTINGS.GENERAL,
        requiresAuth: false
    },
    {
        label: "SECURITY",
        icon: ICONS.SHIELD_CHECK,
        href: ROUTES.SETTINGS.SECURITY,
        requiresAuth: false
    },
    {
        label: "OFFERS",
        icon: ICONS.TICKET_PERCENT,
        href: ROUTES.SETTINGS.OFFERS,
        requiresAuth: false
    }
]

export const ACCOUNT_MENU: AsideNavType[] = [
    {
        label: "BALANCE",
        icon: ICONS.WALLET,
        href: ROUTES.ACCOUNT.BALANCE,
        requiresAuth: false
    },
    {
        label: "DEPOSIT",
        icon: ICONS.WALLET_ADD,
        href: ROUTES.ACCOUNT.DEPOSIT,
        requiresAuth: false
    },
    {
        label: "WITHDRAW",
        icon: ICONS.BANK,
        href: ROUTES.ACCOUNT.WITHDRAWAL,
        requiresAuth: false
    },
    {
        label: "TRANSACTIONS",
        icon: ICONS.BTC_TRANSACTION,
        href: ROUTES.ACCOUNT.TRANSACTIONS,
        requiresAuth: false
    },
    {
        label: "BET_HISTORY",
        icon: ICONS.HISTORY,
        href: ROUTES.ACCOUNT.BET_HISTORY,
        requiresAuth: false
    }
]

export const HELP_CENTER_MENU: AsideNavType[] = [
    {
        label: "BETTING",
        icon: ICONS.BANK,
        href: ROUTES.HELP_CENTER.BETTING,
        requiresAuth: false
    },
    {
        label: "BONUS",
        icon: ICONS.GIFT,
        href: ROUTES.HELP_CENTER.BONUS,
        requiresAuth: false
    },
    // {
    //     label: "SUPPORT",
    //     icon: ICONS.HEADPHONES,
    //     href: ROUTES.HELP_CENTER.SUPPORT
    // },
    {
        label: "TRANSACTIONS",
        icon: ICONS.BITCOIN_BAG,
        href: ROUTES.HELP_CENTER.TRANSACTIONS,
        requiresAuth: false
    },
    {
        label: "REGISTRATION_LOGIN",
        icon: ICONS.USER,
        href: ROUTES.HELP_CENTER.REGISTRATION_LOGIN,
        requiresAuth: false
    },
]