<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case RequiresAction = 'requires_action';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';

    /**
     * Statuts qui empêchent d'ouvrir un nouveau paiement sur la même commande.
     *
     * @return list<string>
     */
    public static function blockingValues(): array
    {
        return [
            self::Created->value,
            self::Pending->value,
            self::RequiresAction->value,
            self::Paid->value,
        ];
    }
}
