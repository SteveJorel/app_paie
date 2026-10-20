<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SupplierStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Supplier::query()->orderBy('business_name');

        $status = SupplierStatus::tryFrom((string) $request->query('status'));
        if ($status) {
            $query->where('status', $status->value);
        }

        return SupplierResource::collection($query->paginate(20));
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = new Supplier($request->validated());
        $supplier->status = SupplierStatus::Pending->value;
        $supplier->save();

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function update(SupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    public function approve(Supplier $supplier): SupplierResource
    {
        return $this->changeStatus($supplier, SupplierStatus::Active);
    }

    public function suspend(Supplier $supplier): SupplierResource
    {
        return $this->changeStatus($supplier, SupplierStatus::Suspended);
    }

    private function changeStatus(Supplier $supplier, SupplierStatus $status): SupplierResource
    {
        $supplier->status = $status->value;
        $supplier->save();

        return new SupplierResource($supplier);
    }
}