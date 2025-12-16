"use client";
import { useEffect } from "react";

import dynamic from "next/dynamic";

import { noticeHelpers } from "@/helpers/notice.helpers";
import { useModal } from "@/hooks/useModal";
import { useNoticeStore } from "@/store/notice.store";
import { useUserStore } from "@/store/user.store";
import { SettingsType } from "@/types/settings.types";
import NoticesModalNew from "./notices/noticesModalNew";

const AuthModal = dynamic(() => import("@/components/modals/authModal"), {
  ssr: false,
});
const WalletModal = dynamic(() => import("@/components/modals/walletModal"), {
  ssr: false,
});
const ProfileModal = dynamic(
  () => import("@/components/modals/profile/ProfileModal"),
  { ssr: false }
);
const NoticesModal = dynamic(
  () => import("@/components/modals/notices/noticesModal"),
  { ssr: false }
);

const PromotionModal = dynamic(
  () => import("@/components/modals/promotion/PromotionModal"),
  {
    ssr: false,
  }
);
const WheelModal = dynamic(
  () => import("@/components/modals/wheel/wheelModal"),
  { ssr: false }
);
const AttendanceModal = dynamic(
  () => import("@/components/modals/attendanceModal"),
  {
    ssr: false,
  }
);

const SearchModal = dynamic(() => import("@/components/modals/searchModal"), {
  ssr: false,
});
const UnlockBonusModal = dynamic(
  () => import("@/components/modals/unlockModal"),
  { ssr: false }
);
const UpdatePasswordModal = dynamic(() => import("./updatePasswordModal"), {
  ssr: false,
});
const UpdateWithdrawalPasswordModal = dynamic(() => import("./updateWithdrawalPasswordModal"), {
  ssr: false,
});
const BetInfoModal = dynamic(() => import("./betInfoModal"), {
  ssr: false,
});

type Props = {
  siteSettings: SettingsType | null;
};

export default function Modals({ siteSettings: settings }: Props) {
  const user = useUserStore((state) => state.user);
  const notices = useNoticeStore((state) => state.notices);
  const noticesModal = useModal("notices");
  const profileModal = useModal("profile");
  const promotionModal = useModal("promotion");
  const wheelModal = useModal("wheel");
  const authModal = useModal("auth");
  const walletModal = useModal("wallet");
  const attendanceModal = useModal("attendance");
  const searchModal = useModal("search");
  const unlockModal = useModal("unlock");
  const updatePasswordModal = useModal("update-password");
  const updateWithdrawalPasswordModal = useModal("update-withdrawal-password");
  const betInfoModal = useModal("betInfo");

  const logo = settings?.site.logo;

  useEffect(() => {
    const hasClosedModal = sessionStorage.getItem("modalClosedInSession");

    const updatedNotices = notices.filter(
      (n) => !noticeHelpers.shouldHideNotice(n.id)
    );

    if (updatedNotices.length > 0 && !hasClosedModal && user) {
      noticesModal.onOpen();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [notices]);

  useEffect(() => {
    const navEntry = performance.getEntriesByType(
      "navigation"
    )[0] as PerformanceNavigationTiming;
    if (navEntry?.type === "reload") {
      // This was a refresh
      sessionStorage.removeItem("modalClosedInSession");
    }
  }, []);

  return (
    <>
      {/* {noticesModal.isOpen && <NoticesModal {...noticesModal} logo={logo} />} */}
      {noticesModal.isOpen && user && <NoticesModalNew {...noticesModal} logo={logo} />}
      {profileModal.isOpen && <ProfileModal {...profileModal} logo={logo} />}
      {promotionModal.isOpen && <PromotionModal {...promotionModal}/>}

      {wheelModal.isOpen && settings && (
        <WheelModal settings={settings} {...wheelModal} logo={logo} />
      )}

      {authModal.isOpen && !user && <AuthModal {...authModal} logo={logo} />}
      {walletModal.isOpen && user && <WalletModal {...walletModal} logo={logo} />}

      {attendanceModal.isOpen && user && settings && (
        <AttendanceModal settings={settings} {...attendanceModal} logo={logo} />
      )}
      {searchModal.isOpen && <SearchModal {...searchModal} />}
      {unlockModal.isOpen && settings && (
        <UnlockBonusModal {...unlockModal} settings={settings} logo={logo} />
      )}
      {updatePasswordModal.isOpen && (
        <UpdatePasswordModal {...updatePasswordModal} logo={logo} />
      )}
      {updateWithdrawalPasswordModal.isOpen && (
        <UpdateWithdrawalPasswordModal {...updateWithdrawalPasswordModal} logo={logo} />
      )}
      {betInfoModal.isOpen && (
        <BetInfoModal {...betInfoModal} logo={logo} />
      )}
    </>
  );
}
