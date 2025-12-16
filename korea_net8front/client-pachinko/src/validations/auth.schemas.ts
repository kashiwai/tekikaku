import { z } from "zod";

const loginSchema = z.object({
    email: z.string().email("INVALID_EMAIL_ADDRESS"),
    password: z.string().min(1, "PASSWORD_REQUIRED")
});

// 韓国ログイン専用スキーマ（loginIdはemailまたはユーザーIDを許可）
const koreaLoginSchema = z.object({
    loginId: z.string().min(1, "LOGIN_ID_REQUIRED"),
    password: z.string().min(1, "PASSWORD_REQUIRED")
});

const registerSchema = z
    .object({
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

        nickname: z
            .string()
            .min(3, "NICKNAME_LENGTH_OVER_3")
            .max(20, "NICKNAME_LENGTH_LESS_20")
            .regex(
                /^[a-zA-Z0-9_]+$/,
                "NICKNAME_CONTAIN_LETTERS"
            ),

        subscription_code: z
            .string(),

        bank: z
            .string()
            .min(1, "BANK_CANT_EMPTY"),

        withdrawal_password: z
            .string()
            .min(8, "WITHDRAWAL_PASSWORD_OVER_8"),

        depositor_name: z
            .string()
            .min(1, "DEPOSITOR_NAME_CANT_EMPTY"),

        account_number: z
            .string()
            .min(1, "ACCOUNT_NUMBER_CANT_EMPTY")
            .regex(/^\d+$/, "ACCOUNT_NUMBER_MUST_DEGITS"),

        withdrawal_type: z
            .string()
            .min(1, "WITHDRAWAL_TYPE_CANT_EMPTY"),

        phone_number: z
            .string()
            .min(1, "PHONENUMBER_CANT_EMPTY")
            .regex(/^\d+$/, "PHONE_NUMBER_MUST_DIGITS"),
    })
    .superRefine(({ password, password_confirmation }, ctx) => {
        if (password !== password_confirmation) {
            ctx.addIssue({
                path: ["password_confirmation"],
                code: "custom",
                message: "PASSWORD_NOT_MATCH",
            });
        }

    });

const verifyEmailSchema = z.object({
    code: z
        .string()
        .length(6, "Verification code must be exactly 6 digits")
        .regex(/^\d{6}$/, "Verification code must be 6 digits"),
});

const resetPasswordSchema = z
    .object({
        email: z.string().email("INVALID_EMAIL_ADDRESS"),

        email_code: z
            .string()
            .length(6, "EMAIL_CODE_LENGTH_6"),

        new_password: z.string().min(8, "PASSWORD_LENGTH_OVER_8"),
        password_confirmation: z.string().min(8, "PASSWORD_LENGTH_OVER_8"),
    }).superRefine(({ new_password, password_confirmation }, ctx) => {
        if (new_password !== password_confirmation) {
            ctx.addIssue({
                path: ["password_confirmation"],
                code: "custom",
                message: "PASSWORD_NOT_MATCH",
            });
        }
    });


export const authSchemas = {
    login: loginSchema,
    koreaLogin: koreaLoginSchema,
    register: registerSchema,
    verifyEmail: verifyEmailSchema,
    resetPassword: resetPasswordSchema
}

export type AuthSchemas = {
    login: z.infer<typeof loginSchema>
    koreaLogin: z.infer<typeof koreaLoginSchema>
    register: z.infer<typeof registerSchema>
    verifyEmail: z.infer<typeof verifyEmailSchema>
    resetPassword: z.infer<typeof resetPasswordSchema>
}