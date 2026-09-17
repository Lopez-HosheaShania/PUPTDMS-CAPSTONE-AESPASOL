<?php

namespace App\Services;

use App\Helpers\PhilippineHolidays;
use App\Models\PhilippineHolidaySnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilippineHolidayService
{
    public const TYPE_REGULAR = 'regular';

    public const TYPE_SPECIAL_NON_WORKING =
    'special_non_working';

    public const TYPE_SPECIAL_WORKING =
    'special_working';

    public const TYPE_ISLAMIC =
    'islamic';

    public function forYear(
        int $year,
        bool $forceRefresh = false
    ): array {
        $freshCacheKey =
            $this->freshCacheKey(
                $year
            );

        if (
            ! $forceRefresh &&
            Cache::has(
                $freshCacheKey
            )
        ) {
            $cached =
                Cache::get(
                    $freshCacheKey
                );

            if (is_array($cached)) {
                return $cached;
            }
        }

        $remoteHolidays = null;

        if (
            $this->shouldQueryRemote(
                $year
            )
        ) {
            $remoteHolidays =
                $this->fetchRemoteYear(
                    $year
                );
        }

        if (
            is_array($remoteHolidays) &&
            $remoteHolidays !== []
        ) {
            $remoteHolidays =
                $this->applyStatutoryHolidayOverlay(
                    $remoteHolidays,
                    $year
                );

            Cache::put(
                $freshCacheKey,
                $remoteHolidays,
                now()->addHours(
                    $this->cacheHours()
                )
            );

            /*
             * Keep the most recent successful official dataset
             * indefinitely as a stale-but-known-good fallback.
             */
            Cache::forever(
                $this->lastGoodCacheKey(
                    $year
                ),
                $remoteHolidays
            );

            /*
            * Persist the most recent successful MCP dataset
            * outside Laravel's cache.
            *
            * This survives optimize:clear / cache:clear.
            */
            $this->storeDatabaseSnapshot(
                $year,
                $remoteHolidays
            );

            return $remoteHolidays;
        }

        /*
         * If the MCP becomes unavailable, prefer previously retrieved
         * official data over generated fallback dates.
         */
        $lastGood =
            Cache::get(
                $this->lastGoodCacheKey(
                    $year
                )
            );

        if (
            is_array($lastGood) &&
            $lastGood !== []
        ) {
            $lastGood =
                $this->applyStatutoryHolidayOverlay(
                    $lastGood,
                    $year
                );

            Cache::put(
                $freshCacheKey,
                $lastGood,
                now()->addHours(
                    $this->fallbackCacheHours()
                )
            );

            return $lastGood;
        }

        /*
        * Laravel's cache may have been cleared during deployment.
        *
        * Recover the most recent successfully retrieved MCP dataset
        * from the persistent database snapshot before falling back
        * to locally generated/provisional holidays.
        */
        $databaseSnapshot =
            $this->loadDatabaseSnapshot(
                $year
            );

        if (
            is_array($databaseSnapshot) &&
            $databaseSnapshot !== []
        ) {
            $databaseSnapshot =
                $this->applyStatutoryHolidayOverlay(
                    $databaseSnapshot,
                    $year
                );

            /*
            * Rehydrate both cache layers so subsequent requests
            * return to the fast cache path.
            */
            Cache::put(
                $freshCacheKey,
                $databaseSnapshot,
                now()->addHours(
                    $this->fallbackCacheHours()
                )
            );

            Cache::forever(
                $this->lastGoodCacheKey(
                    $year
                ),
                $databaseSnapshot
            );

            return $databaseSnapshot;
        }

        /*
         *
         * This preserves calendar functionality when:
         * - the external source is unavailable;
         * - a future year has not yet been officially loaded;
         * - historical data is requested.
         */
        $fallback =
            $this->fallbackForYear(
                $year
            );

        $fallback =
            $this->applyStatutoryHolidayOverlay(
                $fallback,
                $year
            );

        Cache::put(
            $freshCacheKey,
            $fallback,
            now()->addHours(
                $this->fallbackCacheHours()
            )
        );

        return $fallback;
    }

    public function range(
        int $yearsBefore = 1,
        int $yearsAfter = 1
    ): array {
        $currentYear =
            Carbon::now(
                'Asia/Manila'
            )->year;

        $holidays = [];

        for (
            $year =
                $currentYear -
                $yearsBefore;
            $year <=
                $currentYear +
                $yearsAfter;
            $year++
        ) {
            $holidays =
                array_replace(
                    $holidays,
                    $this->forYear(
                        $year
                    )
                );
        }

        ksort(
            $holidays
        );

        return $holidays;
    }

    public function holiday(
        string $date
    ): ?array {
        try {
            $iso =
                Carbon::parse(
                    $date
                )->toDateString();
        } catch (\Throwable) {
            return null;
        }

        $year =
            (int) substr(
                $iso,
                0,
                4
            );

        return $this
            ->forYear(
                $year
            )[$iso] ?? null;
    }

    public function isHoliday(
        string $date
    ): bool {
        return $this->holiday(
            $date
        ) !== null;
    }

    public function isWorkingHoliday(
        string $date
    ): bool {
        $holiday =
            $this->holiday(
                $date
            );

        if (! $holiday) {
            return false;
        }

        return (
            $holiday['is_working_day'] ?? false
        ) === true;
    }

    /**
     * Central booking rule.
     *
     * regular               => blocked
     * special_non_working   => blocked
     * special_working       => allowed
     * confirmed Islamic     => blocked
     * unconfirmed Islamic   => allowed
     */
    public function isBlockedForBooking(
        string $date
    ): bool {
        $holiday =
            $this->holiday(
                $date
            );

        if (! $holiday) {
            return false;
        }

        return (
            $holiday['is_blocked_for_booking'] ?? false
        ) === true;
    }

    private function initializeMcpSession(): ?array
    {
        try {
            $response =
                Http::withHeaders([
                    'Accept' =>
                    'application/json, text/event-stream',
                ])
                ->asJson()
                ->connectTimeout(
                    max(
                        1,
                        (int) config(
                            'services.ph_holidays.connect_timeout',
                            2
                        )
                    )
                )
                ->timeout(
                    max(
                        2,
                        (int) config(
                            'services.ph_holidays.timeout',
                            4
                        )
                    )
                )
                ->post(
                    config(
                        'services.ph_holidays.mcp_url'
                    ),
                    [
                        'jsonrpc' =>
                        '2.0',

                        'id' =>
                        'dcms-ph-holidays-init',

                        'method' =>
                        'initialize',

                        'params' => [
                            'protocolVersion' =>
                            '2025-11-25',

                            'capabilities' =>
                            new \stdClass(),

                            'clientInfo' => [
                                'name' =>
                                'pupt-dental-management-system',

                                'version' =>
                                '1.0.0',
                            ],
                        ],
                    ]
                );

            if (! $response->successful()) {
                Log::warning(
                    'PH holiday MCP initialization failed.',
                    [
                        'status' =>
                        $response->status(),

                        'body' =>
                        mb_substr(
                            $response->body(),
                            0,
                            1000
                        ),
                    ]
                );

                return null;
            }

            $sessionId =
                trim(
                    (string) $response->header(
                        'Mcp-Session-Id'
                    )
                );

            if ($sessionId === '') {
                Log::warning(
                    'PH holiday MCP initialization returned no session ID.'
                );

                return null;
            }

            $payload =
                $this->decodeMcpHttpResponse(
                    $response
                );

            $protocolVersion =
                data_get(
                    $payload,
                    'result.protocolVersion',
                    '2025-11-25'
                );

            $initialized =
                Http::withHeaders([
                    'Accept' =>
                    'application/json, text/event-stream',

                    'Mcp-Session-Id' =>
                    $sessionId,

                    'MCP-Protocol-Version' =>
                    $protocolVersion,
                ])
                ->asJson()
                ->connectTimeout(
                    max(
                        1,
                        (int) config(
                            'services.ph_holidays.connect_timeout',
                            2
                        )
                    )
                )
                ->timeout(
                    max(
                        2,
                        (int) config(
                            'services.ph_holidays.timeout',
                            4
                        )
                    )
                )
                ->post(
                    config(
                        'services.ph_holidays.mcp_url'
                    ),
                    [
                        'jsonrpc' =>
                        '2.0',

                        'method' =>
                        'notifications/initialized',
                    ]
                );

            if (
                ! in_array(
                    $initialized->status(),
                    [
                        200,
                        202,
                        204,
                    ],
                    true
                )
            ) {
                Log::warning(
                    'PH holiday MCP initialized notification failed.',
                    [
                        'status' =>
                        $initialized->status(),

                        'body' =>
                        mb_substr(
                            $initialized->body(),
                            0,
                            1000
                        ),
                    ]
                );

                return null;
            }

            return [
                'session_id' =>
                $sessionId,

                'protocol_version' =>
                $protocolVersion,
            ];
        } catch (\Throwable $exception) {
            Log::warning(
                'Unable to initialize PH holiday MCP session.',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    private function fetchRemoteYear(
        int $year
    ): ?array {
        try {
            $session =
                $this->initializeMcpSession();

            if (! $session) {
                return null;
            }

            $response =
                Http::withHeaders([
                    'Accept' =>
                    'application/json, text/event-stream',

                    'Mcp-Session-Id' =>
                    $session['session_id'],

                    'MCP-Protocol-Version' =>
                    $session['protocol_version'],
                ])
                ->asJson()
                ->connectTimeout(
                    max(
                        1,
                        (int) config(
                            'services.ph_holidays.connect_timeout',
                            2
                        )
                    )
                )
                ->timeout(
                    max(
                        2,
                        (int) config(
                            'services.ph_holidays.timeout',
                            4
                        )
                    )
                )
                ->post(
                    config(
                        'services.ph_holidays.mcp_url'
                    ),
                    [
                        'jsonrpc' =>
                        '2.0',

                        'id' =>
                        'dcms-ph-holidays-' .
                            $year,

                        'method' =>
                        'tools/call',

                        'params' => [
                            'name' =>
                            'get_holidays',

                            'arguments' => [
                                'year' =>
                                $year,
                            ],
                        ],
                    ]
                );

            if (! $response->successful()) {
                Log::warning(
                    'PH holiday MCP request failed.',
                    [
                        'year' =>
                        $year,

                        'status' =>
                        $response->status(),

                        'content_type' =>
                        $response->header(
                            'Content-Type'
                        ),

                        'body' =>
                        mb_substr(
                            $response->body(),
                            0,
                            1000
                        ),
                    ]
                );

                return null;
            }

            $payload =
                $this->decodeMcpHttpResponse(
                    $response
                );

            if (
                ! is_array($payload)
            ) {
                Log::warning(
                    'PH holiday MCP returned an unreadable response.',
                    [
                        'year' =>
                        $year,

                        'content_type' =>
                        $response->header(
                            'Content-Type'
                        ),

                        'body' =>
                        mb_substr(
                            $response->body(),
                            0,
                            1000
                        ),
                    ]
                );

                return null;
            }

            if (
                isset(
                    $payload['error']
                )
            ) {
                Log::warning(
                    'PH holiday MCP returned a JSON-RPC error.',
                    [
                        'year' =>
                        $year,

                        'error' =>
                        $payload['error'],
                    ]
                );

                return null;
            }

            $result =
                $payload['result']
                ?? null;

            if (
                ! is_array($result)
            ) {
                return null;
            }

            if (
                ($result['isError'] ?? false)
                === true
            ) {
                return null;
            }

            $toolPayload =
                $this->decodeToolResult(
                    $result
                );

            if (
                ! is_array($toolPayload)
            ) {
                return null;
            }

            $records =
                $toolPayload['data']
                ?? null;

            $meta =
                $toolPayload['_meta']
                ?? [];

            if (
                ! is_array($records)
            ) {
                return null;
            }

            return $this
                ->normalizeRemoteRecords(
                    $records,
                    is_array($meta)
                        ? $meta
                        : []
                );
        } catch (\Throwable $exception) {
            Log::warning(
                'Unable to retrieve PH holiday data.',
                [
                    'year' =>
                    $year,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    private function decodeMcpHttpResponse(
        $response
    ): ?array {
        $contentType =
            strtolower(
                trim(
                    (string) $response->header(
                        'Content-Type'
                    )
                )
            );

        if (
            str_contains(
                $contentType,
                'application/json'
            )
        ) {
            $decoded =
                $response->json();

            return is_array($decoded)
                ? $decoded
                : null;
        }

        $body =
            trim(
                $response->body()
            );

        if ($body === '') {
            return null;
        }

        /*
     * Streamable HTTP MCP may answer through SSE.
     *
     * Example:
     *
     * event: message
     * data: {"jsonrpc":"2.0", ...}
     */
        if (
            str_contains(
                $contentType,
                'text/event-stream'
            )
        ) {
            $events =
                preg_split(
                    '/\R\R+/',
                    $body
                ) ?: [];

            foreach (
                $events as $event
            ) {
                $dataLines = [];

                foreach (
                    preg_split(
                        '/\R/',
                        $event
                    ) ?: [] as $line
                ) {
                    $line =
                        trim(
                            $line
                        );

                    if (
                        ! str_starts_with(
                            $line,
                            'data:'
                        )
                    ) {
                        continue;
                    }

                    $dataLines[] =
                        trim(
                            substr(
                                $line,
                                5
                            )
                        );
                }

                if ($dataLines === []) {
                    continue;
                }

                $json =
                    implode(
                        "\n",
                        $dataLines
                    );

                if (
                    $json === '' ||
                    $json === '[DONE]'
                ) {
                    continue;
                }

                $decoded =
                    json_decode(
                        $json,
                        true
                    );

                if (
                    json_last_error()
                    === JSON_ERROR_NONE &&
                    is_array($decoded) &&
                    (
                        array_key_exists(
                            'result',
                            $decoded
                        ) ||
                        array_key_exists(
                            'error',
                            $decoded
                        )
                    )
                ) {
                    return $decoded;
                }
            }

            return null;
        }

        /*
     * Defensive fallback in case the server omitted or changed
     * its Content-Type but still returned raw JSON.
     */
        $decoded =
            json_decode(
                $body,
                true
            );

        return (
            json_last_error()
            === JSON_ERROR_NONE &&
            is_array($decoded)
        )
            ? $decoded
            : null;
    }

    private function decodeToolResult(
        array $result
    ): ?array {
        /*
         * Some MCP SDK versions may expose structuredContent.
         * Prefer it when available.
         */
        $structured =
            $result['structuredContent'] ?? null;

        if (
            is_array($structured)
        ) {
            if (
                isset(
                    $structured['data']
                )
            ) {
                return $structured;
            }

            if (
                isset(
                    $structured['result']
                ) &&
                is_array(
                    $structured['result']
                )
            ) {
                return $structured['result'];
            }
        }

        $content =
            $result['content']
            ?? [];

        if (
            ! is_array($content)
        ) {
            return null;
        }

        foreach (
            $content as $item
        ) {
            if (
                ! is_array($item) ||
                ($item['type'] ?? null)
                !== 'text'
            ) {
                continue;
            }

            $text =
                trim(
                    (string) (
                        $item['text']
                        ?? ''
                    )
                );

            if ($text === '') {
                continue;
            }

            $decoded =
                json_decode(
                    $text,
                    true
                );

            if (
                json_last_error()
                === JSON_ERROR_NONE &&
                is_array($decoded)
            ) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeRemoteRecords(
        array $records,
        array $meta
    ): array {
        $holidays = [];

        foreach (
            $records as $record
        ) {
            if (
                ! is_array($record)
            ) {
                continue;
            }

            $normalized =
                $this->normalizeRemoteRecord(
                    $record,
                    $meta
                );

            if (! $normalized) {
                continue;
            }

            $date =
                $normalized['date'];

            if (
                isset(
                    $holidays[$date]
                )
            ) {
                $holidays[$date] =
                    $this->mergeHolidayRecords(
                        $holidays[$date],
                        $normalized
                    );

                continue;
            }

            $holidays[$date] =
                $normalized;
        }

        ksort(
            $holidays
        );

        return $holidays;
    }

    private function normalizeRemoteRecord(
        array $record,
        array $meta
    ): ?array {
        $date =
            trim(
                (string) (
                    $record['date']
                    ?? ''
                )
            );

        if (
            ! preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $date
            )
        ) {
            return null;
        }

        $type =
            $this->normalizeType(
                $record['type']
                    ?? null
            );

        if (! $type) {
            return null;
        }

        $isIslamic =
            $type ===
            self::TYPE_ISLAMIC;

        $eidConfirmed =
            $isIslamic
            ? (
                ($record['eid_confirmed'] ?? false)
                === true
            )
            : null;

        $isBlocked =
            match ($type) {
                self::TYPE_REGULAR,
                self::TYPE_SPECIAL_NON_WORKING
                => true,

                self::TYPE_SPECIAL_WORKING
                => false,

                self::TYPE_ISLAMIC
                => $eidConfirmed === true,

                default
                => false,
            };

        return [
            'date' =>
            $date,

            'name' =>
            trim(
                (string) (
                    $record['name']
                    ?? 'Philippine Holiday'
                )
            ),

            'type' =>
            $type,

            'types' => [
                $type,
            ],

            'is_working_day' =>
            ! $isBlocked,

            'is_blocked_for_booking' =>
            $isBlocked,

            'eid_confirmed' =>
            $eidConfirmed,

            'estimated_date' =>
            $record['estimated_date'] ?? null,

            'confirmed_date' =>
            $record['confirmed_date'] ?? null,

            'proclamation_ref' =>
            $record['proclamation_ref'] ?? null,

            'double_holiday' =>
            (bool) (
                $record['double_holiday'] ?? false
            ),

            'double_holiday_names' =>
            $record['double_holiday_names'] ?? null,

            'notes' =>
            $record['notes']
                ?? null,

            'source' =>
            'ph_holidays_mcp',

            'official' =>
            true,

            'provisional' =>
            $isIslamic &&
                $eidConfirmed !== true,

            'source_meta' => [
                'year' =>
                $meta['year']
                    ?? null,

                'tier' =>
                $meta['tier']
                    ?? null,

                'proclamation' =>
                $meta['proclamation'] ?? null,

                'eid_fitr_status' =>
                $meta['eid_fitr_status'] ?? null,

                'eid_adha_status' =>
                $meta['eid_adha_status'] ?? null,

                'last_updated' =>
                $meta['last_updated'] ?? null,

                'source' =>
                $meta['source'] ?? null,
            ],
        ];
    }

    private function mergeHolidayRecords(
        array $first,
        array $second
    ): array {
        $types =
            array_values(
                array_unique(
                    array_merge(
                        $first['types']
                            ?? [
                                $first['type']
                                    ?? null
                            ],

                        $second['types']
                            ?? [
                                $second['type']
                                    ?? null
                            ]
                    )
                )
            );

        $types =
            array_values(
                array_filter(
                    $types
                )
            );

        $blocked =
            (
                $first['is_blocked_for_booking'] ?? false
            ) ||
            (
                $second['is_blocked_for_booking'] ?? false
            );

        $names =
            array_values(
                array_unique(
                    array_filter([
                        $first['name']
                            ?? null,

                        $second['name']
                            ?? null,
                    ])
                )
            );

        $first['name'] =
            implode(
                ' / ',
                $names
            );

        $first['types'] =
            $types;

        $first['type'] =
            count($types) === 1
            ? $types[0]
            : 'mixed';

        $first['is_blocked_for_booking'] =
            $blocked;

        $first['is_working_day'] =
            ! $blocked;

        $first['double_holiday'] =
            true;

        $first['double_holiday_names'] =
            $names;

        if (
            $second['eid_confirmed'] ?? null
        ) {
            $first['eid_confirmed'] =
                true;
        }

        return $first;
    }

    private function fallbackForYear(
        int $year
    ): array {
        $legacy =
            PhilippineHolidays::forYear(
                $year
            );

        $holidays = [];

        $currentYear =
            Carbon::now(
                'Asia/Manila'
            )->year;

        /*
     * Future years without an official MCP dataset must use
     * only holidays whose recurring status is safe to infer.
     *
     * Proclamation-dependent dates are intentionally omitted
     * until an official annual dataset becomes available.
     */
        $isFutureUnpublishedYear =
            $year > $currentYear;

        $correctNationalHeroesDay =
            $this->lastMondayOfAugust(
                $year
            );

        foreach (
            $legacy as $date => $name
        ) {
            /*
         * The legacy helper still contains a fixed Aug. 26
         * National Heroes Day in addition to the calculated
         * last Monday of August.
         */
            if (
                $name ===
                'National Heroes Day' &&
                $date !==
                $correctNationalHeroesDay
            ) {
                continue;
            }

            /*
         * For unpublished future years, do not carry forward
         * proclamation-dependent special days from an older
         * hardcoded calendar.
         */
            if (
                $isFutureUnpublishedYear &&
                ! $this->isSafeFutureFallbackHoliday(
                    $name
                )
            ) {
                continue;
            }

            $type =
                $this->fallbackType(
                    $name
                );

            $isBlocked =
                $type !==
                self::TYPE_SPECIAL_WORKING;

            $displayName =
                $name;

            /*
         * Use the statutory terminology for Dec. 31 in
         * provisional future-year data.
         */
            if (
                $isFutureUnpublishedYear &&
                $name ===
                "New Year's Eve"
            ) {
                $displayName =
                    'Last Day of the Year';
            }

            $holidays[$date] = [
                'date' =>
                $date,

                'name' =>
                $displayName,

                'type' =>
                $type,

                'types' => [
                    $type,
                ],

                'is_working_day' =>
                ! $isBlocked,

                'is_blocked_for_booking' =>
                $isBlocked,

                'eid_confirmed' =>
                null,

                'estimated_date' =>
                null,

                'confirmed_date' =>
                null,

                'proclamation_ref' =>
                null,

                'double_holiday' =>
                false,

                'double_holiday_names' =>
                null,

                'notes' =>
                $isFutureUnpublishedYear
                    ? 'Provisional recurring holiday fallback used until official annual holiday data becomes available.'
                    : 'Generated from the legacy holiday fallback.',

                'source' =>
                $isFutureUnpublishedYear
                    ? 'provisional_fallback'
                    : 'legacy_fallback',

                'official' =>
                false,

                'provisional' =>
                true,

                'source_meta' => [
                    'year' =>
                    $year,

                    'tier' =>
                    $isFutureUnpublishedYear
                        ? 'future_provisional'
                        : 'fallback',

                    'proclamation' =>
                    null,

                    'last_updated' =>
                    null,
                ],
            ];
        }

        ksort(
            $holidays
        );

        return $holidays;
    }

    private function isSafeFutureFallbackHoliday(
        string $name
    ): bool {
        return in_array(
            $name,
            [
                /*
             * Recurring regular holidays.
             */
                "New Year's Day",
                'Maundy Thursday',
                'Good Friday',
                'Araw ng Kagitingan (Day of Valor)',
                'Labor Day',
                'Independence Day',
                'National Heroes Day',
                'Bonifacio Day',
                'Christmas Day',
                'Rizal Day',

                /*
             * Recurring nationwide special non-working days
             * established independently of the annual
             * proclamation.
             */
                'Ninoy Aquino Day',
                "All Saints' Day",
                'Feast of the Immaculate Conception of Mary',
                "New Year's Eve",
            ],
            true
        );
    }

    private function fallbackType(
        string $name
    ): string {
        $specialNonWorking = [
            'Black Saturday',
            'EDSA People Power Revolution Anniversary',
            'Ninoy Aquino Day',
            "All Saints' Day",
            "All Souls' Day",
            'Feast of the Immaculate Conception of Mary',
            'Christmas Eve',
            "New Year's Eve",
        ];

        if (
            in_array(
                $name,
                $specialNonWorking,
                true
            )
        ) {
            return self::TYPE_SPECIAL_NON_WORKING;
        }

        return self::TYPE_REGULAR;
    }

    private function shouldQueryRemote(
        int $year
    ): bool {
        $currentYear =
            Carbon::now(
                'Asia/Manila'
            )->year;

        /*
         * The MCP currently stores at most current + next year.
         * Historical and farther-future requests should not cause
         * pointless external calls.
         */
        return in_array(
            $year,
            [
                $currentYear,
                $currentYear + 1,
            ],
            true
        );
    }

    private function normalizeType(
        mixed $type
    ): ?string {
        $normalized =
            strtolower(
                trim(
                    (string) $type
                )
            );

        return in_array(
            $normalized,
            [
                self::TYPE_REGULAR,
                self::TYPE_SPECIAL_NON_WORKING,
                self::TYPE_SPECIAL_WORKING,
                self::TYPE_ISLAMIC,
            ],
            true
        )
            ? $normalized
            : null;
    }

    private function applyStatutoryHolidayOverlay(
        array $holidays,
        int $year
    ): array {
        /*
     * Republic Act No. 11370:
     * September 8 of every year is a nationwide
     * special working holiday.
     *
     * The law took effect in 2019.
     */
        if ($year < 2019) {
            return $holidays;
        }

        $date =
            sprintf(
                '%04d-09-08',
                $year
            );

        /*
     * If the annual MCP dataset explicitly contains
     * September 8, preserve that official annual record.
     */
        if (
            isset($holidays[$date]) &&
            ($holidays[$date]['source'] ?? null)
            === 'ph_holidays_mcp'
        ) {
            return $holidays;
        }

        $holidays[$date] = [
            'date' =>
            $date,

            'name' =>
            'Feast of the Nativity of the Blessed Virgin Mary',

            'type' =>
            self::TYPE_SPECIAL_WORKING,

            'types' => [
                self::TYPE_SPECIAL_WORKING,
            ],

            'is_working_day' =>
            true,

            'is_blocked_for_booking' =>
            false,

            'eid_confirmed' =>
            null,

            'estimated_date' =>
            null,

            'confirmed_date' =>
            null,

            'proclamation_ref' =>
            null,

            'double_holiday' =>
            false,

            'double_holiday_names' =>
            null,

            'notes' =>
            'Nationwide special working holiday under Republic Act No. 11370.',

            'source' =>
            'statutory_overlay',

            'official' =>
            true,

            'provisional' =>
            false,

            'legal_basis' =>
            'Republic Act No. 11370',

            'source_meta' => [
                'year' =>
                $year,

                'tier' =>
                'statutory',

                'proclamation' =>
                null,

                'last_updated' =>
                null,
            ],
        ];

        ksort(
            $holidays
        );

        return $holidays;
    }

    private function lastMondayOfAugust(
        int $year
    ): string {
        $date =
            Carbon::create(
                $year,
                8,
                31,
                12,
                0,
                0,
                'Asia/Manila'
            );

        while (
            $date->dayOfWeek !==
            Carbon::MONDAY
        ) {
            $date->subDay();
        }

        return $date
            ->toDateString();
    }

    private function storeDatabaseSnapshot(
        int $year,
        array $holidays
    ): void {
        if ($holidays === []) {
            return;
        }

        try {
            PhilippineHolidaySnapshot::updateOrCreate(
                [
                    'year' =>
                    $year,
                ],
                [
                    'holidays' =>
                    $holidays,

                    'source' =>
                    'ph_holidays_mcp',

                    'fetched_at' =>
                    now(),
                ]
            );
        } catch (\Throwable $exception) {
            /*
         * Database snapshot persistence must never break
         * appointment/calendar functionality.
         */
            Log::warning(
                'Unable to persist PH holiday database snapshot.',
                [
                    'year' =>
                    $year,

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    private function loadDatabaseSnapshot(
        int $year
    ): ?array {
        try {
            $snapshot =
                PhilippineHolidaySnapshot::query()
                ->where(
                    'year',
                    $year
                )
                ->first();

            if (! $snapshot) {
                return null;
            }

            $holidays =
                $snapshot->holidays;

            return (
                is_array($holidays) &&
                $holidays !== []
            )
                ? $holidays
                : null;
        } catch (\Throwable $exception) {
            /*
         * If the database itself is unavailable, continue
         * to the existing local fallback instead of failing
         * appointment booking.
         */
            Log::warning(
                'Unable to read PH holiday database snapshot.',
                [
                    'year' =>
                    $year,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    private function freshCacheKey(
        int $year
    ): string {
        return "ph_holidays:{$year}:fresh";
    }

    private function lastGoodCacheKey(
        int $year
    ): string {
        return "ph_holidays:{$year}:last_good";
    }

    private function cacheHours(): int
    {
        return max(
            1,
            (int) config(
                'services.ph_holidays.cache_hours',
                24
            )
        );
    }

    private function fallbackCacheHours(): int
    {
        return max(
            1,
            (int) config(
                'services.ph_holidays.fallback_cache_hours',
                6
            )
        );
    }
}
