import { ReadonlyURLSearchParams } from "next/navigation";

export const searchParamUtils = {
    /**
     * Safely extract a search param as string.
     */
    /**
     * Safely get a string param from a plain searchParams object.
     */
    getParam: (
        params: { [key: string]: string | string[] | undefined },
        key: string,
        fallback?: string
    ): string => {
        const value = params[key];
        if (Array.isArray(value)) return value[0] ?? fallback ?? "";
        if (typeof value === "string") return value || fallback || "";
        return fallback || "";
    },

    /**
     * Normalize all searchParams into a plain object of strings.
     * If a param has multiple values (array), only the first one is returned.
     * If a param is undefined, fallback to empty string or provided fallback map.
     */
    getParams: <T extends { [key: string]: string }>(
        params: { [key: string]: string | string[] | undefined },
        fallback: T
    ): { [K in keyof T]: string } => {
        const result = {} as { [K in keyof T]: string };

        for (const key in fallback) {
            const value = params[key];
            if (Array.isArray(value)) {
                result[key as keyof T] = value[0] || fallback[key];
            } else if (typeof value === "string") {
                result[key as keyof T] = value || fallback[key];
            } else {
                result[key as keyof T] = fallback[key];
            }
        }

        return result;
    },

    /**
     * Safely extract a search param as number.
     */
    getParamNumber: (
        params: ReadonlyURLSearchParams,
        key: string,
        fallback?: number
    ): number | undefined => {
        const value = params.get(key);
        if (!value) return fallback;
        const num = Number(value);
        return Number.isNaN(num) ? fallback : num;
    },

    /**
     * Safely extract a search param as boolean.
     * Accepts: "1", "true", "yes"
     */
    getParamBool: (
        params: ReadonlyURLSearchParams,
        key: string,
        fallback = false
    ): boolean => {
        const value = params.get(key)?.toLowerCase();
        if (value == null) return fallback;
        return ["1", "true", "yes"].includes(value);
    },

    /**
     * Utility to build a new search string with updated params.
     */
    updateParams: (
        params: ReadonlyURLSearchParams,
        updates: Record<string, string | number | boolean | null | undefined>
    ): string => {
        const newParams = new URLSearchParams(params.toString());

        Object.entries(updates).forEach(([key, value]) => {
            if (value === null || value === undefined || value === "") {
                newParams.delete(key);
            } else {
                newParams.set(key, String(value));
            }
        });
        return newParams.toString();
    },

    updateParamsSorted: (
        params: URLSearchParams | ReadonlyURLSearchParams,
        updates: Record<string, string | number | boolean | null | undefined>
    ): string => {
        const newParams = new URLSearchParams(params.toString());

        // apply updates
        Object.entries(updates).forEach(([key, value]) => {
            if (value === null || value === undefined || value === "") {
                newParams.delete(key);
            } else {
                newParams.set(key, String(value));
            }
        });

        // sort keys alphabetically
        const orderedParams = new URLSearchParams();
        Array.from(newParams.keys())
            .sort()
            .forEach((key) => {
                const value = newParams.get(key);
                if (value !== null) orderedParams.set(key, value);
            });

        return orderedParams.toString();
    },

    buildSearchParams: (
        values: Record<string, string | undefined>,
        defaults?: Record<string, string>
    ): URLSearchParams => {
        const params = new URLSearchParams();
        const finalValues = { ...defaults, ...values };

        Object.entries(finalValues).forEach(([key, value]) => {
            if (value !== undefined && value !== "") {
                params.set(key, value);
            }
        });

        return params;
    }
}