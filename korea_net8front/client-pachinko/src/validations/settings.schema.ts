import { z } from "zod";

const accountInformationSchema = z
    .object({
        nickname: z.string().min(1, "Name is required"),

        password: z
            .string()
            .min(8, "Password must be at least 8 characters"),


        bank: z
            .string()
            .min(1, "Bank cannot be empty"),


        bankNumber: z
            .string()
            .min(1, "Account number cannot be empty")
            .regex(/^\d+$/, "Account number must contain only digits"),

        withdrawal_password: z
            .string()
            .min(8, "Withdrawal password must be at least 8 characters"),
    })


const updatePasswordSchema = z.object({
    email: z.string().email("INVALID_EMAIL_ADDRESS"),
    email_code: z
        .string()
        .length(6, "EMAIL_CODE_LENGTH_6"),
    password: z
        .string()
        .min(8, "PASSWORD_LENGTH_OVER_8"),
    password_confirmation: z
        .string()
        .min(8, "PASSWORD_LENGTH_OVER_8"),
}).superRefine(({ password, password_confirmation }, ctx) => {
    if (password !== password_confirmation) {
        ctx.addIssue({
            path: ["password_confirmation"],
            code: "custom",
            message: "PASSWORD_NOT_MATCH",
        });
    }

});
export const settingsSchemas = {
    accountInformation: accountInformationSchema,
    updatePassword: updatePasswordSchema
}

export type SettingsSchemas = {
    accountInformation: z.infer<typeof accountInformationSchema>
    updatePassword: z.infer<typeof updatePasswordSchema>
}

