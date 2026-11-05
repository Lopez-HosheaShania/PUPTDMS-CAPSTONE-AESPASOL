<?php

namespace App\Http\Controllers\ClinicSchedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservedBookingPeriodRequest;
use App\Models\ReservedBookingPeriod;
use App\Services\ReservedBookingPeriodService;
use App\Services\ReservedBookingInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservedBookingPeriodController extends Controller
{
    public function __construct(
        private readonly ReservedBookingPeriodService $service,
        private readonly ReservedBookingInvitationService $invitationService
    ) {}

    public function store(ReservedBookingPeriodRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Reserved booking period created successfully.');
    }

    public function update(
        ReservedBookingPeriodRequest $request,
        ReservedBookingPeriod $reservedBookingPeriod
    ): RedirectResponse {
        $this->service->update(
            $reservedBookingPeriod,
            $request->validated(),
            $request->user()
        );

        return back()->with('success', 'Reserved booking period updated successfully.');
    }

    public function destroy(
        Request $request,
        ReservedBookingPeriod $reservedBookingPeriod
    ): RedirectResponse {
        if ($reservedBookingPeriod->appointments()
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists()) {
            return back()->with(
                'error',
                'This reserved period already has booked patients and cannot be removed.'
            );
        }

        $reservedBookingPeriod->forceFill([
            'updated_by' => $request->user()?->id,
            'is_active' => false,
            'active_reserved_date' => null,
        ])->save();
        $reservedBookingPeriod->delete();
        $this->invitationService->removePeriod($reservedBookingPeriod);

        return back()->with('success', 'Reserved booking period removed.');
    }
}
