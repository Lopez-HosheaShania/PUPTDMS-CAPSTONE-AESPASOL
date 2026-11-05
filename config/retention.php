<?php

return [
    'categories' => [
        'users' => [
            'record_label' => 'Account',
            'counting_rules' => ['account_deactivated' => 'Account deactivation date'],
            'label' => 'User accounts',
            'description' => 'Account and profile details kept for user administration and activity tracking.',
        ],
        'patients' => [
            'record_label' => 'Patient',
            'counting_note' => 'The period starts after the linked login account is deactivated. This does not close or delete the clinical record.',
            'counting_rules' => ['linked_account_deactivated' => 'Linked account deactivation date'],
            'label' => 'Patient profiles',
            'description' => 'Personal, contact, and student or faculty details kept to identify patients and support their care.',
        ],
        'clinical_records' => [
            'record_label' => 'Patient',
            'counting_rules' => ['last_completed_treatment' => 'Last completed treatment for the patient'],
            'label' => 'Clinical records',
            'description' => 'Diagnoses, treatments, prescriptions, medical and dental histories, odontograms, and related signatures kept together to support patient care.',
        ],
        'appointments' => [
            'record_label' => 'Appointment',
            'counting_rules' => ['appointment_closed' => 'Appointment completion or cancellation date'],
            'label' => 'Appointment records',
            'description' => 'Booking, rescheduling, cancellation, and attendance history kept to manage clinic visits. Clinical information follows the Clinical records policy.',
        ],
        'document_requests' => [
            'record_label' => 'Document request',
            'counting_note' => 'Generating or downloading a PDF does not start or restart the retention period.',
            'counting_rules' => ['document_request_decided' => 'Document request approval or rejection date'],
            'label' => 'Document requests and issued documents',
            'description' => 'Requests, approval or rejection details, and any issued patient documents stored in the system, kept to track document processing.',
        ],
        'audit_logs' => [
            'record_label' => 'Audit event',
            'counting_rules' => ['record_created' => 'Event date'],
            'label' => 'Audit and security logs',
            'description' => 'Records of who performed system actions and when, kept for security monitoring and accountability.',
        ],
        'ai_service_logs' => [
            'record_label' => 'AI log',
            'counting_rules' => ['record_created' => 'Log date'],
            'label' => 'Technical and AI service logs',
            'description' => 'Stored AI feature activity, providers, errors, and diagnostic messages kept to monitor reliability and troubleshoot issues.',
        ],
    ],
];
