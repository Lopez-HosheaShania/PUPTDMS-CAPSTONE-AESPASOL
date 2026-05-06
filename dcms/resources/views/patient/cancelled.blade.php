@extends('layouts.patient')

@section('title', 'Appointment Cancelled | PUP Taguig Dental Clinic')

@section('content')
<main class="pt-[100px] px-4 md:px-8 min-h-screen bg-[#f5f5f5] flex items-center justify-center">
    <div class="w-full max-w-[540px] bg-white rounded-[20px] border border-red-200 overflow-hidden">

        {{-- Colored top section --}}
        <div class="bg-[#8B0000] px-10 pt-10 pb-14 text-center relative">
            {{-- Rounded white overlap at bottom --}}
            <div class="absolute bottom-0 left-0 right-0 h-7 bg-white rounded-t-[20px]"></div>

            {{-- Icon --}}
            <div class="w-[72px] h-[72px] mx-auto rounded-full bg-white/15 border border-white/30 flex items-center justify-center mb-5 relative z-10">
                <i class="fa-solid fa-calendar-xmark text-[1.75rem] text-white"></i>
            </div>

            {{-- Title --}}
            <h1 class="text-[1.35rem] font-extrabold text-white leading-snug max-w-xs mx-auto relative z-10">
                Your appointment has been cancelled
            </h1>
        </div>

        {{-- Card body --}}
        <div class="px-10 pt-8 pb-9 text-center">

            {{-- Status badge --}}
            <div class="inline-flex items-center gap-2 bg-red-50 border border-red-200 rounded-[10px] px-4 py-2.5 mb-6">
                <i class="fa-solid fa-circle-exclamation text-[#8B0000] text-sm"></i>
                <span class="text-[#8B0000] text-[0.8rem] font-semibold tracking-wide">Cancelled by the clinic</span>
            </div>

            {{-- Message --}}
            <p class="text-gray-500 text-sm leading-relaxed mb-7">
                Your dental appointment was cancelled by the clinic. Please review your appointment
                details or schedule a new visit at your convenience.
            </p>

            {{-- Divider --}}
            <div class="border-t border-red-50 mb-7"></div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-2.5">
                <a href="{{ route('book.appointment.index') }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#8B0000] text-white font-bold text-sm hover:bg-[#6b0000] hover:-translate-y-px transition-all duration-150">
                    <i class="fa-solid fa-list-ul text-xs"></i>
                    View Appointments
                </a>
                <a href="{{ route('book.appointment.create') }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border-[1.5px] border-[#8B0000] text-[#8B0000] font-bold text-sm hover:bg-red-50 hover:-translate-y-px transition-all duration-150">
                    <i class="fa-solid fa-calendar-plus text-xs"></i>
                    Book Again
                </a>
            </div>

        </div>
    </div>
</main>
@endsection