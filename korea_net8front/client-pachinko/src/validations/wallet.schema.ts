import { z } from "zod";

const depositCryptoSchema = z.object({
    network: z.string().min(1, "Network is required"),
    currency: z.string().min(1, "Currency is required"),
});

const depositFiatSchema = z.object({
    depositor_name: z.string().min(1, "Depositor name is required"),
    amount: z
        .string()
        .min(1, { message: "Amount is required" })
        .refine(
            (val) =>
                /^\d{1,3}(,\d{3})*(\.\d{1,2})?$/.test(val) || /^\d+(\.\d{1,2})?$/.test(val),
            {
                message: "Must be a valid amount (e.g. 10000, 10,000, or 12,401.00)",
            }
        ),
});

const withdrawalCryptoSchema = z.object({
    network: z.string().min(1, "Network is required"),
    currency: z.string().min(1, "Currency is required"),
    address: z.string().min(1, "Address is required"),
    amount: z
        .string()
        .min(1, "Amount is required")
        .regex(/^(?!0\.00)\d+(\.\d{1,8})?$/, "Enter a valid amount"),
});

const withdrawalFiatSchema = z.object({
    currency: z.string().min(1, "Currency is required"),
    bank: z.string().min(1, "Bank name is required!"),
    account_number: z.string().min(1, "Account number is required!"),
    withdrawal_name: z.string().min(1, "Withdrawal name is required!"),
    withdrawal_password: z.string().min(1, "Withdrawal passwors is required!"),
    amount: z
        .string()
        .min(1, "Amount is required")
        .regex(/^(?!0\.00)\d+(\.\d{1,8})?$/, "Enter a valid amount"),
});

export const walletSchemas = {
    depositCrypto: depositCryptoSchema,
    depositFiat: depositFiatSchema,
    withdrawalCrypto: withdrawalCryptoSchema,
    withdrawalFiat: withdrawalFiatSchema
}

export type WalletSchemas = {
    depositCrypto: z.infer<typeof depositCryptoSchema>
    depositFiat: z.infer<typeof depositFiatSchema>
    withdarawlCrypto: z.infer<typeof withdrawalCryptoSchema>
    withdrawalFiat: z.infer<typeof withdrawalFiatSchema>
}