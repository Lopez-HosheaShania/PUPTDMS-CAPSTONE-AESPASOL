<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignatureAiVerifier
{
    public function verify(UploadedFile $file): array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.signature_model', 'gpt-4.1-mini');
        $threshold = (float) config('services.signature_ai.threshold', 0.80);

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

        if (!$file->isValid() || !is_readable($file->getRealPath())) {
            return $this->failResult(
                'invalid_upload',
                'Uploaded signature file is invalid or unreadable.'
            );
        }

        $base64 = base64_encode(file_get_contents($file->getRealPath()));
        $dataUrl = "data:{$mime};base64,{$base64}";

        $prompt = <<<PROMPT
You are validating an uploaded patient e-signature image for a dental management system.

Your task:
Decide if the uploaded image itself is ONLY a signature or initials image.

Accept ONLY these:
- handwritten signature
- cursive signature
- stylized written signature
- drawn digital signature
- handwritten initials

Reject these:
- screenshots
- computer screens
- desktop/app/browser windows
- code editors
- user interface images
- documents or forms
- IDs
- selfies or photos of people
- logos
- icons
- QR codes or barcodes
- random photos
- typed text only
- blank or almost blank images
- drawings that are not signatures
- any image with lots of background, panels, windows, menus, or non-signature content

Strict rules:
- A valid signature image should mostly contain signature-like strokes on a plain, white, or transparent background.
- If the image looks like a screenshot, reject it.
- If the image shows an application, website, editor, desktop, taskbar, menu, or window, reject it.
- If unsure, reject it.
- Do not try to verify the identity of the patient.
- Only decide whether the image appears to contain a signature/initials.

Return JSON only.
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(45)
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
                    'max_output_tokens' => 250,
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
                                        'description' => 'True only when the image mainly contains a valid signature or initials.',
                                    ],
                                    'confidence' => [
                                        'type' => 'number',
                                        'description' => 'Confidence score from 0.0 to 1.0.',
                                    ],
                                    'detected_type' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'signature',
                                            'initials',
                                            'blank',
                                            'photo',
                                            'document',
                                            'logo',
                                            'screenshot',
                                            'typed_text',
                                            'drawing',
                                            'other',
                                        ],
                                        'description' => 'Main detected content type of the uploaded image.',
                                    ],
                                    'reason' => [
                                        'type' => 'string',
                                        'description' => 'Short reason for the decision.',
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

            $result = json_decode($jsonText, true);

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

            $isSignature = (bool) ($result['is_signature'] ?? false);
            $confidence = (float) ($result['confidence'] ?? 0);
            $detectedType = strtolower(trim((string) ($result['detected_type'] ?? 'other')));
            $reason = trim((string) ($result['reason'] ?? ''));

            $validSignatureTypes = ['signature', 'initials'];

            $accepted = $isSignature
                && $confidence >= $threshold
                && in_array($detectedType, $validSignatureTypes, true);

            Log::info('Signature AI Parsed Result', [
                'accepted' => $accepted,
                'is_signature' => $isSignature,
                'confidence' => $confidence,
                'detected_type' => $detectedType,
                'threshold' => $threshold,
                'reason' => $reason,
            ]);

            return [
                'accepted' => $accepted,
                'is_signature' => $isSignature,
                'confidence' => $confidence,
                'detected_type' => $detectedType,
                'reason' => $reason,
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
                if (
                    isset($outputItem['content'])
                    && is_array($outputItem['content'])
                ) {
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
        }

        return null;
    }

    private function failResult(string $detectedType, string $reason): array
    {
        Log::info('Signature AI Failed Result', [
            'accepted' => false,
            'is_signature' => false,
            'confidence' => 0,
            'detected_type' => $detectedType,
            'reason' => $reason,
        ]);

        return [
            'accepted' => false,
            'is_signature' => false,
            'confidence' => 0,
            'detected_type' => $detectedType,
            'reason' => $reason,
        ];
    }
}