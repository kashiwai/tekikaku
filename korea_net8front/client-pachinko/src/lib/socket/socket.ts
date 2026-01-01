import { io, Socket } from "socket.io-client";

let socket: Socket | null = null;

// ローカルモード判定（API URLが空または未設定の場合）
const isLocalMode = !process.env.NEXT_PUBLIC_API_URL || process.env.NEXT_PUBLIC_API_URL === '';

export function getSocket(): Socket | null {
  // ローカルモードではソケット接続を無効化
  if (isLocalMode) {
    console.log("🔌 Socket disabled in local mode");
    return null;
  }

  if (!socket) {
    socket = io(process.env.NEXT_PUBLIC_API_URL as string, {
      withCredentials: true,
      reconnection: true,
      reconnectionAttempts: 5, // 無限再接続を防ぐ
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      transports: ["websocket"],
    });

    socket.on("connect", () => {
      console.log("✅ Socket connected:", socket!.id);
    });

    socket.on("disconnect", (reason) => {
      console.log("❌ Socket disconnected:", reason);
    });

    socket.on("connect_error", (error) => {
      console.log("⚠️ Socket connection error:", error.message);
    });
  }

  return socket;
}