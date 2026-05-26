import { create } from "zustand";
import { persist } from "zustand/middleware";

interface SlippageState {
  slippageBps: number; // basis points (100 = 1%)
  setSlippage: (bps: number) => void;
}

export const useSlippage = create<SlippageState>()(
  persist(
    (set) => ({
      slippageBps: 100, // 1% default
      setSlippage: (bps) => set({ slippageBps: bps }),
    }),
    { name: "xegm-slippage" }
  )
);

/** Apply slippage to an amount. Returns minOut = amount * (1 - slippage). */
export function applySlippage(amount: bigint, slippageBps: number): bigint {
  return (amount * BigInt(10_000 - slippageBps)) / 10_000n;
}

/** Deadline: now + 20 minutes */
export function getDeadline(): bigint {
  return BigInt(Math.floor(Date.now() / 1000) + 1200);
}
