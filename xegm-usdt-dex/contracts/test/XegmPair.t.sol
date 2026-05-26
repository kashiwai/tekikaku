// SPDX-License-Identifier: MIT
pragma solidity 0.8.20;

import "forge-std/Test.sol";
import "../src/XegmPair.sol";
import "../src/XegmRouter.sol";

// Minimal ERC20 mock that mimics USDT (no return value from transfer)
contract MockUSDT {
    string public name = "Mock USDT";
    string public symbol = "USDT";
    uint8 public decimals = 6;
    uint256 public totalSupply;
    mapping(address => uint256) public balanceOf;
    mapping(address => mapping(address => uint256)) public allowance;

    function mint(address to, uint256 amount) external {
        balanceOf[to] += amount;
        totalSupply += amount;
    }

    function approve(address spender, uint256 amount) external returns (bool) {
        allowance[msg.sender][spender] = amount;
        return true;
    }

    // Intentionally no return value — USDT compatibility
    function transfer(address to, uint256 amount) external {
        require(balanceOf[msg.sender] >= amount, "USDT: insufficient");
        balanceOf[msg.sender] -= amount;
        balanceOf[to] += amount;
    }

    function transferFrom(address from, address to, uint256 amount) external {
        require(allowance[from][msg.sender] >= amount, "USDT: insufficient allowance");
        require(balanceOf[from] >= amount, "USDT: insufficient balance");
        allowance[from][msg.sender] -= amount;
        balanceOf[from] -= amount;
        balanceOf[to] += amount;
    }
}

contract MockXEGM is ERC20 {
    constructor() ERC20("Mock xEGM", "xEGM") {}
    function mint(address to, uint256 amount) external {
        _mint(to, amount);
    }
}

// Pair subclass that swaps hardcoded addresses for test mocks
contract TestXegmPair is ERC20, ReentrancyGuard {
    using SafeERC20 for IERC20;

    address public immutable TOKEN0;
    address public immutable TOKEN1;
    uint256 public constant MINIMUM_LIQUIDITY = 1_000;
    address private constant DEAD = 0x000000000000000000000000000000000000dEaD;

    uint112 private _reserve0;
    uint112 private _reserve1;
    uint32  private _blockTimestampLast;

    event Mint(address indexed sender, uint256 amount0, uint256 amount1);
    event Burn(address indexed sender, uint256 amount0, uint256 amount1, address indexed to);
    event Swap(address indexed sender, uint256 amount0In, uint256 amount1In, uint256 amount0Out, uint256 amount1Out, address indexed to);
    event Sync(uint112 reserve0, uint112 reserve1);

    constructor(address token0, address token1) ERC20("Test xEGM/USDT LP", "xEGM-LP") {
        TOKEN0 = token0;
        TOKEN1 = token1;
    }

    function getReserves() public view returns (uint112 reserve0, uint112 reserve1, uint32 blockTimestampLast) {
        reserve0 = _reserve0;
        reserve1 = _reserve1;
        blockTimestampLast = _blockTimestampLast;
    }

    function _update(uint256 balance0, uint256 balance1) private {
        require(balance0 <= type(uint112).max && balance1 <= type(uint112).max, "OVERFLOW");
        _reserve0 = uint112(balance0);
        _reserve1 = uint112(balance1);
        _blockTimestampLast = uint32(block.timestamp);
        emit Sync(_reserve0, _reserve1);
    }

    function mint(address to) external nonReentrant returns (uint256 liquidity) {
        (uint112 reserve0, uint112 reserve1,) = getReserves();
        uint256 balance0 = IERC20(TOKEN0).balanceOf(address(this));
        uint256 balance1 = IERC20(TOKEN1).balanceOf(address(this));
        uint256 amount0 = balance0 - reserve0;
        uint256 amount1 = balance1 - reserve1;
        uint256 _totalSupply = totalSupply();
        if (_totalSupply == 0) {
            liquidity = _sqrt(amount0 * amount1) - MINIMUM_LIQUIDITY;
            _mint(DEAD, MINIMUM_LIQUIDITY);
        } else {
            liquidity = _min((amount0 * _totalSupply) / reserve0, (amount1 * _totalSupply) / reserve1);
        }
        require(liquidity > 0, "INSUFFICIENT_LIQUIDITY_MINTED");
        _mint(to, liquidity);
        _update(balance0, balance1);
        emit Mint(msg.sender, amount0, amount1);
    }

    function burn(address to) external nonReentrant returns (uint256 amount0, uint256 amount1) {
        uint256 balance0 = IERC20(TOKEN0).balanceOf(address(this));
        uint256 balance1 = IERC20(TOKEN1).balanceOf(address(this));
        uint256 liquidity = balanceOf(address(this));
        uint256 _totalSupply = totalSupply();
        amount0 = (liquidity * balance0) / _totalSupply;
        amount1 = (liquidity * balance1) / _totalSupply;
        require(amount0 > 0 && amount1 > 0, "INSUFFICIENT_LIQUIDITY_BURNED");
        _burn(address(this), liquidity);
        IERC20(TOKEN0).safeTransfer(to, amount0);
        IERC20(TOKEN1).safeTransfer(to, amount1);
        balance0 = IERC20(TOKEN0).balanceOf(address(this));
        balance1 = IERC20(TOKEN1).balanceOf(address(this));
        _update(balance0, balance1);
        emit Burn(msg.sender, amount0, amount1, to);
    }

    function swap(uint256 amount0Out, uint256 amount1Out, address to, bytes calldata data) external nonReentrant {
        require(amount0Out > 0 || amount1Out > 0, "INSUFFICIENT_OUTPUT_AMOUNT");
        (uint112 reserve0, uint112 reserve1,) = getReserves();
        require(amount0Out < reserve0 && amount1Out < reserve1, "INSUFFICIENT_LIQUIDITY");
        require(to != TOKEN0 && to != TOKEN1, "INVALID_TO");
        if (amount0Out > 0) IERC20(TOKEN0).safeTransfer(to, amount0Out);
        if (amount1Out > 0) IERC20(TOKEN1).safeTransfer(to, amount1Out);
        if (data.length > 0) IUniswapV2Callee(to).uniswapV2Call(msg.sender, amount0Out, amount1Out, data);
        uint256 balance0 = IERC20(TOKEN0).balanceOf(address(this));
        uint256 balance1 = IERC20(TOKEN1).balanceOf(address(this));
        uint256 amount0In = balance0 > reserve0 - amount0Out ? balance0 - (reserve0 - amount0Out) : 0;
        uint256 amount1In = balance1 > reserve1 - amount1Out ? balance1 - (reserve1 - amount1Out) : 0;
        require(amount0In > 0 || amount1In > 0, "INSUFFICIENT_INPUT_AMOUNT");
        uint256 b0Adj = balance0 * 1000 - amount0In * 3;
        uint256 b1Adj = balance1 * 1000 - amount1In * 3;
        require(b0Adj * b1Adj >= uint256(reserve0) * uint256(reserve1) * 1_000_000, "K");
        _update(balance0, balance1);
        emit Swap(msg.sender, amount0In, amount1In, amount0Out, amount1Out, to);
    }

    function sync() external nonReentrant {
        _update(IERC20(TOKEN0).balanceOf(address(this)), IERC20(TOKEN1).balanceOf(address(this)));
    }

    function _sqrt(uint256 y) internal pure returns (uint256 z) {
        if (y > 3) { z = y; uint256 x = y / 2 + 1; while (x < z) { z = x; x = (y / x + x) / 2; } }
        else if (y != 0) { z = 1; }
    }
    function _min(uint256 a, uint256 b) internal pure returns (uint256) { return a < b ? a : b; }
}

// Router subclass for testing
contract TestXegmRouter {
    using SafeERC20 for IERC20;

    address public immutable PAIR;
    address public immutable XEGM;
    address public immutable USDT;

    constructor(address pair, address xegm, address usdt) {
        PAIR = pair;
        XEGM = xegm;
        USDT = usdt;
    }

    modifier ensure(uint256 deadline) {
        require(deadline >= block.timestamp, "EXPIRED");
        _;
    }

    function getAmountOut(uint256 amountIn, bool xegmIn) public view returns (uint256 amountOut) {
        (uint112 r0, uint112 r1,) = TestXegmPair(PAIR).getReserves();
        uint256 reserveIn  = xegmIn ? r0 : r1;
        uint256 reserveOut = xegmIn ? r1 : r0;
        require(amountIn > 0, "INSUFFICIENT_INPUT");
        require(reserveIn > 0 && reserveOut > 0, "INSUFFICIENT_LIQUIDITY");
        uint256 amountInWithFee = amountIn * 997;
        amountOut = (amountInWithFee * reserveOut) / (reserveIn * 1000 + amountInWithFee);
    }

    function getAmountIn(uint256 amountOut, bool xegmIn) public view returns (uint256 amountIn) {
        (uint112 r0, uint112 r1,) = TestXegmPair(PAIR).getReserves();
        uint256 reserveIn  = xegmIn ? r0 : r1;
        uint256 reserveOut = xegmIn ? r1 : r0;
        require(amountOut > 0, "INSUFFICIENT_OUTPUT");
        require(reserveOut > amountOut, "INSUFFICIENT_LIQUIDITY");
        amountIn = (reserveIn * amountOut * 1000) / ((reserveOut - amountOut) * 997) + 1;
    }

    function quote(uint256 amountA, bool aIsXegm) public view returns (uint256) {
        (uint112 r0, uint112 r1,) = TestXegmPair(PAIR).getReserves();
        uint256 reserveA = aIsXegm ? r0 : r1;
        uint256 reserveB = aIsXegm ? r1 : r0;
        require(amountA > 0, "INSUFFICIENT_AMOUNT");
        require(reserveA > 0 && reserveB > 0, "INSUFFICIENT_LIQUIDITY");
        return (amountA * reserveB) / reserveA;
    }

    function swapExactXegmForUsdt(uint256 amountIn, uint256 amountOutMin, address to, uint256 deadline)
        external ensure(deadline) returns (uint256 amountOut) {
        amountOut = getAmountOut(amountIn, true);
        require(amountOut >= amountOutMin, "INSUFFICIENT_OUTPUT");
        IERC20(XEGM).safeTransferFrom(msg.sender, PAIR, amountIn);
        TestXegmPair(PAIR).swap(0, amountOut, to, new bytes(0));
    }

    function swapExactUsdtForXegm(uint256 amountIn, uint256 amountOutMin, address to, uint256 deadline)
        external ensure(deadline) returns (uint256 amountOut) {
        amountOut = getAmountOut(amountIn, false);
        require(amountOut >= amountOutMin, "INSUFFICIENT_OUTPUT");
        IERC20(USDT).safeTransferFrom(msg.sender, PAIR, amountIn);
        TestXegmPair(PAIR).swap(amountOut, 0, to, new bytes(0));
    }

    function addLiquidity(
        uint256 amountXegmDesired, uint256 amountUsdtDesired,
        uint256 amountXegmMin, uint256 amountUsdtMin,
        address to, uint256 deadline
    ) external ensure(deadline) returns (uint256 amountXegm, uint256 amountUsdt, uint256 liquidity) {
        (uint112 r0, uint112 r1,) = TestXegmPair(PAIR).getReserves();
        if (r0 == 0 && r1 == 0) {
            (amountXegm, amountUsdt) = (amountXegmDesired, amountUsdtDesired);
        } else {
            uint256 usdtOpt = quote(amountXegmDesired, true);
            if (usdtOpt <= amountUsdtDesired) {
                require(usdtOpt >= amountUsdtMin, "INSUFFICIENT_USDT");
                (amountXegm, amountUsdt) = (amountXegmDesired, usdtOpt);
            } else {
                uint256 xegmOpt = quote(amountUsdtDesired, false);
                require(xegmOpt <= amountXegmDesired, "EXCESSIVE_XEGM");
                require(xegmOpt >= amountXegmMin, "INSUFFICIENT_XEGM");
                (amountXegm, amountUsdt) = (xegmOpt, amountUsdtDesired);
            }
        }
        IERC20(XEGM).safeTransferFrom(msg.sender, PAIR, amountXegm);
        IERC20(USDT).safeTransferFrom(msg.sender, PAIR, amountUsdt);
        liquidity = TestXegmPair(PAIR).mint(to);
    }

    function removeLiquidity(
        uint256 liquidity, uint256 amountXegmMin, uint256 amountUsdtMin,
        address to, uint256 deadline
    ) external ensure(deadline) returns (uint256 amountXegm, uint256 amountUsdt) {
        IERC20(PAIR).safeTransferFrom(msg.sender, PAIR, liquidity);
        (amountXegm, amountUsdt) = TestXegmPair(PAIR).burn(to);
        require(amountXegm >= amountXegmMin, "INSUFFICIENT_XEGM");
        require(amountUsdt >= amountUsdtMin, "INSUFFICIENT_USDT");
    }
}

contract XegmPairTest is Test {
    MockXEGM xegm;
    MockUSDT usdt;
    TestXegmPair pair;
    TestXegmRouter router;

    address alice = makeAddr("alice");
    address bob   = makeAddr("bob");

    uint256 constant XEGM_INIT = 1_000_000 * 1e18; // 1M xEGM
    uint256 constant USDT_INIT = 100 * 1e6;         // 100 USDT → 0.0001 USDT/xEGM

    function setUp() public {
        xegm = new MockXEGM();
        usdt = new MockUSDT();
        pair = new TestXegmPair(address(xegm), address(usdt));
        router = new TestXegmRouter(address(pair), address(xegm), address(usdt));

        xegm.mint(alice, XEGM_INIT * 10);
        usdt.mint(alice, USDT_INIT * 100);
        xegm.mint(bob, XEGM_INIT);
        usdt.mint(bob, USDT_INIT * 10);

        vm.startPrank(alice);
        xegm.approve(address(router), type(uint256).max);
        IERC20(address(usdt)).approve(address(router), type(uint256).max);
        vm.stopPrank();

        vm.startPrank(bob);
        xegm.approve(address(router), type(uint256).max);
        IERC20(address(usdt)).approve(address(router), type(uint256).max);
        vm.stopPrank();
    }

    function _seed() internal {
        vm.prank(alice);
        router.addLiquidity(XEGM_INIT, USDT_INIT, 0, 0, alice, block.timestamp + 3600);
    }

    // ── Liquidity ─────────────────────────────────────────────────────────────

    function test_InitialLiquidity() public {
        _seed();
        (uint112 r0, uint112 r1,) = pair.getReserves();
        assertEq(r0, XEGM_INIT);
        assertEq(r1, USDT_INIT);
        // 1000 minimum liquidity locked at dead address
        assertEq(pair.balanceOf(0x000000000000000000000000000000000000dEaD), 1000);
        assertTrue(pair.balanceOf(alice) > 0);
    }

    function test_MintAfterInitial() public {
        _seed();
        uint256 lpBefore = pair.balanceOf(alice);

        vm.prank(alice);
        router.addLiquidity(XEGM_INIT, USDT_INIT, 0, 0, alice, block.timestamp + 3600);

        uint256 lpAfter = pair.balanceOf(alice);
        assertTrue(lpAfter > lpBefore);
        (uint112 r0, uint112 r1,) = pair.getReserves();
        assertEq(r0, XEGM_INIT * 2);
        assertEq(r1, USDT_INIT * 2);
    }

    function test_BurnLiquidity() public {
        _seed();
        uint256 lp = pair.balanceOf(alice);
        IERC20(address(pair)).approve(address(router), type(uint256).max);

        vm.startPrank(alice);
        IERC20(address(pair)).approve(address(router), type(uint256).max);
        (uint256 a0, uint256 a1) = router.removeLiquidity(lp, 0, 0, alice, block.timestamp + 3600);
        vm.stopPrank();

        assertTrue(a0 > 0 && a1 > 0);
        assertEq(pair.balanceOf(alice), 0);
    }

    // ── Swaps ─────────────────────────────────────────────────────────────────

    function test_SwapXegmToUsdt() public {
        _seed();
        uint256 amountIn = 10_000 * 1e18; // 10k xEGM
        uint256 expectedOut = router.getAmountOut(amountIn, true);
        uint256 usdtBefore = IERC20(address(usdt)).balanceOf(bob);

        vm.prank(bob);
        uint256 actualOut = router.swapExactXegmForUsdt(amountIn, expectedOut, bob, block.timestamp + 3600);

        assertEq(actualOut, expectedOut);
        assertEq(IERC20(address(usdt)).balanceOf(bob), usdtBefore + expectedOut);
    }

    function test_SwapUsdtToXegm() public {
        _seed();
        uint256 amountIn = 1 * 1e6; // 1 USDT
        uint256 expectedOut = router.getAmountOut(amountIn, false);
        uint256 xegmBefore = xegm.balanceOf(bob);

        vm.prank(bob);
        uint256 actualOut = router.swapExactUsdtForXegm(amountIn, expectedOut, bob, block.timestamp + 3600);

        assertEq(actualOut, expectedOut);
        assertEq(xegm.balanceOf(bob), xegmBefore + expectedOut);
    }

    function test_DecimalsAsymmetry() public {
        // Pool: 1M xEGM (1e24) / 100 USDT (100e6). Need ~0.1 xEGM (1e17) to get >= 1 wei USDT.
        _seed();
        uint256 smallIn = 1e17; // 0.1 xEGM → ~9 wei USDT
        uint256 out = router.getAmountOut(smallIn, true);
        assertTrue(out > 0, "decimals asymmetry: 0.1 xEGM swap must yield > 0 USDT wei");
    }

    // ── Reverts ───────────────────────────────────────────────────────────────

    function test_RevertOn_SlippageXegmToUsdt() public {
        _seed();
        uint256 amountIn = 10_000 * 1e18;
        uint256 expectedOut = router.getAmountOut(amountIn, true);

        vm.prank(bob);
        vm.expectRevert("INSUFFICIENT_OUTPUT");
        router.swapExactXegmForUsdt(amountIn, expectedOut + 1, bob, block.timestamp + 3600);
    }

    function test_RevertOn_Deadline() public {
        _seed();
        vm.warp(block.timestamp + 3601);

        vm.prank(bob);
        vm.expectRevert("EXPIRED");
        router.swapExactXegmForUsdt(1e18, 0, bob, block.timestamp - 1);
    }

    function test_RevertOn_InsufficientLiquidity() public {
        _seed();
        (,uint112 r1,) = pair.getReserves();
        // Call pair.swap directly requesting all USDT reserve — reverts in pair
        vm.expectRevert("INSUFFICIENT_LIQUIDITY");
        pair.swap(0, uint256(r1), bob, new bytes(0));
    }

    // ── k invariant fuzz ──────────────────────────────────────────────────────

    function testFuzz_KInvariant(uint256 amountIn) public {
        _seed();
        (uint112 r0, uint112 r1,) = pair.getReserves();
        // Min 1e17 xEGM to ensure USDT output >= 1 wei (pool is 1e24 xEGM / 100e6 USDT)
        amountIn = bound(amountIn, 1e17, uint256(r0) / 100);

        uint256 amountOut = router.getAmountOut(amountIn, true);
        xegm.mint(bob, amountIn);
        vm.startPrank(bob);
        xegm.approve(address(router), amountIn);
        router.swapExactXegmForUsdt(amountIn, amountOut, bob, block.timestamp + 3600);
        vm.stopPrank();

        (uint112 newR0, uint112 newR1,) = pair.getReserves();
        // k should be >= original k (fees increase it slightly)
        assertTrue(uint256(newR0) * uint256(newR1) >= uint256(r0) * uint256(r1));
    }
}
