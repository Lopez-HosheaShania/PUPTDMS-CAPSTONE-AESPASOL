<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExistingRecordController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->routeIs('admin.*')
            ? 'admin'
            : 'dentist';

        $existingAppointmentRoute = $role === 'admin'
            ? 'admin.odontogram.existing-appointment.create'
            : 'dentist.odontogram.existing-appointment.create';

        return view('shared.add-existing-record', [
            'layoutRole' => $role,
            'patientSearchRoute' => 'shared.existing-record.search-patient',
            'existingAppointmentRoute' => $existingAppointmentRoute,
        ]);
    }
}