"use client";

import dynamic from "next/dynamic";

export const PropertyMapClient = dynamic(
  () => import("@/components/property/PropertyMap").then((m) => m.PropertyMap),
  { ssr: false, loading: () => <div className="h-72 w-full animate-pulse rounded-xl bg-brand-bg-subtle" /> }
);
