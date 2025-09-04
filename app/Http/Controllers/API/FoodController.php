<?php

namespace App\Http\Controllers\API;

use App\Models\Food;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FoodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/foods",
     *     tags={"FoodsController"},
     *     summary="Get all foods",
     *     description="Mengambil semua daftar foods yang tersedia. (hanya user dengan role pelayan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar foods berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Daftar foods"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="uuid-123"),
     *                     @OA\Property(property="name", type="string", example="Nasi Goreng"),
     *                     @OA\Property(property="description", type="string", example="Nasi goreng dengan ayam"),
     *                     @OA\Property(property="price", type="number", format="float", example=25000),
     *                     @OA\Property(property="category", type="string", enum={"food","drink","snack"}, example="food"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk mengambil data"),
     * )
     */

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data foods.', 403);
        }
        $foods = Food::all();
        return ApiResponse::success('Daftar foods.', $foods);
    }

    /**
     * @OA\Post(
     *     path="/foods",
     *     tags={"FoodsController"},
     *     summary="Add data foods",
     *     description="Menambahkan data foods baru. (hanya user dengan role pelayan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","category"},
     *             @OA\Property(property="name", type="string", example="Nama foods"),
     *             @OA\Property(property="description", type="string", example="Deskripsi foods"),
     *             @OA\Property(property="price", type="number", format="float", example=20000),
     *             @OA\Property(property="category", type="string", enum={"food","drink","snack"}, example="food"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Data berhasil disimpan"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *     @OA\Response(response=409, description="Data foods dengan nama, harga, dan kategori yang sama sudah ada"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk menambahkan data.', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:food,drink,snack',
            'is_active' => 'boolean'
        ]);

        $checkData = Food::where('name', $validated['name'])
            ->where('price', $validated['price'])
            ->where('category', $validated['category'])
            ->first();

        if ($checkData) {
            $message = ucfirst($validated['category']) . ' ' . $validated['name'] . ' dengan harga: ' . $validated['price'] . ' sudah ada.';
            return ApiResponse::error($message, 409);
        }

        $food = Food::create($validated);

        return ApiResponse::success('Data berhasil disimpan.', $food, 201);
    }

    /**
     * @OA\Get(
     *     path="/foods/{id}",
     *     tags={"FoodsController"},
     *     summary="Detail foods",
     *     description="Mengambil detail foods berdasarkan id. (hanya user dengan role pelayan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID foods",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Detail data foods berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *     @OA\Response(response=404, description="Data tidak ditemukan"),
     * )
     */

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data foods.', 403);
        }

        $food = Food::find($id);
        if (!$food) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        return ApiResponse::success('Detail foods', $food);
    }

    /**
     * @OA\Put(
     *     path="/foods/{id}",
     *     tags={"FoodsController"},
     *     summary="Update foods",
     *     description="Memperbarui data foods berdasarkan id. (hanya user dengan role pelayan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID foods",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Nama foods"),
     *             @OA\Property(property="description", type="string", example="Deskripsi foods"),
     *             @OA\Property(property="price", type="number", format="float", example=20000),
     *             @OA\Property(property="category", type="string", enum={"food","drink","snack"}, example="food"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Data berhasil diperbarui"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *     @OA\Response(response=404, description="Data tidak ditemukan"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     * )
     */

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data foods.', 403);
        }

        $food = Food::find($id);
        if (!$food) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'category'    => 'sometimes|in:food,drink,snack',
            'is_active'   => 'boolean'
        ]);

        $checkData = Food::where('name', $validated['name'])
            ->where('price', $validated['price'])
            ->where('category', $validated['category'])
            ->where('id', '!=', $food->id)
            ->first();

        if ($checkData) {
            $message = ucfirst($validated['category']) . ' ' . $validated['name'] . ' dengan harga: ' . $validated['price'] . ' sudah ada.';
            return ApiResponse::error($message, 409);
        }

        $food->update($validated);

        return ApiResponse::success('Data berhasil diperbarui', $food);
    }

    /**
     * @OA\Delete(
     *      path="/foods/${id}",
     *      tags={"FoodsController"},
     *      summary="Delete foods",
     *      description="Hapus data foods berdasarkan id. (hanya user dengan role pelayan)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="UUID foods",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(response=200, description="Makanan berhasil dihapus"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *      @OA\Response(response=404, description="Makanan tidak ditemukan"),
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data foods.', 403);
        }

        $food = Food::find($id);
        if (!$food) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        $food->delete();
        return ApiResponse::success('Data berhasil dihapus', null);
    }
}
