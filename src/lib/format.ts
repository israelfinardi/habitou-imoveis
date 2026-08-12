export function formatCurrencyBRL(value: number | string | null | undefined): string {
  if (value === null || value === undefined) return "Consulte";
  const n = typeof value === "string" ? Number(value) : value;
  if (Number.isNaN(n)) return "Consulte";
  return n.toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
    maximumFractionDigits: 0,
  });
}

export function formatArea(value: number | null | undefined): string {
  if (!value) return "";
  return `${value.toLocaleString("pt-BR")} m²`;
}

export function formatDate(value: Date | string | null | undefined): string {
  if (!value) return "";
  const date = typeof value === "string" ? new Date(value) : value;
  return date.toLocaleDateString("pt-BR");
}
