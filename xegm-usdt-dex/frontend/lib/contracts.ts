import { type Address } from "viem";

// xEGM and USDT — mainnet addresses (immutable)
export const XEGM_ADDRESS = "0x212b505dFE47086f1ED5D497323d8478209fC517" as Address;
export const USDT_ADDRESS  = "0xdAC17F958D2ee523a2206206994597C13D831ec7" as Address;

// TODO: fill in after deployment
export const PAIR_ADDRESS   = "" as Address;
export const ROUTER_ADDRESS = "" as Address;

export const XEGM_DECIMALS = 18;
export const USDT_DECIMALS  = 6;

// Minimal ABIs — only the functions we call from the frontend
export const PAIR_ABI = [
  {
    name: "getReserves",
    type: "function",
    stateMutability: "view",
    inputs: [],
    outputs: [
      { name: "reserve0", type: "uint112" },
      { name: "reserve1", type: "uint112" },
      { name: "blockTimestampLast", type: "uint32" },
    ],
  },
  {
    name: "totalSupply",
    type: "function",
    stateMutability: "view",
    inputs: [],
    outputs: [{ name: "", type: "uint256" }],
  },
  {
    name: "balanceOf",
    type: "function",
    stateMutability: "view",
    inputs: [{ name: "account", type: "address" }],
    outputs: [{ name: "", type: "uint256" }],
  },
  {
    name: "approve",
    type: "function",
    stateMutability: "nonpayable",
    inputs: [
      { name: "spender", type: "address" },
      { name: "amount", type: "uint256" },
    ],
    outputs: [{ name: "", type: "bool" }],
  },
] as const;

export const ROUTER_ABI = [
  {
    name: "getAmountOut",
    type: "function",
    stateMutability: "view",
    inputs: [
      { name: "amountIn", type: "uint256" },
      { name: "xegmIn", type: "bool" },
    ],
    outputs: [{ name: "amountOut", type: "uint256" }],
  },
  {
    name: "quote",
    type: "function",
    stateMutability: "view",
    inputs: [
      { name: "amountA", type: "uint256" },
      { name: "aIsXegm", type: "bool" },
    ],
    outputs: [{ name: "amountB", type: "uint256" }],
  },
  {
    name: "swapExactXegmForUsdt",
    type: "function",
    stateMutability: "nonpayable",
    inputs: [
      { name: "amountIn", type: "uint256" },
      { name: "amountOutMin", type: "uint256" },
      { name: "to", type: "address" },
      { name: "deadline", type: "uint256" },
    ],
    outputs: [{ name: "amountOut", type: "uint256" }],
  },
  {
    name: "swapExactUsdtForXegm",
    type: "function",
    stateMutability: "nonpayable",
    inputs: [
      { name: "amountIn", type: "uint256" },
      { name: "amountOutMin", type: "uint256" },
      { name: "to", type: "address" },
      { name: "deadline", type: "uint256" },
    ],
    outputs: [{ name: "amountOut", type: "uint256" }],
  },
  {
    name: "addLiquidity",
    type: "function",
    stateMutability: "nonpayable",
    inputs: [
      { name: "amountXegmDesired", type: "uint256" },
      { name: "amountUsdtDesired", type: "uint256" },
      { name: "amountXegmMin", type: "uint256" },
      { name: "amountUsdtMin", type: "uint256" },
      { name: "to", type: "address" },
      { name: "deadline", type: "uint256" },
    ],
    outputs: [
      { name: "amountXegm", type: "uint256" },
      { name: "amountUsdt", type: "uint256" },
      { name: "liquidity", type: "uint256" },
    ],
  },
  {
    name: "removeLiquidity",
    type: "function",
    stateMutability: "nonpayable",
    inputs: [
      { name: "liquidity", type: "uint256" },
      { name: "amountXegmMin", type: "uint256" },
      { name: "amountUsdtMin", type: "uint256" },
      { name: "to", type: "address" },
      { name: "deadline", type: "uint256" },
    ],
    outputs: [
      { name: "amountXegm", type: "uint256" },
      { name: "amountUsdt", type: "uint256" },
    ],
  },
] as const;

export const ERC20_ABI = [
  {
    name: "allowance",
    type: "function",
    stateMutability: "view",
    inputs: [
      { name: "owner", type: "address" },
      { name: "spender", type: "address" },
    ],
    outputs: [{ name: "", type: "uint256" }],
  },
  {
    name: "balanceOf",
    type: "function",
    stateMutability: "view",
    inputs: [{ name: "account", type: "address" }],
    outputs: [{ name: "", type: "uint256" }],
  },
  {
    name: "approve",
    type: "function",
    stateMutability: "nonpayable",
    inputs: [
      { name: "spender", type: "address" },
      { name: "amount", type: "uint256" },
    ],
    outputs: [{ name: "", type: "bool" }],
  },
] as const;
