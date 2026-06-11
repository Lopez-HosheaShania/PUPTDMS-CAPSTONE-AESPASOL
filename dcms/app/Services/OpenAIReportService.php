<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAIReportService
{
    public function generate(array $reportData): ?array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.report_model', 'gpt-5.4-mini');

        if (!$apiKey) {
            return null;
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'executive_summary' => [
                    'type' => 'string',
                ],
                'key_findings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'treatment_analysis' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'inventory_analysis' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'executive_summary',
                'key_findings',
                'treatment_analysis',
                'inventory_analysis',
                'recommendations',
            ],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->retry(2, 500)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'instructions' => implode("\n", [
                        'You are an administrative dental clinic report analyst.',
                        'Generate a concise, formal, and professional AI-generated report.',
                        'Use only the provided aggregate system data.',
                        'Do not invent patient counts, treatment counts, inventory counts, or percentages.',
                        'Do not mention that data is missing unless the provided values are zero or empty.',
                        'Keep the report suitable for a Dental Management System administrative report.',
                    ]),
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => "Generate the report using this system data:\n" .
                                        json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'dental_ai_report',
                            'strict' => true,
                            'schema' => $schema,
                        ],
                    ],
                    'max_output_tokens' => 2000,
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAI report generation failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = $this->extractOutputText($response->json());

            if (!$text) {
                Log::warning('OpenAI report generation returned empty output.', [
                    'response' => $response->json(),
                ]);

                return null;
            }

            $decoded = json_decode($text, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            Log::error('OpenAI report generation exception.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extractOutputText(array $payload): ?string
    {
        if (!empty($payload['output_text']) && is_string($payload['output_text'])) {
            return trim($payload['output_text']);
        }

        foreach (($payload['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (isset($content['text']) && is_string($content['text'])) {
                    return trim($content['text']);
                }
            }
        }

        return null;
    }
}