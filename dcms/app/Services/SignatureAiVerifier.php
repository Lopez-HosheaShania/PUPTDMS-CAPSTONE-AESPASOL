<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignatureAiVerifier
{
    private const ACCEPTED_MESSAGE = 'Signature verified and accepted';
    private const DECLINED_MESSAGE = 'Signature could not be processed. Please try again.';

    public function verify(UploadedFile $file): array
    {
        $apiKey = config('services.openai.api_key');

        $model = trim((string) (
            config('services.openai.signature_model')
            ?: config('services.openai.model')
            ?: env('OPENAI_MODEL', 'gpt-5.4-mini')
        ));

        $threshold = (float) config('services.signature_ai.threshold', 0.75);

        if (empty($apiKey)) {
            return $this->failResult(
                'system_error',
                'OpenAI API key is not configured.'
            );
        }

        $mime = $file->getMimeType();

        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return $this->failResult(
                'invalid_file_type',
                'Only JPG and PNG images are allowed.'
            );
        }

        if ($file->getSize() > (25 * 1024 * 1024)) {
            return $this->failResult(
                'file_too_large',
                'Signature file must not exceed 25 MB.'
            );
        }

        if (!$file->isValid() || !is_readable($file->getRealPath())) {
            return $this->failResult(
                'invalid_upload',
                'Uploaded signature file is invalid or unreadable.'
            );
        }

        $imageContent = file_get_contents($file->getRealPath());

        if ($imageContent === false) {
            return $this->failResult(
                'invalid_upload',
                'Unable to read uploaded signature file.'
            );
        }

        $base64 = base64_encode($imageContent);
        $dataUrl = "data:{$mime};base64,{$base64}";

        $prompt = <<<PROMPT
You are validating an uploaded patient signature image for a dental management system.

Your task:
Decide if the uploaded image mainly contains a patient signature or initials.

ACCEPT these:
- handwritten signature on a plain or simple background
- cursive signature
- stylized written signature
- handwritten initials
- digitally drawn e-signature
- electronic signature made with a pen, mouse, touchscreen, or signature pad
- cropped photo of a real handwritten signature on paper
- clear picture of a handwritten signature where the signature is the main subject

REJECT these:
- random photos
- selfies or photos of people
- screenshots of apps, browsers, desktops, code editors, forms, or websites
- images with visible UI, menus, taskbars, buttons, windows, or panels
- documents or forms where the signature is only a small part of the page
- IDs
- logos
- icons
- QR codes or barcodes
- typed text only
- blank or almost blank images
- drawings that are not signatures

Important rules:
- Accept if the image clearly shows a signature or initials as the main subject.
- Accept both e-signatures and actual photos of handwritten signatures.
- Reject if the image is mainly a screenshot, random photo, document, form, UI, logo, ID, QR code, barcode, or typed text.
- Do not verify whether the signature belongs to the patient.
- Only decide whether the uploaded image is a valid signature image.
- If detected_type is signature, handwritten_signature, cursive_signature, initials, digital_signature, e_signature, electronic_signature, or signature_photo, then is_signature should be true.
- If detected_type is blank, photo, random_photo, selfie, document, logo, screenshot, typed_text, drawing, id, qr_code, barcode, icon, or other, then is_signature should be false.
- The reason must be specific to the uploaded image.

Return JSON only.
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->retry(1, 500)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'input_image',
                                    'image_url' => $dataUrl,
                                    'detail' => 'auto',
                                ],
                            ],
                        ],
                    ],
                    'max_output_tokens' => 300,
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'signature_validation_result',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'is_signature' => [
                                        'type' => 'boolean',
                                        'description' => 'True when the image mainly contains a signature or initials.',
                                    ],
                                    'confidence' => [
                                        'type' => 'number',
                                        'description' => 'Confidence score from 0.0 to 1.0.',
                                    ],
                                    'detected_type' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'signature',
                                            'handwritten_signature',
                                            'cursive_signature',
                                            'initials',
                                            'digital_signature',
                                            'e_signature',
                                            'electronic_signature',
                                            'signature_photo',

                                            'blank',
                                            'photo',
                                            'random_photo',
                                            'selfie',
                                            'document',
                                            'logo',
                                            'screenshot',
                                            'typed_text',
                                            'drawing',
                                            'id',
                                            'qr_code',
                                            'barcode',
                                            'icon',
                                            'other',
                                        ],
                                        'description' => 'Main detected content type of the uploaded image.',
                                    ],
                                    'reason' => [
                                        'type' => 'string',
                                        'description' => 'Specific reason based on the uploaded image.',
                                    ],
                                ],
                                'required' => [
                                    'is_signature',
                                    'confidence',
                                    'detected_type',
                                    'reason',
                                ],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAI signature verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->failResult(
                    'api_error',
                    'AI verification failed.'
                );
            }

            $body = $response->json();
            $jsonText = $this->extractOutputText($body);

            if (!$jsonText) {
                Log::warning('OpenAI signature verification missing output text', [
                    'response' => $body,
                ]);

                return $this->failResult(
                    'missing_ai_output',
                    'AI did not return a readable result.'
                );
            }

            $result = $this->decodeJsonResult($jsonText);

            if (!is_array($result)) {
                Log::warning('OpenAI signature verification invalid JSON', [
                    'json_text' => $jsonText,
                    'response' => $body,
                ]);

                return $this->failResult(
                    'invalid_ai_response',
                    'AI returned an invalid JSON response.'
                );
            }

            $aiIsSignature = (bool) ($result['is_signature'] ?? false);
            $confidence = $this->normalizeConfidence($result['confidence'] ?? 0);
            $detectedType = $this->normalizeDetectedType($result['detected_type'] ?? 'other');
            $reason = trim((string) ($result['reason'] ?? ''));

            if ($reason === '') {
                $reason = 'The uploaded image did not provide enough clear signature details.';
            }

            $validSignatureTypes = [
                'signature',
                'handwritten_signature',
                'cursive_signature',
                'initials',
                'digital_signature',
                'e_signature',
                'electronic_signature',
                'signature_photo',
            ];

            $rejectedTypes = [
                'blank',
                'photo',
                'random_photo',
                'selfie',
                'document',
                'logo',
                'screenshot',
                'typed_text',
                'drawing',
                'id',
                'qr_code',
                'barcode',
                'icon',
                'other',
            ];

            /*
             * FINAL BACKEND DECISION:
             * This is the main fix.
             *
             * The backend will accept when:
             * - detected_type is one of the valid signature types
             * - confidence is equal or above the threshold
             *
             * This avoids the bug where AI says:
             * detected_type = signature
             * confidence = 0.98
             * but is_signature = false
             *
             * In that case, this service will still accept it because the stronger
             * signal is detected_type + confidence.
             */
            $accepted =
                $confidence >= $threshold
                && in_array($detectedType, $validSignatureTypes, true)
                && !in_array($detectedType, $rejectedTypes, true);

            Log::info('Signature AI Parsed Result', [
                'accepted' => $accepted,
                'valid' => $accepted,
                'final_is_signature' => $accepted,
                'ai_is_signature' => $aiIsSignature,
                'confidence' => $confidence,
                'detected_type' => $detectedType,
                'threshold' => $threshold,
                'reason' => $reason,
            ]);

            return [
                /*
                 * These three are intentionally the same.
                 * This makes the UI and backend behave consistently even if
                 * your controller checks accepted, valid, or is_signature.
                 */
                'accepted' => $accepted,
                'valid' => $accepted,
                'is_signature' => $accepted,

                /*
                 * This keeps the original AI boolean for logging/debugging.
                 */
                'ai_is_signature' => $aiIsSignature,

                'confidence' => $confidence,
                'detected_type' => $detectedType,
                'reason' => $reason,
                'message' => $accepted
                    ? self::ACCEPTED_MESSAGE
                    : self::DECLINED_MESSAGE,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI signature verification exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->failResult(
                'exception',
                'Unable to verify signature image.'
            );
        }
    }

    private function extractOutputText(array $body): ?string
    {
        if (!empty($body['output_text']) && is_string($body['output_text'])) {
            return trim($body['output_text']);
        }

        if (!empty($body['output']) && is_array($body['output'])) {
            foreach ($body['output'] as $outputItem) {
                if (!isset($outputItem['content']) || !is_array($outputItem['content'])) {
                    continue;
                }

                foreach ($outputItem['content'] as $contentItem) {
                    if (
                        isset($contentItem['text'])
                        && is_string($contentItem['text'])
                        && trim($contentItem['text']) !== ''
                    ) {
                        return trim($contentItem['text']);
                    }

                    if (
                        isset($contentItem['type'])
                        && $contentItem['type'] === 'output_text'
                        && isset($contentItem['text'])
                        && is_string($contentItem['text'])
                        && trim($contentItem['text']) !== ''
                    ) {
                        return trim($contentItem['text']);
                    }
                }
            }
        }

        return null;
    }

    private function decodeJsonResult(string $jsonText): ?array
    {
        $jsonText = trim($jsonText);

        $decoded = json_decode($jsonText, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        /*
         * Fallback only:
         * In case the model somehow returns extra text or code fences,
         * extract the first JSON object.
         */
        if (preg_match('/\{.*\}/s', $jsonText, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeDetectedType(mixed $value): string
    {
        $type = strtolower(trim((string) $value));
        $type = str_replace(['-', ' '], '_', $type);

        $aliases = [
            'handwritten' => 'signature',
            'handwritten_sign' => 'handwritten_signature',
            'handwritten_signature_image' => 'handwritten_signature',
            'cursive' => 'cursive_signature',
            'cursive_sign' => 'cursive_signature',
            'digital' => 'digital_signature',
            'digital_sign' => 'digital_signature',
            'esignature' => 'e_signature',
            'e-signature' => 'e_signature',
            'electronic' => 'electronic_signature',
            'electronic_sign' => 'electronic_signature',
            'photo_of_signature' => 'signature_photo',
            'signature_image' => 'signature',
            'signature_like' => 'signature',
            'initial' => 'initials',
            'initial_signature' => 'initials',
            'random' => 'random_photo',
            'random_image' => 'random_photo',
            'picture' => 'photo',
            'person' => 'selfie',
            'identity_card' => 'id',
            'qr' => 'qr_code',
            'qrcode' => 'qr_code',
            'bar_code' => 'barcode',
            'typed' => 'typed_text',
            'text' => 'typed_text',
        ];

        if (isset($aliases[$type])) {
            return $aliases[$type];
        }

        $allowedTypes = [
            'signature',
            'handwritten_signature',
            'cursive_signature',
            'initials',
            'digital_signature',
            'e_signature',
            'electronic_signature',
            'signature_photo',

            'blank',
            'photo',
            'random_photo',
            'selfie',
            'document',
            'logo',
            'screenshot',
            'typed_text',
            'drawing',
            'id',
            'qr_code',
            'barcode',
            'icon',
            'other',
        ];

        return in_array($type, $allowedTypes, true) ? $type : 'other';
    }

    private function normalizeConfidence(mixed $value): float
    {
        $confidence = (float) $value;

        if ($confidence < 0) {
            return 0.0;
        }

        if ($confidence > 1) {
            return 1.0;
        }

        return $confidence;
    }

    private function failResult(string $detectedType, string $reason): array
    {
        $detectedType = $this->normalizeDetectedType($detectedType);

        Log::info('Signature AI Failed Result', [
            'accepted' => false,
            'valid' => false,
            'is_signature' => false,
            'ai_is_signature' => false,
            'confidence' => 0,
            'detected_type' => $detectedType,
            'reason' => $reason,
        ]);

        return [
            'accepted' => false,
            'valid' => false,
            'is_signature' => false,
            'ai_is_signature' => false,
            'confidence' => 0.0,
            'detected_type' => $detectedType,
            'reason' => $reason,
            'message' => self::DECLINED_MESSAGE,
        ];
    }
}