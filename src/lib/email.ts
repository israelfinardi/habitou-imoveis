import "server-only";
import nodemailer from "nodemailer";

let transporter: nodemailer.Transporter | null = null;

function getTransporter() {
  if (transporter) return transporter;
  if (!process.env.SMTP_HOST) return null;
  transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: Number(process.env.SMTP_PORT || 587),
    secure: Number(process.env.SMTP_PORT) === 465,
    auth: process.env.SMTP_USER
      ? { user: process.env.SMTP_USER, pass: process.env.SMTP_PASSWORD }
      : undefined,
  });
  return transporter;
}

export async function sendMail(options: { to: string; subject: string; html: string }) {
  const t = getTransporter();
  if (!t) {
    // SMTP não configurado neste ambiente: registra no log em vez de falhar.
    console.info(`[email] SMTP não configurado — e-mail para ${options.to} não enviado.`, {
      subject: options.subject,
    });
    return;
  }
  await t.sendMail({
    from: process.env.SMTP_FROM || "Habitou Imóveis <no-reply@habitou.com.br>",
    to: options.to,
    subject: options.subject,
    html: options.html,
  });
}
