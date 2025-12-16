import { useEffect, useState } from "react";
import { intervalToDuration, formatDuration, isAfter } from "date-fns";

export function useCountdown(targetTime?: string) {
    const [remaining, setRemaining] = useState<string | null>(null);
    const [done, setDone] = useState(false);

    useEffect(() => {
        if (!targetTime) {
            setRemaining(null);
            setDone(true);
            return;
        } else
            setDone(false)

        const target = new Date(targetTime);

        const tick = () => {
            const now = new Date();
            if (isAfter(now, target)) {
                setDone(true);
                setRemaining(null);
                return;
            }

            const duration = intervalToDuration({ start: now, end: target });
            // Format to "1h 30m 15s"
            const formatted = formatDuration(duration, {
                format: ["hours", "minutes", "seconds"],
                delimiter: " ",
            })
                .replace(/hours?/, "h")
                .replace(/minutes?/, "m")
                .replace(/seconds?/, "s");
            setRemaining(formatted);
        };

        tick();
        const timer = setInterval(tick, 1000);
        return () => clearInterval(timer);
    }, [targetTime]);

    return { remaining, done };
}