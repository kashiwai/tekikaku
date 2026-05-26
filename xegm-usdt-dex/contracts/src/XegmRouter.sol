// SPDX-License-Identifier: MIT
pragma solidity 0.8.20;

import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "./XegmPair.sol";

/**
 * @title XegmRouter
 * @notice User-facing entry point for swaps and liquidity operations on XegmPair.
 *         Provides slippage and deadline protection.
 */
contract XegmRouter {
    using SafeERC20 for IERC20;

    address public immutable PAIR;
    address public constant XEGM = 0x212b505dFE47086f1ED5D497323d8478209fC517;
    address public constant USDT = 0xdAC17F958D2ee523a2206206994597C13D831ec7;

    constructor(address pair) {
        PAIR = pair;
    }

    modifier ensure(uint256 deadline) {
        require(deadline >= block.timestamp, "XegmRouter: EXPIRED");
        _;
    }

    // ── View helpers ─────────────────────────────────────────────────────────

    /**
     * @notice Compute output amount for a given input amount and direction.
     * @param amountIn  Input token amount
     * @param xegmIn    true = xEGM in / USDT out; false = USDT in / xEGM out
     */
    function getAmountOut(uint256 amountIn, bool xegmIn) public view returns (uint256 amountOut) {
        (uint112 r0, uint112 r1,) = XegmPair(PAIR).getReserves();
        uint256 reserveIn  = xegmIn ? r0 : r1;
        uint256 reserveOut = xegmIn ? r1 : r0;
        require(amountIn > 0, "XegmRouter: INSUFFICIENT_INPUT_AMOUNT");
        require(reserveIn > 0 && reserveOut > 0, "XegmRouter: INSUFFICIENT_LIQUIDITY");
        uint256 amountInWithFee = amountIn * 997;
        amountOut = (amountInWithFee * reserveOut) / (reserveIn * 1000 + amountInWithFee);
    }

    /**
     * @notice Compute required input amount to receive an exact output.
     * @param amountOut Output token amount desired
     * @param xegmIn    true = xEGM in / USDT out; false = USDT in / xEGM out
     */
    function getAmountIn(uint256 amountOut, bool xegmIn) public view returns (uint256 amountIn) {
        (uint112 r0, uint112 r1,) = XegmPair(PAIR).getReserves();
        uint256 reserveIn  = xegmIn ? r0 : r1;
        uint256 reserveOut = xegmIn ? r1 : r0;
        require(amountOut > 0, "XegmRouter: INSUFFICIENT_OUTPUT_AMOUNT");
        require(reserveOut > amountOut, "XegmRouter: INSUFFICIENT_LIQUIDITY");
        amountIn = (reserveIn * amountOut * 1000) / ((reserveOut - amountOut) * 997) + 1;
    }

    /**
     * @notice Given amountA of one token, returns the proportional amountB.
     * @param amountA   Input token amount
     * @param aIsXegm   true = amountA is xEGM; false = amountA is USDT
     */
    function quote(uint256 amountA, bool aIsXegm) public view returns (uint256 amountB) {
        (uint112 r0, uint112 r1,) = XegmPair(PAIR).getReserves();
        uint256 reserveA = aIsXegm ? r0 : r1;
        uint256 reserveB = aIsXegm ? r1 : r0;
        require(amountA > 0, "XegmRouter: INSUFFICIENT_AMOUNT");
        require(reserveA > 0 && reserveB > 0, "XegmRouter: INSUFFICIENT_LIQUIDITY");
        amountB = (amountA * reserveB) / reserveA;
    }

    // ── Swap ─────────────────────────────────────────────────────────────────

    /// @notice Swap exact xEGM for USDT
    function swapExactXegmForUsdt(
        uint256 amountIn,
        uint256 amountOutMin,
        address to,
        uint256 deadline
    ) external ensure(deadline) returns (uint256 amountOut) {
        amountOut = getAmountOut(amountIn, true);
        require(amountOut >= amountOutMin, "XegmRouter: INSUFFICIENT_OUTPUT_AMOUNT");
        IERC20(XEGM).safeTransferFrom(msg.sender, PAIR, amountIn);
        XegmPair(PAIR).swap(0, amountOut, to, new bytes(0));
    }

    /// @notice Swap xEGM to receive exact USDT
    function swapXegmForExactUsdt(
        uint256 amountOut,
        uint256 amountInMax,
        address to,
        uint256 deadline
    ) external ensure(deadline) returns (uint256 amountIn) {
        amountIn = getAmountIn(amountOut, true);
        require(amountIn <= amountInMax, "XegmRouter: EXCESSIVE_INPUT_AMOUNT");
        IERC20(XEGM).safeTransferFrom(msg.sender, PAIR, amountIn);
        XegmPair(PAIR).swap(0, amountOut, to, new bytes(0));
    }

    /// @notice Swap exact USDT for xEGM
    function swapExactUsdtForXegm(
        uint256 amountIn,
        uint256 amountOutMin,
        address to,
        uint256 deadline
    ) external ensure(deadline) returns (uint256 amountOut) {
        amountOut = getAmountOut(amountIn, false);
        require(amountOut >= amountOutMin, "XegmRouter: INSUFFICIENT_OUTPUT_AMOUNT");
        IERC20(USDT).safeTransferFrom(msg.sender, PAIR, amountIn);
        XegmPair(PAIR).swap(amountOut, 0, to, new bytes(0));
    }

    /// @notice Swap USDT to receive exact xEGM
    function swapUsdtForExactXegm(
        uint256 amountOut,
        uint256 amountInMax,
        address to,
        uint256 deadline
    ) external ensure(deadline) returns (uint256 amountIn) {
        amountIn = getAmountIn(amountOut, false);
        require(amountIn <= amountInMax, "XegmRouter: EXCESSIVE_INPUT_AMOUNT");
        IERC20(USDT).safeTransferFrom(msg.sender, PAIR, amountIn);
        XegmPair(PAIR).swap(amountOut, 0, to, new bytes(0));
    }

    // ── Liquidity ─────────────────────────────────────────────────────────────

    /**
     * @notice Add liquidity to the pool.
     * @return amountXegm   Actual xEGM deposited
     * @return amountUsdt   Actual USDT deposited
     * @return liquidity    LP tokens minted
     */
    function addLiquidity(
        uint256 amountXegmDesired,
        uint256 amountUsdtDesired,
        uint256 amountXegmMin,
        uint256 amountUsdtMin,
        address to,
        uint256 deadline
    ) external ensure(deadline) returns (uint256 amountXegm, uint256 amountUsdt, uint256 liquidity) {
        (amountXegm, amountUsdt) = _computeLiquidityAmounts(
            amountXegmDesired, amountUsdtDesired, amountXegmMin, amountUsdtMin
        );
        IERC20(XEGM).safeTransferFrom(msg.sender, PAIR, amountXegm);
        IERC20(USDT).safeTransferFrom(msg.sender, PAIR, amountUsdt);
        liquidity = XegmPair(PAIR).mint(to);
    }

    /**
     * @notice Remove liquidity from the pool.
     * @return amountXegm   xEGM received
     * @return amountUsdt   USDT received
     */
    function removeLiquidity(
        uint256 liquidity,
        uint256 amountXegmMin,
        uint256 amountUsdtMin,
        address to,
        uint256 deadline
    ) external ensure(deadline) returns (uint256 amountXegm, uint256 amountUsdt) {
        IERC20(PAIR).safeTransferFrom(msg.sender, PAIR, liquidity);
        (amountXegm, amountUsdt) = XegmPair(PAIR).burn(to);
        require(amountXegm >= amountXegmMin, "XegmRouter: INSUFFICIENT_XEGM_AMOUNT");
        require(amountUsdt >= amountUsdtMin, "XegmRouter: INSUFFICIENT_USDT_AMOUNT");
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    function _computeLiquidityAmounts(
        uint256 amountXegmDesired,
        uint256 amountUsdtDesired,
        uint256 amountXegmMin,
        uint256 amountUsdtMin
    ) internal view returns (uint256 amountXegm, uint256 amountUsdt) {
        (uint112 reserve0, uint112 reserve1,) = XegmPair(PAIR).getReserves();
        if (reserve0 == 0 && reserve1 == 0) {
            // First liquidity provider sets the price
            return (amountXegmDesired, amountUsdtDesired);
        }
        uint256 amountUsdtOptimal = quote(amountXegmDesired, true);
        if (amountUsdtOptimal <= amountUsdtDesired) {
            require(amountUsdtOptimal >= amountUsdtMin, "XegmRouter: INSUFFICIENT_USDT_AMOUNT");
            return (amountXegmDesired, amountUsdtOptimal);
        }
        uint256 amountXegmOptimal = quote(amountUsdtDesired, false);
        require(amountXegmOptimal <= amountXegmDesired, "XegmRouter: EXCESSIVE_XEGM_AMOUNT");
        require(amountXegmOptimal >= amountXegmMin, "XegmRouter: INSUFFICIENT_XEGM_AMOUNT");
        return (amountXegmOptimal, amountUsdtDesired);
    }
}
