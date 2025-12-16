import { create } from "zustand";
import { BetHistoryItem, BetItem } from "@/types/bethistory";

type BetHistoryState = {
  betsData: {
    betlist: BetItem[];
    winlist: BetItem[];
  };
  // 🔹 Actions
  setRecentBets: (bets: BetItem[]) => void;
  setRecentWinBets: (wins: BetItem[]) => void;
  addBet: (bet: BetItem) => void;
  addWinBet: (bet: BetItem) => void;
};

export const useBetHistoryStore = create<BetHistoryState>((set) => ({
  betsData: {
    betlist: [],
    winlist: [],
  },

  setRecentBets: (bets) =>
    set((state) => ({
      betsData: { ...state.betsData, betlist: bets },
    })),

  setRecentWinBets: (wins) =>
    set((state) => ({
      betsData: { ...state.betsData, winlist: wins },
    })),

  addBet: (bet) =>
    set((state) => ({
      betsData: {
        ...state.betsData,
        betlist: [bet, ...state.betsData.betlist].slice(0, 50), // keep max 50
      },
    })),

  addWinBet: (bet) =>
    set((state) => ({
      betsData: {
        ...state.betsData,
        winlist: [bet, ...state.betsData.winlist].slice(0, 50),
      },
    })),
}));