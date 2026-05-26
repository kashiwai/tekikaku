"use client";

import { useState, useCallback } from "react";
import { useAccount, useReadContract, useWriteContract, useWaitForTransactionReceipt } from "wagmi";
import { formatUnits, maxUint256 } from "viem";
import {
  ROUTER_ADDRESS, PAIR_ADDRESS,
  XEGM_DECIMALS, USDT_DECIMALS,
  ROUTER_ABI, ERC20_ABI, PAIR_ABI,
} from "@/lib/contracts";
import { useSlippage, applySlippage, getDeadline } from "@/lib/slippage";

const PCTS = [25, 50, 75, 100];

export default function RemoveLiquidityPage() {
  const { address, isConnected, chain } = useAccount();
  const { slippageBps } = useSlippage();
  const [pct, setPct] = useState(100);

  // LP balance
  const { data: lpBalance } = useReadContract({
    address: PAIR_ADDRESS || undefined,
    abi: PAIR_ABI,
    functionName: "balanceOf",
    args: address ? [address] : undefined,
    query: { enabled: Boolean(address && PAIR_ADDRESS) },
  });
  const { data: lpSupply } = useReadContract({
    address: PAIR_ADDRESS || undefined,
    abi: PAIR_ABI,
    functionName: "totalSupply",
    query: { enabled: Boolean(PAIR_ADDRESS) },
  });
  const { data: reserves } = useReadContract({
    address: PAIR_ADDRESS || undefined,
    abi: PAIR_ABI,
    functionName: "getReserves",
    query: { enabled: Boolean(PAIR_ADDRESS) },
  });

  const liquidityToRemove = lpBalance ? (lpBalance * BigInt(pct)) / 100n : 0n;

  let estXegm = 0n;
  let estUsdt = 0n;
  if (lpSupply && lpSupply > 0n && reserves && liquidityToRemove > 0n) {
    estXegm = (liquidityToRemove * reserves[0]) / lpSupply;
    estUsdt = (liquidityToRemove * reserves[1]) / lpSupply;
  }

  // Allowance
  const { data: lpAllowance } = useReadContract({
    address: PAIR_ADDRESS || undefined,
    abi: ERC20_ABI,
    functionName: "allowance",
    args: address && ROUTER_ADDRESS ? [address, ROUTER_ADDRESS] : undefined,
    query: { enabled: Boolean(address && ROUTER_ADDRESS && PAIR_ADDRESS) },
  });
  const needsApprove = liquidityToRemove > 0n && (lpAllowance ?? 0n) < liquidityToRemove;

  const { writeContract: writeApprove, data: approveTxHash, isPending: isApprovePending } = useWriteContract();
  const { writeContract: writeRemove, data: removeTxHash, isPending: isRemovePending } = useWriteContract();
  const { isLoading: isApproveConfirming, isSuccess: isApproveSuccess } = useWaitForTransactionReceipt({ hash: approveTxHash });
  const { isLoading: isRemoveConfirming, isSuccess: isRemoveSuccess } = useWaitForTransactionReceipt({ hash: removeTxHash });

  const handleApprove = () => {
    if (!ROUTER_ADDRESS || !PAIR_ADDRESS) return;
    writeApprove({ address: PAIR_ADDRESS, abi: ERC20_ABI, functionName: "approve", args: [ROUTER_ADDRESS, maxUint256] });
  };

  const handleRemove = useCallback(() => {
    if (!liquidityToRemove || !address || !ROUTER_ADDRESS) return;
    const xegmMin = applySlippage(estXegm, slippageBps);
    const usdtMin  = applySlippage(estUsdt, slippageBps);
    writeRemove({
      address: ROUTER_ADDRESS,
      abi: ROUTER_ABI,
      functionName: "removeLiquidity",
      args: [liquidityToRemove, xegmMin, usdtMin, address, getDeadline()],
    });
  }, [liquidityToRemove, estXegm, estUsdt, address, slippageBps, writeRemove]);

  const isLoading = isApprovePending || isApproveConfirming || isRemovePending || isRemoveConfirming;
  const isWrongNetwork = isConnected && chain?.id !== 1;
  const isDeployed = Boolean(ROUTER_ADDRESS);

  return (
    <div className="bg-white rounded-[6px] border border-[#E5E8EC] p-5 shadow-sm">
      <h1 className="text-sm font-semibold text-gray-900 mb-4">LP 削除</h1>

      {/* LP balance */}
      <div className="bg-[#F5F7FA] rounded-[6px] p-3 mb-4">
        <div className="text-xs text-gray-500 mb-1">保有 LP トークン</div>
        <div className="text-xl font-semibold tabular-nums text-gray-900">
          {lpBalance != null ? parseFloat(formatUnits(lpBalance, 18)).toLocaleString("ja-JP", { maximumFractionDigits: 8 }) : "—"}
        </div>
      </div>

      {/* Percentage selector */}
      <div className="mb-4">
        <div className="flex gap-2 mb-2">
          {PCTS.map((p) => (
            <button
              key={p}
              onClick={() => setPct(p)}
              className={`flex-1 py-2 text-xs font-medium rounded transition-colors ${
                pct === p
                  ? "bg-[#2563EB] text-white"
                  : "bg-[#F5F7FA] text-gray-600 hover:bg-gray-200"
              }`}
            >
              {p}%
            </button>
          ))}
        </div>
      </div>

      {/* Estimate */}
      {liquidityToRemove > 0n && (
        <div className="bg-[#F5F7FA] rounded-[6px] p-3 mb-4 space-y-1 text-sm">
          <div className="flex justify-between">
            <span className="text-gray-500">受け取る xEGM</span>
            <span className="tabular-nums font-medium">
              {parseFloat(formatUnits(estXegm, XEGM_DECIMALS)).toLocaleString("ja-JP", { maximumFractionDigits: 6 })}
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">受け取る USDT</span>
            <span className="tabular-nums font-medium">
              {parseFloat(formatUnits(estUsdt, USDT_DECIMALS)).toLocaleString("ja-JP", { maximumFractionDigits: 6 })}
            </span>
          </div>
        </div>
      )}

      {/* Actions */}
      {!isConnected ? (
        <p className="text-center text-sm text-gray-500">MetaMask を接続してください</p>
      ) : isWrongNetwork ? (
        <p className="text-center text-sm text-red-500">Mainnet に切り替えてください</p>
      ) : !isDeployed ? (
        <p className="text-center text-sm text-gray-400">コントラクトアドレスを contracts.ts に設定してください</p>
      ) : !lpBalance || lpBalance === 0n ? (
        <button disabled className="w-full py-3 text-sm font-medium bg-gray-100 text-gray-400 rounded-[6px]">
          LP なし
        </button>
      ) : needsApprove ? (
        <button onClick={handleApprove} disabled={isLoading}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60">
          {isLoading ? "処理中..." : "LP トークンを承認"}
        </button>
      ) : (
        <button onClick={handleRemove} disabled={isLoading || liquidityToRemove === 0n}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60">
          {isLoading ? "処理中..." : `LP を削除 (${pct}%)`}
        </button>
      )}

      {isRemoveSuccess && <p className="mt-3 text-center text-xs text-green-600">✓ LP 削除完了</p>}
      {isApproveSuccess && <p className="mt-3 text-center text-xs text-green-600">✓ 承認完了</p>}
    </div>
  );
}
