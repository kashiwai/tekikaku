import { z } from "zod";

const sendMessageSchema = z.object({
    message: z
        .string({ required_error: "Message content is required" })
        .trim()
        .min(1, "Message can't be empty")
        .max(1000, "Message can't exceed 1000 characters"),
});


export const chatSchemas = {
    sendMessage: sendMessageSchema,
}

export type ChatSchemas = {
    sendMessage: z.infer<typeof sendMessageSchema>
}