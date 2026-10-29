<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\ReportSignatory;
use App\Services\ReportSignatory as SignatoryService;
use App\Services\AssignedDentistService;
use App\Services\SignatureAiVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportSignatoryController extends Controller
{
    private function signatureRules(bool $required, bool $drawn = false): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048',
            $drawn ? 'dimensions:max_width=4000,max_height=4000' : 'dimensions:min_width=120,min_height=60,max_width=4000,max_height=4000'];
    }

    private function isDrawnSignatureSubmission(Request $request): bool
    {
        $file = $request->file('signature');
        return $request->input('signature_source') === 'drawn'
            && $request->hasFile('signature')
            && str_starts_with($file->getClientOriginalName(), 'drawn-signature-')
            && $file->getMimeType() === 'image/png';
    }

    private function verifySignature(UploadedFile $file, SignatureAiVerifier $signatureVerifier): array
    {
        $result = $signatureVerifier->verify($file);

        if (($result['review_required'] ?? false)
            || ($result['detected_type'] ?? '') === 'manual_review_required') {
            $result['accepted'] = false;
            $result['valid'] = false;
            $result['is_signature'] = false;
            $result['message'] = $result['reason'] = 'Signature verification is currently unavailable. Please try again later. The signature has not been saved.';
        }

        return $result;
    }

    public function validateSignature(Request $request, SignatureAiVerifier $signatureVerifier)
    {
        abort_unless(SignatoryService::canManage($request->user()), 403);
        $request->validate(['signature' => $this->signatureRules(true)]);
        $result = $this->verifySignature($request->file('signature'), $signatureVerifier);

        return response()->json(array_merge($result, [
            'valid' => (bool) ($result['accepted'] ?? false),
        ]), ($result['accepted'] ?? false) ? 200 : 422);
    }

    public function update(Request $request, SignatureAiVerifier $signatureVerifier)
    {
        $user = $request->user();
        abort_unless(SignatoryService::canManage($user), 403);
        $isDentist = AssignedDentistService::isDentist($user);
        $profile = ReportSignatory::where('user_id', $user->id)->first();
        $existingSignaturePath = $profile?->signaturePath();
        $isDrawn = $this->isDrawnSignatureSubmission($request);
        $data = $request->validateWithBag('reportSignatory', [
            'signatory_name' => ['required', 'string', 'max:120', 'regex:/^[\p{L}\p{M}]+(?: +[\p{L}\p{M}]+)*$/u'],
            'license_number' => [$isDentist ? 'required' : 'exclude', 'bail', 'string', 'min:6', 'max:7', 'regex:/^[0-9]+$/D'],
            'signature' => $this->signatureRules(! $existingSignaturePath, $isDrawn),
            'signature_source' => ['nullable', 'in:upload,drawn'],
        ], [
            'signatory_name.regex' => 'Name must contain letters and spaces only.',
            'license_number.min' => 'License number is incomplete. Please enter at least 6 digits.',
            'license_number.max' => 'License number must not exceed 7 digits.',
            'license_number.regex' => 'License number must contain digits (0-9) only, with no special characters.',
        ]);

        $signatureFile = $request->file('signature');
        if (! $signatureFile && $existingSignaturePath) {

            $signatureFile = new UploadedFile(
                $existingSignaturePath,
                basename($existingSignaturePath),
                mime_content_type($existingSignaturePath),
                null,
                true,
            );
        }
        $retainingDrawing = ! $request->hasFile('signature')
            && str_starts_with($profile?->signature_path ?? '', 'report-signatures/'.$user->id.'/drawn/');
        $result = $isDrawn || $retainingDrawing
            ? ['accepted' => true]
            : $this->verifySignature($signatureFile, $signatureVerifier);
        if (! ($result['accepted'] ?? false)) {
            throw ValidationException::withMessages([
                'signature' => $result['reason'] ?? $result['message'] ?? 'The uploaded image did not pass signature validation.',
            ])->errorBag('reportSignatory');
        }

        $path = $profile?->signature_path;
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            if ($file->getMimeType() === 'image/png') {

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
            $directory = 'report-signatures/'.$user->id.($isDrawn ? '/drawn' : '');
            $path = $request->file('signature')->store($directory, 'local');
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
