<?php

return [
    'categories' => [
        'users' => [
            'label' => 'User accounts',
            'description' => 'Account and profile details kept for user administration and activity tracking.',
        ],
        'patients' => [
            'label' => 'Patient profiles and related information',
            'description' => 'Personal, contact, and student or faculty details kept to identify patients and support their care.',
        ],
        'appointments' => [
            'label' => 'Appointments and treatment records',
            'description' => 'Visit schedules, diagnoses, procedures, and prescriptions kept to track treatment and follow-up care.',
        ],
        'medical_histories' => [
            'label' => 'Medical histories and answers',
            'description' => 'Health questionnaire answers, medical conditions, and emergency contacts kept to guide safe dental care.',
        ],
        'dental_histories' => [
            'label' => 'Dental histories and odontograms',
            'description' => 'Previous dental visits, concerns, and tooth charts kept to track oral health over time.',
        ],
        'document_requests' => [
            'label' => 'Patient document requests and reviews',
            'description' => 'Requests for patient documents and approval or rejection details kept to track how requests were handled.',
        ],
        'audit_logs' => [
            'label' => 'System audit logs and related details',
            'description' => 'Records of who performed system actions and when, kept for security monitoring and accountability.',
        ],
        'ai_service_logs' => [
            'label' => 'AI service logs',
            'description' => 'AI feature activity, service providers, and results kept to monitor reliability and troubleshoot issues.',
        ],
    ],
];
