export type ModalType =
    | "auth"
    | "wallet"
    | "notices"
    | "profile"
    | "promotion"
    | "wheel"
    | "attendance"
    | "search"
    | "unlock"
    | "update-password"
    | "update-withdrawal-password"
    | "betInfo"

export type ModalParamsMap = {
    auth: {
        tab: "register" | "login" | "verify-email" | "reset-password";
    };
    wallet: {
        tab: "deposit" | "withdraw" | "current-rolling";
    },
    profile: {
        tab: "profile" | "edit" | "vip",
    },
    promotion: {
        promotionId: string;
    },
    wheel: {
        tab: "wheel" | "details";
    },
    betInfo: {
        id: string;
    }
};

export type AuthModalTab = ModalParamsMap['auth']['tab'];
export type WalletModalTab = ModalParamsMap['wallet']['tab'];
export type ProfileModalTab = ModalParamsMap['profile']['tab'];
export type WheelModalTab = ModalParamsMap['wheel']['tab'];

export type ModalParams<T extends ModalType> =
    T extends keyof ModalParamsMap ? ModalParamsMap[T] : object;