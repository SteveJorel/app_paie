export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
}

function asNumber(value: unknown, fallback: number): number {
  return typeof value === 'number' && Number.isFinite(value) ? value : fallback;
}

// Accepte le paginateur Laravel brut ({ data, current_page, last_page })
// ou une collection de ressources ({ data, meta: { current_page, last_page } }).
export function extractPaginated<T>(response: unknown): Paginated<T> {
  if (response && typeof response === 'object' && 'data' in response) {
    const root = response as Record<string, unknown>;
    const meta = root.meta && typeof root.meta === 'object' ? (root.meta as Record<string, unknown>) : root;

    return {
      data: Array.isArray(root.data) ? (root.data as T[]) : [],
      current_page: asNumber(meta.current_page, asNumber(root.current_page, 1)),
      last_page: asNumber(meta.last_page, asNumber(root.last_page, 1)),
    };
  }

  return { data: [], current_page: 1, last_page: 1 };
}