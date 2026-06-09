<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceType;

class ServiceTypeController extends Controller
{
    public function index()
    {
        $services = ServiceType::orderBy('name')->get();

        return view('admin.service-types', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        $service = ServiceType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active_for_booking' => true,
            'is_default' => false,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Service type added.',
                'service' => $this->servicePayload($service),
            ]);
        }

        return back()->with('success', 'Service type added');
    }

    public function update(Request $request, $id)
    {
        $service = ServiceType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name,' . $service->id,
            'description' => 'nullable|string|max:255',
        ]);

        $service->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active_for_booking' => $request->boolean('is_active_for_booking'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Service updated.',
                'service' => $this->servicePayload($service->fresh()),
            ]);
        }

        return back()->with('success', 'Service updated');
    }

    public function destroy(Request $request, $id)
    {
        $service = ServiceType::findOrFail($id);

        if ($service->is_default) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Default services cannot be deleted.',
                ], 422);
            }

            return back()->with('error', 'Default services cannot be deleted.');
        }

        $deletedId = $service->id;
        $service->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Service type deleted.',
                'deleted_id' => $deletedId,
            ]);
        }

        return back()->with('success', 'Service type deleted');
    }

    private function servicePayload(ServiceType $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'is_active_for_booking' => (bool) $service->is_active_for_booking,
            'is_default' => (bool) $service->is_default,
        ];
    }
}
