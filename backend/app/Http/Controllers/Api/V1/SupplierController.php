<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SupplierStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SupplierResource::collection(
            Supplier::where('status', SupplierStatus::Active->value)
                ->orderBy('business_name')
                ->paginate(20)
        );
    }

    public function show(Supplier $supplier): SupplierResource
    {
        abort_unless($supplier->status === SupplierStatus::Active->value, 404);

        return new SupplierResource($supplier);
    }
}