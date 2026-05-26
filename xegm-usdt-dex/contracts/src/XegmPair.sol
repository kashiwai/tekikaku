// SPDX-License-Identifier: MIT
pragma solidity 0.8.20;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

/**
 * @title XegmPair
 * @notice xEGM/USDT liquidity pool — Uniswap V2 single-pair, factory removed.
 *         token0 = xEGM (18 decimals), token1 = USDT (6 decimals)
 */
contract XegmPair is ERC20, ReentrancyGuard {
    using SafeERC20 for IERC20;

    // ── Constants ────────────────────────────────────────────────────────────
    address public constant TOKEN0 = 0x212b505dFE47086f1ED5D497323d8478209fC517; // xEGM
    address public constant TOKEN1 = 0xdAC17F958D2ee523a2206206994597C13D831ec7; // USDT
    uint256 public constant MINIMUM_LIQUIDITY = 1_000;
    // OZ ERC20 v5 rejects mint to address(0); use a standard dead address instead
    address private constant DEAD = 0x000000000000000000000000000000000000dEaD;

    // ── Storage ──────────────────────────────────────────────────────────────
    uint112 private _reserve0; // xEGM
    uint112 private _reserve1; // USDT
    uint32  private _blockTimestampLast;

    // ── Events ───────────────────────────────────────────────────────────────
    event Mint(address indexed sender, uint256 amount0, uint256 amount1);
    event Burn(address indexed sender, uint256 amount0, uint256 amount1, address indexed to);
    event Swap(
        address indexed sender,
        uint256 amount0In,
        uint256 amount1In,
        uint256 amount0Out,
        uint256 amount1Out,
        address indexed to
    );
    event Sync(uint112 reserve0, uint112 reserve1);

    constructor() ERC20("xEGM/USDT LP", "xEGM-LP") {}

    // ── Views ─────────────────────────────────────────────────────────────────
    function getReserves() public view returns (uint112 reserve0, uint112 reserve1, uint32 blockTimestampLast) {
        reserve0 = _reserve0;
        reserve1 = _reserve1;
        blockTimestampLast = _blockTimestampLast;
    }

    // ── Internal helpers ─────────────────────────────────────────────────────

    function _update(uint256 balance0, uint256 balance1) private {
        require(balance0 <= type(uint112).max && balance1 <= type(uint112).max, "XegmPair: OVERFLOW");
        _reserve0 = uint112(balance0);
        _reserve1 = uint112(balance1);
        _blockTimestampLast = uint32(block.timestamp);
        emit Sync(_reserve0, _reserve1);
    }

    function _mintFee() private pure returns (bool) {
        return false; // no protocol fee
    }

    // ── LP operations ─────────────────────────────────────────────────────────

    /// @notice Mint LP tokens. Caller must have transferred token0/token1 to this contract first.
    function mint(address to) external nonReentrant returns (uint256 liquidity) {
        (uint112 reserve0, uint112 reserve1,) = getReserves();
        uint256 balance0 = IERC20(TOKEN0).balanceOf(address(this));
        uint256 balance1 = IERC20(TOKEN1).balanceOf(address(this));
        uint256 amount0 = balance0 - reserve0;
        uint256 amount1 = balance1 - reserve1;

        uint256 _totalSupply = totalSupply();
        if (_totalSupply == 0) {
            liquidity = _sqrt(amount0 * amount1) - MINIMUM_LIQUIDITY;
            _mint(DEAD, MINIMUM_LIQUIDITY); // permanently lock minimum liquidity
        } else {
            liquidity = _min(
                (amount0 * _totalSupply) / reserve0,
                (amount1 * _totalSupply) / reserve1
            );
        }
        require(liquidity > 0, "XegmPair: INSUFFICIENT_LIQUIDITY_MINTED");
        _mint(to, liquidity);

        _update(balance0, balance1);
        emit Mint(msg.sender, amount0, amount1);
    }

    /// @notice Burn LP tokens to receive token0/token1. Caller must have sent LP tokens to this contract first.
    function burn(address to) external nonReentrant returns (uint256 amount0, uint256 amount1) {
        uint256 balance0 = IERC20(TOKEN0).balanceOf(address(this));
        uint256 balance1 = IERC20(TOKEN1).balanceOf(address(this));
        uint256 liquidity = balanceOf(address(this));
        uint256 _totalSupply = totalSupply();

        amount0 = (liquidity * balance0) / _totalSupply;
        amount1 = (liquidity * balance1) / _totalSupply;
        require(amount0 > 0 && amount1 > 0, "XegmPair: INSUFFICIENT_LIQUIDITY_BURNED");

        _burn(address(this), liquidity);
        IERC20(TOKEN0).safeTransfer(to, amount0);
        IERC20(TOKEN1).safeTransfer(to, amount1);

        balance0 = IERC20(TOKEN0).balanceOf(address(this));
        balance1 = IERC20(TOKEN1).balanceOf(address(this));
        _update(balance0, balance1);
        emit Burn(msg.sender, amount0, amount1, to);
    }

    /// @notice Low-level swap. Use Router for user-facing swaps.
    /// @param amount0Out xEGM to receive
    /// @param amount1Out USDT to receive
    /// @param to recipient
    /// @param data if non-empty, calls IUniswapV2Callee(to).uniswapV2Call (flash swap)
    function swap(
        uint256 amount0Out,
        uint256 amount1Out,
        address to,
        bytes calldata data
    ) external nonReentrant {
        require(amount0Out > 0 || amount1Out > 0, "XegmPair: INSUFFICIENT_OUTPUT_AMOUNT");
        (uint112 reserve0, uint112 reserve1,) = getReserves();
        require(amount0Out < reserve0 && amount1Out < reserve1, "XegmPair: INSUFFICIENT_LIQUIDITY");
        require(to != TOKEN0 && to != TOKEN1, "XegmPair: INVALID_TO");

        if (amount0Out > 0) IERC20(TOKEN0).safeTransfer(to, amount0Out);
        if (amount1Out > 0) IERC20(TOKEN1).safeTransfer(to, amount1Out);
        if (data.length > 0) {
            IUniswapV2Callee(to).uniswapV2Call(msg.sender, amount0Out, amount1Out, data);
        }

        uint256 balance0 = IERC20(TOKEN0).balanceOf(address(this));
        uint256 balance1 = IERC20(TOKEN1).balanceOf(address(this));
        uint256 amount0In = balance0 > reserve0 - amount0Out ? balance0 - (reserve0 - amount0Out) : 0;
        uint256 amount1In = balance1 > reserve1 - amount1Out ? balance1 - (reserve1 - amount1Out) : 0;
        require(amount0In > 0 || amount1In > 0, "XegmPair: INSUFFICIENT_INPUT_AMOUNT");

        // k invariant check with 0.3% fee: (b0*1000 - a0In*3) * (b1*1000 - a1In*3) >= r0*r1*1000^2
        uint256 balance0Adjusted = balance0 * 1000 - amount0In * 3;
        uint256 balance1Adjusted = balance1 * 1000 - amount1In * 3;
        require(
            balance0Adjusted * balance1Adjusted >= uint256(reserve0) * uint256(reserve1) * 1_000_000,
            "XegmPair: K"
        );

        _update(balance0, balance1);
        emit Swap(msg.sender, amount0In, amount1In, amount0Out, amount1Out, to);
    }

    /// @notice Force balances to match reserves (collect any excess tokens)
    function skim(address to) external nonReentrant {
        IERC20(TOKEN0).safeTransfer(to, IERC20(TOKEN0).balanceOf(address(this)) - _reserve0);
        IERC20(TOKEN1).safeTransfer(to, IERC20(TOKEN1).balanceOf(address(this)) - _reserve1);
    }

    /// @notice Force reserves to match balances
    function sync() external nonReentrant {
        _update(IERC20(TOKEN0).balanceOf(address(this)), IERC20(TOKEN1).balanceOf(address(this)));
    }

    // ── Math ──────────────────────────────────────────────────────────────────

    function _sqrt(uint256 y) internal pure returns (uint256 z) {
        if (y > 3) {
            z = y;
            uint256 x = y / 2 + 1;
            while (x < z) {
                z = x;
                x = (y / x + x) / 2;
            }
        } else if (y != 0) {
            z = 1;
        }
    }

    function _min(uint256 a, uint256 b) internal pure returns (uint256) {
        return a < b ? a : b;
    }
}

interface IUniswapV2Callee {
    function uniswapV2Call(address sender, uint256 amount0, uint256 amount1, bytes calldata data) external;
}
