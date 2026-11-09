// En local : http://127.0.0.1:8000/api/v1. En production : définir VITE_API_BASE_URL avant `npm run build`.
const configured = (import.meta.env.VITE_API_BASE_URL as string | undefined)?.trim();

export const API_BASE_URL = (configured || 'http://127.0.0.1:8000/api/v1').replace(/\/+$/, '');