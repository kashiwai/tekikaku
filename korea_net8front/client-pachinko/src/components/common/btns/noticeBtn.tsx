import { HTMLAttributes } from "react";

import IconBase from "@/components/icon/iconBase";
import { Button } from "@/components/ui/button";
import { ICONS } from "@/constants/icons";
import { cn } from "@/lib/utils";
import { useLayoutStore } from "@/store/layout.store";
import { useNoticeStore } from "@/store/notice.store";

export default function NotificationBtn({
  ...props
}: HTMLAttributes<HTMLDivElement>) {
  const isNotificationOpen = useLayoutStore((store) => store.isNotificationOpen);
  const toggleNotification = useLayoutStore((store) => store.toggleNotification);

  const notifications = useNoticeStore((store) => store.notices);

  return (
    <div className={cn("relative", props.className)} {...props}>
      {notifications.length > 0 && (
        <div className="absolute -top-1 -right-1 z-10 flex items-center justify-center text-xs font-semibold w-4.5 h-4.5 rounded-full bg-danger text-white">
          {notifications.length > 9 ? "9+" : notifications.length}
        </div>
      )}

      <Button
        onClick={toggleNotification}
        size={"icon_default"}
        variant={isNotificationOpen ? "primary_bordered" : "default"}
        className="hover:bg-primary hover:text-white"
      >
        <IconBase icon={ICONS.NOTICE_BELL} />
      </Button>
    </div>
  );
}
