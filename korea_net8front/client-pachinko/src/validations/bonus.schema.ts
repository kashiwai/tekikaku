import { z } from "zod";

const claimBonusSchema = z.object({
  amount: z
    .string()
    .min(1, "Amount is required")
    .regex(/^\d+(\.\d{1,2})?$/, "Amount must be a valid number (e.g. 125 or 125.12)"),
});

export const bonusSchemas = {
  claimBonus: claimBonusSchema
}

export type BonusSchemas = {
  claimBonus: z.infer<typeof claimBonusSchema>
}