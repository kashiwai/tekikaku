import { createConfig, http } from "wagmi";
import { mainnet, sepolia } from "wagmi/chains";
import { injected } from "wagmi/connectors";

const alchemyKey = process.env.NEXT_PUBLIC_ALCHEMY_KEY ?? "";
const rpcUrl = alchemyKey
  ? `https://eth-mainnet.g.alchemy.com/v2/${alchemyKey}`
  : undefined;

export const wagmiConfig = createConfig({
  chains: [mainnet, sepolia],
  connectors: [injected()],
  transports: {
    [mainnet.id]: rpcUrl ? http(rpcUrl) : http(),
    [sepolia.id]: http(),
  },
});
