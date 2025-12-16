import { API_ROUTES } from "@/config/routes.config";
import fetcher from "../fetcher";

export const attendanceApi = {
    get: async () => {
        const res = await fetcher<{ attendanceDates: string[]; totalDays: number }>(
            API_ROUTES.ATTENDANCE,
        );

        if (!res.success) return { attendanceDates: [], totalDays: 0 };

        return res.data;
    },
}