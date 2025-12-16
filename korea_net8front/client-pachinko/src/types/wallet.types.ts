type RollingCategory =
    | "sports"
    | "casino"
    | "slot"
    | "holdem"
    | "minigame"
    | "virtual";

interface RollingInfo {
    setRolling: number;
    percent: number;
    betting: string; // e.g. "56533.9028/60000"
}

interface WithdrawListItem {
    depositMoney: number;
    status: "complete" | "pending" | "failed"; // extend if needed
    withdrawalable: number;
    rollings: Record<RollingCategory, RollingInfo>;
}

export interface WithdrawResponse {
    list: WithdrawListItem[];
    totalWithdrawalable: number;
    max: number;
    min: number;
    duration: number;
    lastWithdraw: string;
    possibleWithdraw: string;
}

export type UserDepositResponse = {
    orderNumber: string;
    amount: number;
}

export interface DepositAddressResponse {
    address?: string;
    bankAccountId?: number;
    type: string;
}