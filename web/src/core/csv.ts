import { DECIMALS } from './format';

// Montant en unités mineures -> nombre décimal brut (virgule), sans séparateur de milliers ni devise.
export function minorToDecimal(amountMinor: number, currency: string): string {
  const decimals = DECIMALS[currency] ?? 2;
  const factor = 10 ** decimals;
  const negative = amountMinor < 0;
  const abs = Math.abs(Math.trunc(amountMinor));
  const whole = Math.trunc(abs / factor);
  const fraction = decimals > 0 ? `,${String(abs % factor).padStart(decimals, '0')}` : '';

  return `${negative ? '-' : ''}${whole}${fraction}`;
}

function escapeCell(value: string): string {
  let text = value;

  // Évite qu'Excel interprète un texte comme une formule (sauf les nombres).
  if (/^[=+\-@\t\r]/.test(text) && !/^-?\d+(,\d+)?$/.test(text)) {
    text = `'${text}`;
  }

  return /[";\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

// CSV pour Excel français : séparateur « ; », BOM UTF-8 pour les accents.
export function downloadCsv(filename: string, headers: string[], rows: string[][]): void {
  const lines = [headers, ...rows].map((line) => line.map(escapeCell).join(';'));
  const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}