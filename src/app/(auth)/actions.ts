"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import {
  registerSchema,
  loginSchema,
  requestPasswordResetSchema,
  resetPasswordSchema,
} from "@/lib/validation/auth";
import {
  registerUser,
  authenticate,
  requestPasswordReset,
  resetPassword,
  AuthServiceError,
} from "@/server/services/auth-service";
import { createSession, destroySession } from "@/lib/auth/session";

export type ActionState = {
  error?: string;
  fieldErrors?: Record<string, string>;
  success?: string;
} | null;

export async function registerAction(_prev: ActionState, formData: FormData): Promise<ActionState> {
  const parsed = registerSchema.safeParse({
    firstName: formData.get("firstName"),
    lastName: formData.get("lastName"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    password: formData.get("password"),
    passwordConfirmation: formData.get("passwordConfirmation"),
  });

  if (!parsed.success) {
    return { fieldErrors: flattenZodErrors(parsed.error) };
  }

  try {
    const user = await registerUser(parsed.data);
    await createSession(user.id);
  } catch (err) {
    if (err instanceof AuthServiceError) return { error: err.message };
    throw err;
  }

  redirect("/minha-conta");
}

export async function loginAction(_prev: ActionState, formData: FormData): Promise<ActionState> {
  const parsed = loginSchema.safeParse({
    email: formData.get("email"),
    password: formData.get("password"),
  });

  if (!parsed.success) {
    return { fieldErrors: flattenZodErrors(parsed.error) };
  }

  try {
    const user = await authenticate(parsed.data);
    await createSession(user.id);
  } catch (err) {
    if (err instanceof AuthServiceError) return { error: err.message };
    throw err;
  }

  redirect("/minha-conta");
}

export async function logoutAction() {
  await destroySession();
  revalidatePath("/");
  redirect("/");
}

export async function requestPasswordResetAction(
  _prev: ActionState,
  formData: FormData
): Promise<ActionState> {
  const parsed = requestPasswordResetSchema.safeParse({ email: formData.get("email") });
  if (!parsed.success) {
    return { fieldErrors: flattenZodErrors(parsed.error) };
  }

  const appUrl = process.env.APP_URL || "http://localhost:3000";
  await requestPasswordReset(parsed.data.email, appUrl);

  return {
    success:
      "Se este e-mail estiver cadastrado, você receberá um link de recuperação em instantes.",
  };
}

export async function resetPasswordAction(
  _prev: ActionState,
  formData: FormData
): Promise<ActionState> {
  const parsed = resetPasswordSchema.safeParse({
    token: formData.get("token"),
    password: formData.get("password"),
    passwordConfirmation: formData.get("passwordConfirmation"),
  });

  if (!parsed.success) {
    return { fieldErrors: flattenZodErrors(parsed.error) };
  }

  try {
    await resetPassword(parsed.data.token, parsed.data.password);
  } catch (err) {
    if (err instanceof AuthServiceError) return { error: err.message };
    throw err;
  }

  redirect("/login?redefinida=1");
}

function flattenZodErrors(error: { issues: { path: PropertyKey[]; message: string }[] }) {
  const out: Record<string, string> = {};
  for (const issue of error.issues) {
    const key = String(issue.path[0] ?? "form");
    if (!out[key]) out[key] = issue.message;
  }
  return out;
}
