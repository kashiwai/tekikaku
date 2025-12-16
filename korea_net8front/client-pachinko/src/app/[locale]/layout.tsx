import "./globals.css";
import { ReactNode } from "react";

import { Sen } from "next/font/google";

import { NextIntlClientProvider } from "next-intl";
import { getMessages } from "next-intl/server";

import { HydrateBets } from "@/components/hydrate/HydrateBets";
import HydrateNotice from "@/components/hydrate/HydrateNotices";
import HydrateUser from "@/components/hydrate/HydrateUser";
import Aside from "@/components/layout/aside/aside";
import Footer from "@/components/layout/footer/footer";
import Header from "@/components/layout/header/header";
import LiveChatBtn from "@/components/layout/live-chat/LiveChatBtn";
import Main from "@/components/layout/main/main";
import MobileNav from "@/components/layout/mobile-nav/MobileNav";
import RightPanel from "@/components/layout/rightPanel/rightPanel";
import PageLoader from "@/components/loader/pageLoader";
import Modals from "@/components/modals/Modals";
import { Toaster } from "@/components/ui/sonner";
import HydrateSettings from "@/components/hydrate/HydrateSettings";
import { Metadata } from "next";
import HydrateChat from "@/components/hydrate/hydrateChat";
import { getSession } from "@/lib/getSession";

const fontPrimary = Sen({
  variable: "--font-sen",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "NET8 Korea - パチンコ・スロット",
  description: "韓国NET8統合プラットフォーム",
  icons: {
    icon: "/favicon.ico",
  },
};

export default async function RootLayout({
  children,
  params,
}: Readonly<{
  children: ReactNode;
  params: Promise<{ locale: string }>;
}>) {
  const { locale } = await params;
  const messages = await getMessages();
  const session = await getSession();
  const noticeList = { list: [], total: 0 };
  const siteSettings = {
    roulette: [],
    banners: [],
    attendance: [],
    design: {
      logo: '/logo.png'
    },
    unlockRate: 0,
    site: {
      minBonusConvert: 0,
      unlockBonus: 0,
      bettingAttendance: 0,
      logo: {
        light: {
          icon: '/logo.png',
          title: '/logo.png'
        },
        dark: {
          icon: '/logo.png',
          title: '/logo.png'
        }
      },
      info: {
        domain: 'net8korea.com',
        nickname: 'NET8 Korea'
      },
      favicon: '/favicon.ico'
    },
    games: []
  };

  const logo = siteSettings?.site?.logo;

  return (
    <html lang={locale} data-theme="dark">
      <body className={`${fontPrimary.className} flex antialiased`}>
        <PageLoader />
        <HydrateUser initialUser={session?.user || null} />
        <HydrateSettings settings={siteSettings} />
        <HydrateNotice initialNotices={noticeList?.list || []} />
        <HydrateBets />
        <HydrateChat />

        <NextIntlClientProvider locale={locale} messages={messages}>
          <div className="relative flex flex-1 overflow-hidden h-svh">
            <Aside logo={logo} />
            <div className="flex flex-1 flex-col custom-scrollbar overflow-auto linear-background">
              <Header logo={logo} />
              <Main>{children}</Main>
              <Footer />
              <LiveChatBtn logo={logo} />
            </div>

            <RightPanel />
            <Modals siteSettings={siteSettings} />
            <MobileNav />
          </div>
        </NextIntlClientProvider>

        <Toaster position="top-right" />
      </body>
    </html>
  );
}
