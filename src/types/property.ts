import type { Prisma } from "@prisma/client";
import type { propertyListItemInclude, propertyDetailInclude } from "@/server/services/property-service";

export type PropertyListItem = Prisma.PropertyGetPayload<{ include: typeof propertyListItemInclude }>;
export type PropertyDetail = Prisma.PropertyGetPayload<{ include: typeof propertyDetailInclude }>;
