"use client";

import { useAccount, useConnect, useDisconnect } from "wagmi";
import { injected } from "wagmi/connectors";

export function ConnectButton() {
  const { address, isConnected, chain } = useAccount();
  const { connect, isPending } = useConnect();
  const { disconnect } = useDisconnect();

  if (!isConnected) {
    return (
      <button
        onClick={() => connect({ connector: injected() })}
        disabled={isPending}
        className="px-4 py-1.5 text-sm font-medium bg-[#2563EB] text-white rounded hover:bg-[#1D4ED8] transition-colors disabled:opacity-50"
      >
        {isPending ? "接続中..." : "MetaMask 接続"}
      </button>
    );
  }

  const isWrongNetwork = chain?.id !== 1;

  return (
    <div className="flex items-center gap-2">
      {isWrongNetwork && (
        <span className="text-xs text-red-500 font-medium">Mainnet に切り替えてください</span>
      )}
      <button
        onClick={() => disconnect()}
        className="px-3 py-1.5 text-xs font-mono bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors"
        title="クリックで切断"
      >
        {address?.slice(0, 6)}...{address?.slice(-4)}
      </button>
    </div>
  );
}
