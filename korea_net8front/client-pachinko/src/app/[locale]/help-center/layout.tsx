import { ReactNode } from "react";

import TabPageLayout from "@/components/layout/tabPageLayout";
import { HELP_CENTER_MENU } from "@/config/aside.config";

export default function Layout({ children }: { children: ReactNode }) {
  return (
    <TabPageLayout
      title="HELP_CENTER"
      asideNav={HELP_CENTER_MENU}
      pageName="HELP_CENTER"
    >
      {children}
    </TabPageLayout>
  );
}
