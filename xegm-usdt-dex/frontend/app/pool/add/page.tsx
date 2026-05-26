"use client";

import { useState, useCallback } from "react";
import { useAccount, useReadContract, useWriteContract, useWaitForTransactionReceipt } from "wagmi";
import { parseUnits, formatUnits, maxUint256 } from "viem";
import {
  XEGM_ADDRESS, USDT_ADDRESS, ROUTER_ADDRESS,
  XEGM_DECIMALS, USDT_DECIMALS,
  ROUTER_ABI, ERC20_ABI, PAIR_ABI, PAIR_ADDRESS,
} from "@/lib/contracts";
import { useSlippage, applySlippage, getDeadline } from "@/lib/slippage";
import { TokenTag } from "@/components/TokenTag";

export default function AddLiquidityPage() {
  const { address, isConnected, chain } = useAccount();
  const { slippageBps } = useSlippage();

  const [amountXegm, setAmountXegm] = useState("");
  const [amountUsdt, setAmountUsdt] = useState("");

  // Reserves for quote calculation
  const { data: reserves } = useReadContract({
    address: PAIR_ADDRESS || undefined,
    abi: PAIR_ABI,
    functionName: "getReserves",
    query: { enabled: Boolean(PAIR_ADDRESS) },
  });
  const isFirstDeposit = !reserves || (reserves[0] === 0n && reserves[1] === 0n);

  // Auto-compute USDT from xEGM input
  const { data: quotedUsdt } = useReadContract({
    address: ROUTER_ADDRESS || undefined,
    abi: ROUTER_ABI,
    functionName: "quote",
    args: amountXegm && parseFloat(amountXegm) > 0
      ? [parseUnits(amountXegm, XEGM_DECIMALS), true]
      : undefined,
    query: { enabled: Boolean(amountXegm && parseFloat(amountXegm) > 0 && ROUTER_ADDRESS && !isFirstDeposit) },
  });

  // When quotedUsdt updates, sync amountUsdt (only if user hasn't edited)
  const displayUsdt = !isFirstDeposit && quotedUsdt
    ? formatUnits(quotedUsdt, USDT_DECIMALS)
    : amountUsdt;

  let amountXegmWei: bigint | undefined;
  let amountUsdtWei: bigint | undefined;
  try {
    if (amountXegm && parseFloat(amountXegm) > 0) amountXegmWei = parseUnits(amountXegm, XEGM_DECIMALS);
    const usdtStr = !isFirstDeposit && quotedUsdt ? formatUnits(quotedUsdt, USDT_DECIMALS) : amountUsdt;
    if (usdtStr && parseFloat(usdtStr) > 0) amountUsdtWei = parseUnits(usdtStr, USDT_DECIMALS);
  } catch {}

  // Allowances
  const { data: xegmAllowance } = useReadContract({
    address: XEGM_ADDRESS,
    abi: ERC20_ABI,
    functionName: "allowance",
    args: address && ROUTER_ADDRESS ? [address, ROUTER_ADDRESS] : undefined,
    query: { enabled: Boolean(address && ROUTER_ADDRESS) },
  });
  const { data: usdtAllowance } = useReadContract({
    address: USDT_ADDRESS,
    abi: ERC20_ABI,
    functionName: "allowance",
    args: address && ROUTER_ADDRESS ? [address, ROUTER_ADDRESS] : undefined,
    query: { enabled: Boolean(address && ROUTER_ADDRESS) },
  });

  const needsXegmApprove = amountXegmWei != null && (xegmAllowance ?? 0n) < amountXegmWei;
  const needsUsdtApprove = amountUsdtWei != null && (usdtAllowance ?? 0n) < amountUsdtWei;

  const { writeContract: writeApprove, data: approveTxHash, isPending: isApprovePending } = useWriteContract();
  const { writeContract: writeAdd, data: addTxHash, isPending: isAddPending } = useWriteContract();
  const { isLoading: isApproveConfirming, isSuccess: isApproveSuccess } = useWaitForTransactionReceipt({ hash: approveTxHash });
  const { isLoading: isAddConfirming, isSuccess: isAddSuccess } = useWaitForTransactionReceipt({ hash: addTxHash });

  const handleApproveXegm = () => {
    if (!ROUTER_ADDRESS) return;
    writeApprove({ address: XEGM_ADDRESS, abi: ERC20_ABI, functionName: "approve", args: [ROUTER_ADDRESS, maxUint256] });
  };
  const handleApproveUsdt = () => {
    if (!ROUTER_ADDRESS) return;
    writeApprove({ address: USDT_ADDRESS, abi: ERC20_ABI, functionName: "approve", args: [ROUTER_ADDRESS, maxUint256] });
  };

  const handleAdd = useCallback(() => {
    if (!amountXegmWei || !amountUsdtWei || !address || !ROUTER_ADDRESS) return;
    const xegmMin = applySlippage(amountXegmWei, slippageBps);
    const usdtMin = applySlippage(amountUsdtWei, slippageBps);
    writeAdd({
      address: ROUTER_ADDRESS,
      abi: ROUTER_ABI,
      functionName: "addLiquidity",
      args: [amountXegmWei, amountUsdtWei, xegmMin, usdtMin, address, getDeadline()],
    });
  }, [amountXegmWei, amountUsdtWei, address, slippageBps, writeAdd]);

  const isLoading = isApprovePending || isApproveConfirming || isAddPending || isAddConfirming;
  const isWrongNetwork = isConnected && chain?.id !== 1;
  const isDeployed = Boolean(ROUTER_ADDRESS);

  return (
    <div className="bg-white rounded-[6px] border border-[#E5E8EC] p-5 shadow-sm">
      <h1 className="text-sm font-semibold text-gray-900 mb-4">LP 追加</h1>

      {isFirstDeposit && (
        <div className="mb-3 bg-[#EFF6FF] border border-[#BFDBFE] rounded p-3 text-xs text-[#1D4ED8]">
          初回の流動性提供です。あなたが最初の価格を設定します。投入比率がそのまま初期価格になります。
        </div>
      )}

      {/* xEGM input */}
      <div className="bg-[#F5F7FA] rounded-[6px] p-3 mb-2">
        <div className="flex items-center justify-between mb-1">
          <span className="text-xs text-gray-500">xEGM</span>
        </div>
        <div className="flex items-center gap-2">
          <input
            type="number"
            placeholder="0.0"
            value={amountXegm}
            onChange={(e) => setAmountXegm(e.target.value)}
            className="flex-1 bg-transparent text-xl font-semibold tabular-nums text-gray-900 placeholder-gray-300 focus:outline-none"
          />
          <TokenTag token="xEGM" />
        </div>
      </div>

      {/* USDT input */}
      <div className="bg-[#F5F7FA] rounded-[6px] p-3 mb-4">
        <div className="flex items-center justify-between mb-1">
          <span className="text-xs text-gray-500">USDT</span>
          {!isFirstDeposit && quotedUsdt && (
            <span className="text-xs text-gray-400">自動計算</span>
          )}
        </div>
        <div className="flex items-center gap-2">
          <input
            type="number"
            placeholder="0.0"
            value={displayUsdt}
            onChange={(e) => { if (isFirstDeposit) setAmountUsdt(e.target.value); }}
            readOnly={!isFirstDeposit}
            className={`flex-1 bg-transparent text-xl font-semibold tabular-nums text-gray-900 placeholder-gray-300 focus:outline-none ${!isFirstDeposit ? "cursor-default" : ""}`}
          />
          <TokenTag token="USDT" />
        </div>
      </div>

      {/* Actions */}
      {!isConnected ? (
        <p className="text-center text-sm text-gray-500">MetaMask を接続してください</p>
      ) : isWrongNetwork ? (
        <p className="text-center text-sm text-red-500">Mainnet に切り替えてください</p>
      ) : !isDeployed ? (
        <p className="text-center text-sm text-gray-400">コントラクトアドレスを contracts.ts に設定してください</p>
      ) : needsXegmApprove ? (
        <button onClick={handleApproveXegm} disabled={isLoading}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60">
          {isLoading ? "処理中..." : "xEGM を承認"}
        </button>
      ) : needsUsdtApprove ? (
        <button onClick={handleApproveUsdt} disabled={isLoading}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60">
          {isLoading ? "処理中..." : "USDT を承認"}
        </button>
      ) : (
        <button onClick={handleAdd} disabled={isLoading || !amountXegmWei || !amountUsdtWei}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60">
          {isLoading ? "処理中..." : "流動性を追加"}
        </button>
      )}

      {isAddSuccess && <p className="mt-3 text-center text-xs text-green-600">✓ LP 追加完了</p>}
      {isApproveSuccess && <p className="mt-3 text-center text-xs text-green-600">✓ 承認完了</p>}
    </div>
  );
}
