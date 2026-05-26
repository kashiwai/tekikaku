"use client";

import { useState } from "react";
import { useSlippage } from "@/lib/slippage";

const PRESETS = [
  { label: "0.1%", bps: 10 },
  { label: "0.5%", bps: 50 },
  { label: "1%",   bps: 100 },
];

export function SlippageSettings() {
  const { slippageBps, setSlippage } = useSlippage();
  const [open, setOpen] = useState(false);
  const [custom, setCustom] = useState("");

  const currentLabel =
    PRESETS.find((p) => p.bps === slippageBps)?.label ??
    `${(slippageBps / 100).toFixed(2)}%`;

  return (
    <div className="relative">
      <button
        onClick={() => setOpen((v) => !v)}
        className="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 transition-colors"
      >
        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
        </svg>
        スリッページ {currentLabel}
      </button>

      {open && (
        <div className="absolute right-0 top-6 z-10 bg-white border border-[#E5E8EC] rounded-lg shadow-lg p-3 w-52">
          <p className="text-xs text-gray-500 mb-2">スリッページ許容値</p>
          <div className="flex gap-1 mb-2">
            {PRESETS.map((p) => (
              <button
                key={p.bps}
                onClick={() => { setSlippage(p.bps); setCustom(""); }}
                className={`flex-1 py-1 text-xs rounded border transition-colors ${
                  slippageBps === p.bps
                    ? "bg-[#2563EB] text-white border-[#2563EB]"
                    : "text-gray-600 border-gray-200 hover:border-[#2563EB]"
                }`}
              >
                {p.label}
              </button>
            ))}
          </div>
          <div className="flex items-center gap-1">
            <input
              type="number"
              min="0.01"
              max="50"
              step="0.01"
              placeholder="カスタム"
              value={custom}
              onChange={(e) => {
                setCustom(e.target.value);
                const v = parseFloat(e.target.value);
                if (!isNaN(v) && v > 0 && v <= 50) setSlippage(Math.round(v * 100));
              }}
              className="flex-1 border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-[#2563EB]"
            />
            <span className="text-xs text-gray-500">%</span>
          </div>
        </div>
      )}
    </div>
  );
}
