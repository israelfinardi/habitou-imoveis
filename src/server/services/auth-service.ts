import "server-only";
import crypto from "node:crypto";
import { prisma } from "@/lib/db";
import { hashPassword, verifyPassword } from "@/lib/auth/password";
import { sendMail } from "@/lib/email";
import type { RegisterInput, LoginInput } from "@/lib/validation/auth";

export class AuthServiceError extends Error {}

const RESET_TOKEN_TTL_MS = 1000 * 60 * 60; // 1 hora

function hashToken(token: string) {
  return crypto.createHash("sha256").update(token).digest("hex");
}

export async function registerUser(input: RegisterInput) {
  const existing = await prisma.user.findUnique({ where: { email: input.email } });
  if (existing) {
    throw new AuthServiceError("Este e-mail já está cadastrado.");
  }

  const passwordHash = await hashPassword(input.password);
  const user = await prisma.user.create({
    data: {
      firstName: input.firstName,
      lastName: input.lastName,
      email: input.email,
      phone: input.phone || null,
      passwordHash,
      role: "USER",
    },
  });
  return user;
}

export async function authenticate(input: LoginInput) {
  const user = await prisma.user.findUnique({ where: { email: input.email } });
  if (!user) throw new AuthServiceError("E-mail ou senha inválidos.");
  if (user.status !== "ACTIVE") throw new AuthServiceError("Esta conta está inativa.");

  const valid = await verifyPassword(input.password, user.passwordHash);
  if (!valid) throw new AuthServiceError("E-mail ou senha inválidos.");

  await prisma.user.update({ where: { id: user.id }, data: { lastLoginAt: new Date() } });
  return user;
}

/** Nunca revela se o e-mail existe: sempre retorna sucesso silenciosamente. */
export async function requestPasswordReset(email: string, appUrl: string) {
  const user = await prisma.user.findUnique({ where: { email } });
  if (!user) return;

  const rawToken = crypto.randomBytes(32).toString("hex");
  const tokenHash = hashToken(rawToken);

  await prisma.passwordResetToken.create({
    data: {
      userId: user.id,
      tokenHash,
      expiresAt: new Date(Date.now() + RESET_TOKEN_TTL_MS),
    },
  });

  const resetUrl = `${appUrl}/redefinir-senha/${rawToken}`;
  await sendMail({
    to: user.email,
    subject: "Recuperação de senha — Habitou Imóveis",
    html: `
      <p>Olá, ${user.firstName}.</p>
      <p>Recebemos uma solicitação para redefinir sua senha. Este link expira em 1 hora e só pode ser usado uma vez:</p>
      <p><a href="${resetUrl}">${resetUrl}</a></p>
      <p>Se você não solicitou, ignore este e-mail.</p>
    `,
  });
}

export async function resetPassword(rawToken: string, newPassword: string) {
  const tokenHash = hashToken(rawToken);
  const resetToken = await prisma.passwordResetToken.findUnique({
    where: { tokenHash },
  });

  if (!resetToken || resetToken.usedAt || resetToken.expiresAt < new Date()) {
    throw new AuthServiceError("Este link de redefinição é inválido ou expirou.");
  }

  const passwordHash = await hashPassword(newPassword);

  await prisma.$transaction([
    prisma.user.update({ where: { id: resetToken.userId }, data: { passwordHash } }),
    prisma.passwordResetToken.update({
      where: { id: resetToken.id },
      data: { usedAt: new Date() },
    }),
  ]);
}

export async function changePassword(userId: string, currentPassword: string, newPassword: string) {
  const user = await prisma.user.findUniqueOrThrow({ where: { id: userId } });
  const valid = await verifyPassword(currentPassword, user.passwordHash);
  if (!valid) throw new AuthServiceError("Senha atual incorreta.");

  const passwordHash = await hashPassword(newPassword);
  await prisma.user.update({ where: { id: userId }, data: { passwordHash } });
}
