"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ConnectButton } from "./ConnectButton";

export function Header() {
  const pathname = usePathname();

  return (
    <header className="bg-white border-b border-[#E5E8EC]">
      <div className="max-w-lg mx-auto px-4 h-14 flex items-center justify-between">
        <div className="flex items-center gap-6">
          <span className="font-semibold text-[#2563EB] text-sm tracking-tight">
            xEGM Exchange
          </span>
          <nav className="flex gap-1">
            <NavLink href="/swap" active={pathname === "/swap"}>Swap</NavLink>
            <NavLink href="/pool" active={pathname.startsWith("/pool")}>Pool</NavLink>
          </nav>
        </div>
        <ConnectButton />
      </div>
    </header>
  );
}

function NavLink({ href, active, children }: { href: string; active: boolean; children: React.ReactNode }) {
  return (
    <Link
      href={href}
      className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
        active
          ? "bg-[#EFF6FF] text-[#2563EB]"
          : "text-gray-600 hover:text-gray-900 hover:bg-gray-100"
      }`}
    >
      {children}
    </Link>
  );
}
