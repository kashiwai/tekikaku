"use client";
import { useEffect } from "react";
import { useBetHistoryStore } from "@/store/bets.store";
import { getSocket } from "@/lib/socket/socket";

export function HydrateBets() {
  const { setRecentBets, setRecentWinBets, addBet, addWinBet } =
    useBetHistoryStore();

  useEffect(() => {
    const socket = getSocket();
    // ローカルモードではソケット接続をスキップ
    if (!socket) return;

    // ask server for recent bets when entering
    socket.emit("recent_bet");
    socket.emit("recent_win_bet");

    // server replies with lists
    socket.on("recent_bet", (data) => {
      setRecentBets(data);
    });
    socket.on("recent_win_bet", (data) => {
      setRecentWinBets(data);
    });

    // server pushes live updates
    socket.on("bet", (data) => {
      addBet(data);
    });
    socket.on("win_bet", (data) => {
      addWinBet(data);
    });

    return () => {
      socket.off("recent_bet");
      socket.off("recent_win_bet");
      socket.off("bet");
      socket.off("win_bet");
    };
  }, [setRecentBets, setRecentWinBets, addBet, addWinBet]);

  return null;
}
