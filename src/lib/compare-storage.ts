export const COMPARE_STORAGE_KEY = "habitou:compare";
export const COMPARE_EVENT = "habitou:compare-change";
export const MAX_COMPARE_ITEMS = 4;

export type CompareItem = { id: string; title: string; imageUrl?: string };

export function readCompareList(): CompareItem[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(COMPARE_STORAGE_KEY);
    return raw ? (JSON.parse(raw) as CompareItem[]) : [];
  } catch {
    return [];
  }
}

function writeCompareList(items: CompareItem[]) {
  window.localStorage.setItem(COMPARE_STORAGE_KEY, JSON.stringify(items));
  window.dispatchEvent(new CustomEvent(COMPARE_EVENT));
}

export function isInCompareList(id: string): boolean {
  return readCompareList().some((i) => i.id === id);
}

export function toggleCompareItem(item: CompareItem): CompareItem[] {
  const current = readCompareList();
  const exists = current.some((i) => i.id === item.id);
  const next = exists
    ? current.filter((i) => i.id !== item.id)
    : [...current, item].slice(0, MAX_COMPARE_ITEMS);
  writeCompareList(next);
  return next;
}

export function removeCompareItem(id: string): CompareItem[] {
  const next = readCompareList().filter((i) => i.id !== id);
  writeCompareList(next);
  return next;
}

export function clearCompareList(): CompareItem[] {
  writeCompareList([]);
  return [];
}
