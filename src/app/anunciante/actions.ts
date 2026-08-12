"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { requireUser, AuthError } from "@/lib/auth/guards";
import { propertyFormSchema } from "@/lib/validation/property";
import {
  createProperty,
  updateProperty,
  deleteProperty,
  setPropertyStatus,
  duplicateProperty,
  removePropertyImage,
  setPrimaryImage,
  reorderPropertyImages,
  type PropertyStatusAction,
} from "@/server/services/property-mutations";

export type PropertyActionState = {
  error?: string;
  fieldErrors?: Record<string, string>;
} | null;

function parsePropertyForm(formData: FormData) {
  return propertyFormSchema.safeParse({
    title: formData.get("title"),
    description: formData.get("description"),
    listingType: formData.get("listingType"),
    propertyType: formData.get("propertyType"),
    priceSale: formData.get("priceSale"),
    priceRent: formData.get("priceRent"),
    condoFee: formData.get("condoFee"),
    iptu: formData.get("iptu"),
    totalArea: formData.get("totalArea"),
    builtArea: formData.get("builtArea"),
    bedrooms: formData.get("bedrooms"),
    suites: formData.get("suites"),
    bathrooms: formData.get("bathrooms"),
    parkingSpaces: formData.get("parkingSpaces"),
    features: formData.getAll("features"),
    cidade: formData.get("cidade"),
    bairro: formData.get("bairro"),
    street: formData.get("street"),
    number: formData.get("number"),
    complement: formData.get("complement"),
    zipCode: formData.get("zipCode"),
    latitude: formData.get("latitude"),
    longitude: formData.get("longitude"),
  });
}

function flatten(error: { issues: { path: PropertyKey[]; message: string }[] }) {
  const out: Record<string, string> = {};
  for (const issue of error.issues) {
    const key = String(issue.path[0] ?? "form");
    if (!out[key]) out[key] = issue.message;
  }
  return out;
}

export async function createPropertyAction(
  _prev: PropertyActionState,
  formData: FormData
): Promise<PropertyActionState> {
  const user = await requireUser();
  const parsed = parsePropertyForm(formData);
  if (!parsed.success) return { fieldErrors: flatten(parsed.error) };

  let result;
  try {
    result = await createProperty(parsed.data, user);
  } catch (err) {
    if (err instanceof AuthError) return { error: err.message };
    throw err;
  }

  revalidatePath("/anunciante/imoveis");
  redirect(`/anunciante/imoveis/${result.id}/editar?criado=1`);
}

export async function updatePropertyAction(
  propertyId: string,
  _prev: PropertyActionState,
  formData: FormData
): Promise<PropertyActionState> {
  const user = await requireUser();
  const parsed = parsePropertyForm(formData);
  if (!parsed.success) return { fieldErrors: flatten(parsed.error) };

  try {
    await updateProperty(propertyId, parsed.data, user);
  } catch (err) {
    if (err instanceof AuthError) return { error: err.message };
    throw err;
  }

  revalidatePath("/anunciante/imoveis");
  revalidatePath(`/anunciante/imoveis/${propertyId}/editar`);
  return { error: undefined };
}

export async function deletePropertyAction(propertyId: string) {
  const user = await requireUser();
  await deleteProperty(propertyId, user);
  revalidatePath("/anunciante/imoveis");
}

export async function setPropertyStatusAction(propertyId: string, action: PropertyStatusAction) {
  const user = await requireUser();
  await setPropertyStatus(propertyId, action, user);
  revalidatePath("/anunciante/imoveis");
}

export async function duplicatePropertyAction(propertyId: string) {
  const user = await requireUser();
  const clone = await duplicateProperty(propertyId, user);
  revalidatePath("/anunciante/imoveis");
  redirect(`/anunciante/imoveis/${clone.id}/editar`);
}

export async function removeImageAction(propertyId: string, imageId: string) {
  const user = await requireUser();
  await removePropertyImage(imageId, user);
  revalidatePath(`/anunciante/imoveis/${propertyId}/editar`);
}

export async function setPrimaryImageAction(propertyId: string, imageId: string) {
  const user = await requireUser();
  await setPrimaryImage(imageId, user);
  revalidatePath(`/anunciante/imoveis/${propertyId}/editar`);
}

export async function reorderImagesAction(propertyId: string, orderedIds: string[]) {
  const user = await requireUser();
  await reorderPropertyImages(propertyId, orderedIds, user);
  revalidatePath(`/anunciante/imoveis/${propertyId}/editar`);
}
