export type TransactionItem = {
    balanceHistory: {
        type: string;
        orderNumber: string;
        paidType: {
            money: boolean;
            locked: boolean;
            unlocked: boolean;
            losing: boolean;
            rolling: boolean;
        }
        money: any;
        locked: any;
        unlocked: any;
        losing: any;
        rolling: any;
        createdAt: string;
        status: string;
    };
};

export type TransactionResponseType = {
    list: TransactionItem[],
    total: number;
    types: string[];
}