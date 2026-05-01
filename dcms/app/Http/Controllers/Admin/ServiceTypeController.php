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
        $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        ServiceType::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active_for_booking' => true,
            'is_default' => false,
        ]);

        return back()->with('success', 'Service type added');
    }


    public function update(Request $request, $id)
    {
        $service = ServiceType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name,' . $service->id,
            'description' => 'nullable|string|max:255',
        ]);

        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active_for_booking' => $request->has('is_active_for_booking'),
        ]);

        return back()->with('success', 'Service updated');
    }


    public function destroy($id)
    {
        $service = ServiceType::findOrFail($id);

        if ($service->is_default) {
                return back()->with('error', 'Default services cannot be deleted.');
            }

            $service->delete();

            return back()->with('success', 'Service type deleted');
    }
}