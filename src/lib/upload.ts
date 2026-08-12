import "server-only";
import { randomUUID } from "node:crypto";
import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";

const ALLOWED_MIME_TYPES: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
};

export const MAX_UPLOAD_SIZE_BYTES = 8 * 1024 * 1024; // 8MB

export class UploadError extends Error {}

/**
 * Salva um arquivo de imagem enviado, validando extensão/MIME/tamanho.
 * Armazenamento local em /public/uploads — trocar por um provedor de objetos
 * (S3, R2, etc.) em produção configurando STORAGE_* no .env.
 */
export async function saveUploadedImage(file: File, subdir: string): Promise<{ url: string; sizeBytes: number }> {
  if (!(file.type in ALLOWED_MIME_TYPES)) {
    throw new UploadError("Formato de imagem não suportado. Envie JPG, PNG ou WEBP.");
  }
  if (file.size > MAX_UPLOAD_SIZE_BYTES) {
    throw new UploadError("Arquivo muito grande. O limite é 8MB.");
  }

  const ext = ALLOWED_MIME_TYPES[file.type];
  const fileName = `${randomUUID()}.${ext}`;
  const dir = path.join(process.cwd(), "public", "uploads", subdir);
  await mkdir(dir, { recursive: true });

  const buffer = Buffer.from(await file.arrayBuffer());
  await writeFile(path.join(dir, fileName), buffer);

  return { url: `/uploads/${subdir}/${fileName}`, sizeBytes: buffer.byteLength };
}
