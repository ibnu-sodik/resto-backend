<?php

namespace App\Http\Controllers\API;

use App\Models\Food;
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
            'order_by' => $validated['order_by']
        ]);

        $table->update(['status' => 'occupied']);

        return ApiResponse::success('Order berhasil dibuka.', $order, 201);
    }

    /**
     * Tambah makanan ke order
     *
     * @OA\Post(
     *      path="/orders/{id}/add-items",
     *      tags={"OrdersController"},
     *      summary="Tambah makanan ke order",
     *      description="Menambahkan makanan/minuman/snack ke order yang masih open. Hanya bisa dilakukan oleh pelayan.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID order",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"food_id","qty"},
     *              @OA\Property(property="food_id", type="string", format="uuid", example="UUID food"),
     *              @OA\Property(property="qty", type="integer", example=2)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Makanan berhasil ditambahkan",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="Makanan berhasil ditambahkan"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="order", type="object",
     *                      @OA\Property(property="id", type="string", format="uuid"),
     *                      @OA\Property(property="table_id", type="string", format="uuid"),
     *                      @OA\Property(property="status", type="string", example="open"),
     *                      @OA\Property(property="total_price", type="number", example=45000),
     *                      @OA\Property(property="items", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="string", format="uuid"),
     *                              @OA\Property(property="food_id", type="string", format="uuid"),
     *                              @OA\Property(property="qty", type="integer", example=2),
     *                              @OA\Property(property="price", type="number", example=22500),
     *                              @OA\Property(property="subtotal", type="number", example=45000),
     *                              @OA\Property(property="food", type="object",
     *                                  @OA\Property(property="id", type="string", format="uuid"),
     *                                  @OA\Property(property="name", type="string", example="Bakso"),
     *                                  @OA\Property(property="price", type="number", example=22500),
     *                                  @OA\Property(property="category", type="string", example="food"),
     *                                  @OA\Property(property="is_active", type="boolean", example=true)
     *                              )
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=403, description="Tidak memiliki akses"),
     *      @OA\Response(response=404, description="Order atau makanan tidak ditemukan"),
     *      @OA\Response(response=409, description="Makanan tidak tersedia"),
     *      @OA\Response(response=422, description="Validasi gagal")
     * )
     */

    public function addFood(Request $request, string $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses.', 403);
        }

        $order = Order::find($id);
        if (!$order || $order->status !== 'open') {
            return ApiResponse::error('Order tidak ditemukan atau sudah ditutup.', 404);
        }

        $validated = $request->validate([
            'food_id'  => 'required|exists:foods,id',
            'qty' => 'required|integer|min:1'
        ]);

        $food = Food::find($validated['food_id']);
        if (!$food) {
            return ApiResponse::error('Makanan tidak ditemukan', 404);
        }

        if ($food->is_active !== 1) {
            return ApiResponse::error('Makanan tidak tersedia.', 409);
        }

        // Cek apakah makanan sudah ada di order_items
        $checkItem = $order->items()->where('food_id', $food->id)->first();
        if ($checkItem) {
            // Update qty dan subtotal jika sudah ada
            $checkItem->qty += $validated['qty'];
            $checkItem->subtotal = $checkItem->qty * $checkItem->price;
            $checkItem->save();
        } else {
            // Tambahkan baru
            $order->items()->create([
                'food_id'  => $food->id,
                'qty' => $validated['qty'],
                'price'    => $food->price,
                'subtotal' => $food->price * $validated['qty'],
            ]);
        }

        $total = $order->items()->sum('subtotal');
        $order->total_price = $total;
        $order->save();

        return ApiResponse::success('Makanan berhasil ditambahkan', [
            'order' => $order->load('items.food')
        ]);
    }
}
