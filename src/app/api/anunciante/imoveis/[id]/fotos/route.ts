import { NextResponse } from "next/server";
import { requireUser } from "@/lib/auth/guards";
import { saveUploadedImage, UploadError } from "@/lib/upload";
import { addPropertyImage } from "@/server/services/property-mutations";
import { AuthError } from "@/lib/auth/guards";

export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  try {
    const user = await requireUser();
    const formData = await request.formData();
    const files = formData.getAll("files").filter((f): f is File => f instanceof File);

    if (files.length === 0) {
      return NextResponse.json({ error: "Nenhum arquivo enviado." }, { status: 400 });
    }

    const created = [];
    for (const file of files) {
      const { url, sizeBytes } = await saveUploadedImage(file, `properties/${id}`);
      created.push(await addPropertyImage(id, url, user, { sizeBytes }));
    }

    return NextResponse.json({ images: created }, { status: 201 });
  } catch (err) {
    if (err instanceof AuthError) return NextResponse.json({ error: err.message }, { status: err.status });
    if (err instanceof UploadError) return NextResponse.json({ error: err.message }, { status: 400 });
    console.error(err);
    return NextResponse.json({ error: "Erro ao enviar imagem." }, { status: 500 });
  }
}
