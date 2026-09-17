import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/pages/error-pages.css',
                'resources/css/pages/auth/login.css',
                'resources/css/pages/auth/backup-login.css',

                // Admin
                'resources/css/pages/admin/admin-dashboard.css',
                'resources/css/pages/admin/admin-shared.css',
                'resources/css/pages/admin/ai-generated-report.css',
                'resources/css/pages/admin/dentist-continuity.css',
                'resources/css/pages/admin/document-templates.css',
                'resources/css/pages/admin/role-permissions.css',
                'resources/css/pages/admin/session-management.css',
                'resources/css/pages/admin/system-logs.css',
                'resources/css/pages/admin/system-settings.css',
                'resources/css/pages/admin/user-management.css',

                // Dentist
                'resources/css/pages/dentist/add-existing-appointment.css',
                'resources/css/pages/dentist/daily-treatment.css',
                'resources/css/pages/dentist/dental-services.css',
                'resources/css/pages/dentist/dentist-dashboard.css',
                'resources/css/pages/dentist/dentist-shared.css',
                'resources/css/pages/dentist/dentist-walk-in.css',
                'resources/css/pages/dentist/odontogram.css',

                // Patient
                'resources/css/pages/patient/about-us.css',
                'resources/css/pages/patient/appointment.css',
                'resources/css/pages/patient/book-appointment.css',
                'resources/css/pages/patient/dashboard.css',
                'resources/css/pages/patient/patient-profile.css',
                'resources/css/pages/patient/patient-shared.css',
                'resources/css/pages/patient/records.css',

                // Shared
                'resources/css/pages/shared/academic-period.css',
                'resources/css/pages/shared/active-sessions.css',
                'resources/css/pages/shared/add-existing-record.css',
                'resources/css/pages/shared/appointments.css',
                'resources/css/pages/shared/clinic-schedule.css',
                'resources/css/pages/shared/dental-records.css',
                'resources/css/pages/shared/document-requests.css',
                'resources/css/pages/shared/inventory.css',
                'resources/css/pages/shared/patient-list.css',
                'resources/css/pages/shared/reports.css',
                'resources/css/pages/shared/service-types.css',

            ],
            refresh: true,
        }),

        tailwindcss(),
    ],

    server: {
        watch: {
            ignored: [
                '**/storage/framework/views/**',
            ],
        },
    },
});