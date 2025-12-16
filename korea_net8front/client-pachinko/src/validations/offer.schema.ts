import { z } from "zod";

export const offerSchema = z
    .object({
        code: z
            .string()
            // .length(6, "CODE_6_LENGTH"),
    })

export type OfferSchema = z.infer<typeof offerSchema>;