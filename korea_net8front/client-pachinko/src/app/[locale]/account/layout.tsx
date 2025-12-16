import React from "react";

import TabPageLayout from "@/components/layout/tabPageLayout";
import { ACCOUNT_MENU } from "@/config/aside.config";

export default function Layout({ children }: { children: React.ReactNode }) {

  return (
    <TabPageLayout title={"ACCOUNT"} asideNav={ACCOUNT_MENU} pageName="ACCOUNT">
      {children}
    </TabPageLayout>
  );
}
