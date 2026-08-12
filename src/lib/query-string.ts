export function buildQueryString(
  params: Record<string, string | string[] | undefined>,
  overrides: Record<string, string | number | undefined> = {}
) {
  const usp = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined) continue;
    if (Array.isArray(value)) usp.set(key, value.join(","));
    else usp.set(key, value);
  }
  for (const [key, value] of Object.entries(overrides)) {
    if (value === undefined) usp.delete(key);
    else usp.set(key, String(value));
  }
  return usp.toString();
}
