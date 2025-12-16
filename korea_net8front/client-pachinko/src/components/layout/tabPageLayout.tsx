"use client";
import { ReactNode } from "react";

import Link from "next/link";
import { usePathname } from "next/navigation";

import { type IconSvgElement } from "@hugeicons/react";
import { useTranslations } from "next-intl";

import PageTitle from "@/components/common/page/pageTitle";
import IconBase from "@/components/icon/iconBase";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { AsideNavType } from "@/config/aside.config";
import { ICONS } from "@/constants/icons";
import { useModal } from "@/hooks/useModal";
import { useSelect } from "@/hooks/useSelect";

type Props = {
  title: string;
  asideNav: AsideNavType[];
  children: ReactNode;
  pageName: string;
};

export default function TabPageLayout({ title, asideNav, pageName, children }: Props) {
  const t = useTranslations(`ASIDEMENU.${pageName}`)
  const pathname = usePathname();
  const navDropdown = useSelect();
  const walletModal = useModal("wallet");

  const onAction = (action: AsideNavType["action"]) => {
    if (action) {
      if (action === "deposit-modal") {
        walletModal.onOpen({ tab: "deposit" });
      }
      if (action === "withdraw-modal") {
        walletModal.onOpen({ tab: "withdraw" });
      }
    }
  };

  return (
    <div className="space-y-4">
      <PageTitle>{t(title)}</PageTitle>
      <div className="grid md:grid-cols-[240px_auto] gap-4">
        <nav className="sticky top-12 w-full md:block hidden">
          <ul>
            {asideNav.map((navItem, index) => {
              const isActive = pathname.endsWith(navItem.href);
              return (
                <li key={index}>
                  <Link
                    href={navItem.href}
                    onClick={(e) => {
                      if (navItem.action) {
                        e.preventDefault();
                        onAction(navItem.action);
                      }
                    }}
                    className={`flex items-center gap-1.5 w-full p-3 rounded-xl text-[13px] font-medium transition-all
                        ${isActive
                        ? "bg-primary/80 text-white"
                        : "text-foreground/80 hover:bg-foreground/5 hover:bg-primary hover:text-white"
                      }`}
                  >
                    <IconBase icon={navItem.icon} className="size-4" />
                    <span>{t(navItem.label)}</span>
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>
        <DropdownMenu
          open={navDropdown.isOpen}
          onOpenChange={navDropdown.onOpenChange}
        >
          <DropdownMenuTrigger
            className={`group md:hidden flex items-center h-max gap-1.5 w-full p-3 rounded-xl text-[13px] font-medium transition-all bg-primary/80 text-white`}
          >
            <IconBase
              icon={
                asideNav.find((item) => pathname.endsWith(item.href))
                  ?.icon as IconSvgElement
              }
              className="size-4"
            />
            <span>
              {asideNav.find((item) => pathname.endsWith(item.href))?.label}
            </span>

            <IconBase
              icon={ICONS.CHEVRON_LEFT}
              className="-rotate-90 group-data-[state=open]:rotate-90 size-4 ml-auto"
            />
          </DropdownMenuTrigger>
          <DropdownMenuContent
            align="end"
            className="bg-background border border-foreground/10 shadow-xl w-[var(--radix-popper-anchor-width)]"
          >
            {asideNav.map((navItem, index) => {
              const isActive = pathname === navItem.href;
              return (
                <DropdownMenuItem key={index} onClick={navDropdown.onClose}>
                  <Link
                    href={navItem.href}
                    onClick={(e) => {
                      if (navItem.action) {
                        e.preventDefault();
                        onAction(navItem.action);
                      }
                    }}
                    className={`flex items-center gap-1.5 w-full p-3 rounded-xl text-[13px] font-medium transition-all
                        ${isActive
                        ? "bg-primary/80 text-white"
                        : "text-foreground/80 hover:bg-foreground/5"
                      }`}
                  >
                    <IconBase icon={navItem.icon} className="size-4" />
                    <span>{navItem.label}</span>
                  </Link>
                </DropdownMenuItem>
              );
            })}
          </DropdownMenuContent>
        </DropdownMenu>
        <div className="space-y-4">{children}</div>
      </div>
    </div>
  );
}
