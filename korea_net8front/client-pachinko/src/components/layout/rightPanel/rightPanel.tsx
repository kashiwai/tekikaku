"use client";

import { useEffect, useState } from "react";

import { AnimatePresence, motion } from "framer-motion";

import { useLayoutStore } from "@/store/layout.store";
import Notices from "@/components/layout/notifications/notices";

export default function SidePanel() {
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    function checkMobile() {
      setIsMobile(window.innerWidth < 1024); // match your lg breakpoint
    }
    checkMobile();
    window.addEventListener("resize", checkMobile);
    return () => window.removeEventListener("resize", checkMobile);
  }, []);

  const isNotificationOpen = useLayoutStore(
    (store) => store.isNotificationOpen
  );
  const toggleNotification = useLayoutStore(
    (store) => store.toggleNotification
  );

  return (
    <AnimatePresence>
      {isNotificationOpen && (
        <motion.div
          className="fixed h-full right-0 w-full lg:sticky top-0 flex-1 border-l border-neutral/10 linear-background overflow-hidden z-50 lg:z-10"
          initial={{ maxWidth: 0 }}
          animate={{ maxWidth: isMobile ? "100%" : "260px" }}
          exit={{ maxWidth: 0 }}
          transition={{ duration: 0.2, ease: "easeInOut" }}
        >
          <AnimatePresence>
            <div className="h-[70px] w-full border-b border-b-neutral/10"></div>
            {/* Notifications Component */}
            {isNotificationOpen && (
              <motion.div
                key="notification"
                initial={{ x: "100%", zIndex: 0 }} // Start notifications off to the right
                animate={{ x: 0, zIndex: 10 }} // Slide in from right to left
                exit={{ x: "100%", zIndex: 0 }} // Slide notifications to the right when exiting
                transition={{ duration: 0.2, ease: "easeInOut" }}
                className="absolute flex flex-1 h-full top-0 right-0 w-full linear-background"
              >
                <Notices {...{ toggleNotification }} />
              </motion.div>
            )}
          </AnimatePresence>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
