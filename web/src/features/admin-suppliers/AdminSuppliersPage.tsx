import { useEffect, useState, type FormEvent } from 'react';
import { adminSuppliersRepository, type Supplier } from './adminSuppliersRepository';
import { ApiException } from '../../core/apiException';

const STATUS_LABELS: Record<string, string> = {
  pending: 'En attente',
  active: 'Actif',
  suspended: 'Suspendu',
};

const STATUS_FILTERS = ['', 'pending', 'active', 'suspended'];

export default function AdminSuppliersPage() {
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [filter, setFilter] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [showCreate, setShowCreate] = useState(false);

  async function load() {
    setIsLoading(true);
    setError(null);
    try {
      setSuppliers(await adminSuppliersRepository.list(filter || undefined));
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Connexion impossible.');
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filter]);

  async function handleApprove(id: number) {
    setBusyId(id);
    try {
      await adminSuppliersRepository.approve(id);
      await load();
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Action impossible.');
    } finally {
      setBusyId(null);
    }
  }

  async function handleSuspend(id: number) {
    setBusyId(id);
    try {
      await adminSuppliersRepository.suspend(id);
      await load();
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Action impossible.');
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div>
      <div className="toolbar">
        <h1>Fournisseurs</h1>
        <button className="btn btn-primary" onClick={() => setShowCreate(true)}>
          + Nouveau fournisseur
        </button>
      </div>

      <div style={{ marginBottom: 16 }}>
        {STATUS_FILTERS.map((status) => (
          <button
            key={status || 'all'}
            className={`btn ${filter === status ? 'btn-primary' : 'btn-outline'}`}
            onClick={() => setFilter(status)}
          >
            {status ? STATUS_LABELS[status] : 'Tous'}
          </button>
        ))}
      </div>

      {error && <p className="auth-error">{error}</p>}

      {isLoading ? (
        <p>Chargement...</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Nom</th>
              <th>Pays</th>
              <th>Catégorie</th>
              <th>Devise</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {suppliers.length === 0 && (
              <tr>
                <td colSpan={6}>Aucun fournisseur.</td>
              </tr>
            )}
            {suppliers.map((supplier) => (
              <tr key={supplier.id}>
                <td>{supplier.business_name ?? supplier.name ?? '-'}</td>
                <td>{supplier.country_code ?? '-'}</td>
                <td>{supplier.category ?? '-'}</td>
                <td>{supplier.settlement_currency ?? '-'}</td>
                <td>
                  <span className={`badge badge-${supplier.status}`}>
                    {STATUS_LABELS[supplier.status] ?? supplier.status}
                  </span>
                </td>
                <td>
                  {supplier.status !== 'active' && (
                    <button
                      className="btn btn-outline"
                      disabled={busyId === supplier.id}
                      onClick={() => handleApprove(supplier.id)}
                    >
                      Activer
                    </button>
                  )}
                  {supplier.status !== 'suspended' && (
                    <button
                      className="btn btn-outline"
                      disabled={busyId === supplier.id}
                      onClick={() => handleSuspend(supplier.id)}
                    >
                      Suspendre
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {showCreate && (
        <CreateSupplierModal
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

function CreateSupplierModal({ onClose, onCreated }: { onClose: () => void; onCreated: () => void }) {
  const [businessName, setBusinessName] = useState('');
  const [countryCode, setCountryCode] = useState('CM');
  const [category, setCategory] = useState('');
  const [settlementCurrency, setSettlementCurrency] = useState('XAF');
  const [contactEmail, setContactEmail] = useState('');
  const [contactPhone, setContactPhone] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await adminSuppliersRepository.create({
        business_name: businessName,
        country_code: countryCode,
        category,
        settlement_currency: settlementCurrency,
        contact_email: contactEmail || undefined,
        contact_phone: contactPhone || undefined,
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
        <h2 style={{ margin: 0 }}>Nouveau fournisseur</h2>

        <label>
          Nom commercial
          <input value={businessName} onChange={(e) => setBusinessName(e.target.value)} required />
        </label>

        <label>
          Pays
          <select value={countryCode} onChange={(e) => setCountryCode(e.target.value)}>
            <option value="CM">Cameroun (CM)</option>
            <option value="NG">Nigeria (NG)</option>
          </select>
        </label>

        <label>
          Catégorie
          <input value={category} onChange={(e) => setCategory(e.target.value)} required />
        </label>

        <label>
          Devise de règlement
          <select value={settlementCurrency} onChange={(e) => setSettlementCurrency(e.target.value)}>
            <option value="XAF">XAF</option>
            <option value="NGN">NGN</option>
          </select>
        </label>

        <label>
          E-mail de contact
          <input type="email" value={contactEmail} onChange={(e) => setContactEmail(e.target.value)} />
        </label>

        <label>
          Téléphone de contact
          <input value={contactPhone} onChange={(e) => setContactPhone(e.target.value)} />
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