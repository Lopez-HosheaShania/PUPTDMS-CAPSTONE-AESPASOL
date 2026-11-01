@php
    $rows = \App\Services\MonthlyDentalReport::ROWS;
    $columns = \App\Services\MonthlyDentalReport::COLUMNS;
@endphp

<section class="monthly-report-counts hidden" data-monthly-report hidden
    data-preview-url="{{ route('dentist.dentist.report.monthly-report-counts') }}" aria-labelledby="monthlyCountsTitle">
    <h3 id="monthlyCountsTitle" class="global-form-label">Monthly patient and service counts</h3>
    <p class="modal-helper-text">
        Load the selected period, then review or correct the counts. Extraction and restoration count each tooth once
        per category per visit. Other services count visits or events. Review Rx versus Med & Rx: a prescription does not
        confirm that medicine was dispensed. Manual entries apply only to this download.
    </p>
    <div class="monthly-report-actions">
        <x-button type="button" variant="secondary" data-monthly-load>Load counts for selected dates</x-button>
        <x-button type="button" variant="secondary" data-monthly-zero disabled>Confirm remaining blanks as zero</x-button>
    </div>
    <p class="modal-helper-text" role="status" aria-live="polite" data-monthly-status>Select dates and load counts.</p>
    <div class="monthly-report-table-wrap" tabindex="0" role="region" aria-label="Monthly counts by patient classification">
        <table class="data-table monthly-report-table">
            <thead>
                <tr>
                    <th scope="col">Patient / service count</th>
                    @foreach ($rows as $row => $label)
                        <th scope="col">{{ $label }}</th>
                    @endforeach
                    <th scope="col">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($columns as $column => $label)
                    <tr>
                        <th scope="row">
                            {{ $label }}
                            <span class="monthly-report-source">
                                @if (in_array($column, ['extraction', 'temporary', 'permanent']))
                                    Tooth suggestions · review existing markings
                                @elseif (in_array($column, ['actual_patient', 'inquiry', 'rx', 'prophylaxis']))
                                    Automatic · editable
                                @else
                                    Enter count or confirm zero
                                @endif
                            </span>
                        </th>
                        @foreach ($rows as $row => $rowLabel)
                            <td data-global-field>
                                <x-number-stepper :id="'monthly-'.$row.'-'.$column"
                                    :name="'report_counts['.$row.']['.$column.']'" min="0" max="99999" disabled
                                    :aria-label="$rowLabel.' — '.$label" :data-field-label="$rowLabel.' '.$label"
                                    data-required-message="Enter a count, or confirm remaining blanks as zero."
                                    data-validation-rule="wholeNumber" data-monthly-count :data-row="$row" :data-column="$column" />
                            </td>
                        @endforeach
                        <td><output data-monthly-column-total="{{ $column }}">—</output></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">Total services (excludes patients)</th>
                    @foreach ($rows as $row => $label)
                        <td><output data-monthly-row-total="{{ $row }}">—</output></td>
                    @endforeach
                    <td><output data-monthly-grand-total>—</output></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div data-global-field>
        <label class="global-checkbox-row" for="monthlyCountsConfirmed">
            <input id="monthlyCountsConfirmed" name="counts_confirmed" type="checkbox" value="1"
                class="global-checkbox-input" data-monthly-confirm disabled
                data-required-message="Please review and confirm the monthly report counts.">
            <span class="global-checkbox-label">
                I reviewed the classifications, tooth suggestions, and all patient and service counts for these dates.
            </span>
        </label>
        <div class="global-field-error" data-error-for="counts_confirmed" aria-live="polite" aria-hidden="true"></div>
    </div>
</section>
