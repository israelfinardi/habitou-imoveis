"use server";

import { z } from "zod";
import { prisma } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth/session";
import { sendMail } from "@/lib/email";

const schema = z.object({
  name: z.string().trim().min(2, "Informe seu nome."),
  email: z.string().trim().email("E-mail inválido."),
  phone: z.string().trim().optional().or(z.literal("")),
  subject: z.string().trim().min(1, "Selecione um assunto."),
  message: z.string().trim().min(10, "Escreva uma mensagem com pelo menos 10 caracteres.").max(1000),
});

export type ContactActionState = { error?: string; success?: string; fieldErrors?: Record<string, string> } | null;

export async function sendContactMessageAction(_prev: ContactActionState, formData: FormData): Promise<ContactActionState> {
  const parsed = schema.safeParse({
    name: formData.get("name"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    subject: formData.get("subject"),
    message: formData.get("message"),
  });

  if (!parsed.success) {
    const fieldErrors: Record<string, string> = {};
    for (const issue of parsed.error.issues) {
      const key = String(issue.path[0] ?? "form");
      if (!fieldErrors[key]) fieldErrors[key] = issue.message;
    }
    return { fieldErrors };
  }

  const user = await getCurrentUser();

  await prisma.contactMessage.create({
    data: {
      name: parsed.data.name,
      email: parsed.data.email,
      phone: parsed.data.phone || null,
      subject: parsed.data.subject,
      message: parsed.data.message,
      userId: user?.id,
    },
  });

  await sendMail({
    to: process.env.SMTP_FROM || "contato@habitou.com.br",
    subject: `Novo contato: ${parsed.data.subject}`,
    html: `<p><strong>${parsed.data.name}</strong> (${parsed.data.email}) enviou:</p><p>${parsed.data.message}</p>`,
  });

  return { success: "Mensagem enviada! Normalmente respondemos em até 2 horas úteis." };
}
