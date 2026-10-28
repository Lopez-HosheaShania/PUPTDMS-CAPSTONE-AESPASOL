<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\ReportSignatory;
use App\Services\ReportSignatory as SignatoryService;
use App\Services\AssignedDentistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportSignatoryController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless(SignatoryService::canManage($user), 403);
        $isDentist = AssignedDentistService::isDentist($user);
        $profile = ReportSignatory::where('user_id', $user->id)->first();
        $data = $request->validateWithBag('reportSignatory', [
            'signatory_name' => ['required', 'string', 'max:120'],
            'license_number' => [$isDentist ? 'required' : 'exclude', 'string', 'max:60'],
            'signature' => [$profile ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $path = $profile?->signature_path;
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            if ($file->getMimeType() === 'image/png') {
                // FPDF cannot read interlaced or 16-bit PNGs. Re-encode uploads
                // as non-interlaced, 8-bit PNGs while preserving transparency.
                $image = @imagecreatefrompng($file->getRealPath());
                if ($image === false) {
                    throw ValidationException::withMessages(['signature' => 'This signature image could not be opened. Please choose another PNG or JPG.'])
                        ->errorBag('reportSignatory');
                }
                try {
                    imageinterlace($image, false);
                    imagesavealpha($image, true);
                    if (! imagepng($image, $file->getRealPath())) {
                        throw new \RuntimeException('Unable to prepare the signature image for PDF reports.');
                    }
                } finally {
                    imagedestroy($image);
                }
            }
            $path = $request->file('signature')->store('report-signatures/'.$user->id, 'local');
        }
        try {
            ReportSignatory::updateOrCreate(['user_id' => $user->id], [
                'name' => trim($data['signatory_name']),
                'license_number' => $isDentist ? trim($data['license_number']) : null,
                'signature_path' => $path,
            ]);
        } catch (\Throwable $exception) {
            if ($path && $path !== $profile?->signature_path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        if ($profile?->signature_path && $profile->signature_path !== $path) {
            Storage::disk('local')->delete($profile->signature_path);
        }

        return back()->with('report_signatory_saved', 'Report signatory saved. Your details will appear on report PDFs.');
    }
}
