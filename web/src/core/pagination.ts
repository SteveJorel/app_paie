export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
}

export function extractPaginated<T>(response: unknown): Paginated<T> {
  if (response && typeof response === 'object' && 'data' in response) {
    const map = response as Record<string, unknown>;
    return {
      data: Array.isArray(map.data) ? (map.data as T[]) : [],
      current_page: typeof map.current_page === 'number' ? map.current_page : 1,
      last_page: typeof map.last_page === 'number' ? map.last_page : 1,
    };
  }
  return { data: [], current_page: 1, last_page: 1 };
}