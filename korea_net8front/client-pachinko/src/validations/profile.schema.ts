import { z } from "zod";

const MAX_AVATAR_SIZE_MB = 5;
const ALLOWED_AVATAR_TYPES = ["image/jpeg", "image/png", "image/webp"];

const editProfileSchema = z.object({
  avatar: z
    .instanceof(File)
    .refine(
      (file) => file.size <= MAX_AVATAR_SIZE_MB * 1024 * 1024,
      `Avatar must be less than ${MAX_AVATAR_SIZE_MB}MB`
    )
    .refine(
      (file) => ALLOWED_AVATAR_TYPES.includes(file.type),
      "Avatar must be a JPG, PNG, or WEBP image"
    ),

  name: z
    .string()
    .trim()
    .min(1, "Please enter your name")
    .max(50, "Name can't be more than 50 characters"),
});

export const profileSchemas = {
  editProfile: editProfileSchema
}

export type ProfileSchemas = {
  editProfile: z.infer<typeof editProfileSchema>
}