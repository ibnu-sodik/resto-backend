<?php

namespace App\Http\Controllers\API;

use App\Models\Order;
use App\Models\Tables;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{

    /**
     * Open order
     * @OA\Post(
     *      path="/orders/open",
     *      tags={"OrdersController"},
     *      summary="Membuka order baru",
     *      description="Membuka order baru di meja yang masih kosong (status=available). Hanya bisa dilakukan jika meja belum ada order open. Hanya bisa dilakukan oleh pelayan.",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"table_id"},
     *              @OA\Property(property="table_id", type="string", format="uuid", example="UUID tables")
     *          )
     *      ),
     *      @OA\Response(response=201, description="Order berhasil dibuka"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *      @OA\Response(response=409, description="Meja sudah terpakai atau sudah ada order open"),
     *      @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function open(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk menambahkan data.', 403);
        }

        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'order_by' => 'nullable|string|max:100',
        ]);

        $table = Tables::find($validated['table_id']);
        if ($table->status !== 'available') {
            return ApiResponse::error('Meja sudah terpakai.', 409);
        }

        $existingOrder = Order::where('table_id', $table->id)
            ->where('status', 'open')
            ->first();
        if ($existingOrder) {
            return ApiResponse::error('Meja ini sudah punya order yang masih open.', 409);
        }

        $order = Order::create([
            'table_id' => $table->id,
            'status'   => 'open',
            'total_price' => 0,
        ]);

        $table->update(['status' => 'occupied']);

        return ApiResponse::success('Order berhasil dibuka.', $order, 201);
    }
}
