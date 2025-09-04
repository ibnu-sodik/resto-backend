<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tables;

class TablesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tables",
     *     tags={"TablesController"},
     *     summary="Get all meja",
     *     description="Mengambil semua daftar meja yang tersedia.",
     *     @OA\Response(
     *         response=200,
     *         description="Daftar meja berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Daftar meja"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="uuid-123"),
     *                     @OA\Property(property="code", type="string", example="T01"),
     *                     @OA\Property(property="capacity", type="number", example=2),
     *                     @OA\Property(property="status", type="string", enum={"available", "occupied", "reserved", "inactive"}, example="available"),
     *                     @OA\Property(property="order_by", type="string", example="Ibnu"),
     *                 )
     *             )
     *         )
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $tables = Tables::all();
        return ApiResponse::success('Daftar meja', $tables);
    }

    /**
     * Store a newly created resource in storage.
     * @OA\Post(
     *     path="/tables",
     *     tags={"TablesController"},
     *     summary="Add data meja",
     *     description="Menambahkan data meja baru. (hanya user dengan role pelayan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","status"},
     *             @OA\Property(property="code", type="string", example="T01"),
     *             @OA\Property(property="capacity", type="integer", example=4),
     *             @OA\Property(property="status", type="string", enum={"available"}, example="available"),
     *         )
     *     ),
     *     @OA\Response(response=201, description="Data berhasil disimpan"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *     @OA\Response(response=409, description="Data sudah ada"),
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
            'code' => 'required|string|max:3',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'required|in:available'
        ]);

        $checkData = Tables::where('code', $validated['code'])->first();

        if ($checkData) {
            $message = 'Meja ' . strtoupper($validated['code']) . ' sudah ada.';
            return ApiResponse::error($message, 409);
        }

        $table = Tables::create($validated);

        return ApiResponse::success('Data berhasil disimpan.', $table, 201);
    }

    /**
     * Display the specified resource.
     * @OA\Get(
     *     path="/tables/{id}",
     *     tags={"TablesController"},
     *     summary="Detail meja",
     *     description="Mengambil detail meja berdasarkan id. (hanya user dengan role pelayan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID tables",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Detail data meja berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *     @OA\Response(response=404, description="Data tidak ditemukan"),
     * )
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data meja.', 403);
        }

        $tables = Tables::find($id);
        if (!$tables) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        return ApiResponse::success('Detail meja', $tables);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/tables/{id}",
     *     tags={"TablesController"},
     *     summary="Update data meja",
     *     description="Memperbarui data meja berdasarkan ID. Hanya user dengan role pelayan yang dapat melakukan ini. Meja dengan status 'reserved' tidak dapat diubah.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID meja",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","status"},
     *             @OA\Property(property="code", type="string", example="T01"),
     *             @OA\Property(property="capacity", type="integer", minimum=0, example=4),
     *             @OA\Property(property="status", type="string", enum={"available", "inactive"}, example="available")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Data berhasil diperbarui"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk mengupdate data"),
     *     @OA\Response(response=404, description="Data tidak ditemukan"),
     *     @OA\Response(response=409, description="Data tidak dapat diperbarui karena status 'reserved' atau kode meja sudah digunakan"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data tables.', 403);
        }

        $tables = Tables::find($id);
        if (!$tables) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        if ($tables->status === 'reserved') {
            $message = 'Meja ' . strtoupper($tables->code) . ' sudah dipesan dan tidak dapat diperbarui.';
            return ApiResponse::error($message, 409);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:3',
            'capacity' => 'sometimes|numeric|min:0',
            'status' => 'required|in:available,inactive',
        ]);

        $checkData = Tables::where('code', $validated['code'])
            ->where('id', '!=', $tables->id)
            ->first();

        if ($checkData) {
            $message = strtoupper($validated['code']) . ' sudah ada.';
            return ApiResponse::error($message, 409);
        }

        $tables->update($validated);

        return ApiResponse::success('Data berhasil diperbarui', $tables);
    }

    /**
     * Remove the specified resource from storage.
     * @OA\Delete(
     *      path="/tables/${id}",
     *      tags={"TablesController"},
     *      summary="Delete meja",
     *      description="Hapus data meja berdasarkan id. (hanya user dengan role pelayan)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="UUID tables",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *      @OA\Response(response=200, description="Data berhasil dihapus"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="User tidak memiliki akses untuk menambahkan data"),
     *      @OA\Response(response=404, description="Data tidak ditemukan"),
     *     @OA\Response(response=409, description="Meja tidak bisa dihapus."),
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data meja.', 403);
        }

        $tables = Tables::find($id);
        if (!$tables) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        if ($tables->status !== 'available' && $tables->status !== 'inactive') {
            $message = 'Meja ' . strtoupper($tables->code) . ' tidak bisa dihapus.';
            return ApiResponse::error($message, 409);
        }

        $tables->delete();
        return ApiResponse::success('Data berhasil dihapus', null);
    }

    /**
     * Melakukan reservasi meja berdasarkan ID.
     *
     * @OA\Post(
     *     path="/tables/{id}/reservation",
     *     tags={"TablesController"},
     *     summary="Reservasi meja",
     *     description="Melakukan reservasi pada meja yang tersedia. Hanya user dengan role pelayan yang dapat mengakses endpoint ini.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID meja yang ingin direservasi",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reserved_by"},
     *             @OA\Property(property="reserved_by", type="string", example="Budi Santoso")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reservasi berhasil dilakukan"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="User tidak memiliki akses untuk melakukan reservasi"),
     *     @OA\Response(response=404, description="Data meja tidak ditemukan"),
     *     @OA\Response(response=409, description="Meja tidak bisa direservasi."),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */

    public function reservation(Request $request, string $id)
    {
        $user = $request->user();
        if ($user->role !== 'pelayan') {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengambil data meja.', 403);
        }

        $tables = Tables::find($id);
        if (!$tables) {
            return ApiResponse::error('Data tidak ditemukan', 404);
        }

        if ($tables->status !== 'available') {
            $message = 'Meja ' . strtoupper($tables->code) . ' tidak bisa direservasi.';
            return ApiResponse::error($message, 409);
        }

        $validated = $request->validate([
            'reserved_by' => 'required|string|max:100',
        ]);

        $tables->update([
            'status'   => 'reserved',
            'reserved_by' => $validated['reserved_by'],
        ]);

        $message = 'Meja ' . strtoupper($tables->code) . ' berhasil direservasi.';
        return ApiResponse::success($message, $tables);
    }
}