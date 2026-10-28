import { useEffect, useState, type FormEvent } from 'react';
import { adminCommissionRulesRepository, type CommissionRule } from './adminCommissionRulesRepository';
import { adminSuppliersRepository, type Supplier } from '../admin-suppliers/adminSuppliersRepository';
import { ApiException } from '../../core/apiException';
import { useAuth } from '../auth/useAuth';

const STATUS_LABELS: Record<string, string> = { active: 'Active', retired: 'Retirée' };
const TYPE_LABELS: Record<string, string> = { percentage: 'Pourcentage', fixed: 'Fixe', combined: 'Combinée' };

function formatRate(rule: CommissionRule) {
  const parts: string[] = [];
  if (rule.percentage_bps) parts.push(`${(rule.percentage_bps / 100).toFixed(2)} %`);
  if (rule.fixed_minor) parts.push(`${rule.fixed_minor} (unités mineures)`);
  return parts.join(' + ') || '-';
}

export default function AdminCommissionRulesPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const [rules, setRules] = useState<CommissionRule[]>([]);
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [showCreate, setShowCreate] = useState(false);

  async function load() {
    setIsLoading(true);
    setError(null);
    try {
      setRules(await adminCommissionRulesRepository.list());
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Connexion impossible.');
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    load();

    if (isAdmin) {
      adminSuppliersRepository.list().then(setSuppliers).catch(() => setSuppliers([]));
    }
  }, [isAdmin]);

  async function handleRetire(id: number) {
    setBusyId(id);
    try {
      await adminCommissionRulesRepository.retire(id);
      await load();
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Action impossible.');
    } finally {
      setBusyId(null);
    }
  }

  function supplierLabel(id: number | null) {
    if (id === null) return 'Toutes (règle par défaut)';
    const supplier = suppliers.find((s) => s.id === id);
    return supplier?.business_name ?? supplier?.name ?? `Fournisseur #${id}`;
  }

  return (
    <div>
      <div className="toolbar">
        <h1>Commissions</h1>
        {isAdmin && (
          <button className="btn btn-primary" onClick={() => setShowCreate(true)}>
            + Nouvelle règle
          </button>
        )}
      </div>

      {error && <p className="auth-error">{error}</p>}

      {isLoading ? (
        <p>Chargement...</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Fournisseur</th>
              <th>Devise</th>
              <th>Type</th>
              <th>Taux / montant</th>
              <th>Valide à partir du</th>
              <th>Version</th>
              <th>Statut</th>
              {isAdmin && <th></th>}
            </tr>
          </thead>
          <tbody>
            {rules.length === 0 && (
              <tr>
                <td colSpan={isAdmin ? 8 : 7}>Aucune règle de commission.</td>
              </tr>
            )}
            {rules.map((rule) => (
              <tr key={rule.id}>
                <td>{supplierLabel(rule.supplier_id)}</td>
                <td>{rule.currency}</td>
                <td>{TYPE_LABELS[rule.type] ?? rule.type}</td>
                <td>{formatRate(rule)}</td>
                <td>{rule.valid_from}</td>
                <td>{rule.version}</td>
                <td>
                  <span className={`badge badge-${rule.status === 'active' ? 'active' : 'suspended'}`}>
                    {STATUS_LABELS[rule.status] ?? rule.status}
                  </span>
                </td>
                {isAdmin && (
                  <td>
                    {rule.status === 'active' && (
                      <button
                        className="btn btn-outline"
                        disabled={busyId === rule.id}
                        onClick={() => handleRetire(rule.id)}
                      >
                        Retirer
                      </button>
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {showCreate && (
        <CreateRuleModal
          suppliers={suppliers}
          onClose={() => setShowCreate(false)}
          onCreated={() => {
            setShowCreate(false);
            load();
          }}
        />
      )}
    </div>
  );
}

function CreateRuleModal({
  suppliers,
  onClose,
  onCreated,
}: {
  suppliers: Supplier[];
  onClose: () => void;
  onCreated: () => void;
}) {
  const [supplierId, setSupplierId] = useState('');
  const [currency, setCurrency] = useState('XAF');
  const [type, setType] = useState<'percentage' | 'fixed' | 'combined'>('percentage');
  const [percentageBps, setPercentageBps] = useState('500');
  const [fixedMinor, setFixedMinor] = useState('0');
  const [validFrom, setValidFrom] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await adminCommissionRulesRepository.create({
        supplier_id: supplierId ? Number(supplierId) : undefined,
        currency,
        type,
        percentage_bps: type !== 'fixed' ? Number(percentageBps) : undefined,
        fixed_minor: type !== 'percentage' ? Number(fixedMinor) : undefined,
        valid_from: validFrom,
      });
      onCreated();
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Création impossible.');
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="modal-backdrop">
      <form className="modal" onSubmit={handleSubmit}>
        <h2 style={{ margin: 0 }}>Nouvelle règle de commission</h2>

        <label>
          Fournisseur
          <select value={supplierId} onChange={(e) => setSupplierId(e.target.value)}>
            <option value="">Toutes (règle par défaut)</option>
            {suppliers.map((s) => (
              <option key={s.id} value={s.id}>
                {s.business_name ?? s.name ?? `#${s.id}`}
              </option>
            ))}
          </select>
        </label>

        <label>
          Devise
          <select value={currency} onChange={(e) => setCurrency(e.target.value)}>
            <option value="XAF">XAF</option>
            <option value="NGN">NGN</option>
          </select>
        </label>

        <label>
          Type
          <select value={type} onChange={(e) => setType(e.target.value as typeof type)}>
            <option value="percentage">Pourcentage</option>
            <option value="fixed">Fixe</option>
            <option value="combined">Combinée</option>
          </select>
        </label>

        {type !== 'fixed' && (
          <label>
            Pourcentage (points de base, 500 = 5 %)
            <input
              type="number"
              min={0}
              max={10000}
              value={percentageBps}
              onChange={(e) => setPercentageBps(e.target.value)}
              required
            />
          </label>
        )}

        {type !== 'percentage' && (
          <label>
            Montant fixe (unités mineures)
            <input type="number" min={0} value={fixedMinor} onChange={(e) => setFixedMinor(e.target.value)} required />
          </label>
        )}

        <label>
          Valide à partir du
          <input type="date" value={validFrom} onChange={(e) => setValidFrom(e.target.value)} required />
        </label>

        {error && <p className="auth-error">{error}</p>}

        <div className="modal-actions">
          <button type="button" className="btn btn-outline" onClick={onClose}>
            Annuler
          </button>
          <button type="submit" className="btn btn-primary" disabled={isSubmitting}>
            {isSubmitting ? 'Création...' : 'Créer'}
          </button>
        </div>
      </form>
    </div>
  );
}