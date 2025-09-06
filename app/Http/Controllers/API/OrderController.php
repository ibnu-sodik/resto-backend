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
     * @OA\Get(
     *      path="/orders",
     *      tags={"OrdersController"},
     *      summary="Daftar order",
     *      description="Mengambil daftar semua order beserta informasi meja (code) dan jumlah item di dalamnya",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Daftar order berhasil diambil",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="Daftar order"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="string", format="uuid", example="f05611aa-1923-47e0-bb8b-446315b9c1a3"),
     *                      @OA\Property(property="table_id", type="string", format="uuid", example="212fa4b3-865f-43ed-bcad-fedd1250c033"),
     *                      @OA\Property(property="order_by", type="string", example="Test Order"),
     *                      @OA\Property(property="status", type="string", example="open"),
     *                      @OA\Property(property="payment_status", type="string", example="unpaid"),
     *                      @OA\Property(property="delivery_status", type="string", example="pending"),
     *                      @OA\Property(property="total_price", type="string", example="148000.00"),
     *                      @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T17:38:19.000000Z"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-04T17:54:22.000000Z"),
     *                      @OA\Property(property="items_count", type="integer", example=3),
     *                      @OA\Property(property="table_code", type="string", example="T01")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $orders = Order::select('orders.*')
            ->withCount('items')
            ->join('tables', 'tables.id', '=', 'orders.table_id')
            ->addSelect('tables.code as table_code')
            ->get();
        // $orders = Order::with(['table', 'items.food'])->get();
        return ApiResponse::success('Daftar order', $orders);
    }

    /**
     * Show order detail
     * @OA\Get(
     *      path="/orders/{id}",
     *      tags={"OrdersController"},
     *      summary="Detail order",
     *      description="Menampilkan detail order beserta data meja dan daftar item makanan yang dipesan.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="UUID Order",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Detail order berhasil ditampilkan",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Detail order"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="string", format="uuid", example="uuid-order"),
     *                  @OA\Property(property="table_id", type="string", format="uuid", example="uuid-table"),
     *                  @OA\Property(property="order_by", type="string", example="Test Order"),
     *                  @OA\Property(property="status", type="string", example="open"),
     *                  @OA\Property(property="payment_status", type="string", example="unpaid"),
     *                  @OA\Property(property="delivery_status", type="string", example="pending"),
     *                  @OA\Property(property="total_price", type="string", example="148000.00"),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T17:38:19.000000Z"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-04T17:54:22.000000Z"),
     *                  @OA\Property(
     *                      property="table",
     *                      type="object",
     *                      @OA\Property(property="id", type="string", format="uuid", example="uuid-table"),
     *                      @OA\Property(property="code", type="string", example="T01"),
     *                      @OA\Property(property="status", type="string", example="occupied")
     *                  ),
     *                  @OA\Property(
     *                      property="items",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="string", format="uuid", example="uuid-order-item"),
     *                          @OA\Property(property="food_id", type="string", format="uuid", example="uuid-food"),
     *                          @OA\Property(property="qty", type="integer", example=2),
     *                          @OA\Property(property="price", type="string", example="25000.00"),
     *                          @OA\Property(property="subtotal", type="string", example="50000.00"),
     *                          @OA\Property(
     *                              property="food",
     *                              type="object",
     *                              @OA\Property(property="id", type="string", format="uuid", example="uuid-food"),
     *                              @OA\Property(property="name", type="string", example="Bakso"),
     *                              @OA\Property(property="category", type="string", example="food"),
     *                              @OA\Property(property="price", type="string", example="25000.00")
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=404, description="Order tidak ditemukan"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(string $id)
    {
        $order = Order::with(['table', 'items.food'])->find($id);

        if (!$order) {
            return ApiResponse::error('Order tidak ditemukan', 404);
        }
        return ApiResponse::success('Detail order', $order);
    }

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
     * Tambah item ke order
     *
     * @OA\Post(
     *      path="/orders/{id}/add-items",
     *      tags={"OrdersController"},
     *      summary="Tambah item ke order",
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
     *          description="Item berhasil ditambahkan",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="Item berhasil ditambahkan"),
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
     *      @OA\Response(response=404, description="Order atau item tidak ditemukan"),
     *      @OA\Response(response=409, description="Item tidak tersedia"),
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
            return ApiResponse::error('Item tidak ditemukan', 404);
        }

        if ($food->is_active !== 1) {
            return ApiResponse::error('Item tidak tersedia.', 409);
        }

        $checkItem = $order->items()->where('food_id', $food->id)->first();
        if ($checkItem) {
            $checkItem->qty += $validated['qty'];
            $checkItem->subtotal = $checkItem->qty * $checkItem->price;
            $checkItem->save();
        } else {
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

        return ApiResponse::success('Item berhasil ditambahkan', [
            'order' => $order->load('items.food')
        ]);
    }

    /**
     * Update jumlah item di order
     * @OA\Put(
     *      path="/orders/{orderId}/items/{itemId}",
     *      tags={"OrdersController"},
     *      summary="Update jumlah item di order",
     *      description="Mengubah jumlah (qty) item yang sudah ada di dalam order. Hanya bisa dilakukan oleh pelayan dan ketika order masih open.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="orderId",
     *          in="path",
     *          required=true,
     *          description="ID Order",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Parameter(
     *          name="itemId",
     *          in="path",
     *          required=true,
     *          description="ID Item di order_items",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"qty"},
     *              @OA\Property(property="qty", type="integer", example=3)
     *          )
     *      ),
     *      @OA\Response(response=200, description="Item order berhasil diperbarui"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Tidak ada akses"),
     *      @OA\Response(response=404, description="Order atau item tidak ditemukan"),
     *      @OA\Response(response=422, description="Validasi gagal")
     * )
     */

    public function updateFood(Request $request, string $orderId, string $itemId)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses.', 403);
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'open') {
            return ApiResponse::error('Order tidak ditemukan atau sudah ditutup.', 404);
        }

        $item = $order->items()->where('id', $itemId)->first();
        if (!$item) {
            return ApiResponse::error('Item tidak ditemukan dalam order ini.', 404);
        }

        $validated = $request->validate([
            'qty' => 'required|integer|min:1'
        ]);

        $item->qty = $validated['qty'];
        $item->subtotal = $item->qty * $item->price;
        $item->save();

        $order->total_price = $order->items()->sum('subtotal');
        $order->save();

        return ApiResponse::success('Item order berhasil diperbarui', [
            'order' => $order->load('items.food')
        ]);
    }

    /**
     * Hapus item dari order
     * @OA\Delete(
     *      path="/orders/{orderId}/items/{itemId}",
     *      tags={"OrdersController"},
     *      summary="Hapus item dari order",
     *      description="Menghapus item dari order. Hanya bisa dilakukan oleh pelayan dan ketika order masih open.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="orderId",
     *          in="path",
     *          required=true,
     *          description="ID Order",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Parameter(
     *          name="itemId",
     *          in="path",
     *          required=true,
     *          description="ID Item di order_items",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(response=200, description="Item order berhasil dihapus"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Tidak ada akses"),
     *      @OA\Response(response=404, description="Order atau item tidak ditemukan")
     * )
     */
    public function deleteFood(Request $request, string $orderId, string $itemId)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses.', 403);
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'open') {
            return ApiResponse::error('Order tidak ditemukan atau sudah ditutup.', 404);
        }

        $item = $order->items()->where('id', $itemId)->first();
        if (!$item) {
            return ApiResponse::error('Item tidak ditemukan dalam order ini.', 404);
        }

        $item->delete();

        $order->total_price = $order->items()->sum('subtotal');
        $order->save();

        return ApiResponse::success('Item order berhasil dihapus', [
            'order' => $order->load('items.food')
        ]);
    }

    /**
     * Close order
     * @OA\Post(
     *      path="/orders/{id}/close",
     *      tags={"OrdersController"},
     *      summary="Menutup order",
     *      description="Menutup order yang masih open, sekaligus menandai order sudah dibayar dan delivered. Hanya bisa dilakukan oleh pelayan atau kasir.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="UUID Order",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order berhasil ditutup",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Order berhasil ditutup."),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="string", format="uuid", example="uuid-order"),
     *                  @OA\Property(property="table_id", type="string", format="uuid", example="uuid-table"),
     *                  @OA\Property(property="order_by", type="string", example="Test Order"),
     *                  @OA\Property(property="status", type="string", example="closed"),
     *                  @OA\Property(property="payment_status", type="string", example="paid"),
     *                  @OA\Property(property="delivery_status", type="string", example="delivered"),
     *                  @OA\Property(property="total_price", type="string", example="148000.00"),
     *                  @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T17:38:19.000000Z"),
     *                  @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-04T17:54:22.000000Z")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=404, description="Order tidak ditemukan atau sudah ditutup"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="User tidak memiliki akses")
     * )
     */

    public function close(Request $request, string $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['pelayan', 'kasir'])) {
            return ApiResponse::error('Anda tidak memiliki akses.', 403);
        }

        $order = Order::find($id);
        if (!$order || $order->status !== 'open') {
            return ApiResponse::error('Order tidak ditemukan atau sudah ditutup.', 404);
        }

        $table = Tables::find($order->table_id);
        $order->update([
            'status' => 'closed',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered'
        ]);

        $table->update(['status' => 'available']);
        return ApiResponse::success('Order berhasil ditutup.', $order, 200);
    }
}