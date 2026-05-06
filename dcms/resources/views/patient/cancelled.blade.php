@extends('layouts.patient')

@section('title', 'Appointment Cancelled | PUP Taguig Dental Clinic')

@section('content')
<main class="pt-[100px] px-4 md:px-8 min-h-screen bg-[#f8f8f8]">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-red-100 p-8 text-center">
        <div class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-5">
            <i class="fa-solid fa-calendar-xmark text-3xl text-[#8B0000]"></i>
        </div>

        <h1 class="text-3xl font-extrabold text-[#8B0000] mb-3">
            Your appointment has been cancelled
        </h1>

        <p class="text-gray-600 text-base leading-relaxed mb-6">
            Your dental appointment was cancelled by the clinic. Please check your appointment details
            or book a new appointment if needed.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/patient/appointments') }}"
               class="px-6 py-3 rounded-xl bg-[#8B0000] text-white font-bold hover:bg-[#6b0000] transition">
                View My Appointments
            </a>

            <a href="{{ url('/patient/appointments/book') }}"
               class="px-6 py-3 rounded-xl border border-[#8B0000] text-[#8B0000] font-bold hover:bg-red-50 transition">
                Book Again
            </a>
        </div>
    </div>
</main>
@endsection