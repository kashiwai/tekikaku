import { paginationConfig } from "@/config/pagination.config";

export const paginationUtils = {
    getPaginationItems: (currentPage: number, totalPages: number) => {

        const items: (number | string)[] = [];

        items.push(1);

        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);

        if (start > 2) items.push("ellipsis-start");

        for (let i = start; i <= end; i++) {
            if (i !== 1 && i !== totalPages) items.push(i);
        }

        if (end < totalPages - 1) items.push("ellipsis-end");

        if (totalPages > 1) items.push(totalPages);

        return items;
    },

    checkLimit: (limit: string | undefined, defaultVal: string) => {
        return limit && paginationConfig.options.includes(limit) ? limit : defaultVal
    }

}