import "server-only";
import { getCurrentUser, type SafeUser } from "@/lib/auth/session";
import type { UserRole } from "@prisma/client";

export class AuthError extends Error {
  status: number;
  constructor(message: string, status = 401) {
    super(message);
    this.status = status;
  }
}

/** Retorna o usuário autenticado ou lança AuthError (401). */
export async function requireUser(): Promise<SafeUser> {
  const user = await getCurrentUser();
  if (!user) throw new AuthError("Você precisa estar autenticado.", 401);
  return user;
}

/** Exige que o usuário autenticado possua um dos papéis informados (403 caso contrário). */
export async function requireRole(roles: UserRole[]): Promise<SafeUser> {
  const user = await requireUser();
  if (!roles.includes(user.role)) {
    throw new AuthError("Você não tem permissão para esta ação.", 403);
  }
  return user;
}

const AGENCY_ROLES: UserRole[] = ["AGENCY_ADMIN", "AGENT"];
const STAFF_ROLES: UserRole[] = ["ADMIN"];

export function isAdmin(user: SafeUser | null): boolean {
  return user?.role === "ADMIN";
}

export function isAgencyMember(user: SafeUser | null): boolean {
  return !!user && AGENCY_ROLES.includes(user.role);
}

/**
 * Um imóvel só pode ser editado por: quem o anunciou, o administrador,
 * ou um membro (corretor/admin) da mesma imobiliária do imóvel.
 */
export function canManageProperty(
  user: SafeUser,
  property: { advertiserId: string; agencyId: string | null }
): boolean {
  if (user.role === "ADMIN") return true;
  if (property.advertiserId === user.id) return true;
  if (
    property.agencyId &&
    user.agencyId === property.agencyId &&
    AGENCY_ROLES.includes(user.role)
  ) {
    return true;
  }
  return false;
}

export function requireStaff(user: SafeUser | null): user is SafeUser {
  return !!user && STAFF_ROLES.includes(user.role);
}
