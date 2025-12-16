"use client";
import { useEffect, useCallback } from "react";
import { useUserStore } from "@/store/user.store";
import { getSocket } from "@/lib/socket/socket";
import { User } from "@/types/user.types";
import { redirectUser } from "@/actions/api.actions";
import { ROUTES } from "@/config/routes.config";
import { deleteConnectSid } from "@/actions/cookie.actions";

interface BalanceUpdateData {
  wallets: User["wallets"];
  bonus: User["bonus"];
}

interface HydrateUserProps {
  initialUser: User | null;
}

export default function HydrateUser({ initialUser }: HydrateUserProps) {
  const user = useUserStore((store) => store.user);
  const setUser = useUserStore((state) => state.setUser);
  const updateUser = useUserStore((state) => state.updateUser);

  /**
   * Hydrate user once
   */
  useEffect(() => {
    if (initialUser) {
      setUser(initialUser);
    }
  }, [initialUser, setUser]);

  /**
   * Socket Listeners
   */
  const handleForceLogout = useCallback(() => {
    setUser(null);
    deleteConnectSid();
  }, []);

  const handleBalanceUpdate = useCallback(
    (data: BalanceUpdateData) => {
      updateUser({
        wallets: data.wallets,
        bonus: data.bonus,
      });
    },
    [updateUser]
  );

  useEffect(() => {
    const socket = getSocket();
    // if (!socket.connected) {
    //   console.log("Socket not connected, connecting...");
    //   socket.connect();
    // }

    socket.on("user_balance_update", handleBalanceUpdate);
    socket.on("force_logout", handleForceLogout);

    if (user?.id) {
      socket.emit("join_online", { userId: user.id, role: "user" });
    }

    return () => {
      socket.off("user_balance_update", handleBalanceUpdate);
      socket.off("force_logout", handleForceLogout);
    };
  }, [user?.id, handleBalanceUpdate, handleForceLogout]);

  return null;
}
