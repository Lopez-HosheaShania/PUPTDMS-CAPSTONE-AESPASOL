<?php

return [
    'archiving_enabled' => false,
    'disposal_enabled' => false,
    'categories' => [
        'users' => [
            'label' => 'User accounts', 'model' => App\Models\User::class,
            'description' => 'Account and profile details kept for user administration and activity tracking.',
        ],
        'patients' => [
            'label' => 'Patient profiles and related information', 'model' => App\Models\Patient::class,
            'description' => 'Personal, contact, and student or faculty details kept to identify patients and support their care.',
        ],
        'appointments' => [
            'label' => 'Appointments and treatment records', 'model' => App\Models\Appointment::class,
            'description' => 'Visit schedules, diagnoses, procedures, and prescriptions kept to track treatment and follow-up care.',
        ],
        'medical_histories' => [
            'label' => 'Medical histories and answers', 'model' => App\Models\MedicalHistory::class,
            'description' => 'Health questionnaire answers, medical conditions, and emergency contacts kept to guide safe dental care.',
        ],
        'dental_histories' => [
            'label' => 'Dental histories and odontograms', 'model' => App\Models\DentalHistory::class,
            'description' => 'Previous dental visits, concerns, and tooth charts kept to track oral health over time.',
        ],
        'document_requests' => [
            'label' => 'Patient document requests and reviews', 'model' => App\Models\DocumentRequest::class,
            'description' => 'Requests for patient documents and approval or rejection details kept to track how requests were handled.',
        ],
        'audit_logs' => [
            'label' => 'System audit logs and related details', 'model' => App\Models\AuditLog::class,
            'description' => 'Records of who performed system actions and when, kept for security monitoring and accountability.',
        ],
        'ai_service_logs' => [
            'label' => 'AI service logs', 'model' => App\Models\AiServiceLog::class,
            'description' => 'AI feature activity, service providers, and results kept to monitor reliability and troubleshoot issues.',
        ],
    ],
];
