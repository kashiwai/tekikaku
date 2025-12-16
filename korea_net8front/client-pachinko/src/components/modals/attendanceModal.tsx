"use client";

import { useEffect, useState } from "react";

import { useTranslations } from "next-intl";

import InfoCard from "@/components/cards/info/statInfoCard";
import Logo from "@/components/common/brand/logo";
import ModalLayout from "@/components/modals/modalLayout";
import { AttendanceCalendar } from "@/components/ui/attendance-calendar";
import { ModalControls, useModal } from "@/hooks/useModal";
import { LogoType, SettingsType } from "@/types/settings.types";
import { Button } from "../ui/button";
import { useUserStore } from "@/store/user.store";
import { claimAttendanceBonus } from "@/actions/api.actions";
import { toastDanger, toastSuccess } from "../ui/sonner";
import { attendanceApi } from "@/lib/api/attendance.api";
import fetcher from "@/lib/fetcher";
import { API_ROUTES } from "@/config/routes.config";
import FormLoader from "../forms/formLoader";
import numeral from "numeral";
import { useFormStore } from "@/store/form.store";
import { toZonedTime, format } from "date-fns-tz";

type Props = ModalControls<"attendance"> & {
  settings: SettingsType;
  logo: LogoType | undefined;
};

export default function AttendanceModal({
  logo,
  isOpen,
  onClose,
  settings,
}: Props) {
  const {
    loading,
    startLoading,
    stopLoading,
    dataLoading,
    startDataLoading,
    stopDataLoading,
  } = useFormStore();
  const t = useTranslations("ATTENDANCE");
  const user = useUserStore((store) => store.user);
  const updateUser = useUserStore((store) => store.updateUser);
  const [claimedDays, setClaimedDays] = useState<string[]>([]);
  const authModal = useModal("auth");
  const [attendance, setAttendance] = useState<{
    attendanceDates: string[];
    totalDays: number;
  } | null>(null);

  const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  const convertToUserLocalDate = (isoString: string): string => {
    const date = new Date(isoString);
    const zonedDate = toZonedTime(date, timezone);
    return format(zonedDate, "yyyy-MM-dd");
  };

  const getData = async () => {
    startDataLoading();
    const attendance = await fetcher<{
      attendanceDates: string[];
      totalDays: number;
    }>(`${API_ROUTES.ATTENDANCE}`);

    stopDataLoading();
    if (!attendance.success) return;

    setAttendance(attendance.data);
    console.log("Attendance data:", attendance.data);
  };

  useEffect(() => {
    getData();
  }, []);

  useEffect(() => {
    if (!user || !attendance) return;

    const convertedDates = attendance?.attendanceDates
      .map(convertToUserLocalDate)
      .filter((date) => date !== ""); // Remove invalid dates

    setClaimedDays(convertedDates);
  }, [user, attendance]);

  const handleClaim = async (day: string, date: Date) => {
    if (!user) return toastDanger("Login to get bonus");
    if (loading) return;

    startLoading();

    try {
      const res = await claimAttendanceBonus(date.toISOString());

      if (!res.success) return toastDanger(res.message);

      getData();

      toastSuccess(t("SUCCESS"));

      updateUser({
        attendance: res.data.attendance,
        bonus: res.data.bonus,
      });

      // Convert the claimed date to user timezone before updating state
      const userTimezoneDate = convertToUserLocalDate(date.toISOString());
      if (userTimezoneDate) {
        setClaimedDays((prev) =>
          prev.includes(userTimezoneDate) ? prev : [...prev, userTimezoneDate]
        );
      }
    } finally {
      stopLoading();
    }
  };

  return (
    <ModalLayout isOpen={isOpen} onClose={onClose} ariaLabel="Attendance">
      <FormLoader loading={dataLoading} />
    </ModalLayout>
  );
}
