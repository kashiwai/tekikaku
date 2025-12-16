import { BitcoinTransactionIcon, Settings01Icon, TransactionHistoryIcon, UserIcon, Wallet03Icon } from '@hugeicons/core-free-icons'

import { IconSvgObject } from '@/components/icon/iconBase';

import { RoutePath, ROUTES } from "./routes.config";
import { ICONS } from '@/constants/icons';

type Action = "wallet-modal" | "profile-modal";

export type ProfileMenu = {
    label: string;
    icon: IconSvgObject;
    href: RoutePath;
    action?: Action
    requiresAuth?: boolean
}

export const PROFILE_MENU: ProfileMenu[] = [
    // {
    //     href: "/",
    //     icon: Wallet03Icon,
    //     label: "WALLET",
    //     action: "wallet-modal"
    // },
    {
        href: ROUTES.ACCOUNT.BALANCE,
        icon: UserIcon,
        label: "BALANCE",
        // action: "profile-modal"
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
        href: ROUTES.ACCOUNT.TRANSACTIONS,
        icon: BitcoinTransactionIcon,
        label: "TRANSACTIONS"
    },
    {
        href: ROUTES.ACCOUNT.BET_HISTORY,
        icon: TransactionHistoryIcon,
        label: "BET_HISTORY"
    },
    {
        href: ROUTES.SETTINGS.GENERAL,
        icon: Settings01Icon,
        label: "SETTINGS"
    },

];