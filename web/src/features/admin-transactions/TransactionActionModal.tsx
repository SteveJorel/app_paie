import { useState, type FormEvent } from 'react';
import { adminTransactionsRepository, type TransactionRow } from './adminTransactionsRepository';
import { ApiException } from '../../core/apiException';
import { formatMinor } from '../../core/format';

export type ActionType = 'refund' | 'dispute' | 'resolve';

const TITLES: Record<ActionType, string> = {
  refund: 'Rembourser la transaction',
  dispute: 'Ouvrir une contestation',
  resolve: 'Résoudre la contestation',
};

interface Props {
  type: ActionType;
  row: TransactionRow;
  onClose: () => void;
  onDone: (message: string) => void;
}

export default function TransactionActionModal({ type, row, onClose, onDone }: Props) {
  const [reason, setReason] = useState('');
  const [resolution, setResolution] = useState<'rejected' | 'upheld'>('rejected');
  const [note, setNote] = useState('');
  const [confirmed, setConfirmed] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const amount = formatMinor(row.amount_minor, row.currency);
  const needsReason = type === 'refund' || type === 'dispute';
  const canSubmit = !isSubmitting && (!needsReason || reason.trim() !== '') && (type !== 'refund' || confirmed);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      if (type === 'refund') {
        const refund = await adminTransactionsRepository.refund(row.id, reason.trim());
        onDone(`Remboursement ${refund.reference} effectué : ${amount} remboursés.`);
      } else if (type === 'dispute') {
        await adminTransactionsRepository.openDispute(row.id, reason.trim());
        onDone(`Contestation ouverte sur ${row.payment_reference}.`);
      } else {
        await adminTransactionsRepository.resolveDispute(row.id, resolution, note.trim());
        onDone(
          resolution === 'rejected'
            ? `Contestation écartée : ${row.payment_reference} est de nouveau payé.`
            : `Contestation retenue : ${row.payment_reference} reste contesté jusqu'au remboursement.`,
        );
      }
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Action impossible.');
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="modal-backdrop">
      <form className="modal" onSubmit={handleSubmit}>
        <h2 style={{ margin: 0 }}>{TITLES[type]}</h2>
        <p className="hint">
          {row.payment_reference} · {row.supplier_name} · {amount}
        </p>

        {type === 'refund' && (
          <p className="warning-text">
            Le remboursement est total, immédiat et irréversible. Un second reçu est émis et le client est notifié.
          </p>
        )}
        {type === 'dispute' && (
          <p className="hint">Le paiement passe au statut « Contesté » et le client est notifié.</p>
        )}

        {needsReason && (
          <label>
            Motif
            <textarea required maxLength={500} rows={3} value={reason} onChange={(e) => setReason(e.target.value)} />
          </label>
        )}

        {type === 'resolve' && (
          <>
            <label className="checkbox-line">
              <input
                type="radio"
                name="resolution"
                checked={resolution === 'rejected'}
                onChange={() => setResolution('rejected')}
              />
              Contestation écartée : le paiement redevient « Payé »
            </label>
            <label className="checkbox-line">
              <input
                type="radio"
                name="resolution"
                checked={resolution === 'upheld'}
                onChange={() => setResolution('upheld')}
              />
              Contestation fondée : le paiement reste « Contesté » jusqu'au remboursement
            </label>
            <label>
              Note (facultative)
              <textarea maxLength={500} rows={3} value={note} onChange={(e) => setNote(e.target.value)} />
            </label>
          </>
        )}

        {type === 'refund' && (
          <label className="checkbox-line">
            <input type="checkbox" checked={confirmed} onChange={(e) => setConfirmed(e.target.checked)} />
            Je confirme le remboursement de {amount}
          </label>
        )}

        {error && <p className="auth-error">{error}</p>}

        <div className="modal-actions">
          <button type="button" className="btn btn-outline" onClick={onClose}>
            Annuler
          </button>
          <button type="submit" className="btn btn-primary" disabled={!canSubmit}>
            {isSubmitting ? 'Traitement...' : 'Confirmer'}
          </button>
        </div>
      </form>
    </div>
  );
}