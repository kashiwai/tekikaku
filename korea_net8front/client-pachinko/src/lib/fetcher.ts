import { redirectUser } from "@/actions/api.actions";
import { setCookiesFromResponse } from "@/actions/cookie.actions";

type ErrorCodes = unknown;

type ApiError = {
    success: false;
    code: ErrorCodes;
    message: string;
    data?: any;
};

type ApiSuccess<T> = {
    success: true;
    message: string;
    data: T;
    headers: {
        [k: string]: string;
    }
};

export type ApiResponse<T = unknown> = ApiError | ApiSuccess<T>;

export default async function fetcher<T = unknown>(
    path: string,
    options: RequestInit = {},
    ssr: boolean = false
): Promise<ApiResponse<T>> {
    const baseUrl = `${process.env.NEXT_PUBLIC_API_URL}/${path}`;
    const GF_API_KEY = `Bearer ${process.env.NEXT_PUBLIC_NEW_GAMING_AUTHORIZATION}`;

    try {
        const response = await fetch(baseUrl, {
            ...options,
            headers: {
                "Authorization": GF_API_KEY,
                "Content-Type": "application/json",
                ...options.headers,
            },
            cache: "no-store",
            next: { revalidate: 0 },
            credentials: options.credentials || "include",
        });

        const contentType = response.headers.get("content-type");
        const isJson = contentType?.includes("application/json");
        const headers = Object.fromEntries(response.headers.entries());

        // console.log("header response => ", response);
        // # only can be usefull when requests sends from csr using "server action"
        if (headers && !ssr) {
            const cookieHeader = headers["set-cookie"];
            await setCookiesFromResponse(cookieHeader || null);
        }

        if (!response.ok) {
            const errorBody = isJson ? await response.json() : null;

            return {
                success: false,
                code: (errorBody?.code as ErrorCodes) || "server-error",
                message: errorBody?.message || "API_FAILED",
                data: errorBody.data,
            };
        }

        const data = isJson ? await response.json() : null;

        return {
            success: true,
            message: data?.message || "SUCCESS",
            data: data?.data ?? data,
            headers,
        };
    } catch (error) {
        return {
            success: false,
            code: "server-error",
            message: "API_FAILED",
        };
    }
}

