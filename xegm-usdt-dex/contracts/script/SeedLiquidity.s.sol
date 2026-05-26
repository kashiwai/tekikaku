// SPDX-License-Identifier: MIT
pragma solidity 0.8.20;

import "forge-std/Script.sol";
import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "../src/XegmPair.sol";
import "../src/XegmRouter.sol";

/**
 * @notice Seed initial liquidity into the deployed XegmPair.
 * Set in .env:
 *   DEPLOYER_PRIVATE_KEY
 *   XEGM_ROUTER_ADDRESS
 *   SEED_XEGM_AMOUNT   (in wei, 18 decimals)
 *   SEED_USDT_AMOUNT   (in wei, 6 decimals)
 *
 * Example: 1,000,000 xEGM + 100 USDT → 0.0001 USDT/xEGM
 *   SEED_XEGM_AMOUNT=1000000000000000000000000
 *   SEED_USDT_AMOUNT=100000000
 */
contract SeedLiquidity is Script {
    address constant XEGM = 0x212b505dFE47086f1ED5D497323d8478209fC517;
    address constant USDT = 0xdAC17F958D2ee523a2206206994597C13D831ec7;

    function run() external {
        uint256 deployerKey = vm.envUint("DEPLOYER_PRIVATE_KEY");
        address routerAddr  = vm.envAddress("XEGM_ROUTER_ADDRESS");
        uint256 xegmAmount  = vm.envUint("SEED_XEGM_AMOUNT");
        uint256 usdtAmount  = vm.envUint("SEED_USDT_AMOUNT");

        vm.startBroadcast(deployerKey);

        IERC20(XEGM).approve(routerAddr, xegmAmount);
        IERC20(USDT).approve(routerAddr, usdtAmount);

        XegmRouter router = XegmRouter(routerAddr);
        (uint256 actualXegm, uint256 actualUsdt, uint256 liquidity) = router.addLiquidity(
            xegmAmount, usdtAmount,
            0, 0,
            msg.sender,
            block.timestamp + 3600
        );

        console.log("Seeded xEGM:", actualXegm);
        console.log("Seeded USDT:", actualUsdt);
        console.log("LP tokens received:", liquidity);

        vm.stopBroadcast();
    }
}
