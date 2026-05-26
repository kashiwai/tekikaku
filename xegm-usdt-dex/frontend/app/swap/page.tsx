"use client";

import { useState, useCallback } from "react";
import { useAccount, useReadContract, useWriteContract, useWaitForTransactionReceipt } from "wagmi";
import { parseUnits, formatUnits, maxUint256 } from "viem";
import {
  XEGM_ADDRESS, USDT_ADDRESS, ROUTER_ADDRESS,
  XEGM_DECIMALS, USDT_DECIMALS,
  ROUTER_ABI, ERC20_ABI,
} from "@/lib/contracts";
import { useSlippage, applySlippage, getDeadline } from "@/lib/slippage";
import { TokenTag } from "@/components/TokenTag";
import { SlippageSettings } from "@/components/SlippageSettings";

type Direction = "xegmToUsdt" | "usdtToXegm";

export default function SwapPage() {
  const { address, isConnected, chain } = useAccount();
  const { slippageBps } = useSlippage();

  const [direction, setDirection] = useState<Direction>("xegmToUsdt");
  const [amountIn, setAmountIn] = useState("");

  const isXegmIn = direction === "xegmToUsdt";
  const tokenIn  = isXegmIn ? "xEGM" : "USDT";
  const tokenOut = isXegmIn ? "USDT" : "xEGM";
  const decimalsIn  = isXegmIn ? XEGM_DECIMALS : USDT_DECIMALS;
  const decimalsOut = isXegmIn ? USDT_DECIMALS  : XEGM_DECIMALS;
  const tokenInAddress = isXegmIn ? XEGM_ADDRESS : USDT_ADDRESS;

  // Parse input
  let amountInWei: bigint | undefined;
  try {
    if (amountIn && parseFloat(amountIn) > 0) {
      amountInWei = parseUnits(amountIn, decimalsIn);
    }
  } catch {}

  // Get quote
  const { data: amountOutWei } = useReadContract({
    address: ROUTER_ADDRESS || undefined,
    abi: ROUTER_ABI,
    functionName: "getAmountOut",
    args: amountInWei ? [amountInWei, isXegmIn] : undefined,
    query: { enabled: Boolean(amountInWei && ROUTER_ADDRESS) },
  });

  const amountOutFormatted = amountOutWei != null
    ? formatUnits(amountOutWei, decimalsOut)
    : "";

  // Balance
  const { data: balanceIn } = useReadContract({
    address: tokenInAddress,
    abi: ERC20_ABI,
    functionName: "balanceOf",
    args: address ? [address] : undefined,
    query: { enabled: Boolean(address) },
  });

  // Allowance
  const { data: allowance, refetch: refetchAllowance } = useReadContract({
    address: tokenInAddress,
    abi: ERC20_ABI,
    functionName: "allowance",
    args: address && ROUTER_ADDRESS ? [address, ROUTER_ADDRESS] : undefined,
    query: { enabled: Boolean(address && ROUTER_ADDRESS) },
  });

  const needsApprove = amountInWei != null && (allowance ?? 0n) < amountInWei;

  // Write hooks
  const { writeContract: writeApprove, data: approveTxHash, isPending: isApprovePending } = useWriteContract();
  const { writeContract: writeSwap, data: swapTxHash, isPending: isSwapPending } = useWriteContract();

  const { isLoading: isApproveConfirming, isSuccess: isApproveSuccess } = useWaitForTransactionReceipt({ hash: approveTxHash });
  const { isLoading: isSwapConfirming, isSuccess: isSwapSuccess } = useWaitForTransactionReceipt({ hash: swapTxHash });

  // Approve
  const handleApprove = useCallback(() => {
    if (!ROUTER_ADDRESS) return;
    writeApprove({
      address: tokenInAddress,
      abi: ERC20_ABI,
      functionName: "approve",
      args: [ROUTER_ADDRESS, maxUint256],
    });
  }, [writeApprove, tokenInAddress]);

  // Swap
  const handleSwap = useCallback(() => {
    if (!amountInWei || !amountOutWei || !address || !ROUTER_ADDRESS) return;
    const amountOutMin = applySlippage(amountOutWei, slippageBps);
    const deadline = getDeadline();

    if (isXegmIn) {
      writeSwap({
        address: ROUTER_ADDRESS,
        abi: ROUTER_ABI,
        functionName: "swapExactXegmForUsdt",
        args: [amountInWei, amountOutMin, address, deadline],
      });
    } else {
      writeSwap({
        address: ROUTER_ADDRESS,
        abi: ROUTER_ABI,
        functionName: "swapExactUsdtForXegm",
        args: [amountInWei, amountOutMin, address, deadline],
      });
    }
  }, [amountInWei, amountOutWei, address, isXegmIn, slippageBps, writeSwap]);

  const isWrongNetwork = isConnected && chain?.id !== 1;
  const insufficientBalance = amountInWei != null && balanceIn != null && amountInWei > balanceIn;
  const isDeployed = Boolean(ROUTER_ADDRESS);
  const isLoading = isApprovePending || isApproveConfirming || isSwapPending || isSwapConfirming;

  return (
    <div className="bg-white rounded-[6px] border border-[#E5E8EC] p-5 shadow-sm">
      {/* Title row */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-sm font-semibold text-gray-900">スワップ</h1>
        <SlippageSettings />
      </div>

      {/* Input */}
      <div className="bg-[#F5F7FA] rounded-[6px] p-3 mb-1">
        <div className="flex items-center justify-between mb-1">
          <span className="text-xs text-gray-500">支払う</span>
          {balanceIn != null && (
            <button
              onClick={() => setAmountIn(formatUnits(balanceIn, decimalsIn))}
              className="text-xs text-[#2563EB] hover:underline"
            >
              残高: {parseFloat(formatUnits(balanceIn, decimalsIn)).toLocaleString("ja-JP", { maximumFractionDigits: 6 })}
            </button>
          )}
        </div>
        <div className="flex items-center gap-2">
          <input
            type="number"
            inputMode="decimal"
            placeholder="0.0"
            value={amountIn}
            onChange={(e) => setAmountIn(e.target.value)}
            className="flex-1 bg-transparent text-xl font-semibold tabular-nums text-gray-900 placeholder-gray-300 focus:outline-none"
          />
          <TokenTag token={tokenIn as "xEGM" | "USDT"} />
        </div>
      </div>

      {/* Flip */}
      <div className="flex justify-center my-1">
        <button
          onClick={() => { setDirection(isXegmIn ? "usdtToXegm" : "xegmToUsdt"); setAmountIn(""); }}
          className="w-7 h-7 rounded-full bg-white border border-[#E5E8EC] flex items-center justify-center hover:bg-gray-50 transition-colors"
        >
          <svg className="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
          </svg>
        </button>
      </div>

      {/* Output */}
      <div className="bg-[#F5F7FA] rounded-[6px] p-3 mb-4">
        <div className="flex items-center justify-between mb-1">
          <span className="text-xs text-gray-500">受け取る</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="flex-1 text-xl font-semibold tabular-nums text-gray-900">
            {amountOutFormatted
              ? parseFloat(amountOutFormatted).toLocaleString("ja-JP", { maximumFractionDigits: 8 })
              : <span className="text-gray-300">0.0</span>
            }
          </span>
          <TokenTag token={tokenOut as "xEGM" | "USDT"} />
        </div>
      </div>

      {/* Action */}
      {!isConnected ? (
        <p className="text-center text-sm text-gray-500">MetaMask を接続してください</p>
      ) : isWrongNetwork ? (
        <p className="text-center text-sm text-red-500 font-medium">Mainnet に切り替えてください</p>
      ) : !isDeployed ? (
        <p className="text-center text-sm text-gray-400">コントラクトアドレスを contracts.ts に設定してください</p>
      ) : insufficientBalance ? (
        <button disabled className="w-full py-3 text-sm font-medium bg-gray-100 text-gray-400 rounded-[6px]">
          残高不足
        </button>
      ) : needsApprove ? (
        <button
          onClick={handleApprove}
          disabled={isLoading}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60"
        >
          {isLoading ? "処理中..." : `${tokenIn} を承認`}
        </button>
      ) : (
        <button
          onClick={handleSwap}
          disabled={isLoading || !amountInWei || !amountOutWei}
          className="w-full py-3 text-sm font-medium bg-[#2563EB] text-white rounded-[6px] hover:bg-[#1D4ED8] transition-colors disabled:opacity-60"
        >
          {isLoading ? "処理中..." : "スワップ"}
        </button>
      )}

      {/* Success */}
      {isSwapSuccess && (
        <p className="mt-3 text-center text-xs text-green-600">✓ スワップ完了</p>
      )}
      {isApproveSuccess && (
        <p className="mt-3 text-center text-xs text-green-600">✓ 承認完了</p>
      )}

      {/* Rate info */}
      {amountInWei && amountOutWei && amountOutWei > 0n && (
        <div className="mt-3 text-xs text-gray-500 text-center tabular-nums">
          1 {tokenIn} ≈ {(parseFloat(formatUnits(amountOutWei, decimalsOut)) / parseFloat(amountIn)).toLocaleString("ja-JP", { maximumFractionDigits: 8 })} {tokenOut}
        </div>
      )}
    </div>
  );
}
