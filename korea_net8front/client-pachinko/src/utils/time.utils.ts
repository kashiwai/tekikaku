import { Duration } from "luxon";

export function formatMinutes(minutes: number) {
    const d = Duration.fromObject({ minutes });
    const hours = Math.floor(d.as("hours"));
    const mins = Math.round(d.as("minutes") % 60);
    return hours ? `${hours}h ${mins}m` : `${mins}m`;
}
