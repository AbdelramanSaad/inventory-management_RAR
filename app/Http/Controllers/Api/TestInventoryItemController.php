<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestInventoryItemController extends Controller
{
    /**
     * Display a listing of the inventory items for testing purposes.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Filtrar por warehouse_id si se proporciona
        $query = InventoryItem::query();
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        // Obtener todos los elementos de inventario
        $inventoryItems = $query->get();
        
        // Transformar los datos manualmente para evitar problemas con recursos
        $data = [];
        foreach ($inventoryItems as $item) {
            $data[] = [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'min_stock_level' => $item->min_stock_level,
                'unit_price' => $item->unit_price,
                'category' => $item->category,
                'warehouse_id' => $item->warehouse_id,
                'user_id' => $item->user_id,
                'created_at' => $item->created_at->toIso8601String(),
                'updated_at' => $item->updated_at->toIso8601String(),
            ];
        }
        
        // Devolver respuesta JSON
        return response()->json(['data' => $data], 200);
    }
    
    /**
     * Store a newly created inventory item.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validar los datos de entrada
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'image' => 'nullable|image|max:2048',
        ]);
        
        // Manejar la carga de imágenes si se proporciona
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('inventory', 'public');
        }
        
        // Crear el elemento de inventario
        $inventoryItem = InventoryItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'min_stock_level' => $validated['min_stock_level'],
            'unit_price' => $validated['unit_price'],
            'category' => $validated['category'],
            'warehouse_id' => $validated['warehouse_id'],
            'user_id' => $request->user_id ?? 1, // ID de usuario por defecto para pruebas
            'image_path' => $imagePath,
        ]);
        
        // Transformar y devolver la respuesta
        return response()->json([
            'message' => 'Inventory item created successfully',
            'data' => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'description' => $inventoryItem->description,
                'quantity' => $inventoryItem->quantity,
                'min_stock_level' => $inventoryItem->min_stock_level,
                'unit_price' => $inventoryItem->unit_price,
                'category' => $inventoryItem->category,
                'warehouse_id' => $inventoryItem->warehouse_id,
                'user_id' => $inventoryItem->user_id,
                'image_path' => $inventoryItem->image_path,
                'created_at' => $inventoryItem->created_at->toIso8601String(),
                'updated_at' => $inventoryItem->updated_at->toIso8601String(),
            ]
        ], 201);
    }
    
    /**
     * Display the specified inventory item.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Encontrar el elemento de inventario
        $inventoryItem = InventoryItem::findOrFail($id);
        
        // Transformar y devolver la respuesta
        return response()->json([
            'data' => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'description' => $inventoryItem->description,
                'quantity' => $inventoryItem->quantity,
                'min_stock_level' => $inventoryItem->min_stock_level,
                'unit_price' => $inventoryItem->unit_price,
                'category' => $inventoryItem->category,
                'warehouse_id' => $inventoryItem->warehouse_id,
                'user_id' => $inventoryItem->user_id,
                'image_path' => $inventoryItem->image_path,
                'created_at' => $inventoryItem->created_at->toIso8601String(),
                'updated_at' => $inventoryItem->updated_at->toIso8601String(),
            ]
        ], 200);
    }
    
    /**
     * Update the specified inventory item.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Encontrar el elemento de inventario
        $inventoryItem = InventoryItem::findOrFail($id);
        
        // Validar los datos de entrada
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'quantity' => 'sometimes|integer|min:0',
            'min_stock_level' => 'sometimes|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|string|max:255',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'image' => 'nullable|image|max:2048',
        ]);
        
        // Manejar la carga de imágenes si se proporciona
        if ($request->hasFile('image')) {
            // Eliminar la imagen anterior si existe
            if ($inventoryItem->image_path) {
                Storage::disk('public')->delete($inventoryItem->image_path);
            }
            
            $validated['image_path'] = $request->file('image')->store('inventory', 'public');
        }
        
        // Actualizar el elemento de inventario
        $inventoryItem->update($validated);
        
        // Transformar y devolver la respuesta
        return response()->json([
            'message' => 'Inventory item updated successfully',
            'data' => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'description' => $inventoryItem->description,
                'quantity' => $inventoryItem->quantity,
                'min_stock_level' => $inventoryItem->min_stock_level,
                'unit_price' => $inventoryItem->unit_price,
                'category' => $inventoryItem->category,
                'warehouse_id' => $inventoryItem->warehouse_id,
                'user_id' => $inventoryItem->user_id,
                'image_path' => $inventoryItem->image_path,
                'created_at' => $inventoryItem->created_at->toIso8601String(),
                'updated_at' => $inventoryItem->updated_at->toIso8601String(),
            ]
        ], 200);
    }
    
    /**
     * Remove the specified inventory item.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Encontrar el elemento de inventario
        $inventoryItem = InventoryItem::findOrFail($id);
        
        // Eliminar la imagen si existe
        if ($inventoryItem->image_path) {
            Storage::disk('public')->delete($inventoryItem->image_path);
        }
        
        // Eliminar el elemento de inventario
        $inventoryItem->delete();
        
        // Devolver la respuesta
        return response()->json(['message' => 'Inventory item deleted successfully'], 200);
    }
}
