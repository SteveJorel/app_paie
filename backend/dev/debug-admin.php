<?php

use App\Models\User;

$admins = User::where('role', 'admin')->get(['id', 'email', 'phone', 'status']);

foreach ($admins as $admin) {
    echo "id={$admin->id} email={$admin->email} phone={$admin->phone} status={$admin->status}\n";
}

if ($admins->isEmpty()) {
    echo "Aucun compte admin trouve.\n";
}
