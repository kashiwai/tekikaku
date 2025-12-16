import { ISOStringFormat } from "date-fns";

export type BetHistoryItem = {
  gameId: string;
  game: string;
  player: string;
  type: string;
  refId: string;
  amount: number;
  bet: number;
  multiplier: number;
  win: number;
  createdAt: string;
  status: string;
  note: string;
  winMoney: string;
  thumbnail: string;
  gameName: string;
};

export type BetHistoryResponseType = {
  list: BetHistoryItem[],
  total: number;
  types: string[];
}

export type BetInfo = {
  id: number;
  userId: number;
  userInfo: {
    ip: string;
    os: Record<string, any>; // empty object in your example
    exp: number;
    spin: number;
    level: number;
    phone: string;
    client: {
      name: string;
      type: string;
      version: string;
    };
    device: {
      id: string;
      type: string;
      brand: string;
      model: string;
    };
    nickname: string;
    isApprove: string; // could be "approve" | "pending" | "reject" if you want stricter typing
    sessionId: string;
    transaction: {
      pw: string;
      bank: string;
      realname: string;
      bankNumber: string;
      withdrawalType: string; // could be "CRYPTO" | "BANK" etc.
    };
  };
  gameId: number;
  bet: number;
  win: number;
  multiplier: number;
  status: string; // could be "complete" | "pending" | "cancel" etc.
  type: string;   // could be "slot" | "casino" | "sport" etc.
  refId: string;
  gameName: string;
  note: string | null;
  createdAt: string; // ISO date string
};

export type BetItem = {
  bet: number;
  gameId: number;
  gameTitle: string;
  multipler: number;
  gameThumbnail: string;
  time: ISOStringFormat;
  userid: 35
  userName: string;
  win: number
}