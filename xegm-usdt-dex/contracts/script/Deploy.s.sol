// SPDX-License-Identifier: MIT
pragma solidity 0.8.20;

import "forge-std/Script.sol";
import "../src/XegmPair.sol";
import "../src/XegmRouter.sol";

/**
 * @notice Deploy XegmPair and XegmRouter.
 * Usage:
 *   Sepolia:  forge script script/Deploy.s.sol --rpc-url sepolia --broadcast --verify
 *   Mainnet:  forge script script/Deploy.s.sol --rpc-url mainnet --broadcast --verify
 *
 * Set DEPLOYER_PRIVATE_KEY in .env
 */
contract Deploy is Script {
    function run() external {
        uint256 deployerKey = vm.envUint("DEPLOYER_PRIVATE_KEY");
        vm.startBroadcast(deployerKey);

        XegmPair pair = new XegmPair();
        console.log("XegmPair deployed:", address(pair));

        XegmRouter router = new XegmRouter(address(pair));
        console.log("XegmRouter deployed:", address(router));

        vm.stopBroadcast();
    }
}
