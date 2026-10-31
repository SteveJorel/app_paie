// Montants en unités mineures (entiers) : 1 XAF = 1, 1 NGN = 100 kobo. Même règle que le serveur.
export const DECIMALS: Record<string, number> = { XAF: 0, NGN: 2 };

export function formatMinor(amountMinor: number, currency: string): string {
  const decimals = DECIMALS[currency] ?? 2;
  const factor = 10 ** decimals;
  const negative = amountMinor < 0;
  const abs = Math.abs(Math.trunc(amountMinor));

  const whole = Math.trunc(abs / factor).toLocaleString('fr-FR');
  const fraction = decimals > 0 ? `,${String(abs % factor).padStart(decimals, '0')}` : '';

  return `${negative ? '-' : ''}${whole}${fraction} ${currency}`;
}

// Les dates du serveur sont en UTC ; une valeur sans fuseau ("2026-10-26 11:00:00") est lue comme UTC.
export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '-';

  const hasZone = /T|Z$|[+-]\d{2}:?\d{2}$/.test(value);
  const date = new Date(hasZone ? value : `${value.replace(' ', 'T')}Z`);

  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('fr-FR');
}

export const STATUS_LABELS: Record<string, string> = {
  created: 'Créé',
  pending: 'En attente',
  requires_action: 'Action requise',
  paid: 'Payé',
  failed: 'Échoué',
  expired: 'Expiré',
  partially_refunded: 'Partiellement remboursé',
  refunded: 'Remboursé',
  disputed: 'Contesté',
};

export function statusBadgeClass(status: string): string {
  if (status === 'paid') return 'badge-active';
  if (status === 'failed' || status === 'expired') return 'badge-failed';
  return 'badge-pending';
}