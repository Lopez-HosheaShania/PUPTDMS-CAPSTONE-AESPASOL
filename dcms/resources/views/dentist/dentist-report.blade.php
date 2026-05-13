@extends('layouts.dentist')

@section('title', 'Reports & Analytics | PUP Taguig Dental Clinic')

@section('styles')
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn .6s ease-out forwards;
        }

        .metric-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: linear-gradient(180deg, #fff5f5 0%, #ffe9e9 100%);
            border: 1px solid #f3caca;
            color: #8B0000;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            white-space: nowrap;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        .kpi-card {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #fffdfd 100%);
            border-radius: 22px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #efe4e4;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .05);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            text-decoration: none;
            overflow: hidden;
            min-height: 126px;
        }

        .kpi-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: linear-gradient(180deg, #8B0000 0%, #d97706 100%);
            opacity: .9;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 34px rgba(139, 0, 0, .10);
            border-color: #e7c9c9;
        }

        .kpi-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
        }

        .kpi-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .kpi-delta {
            font-size: 0.78rem;
            font-weight: 500;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .kpi-delta.up {
            color: #16a34a;
        }

        .kpi-delta.down {
            color: #dc2626;
        }

        .kpi-arrow {
            margin-left: auto;
            color: #d1d5db;
            font-size: 0.95rem;
            transition: all .2s ease;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #faf5f5;
            flex-shrink: 0;
        }

        .kpi-card:hover .kpi-arrow {
            color: #8B0000;
            background: #fff1f1;
            transform: translateX(2px);
        }

        .chart-card {
            background: linear-gradient(180deg, #ffffff 0%, #fffdfd 100%);
            border-radius: 20px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .04);
            border: 1px solid #efe4e4;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .chart-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .chart-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: #333333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-title i {
            color: #8B0000;
        }

        .period-select {
            font-size: 0.75rem;
            font-weight: 600;
            color: #333333;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 99px;
            padding: 6px 28px 6px 14px;
            cursor: pointer;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2364748b'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            transition: all .2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .period-select:hover {
            border-color: #94a3b8;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .period-select:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.1);
        }

        .action-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all .2s ease;
            cursor: pointer;
        }

        .action-card:hover {
            border-color: #8B0000;
            box-shadow: 0 4px 12px rgba(139, 0, 0, .05);
            transform: translateY(-2px);
        }

        .action-icon {
            width: 42px;
            height: 42px;
            background: #FFF5F5;
            color: #8B0000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all .2s;
        }

        .action-card:hover .action-icon {
            background: #8B0000;
            color: white;
        }

        .stock-row {
            padding: 10px 0;
            border-bottom: 1px solid #F3F4F6;
        }

        .stock-row:last-child {
            border-bottom: none;
        }

        .stock-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }

        .stock-bar-bg {
            height: 6px;
            background: #F3F4F6;
            border-radius: 10px;
            overflow: hidden;
        }

        .stock-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width .8s cubic-bezier(.4, 0, .2, 1);
        }

        .chart-empty {
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 10px;
            color: #9CA3AF;
            text-align: center;
        }

        .chart-empty>i {
            font-size: 2.5rem;
            color: #E5E7EB;
            margin-bottom: 4px;
        }

        .chart-empty p {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6B7280;
            margin: 0;
        }

        .chart-empty span {
            font-size: 0.75rem;
        }

        .chart-empty,
        .chart-loading {
            height: 100%;
        }

        .chart-empty:not(.hidden) {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .chart-loading:not(.hidden) {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-loading {
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .chart-loading i {
            font-size: 1.5rem;
            color: #8B0000;
            animation: spin 1s linear infinite;
        }

        .analytics-hero {
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .10), transparent 22%),
                linear-gradient(135deg, #8B0000 0%, #660000 100%);
            border-radius: 24px;
            padding: 24px 28px;
            color: white;
            box-shadow: 0 18px 42px rgba(139, 0, 0, .18);
            margin-bottom: 28px;
        }

        .analytics-hero-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .analytics-hero-btn {
            background: #fff;
            color: #8B0000;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 10px 16px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .2s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        }

        .analytics-hero-btn:hover {
            transform: translateY(-4px);
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .10), transparent 22%),
                linear-gradient(135deg, #8B0000 0%, #660000 100%);
            color: #fff;
        }

        .analytics-section-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #8B0000;
            letter-spacing: .10em;
            text-transform: uppercase;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .analytics-subgrid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .analytics-main-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr .9fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .analytics-secondary-grid {
            display: grid;
            grid-template-columns: 1.5fr .9fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .pro-card {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
            padding: 20px;
            min-height: 220px;
        }

        .service-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .service-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #fafafa;
            border: 1px solid #f1f5f9;
        }

        .service-rank {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #8B0000;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .service-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .service-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1f2937;
        }

        .service-count {
            font-size: 0.8rem;
            font-weight: 800;
            color: #8B0000;
            white-space: nowrap;
        }

        .mini-kpi {
            background: linear-gradient(180deg, #ffffff 0%, #fffdfd 100%);
            border: 1px solid #efe7e7;
            border-radius: 20px;
            padding: 18px 20px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
            min-height: 116px;
            position: relative;
            overflow: hidden;
        }

        .mini-kpi::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 64px;
            height: 64px;
            background: radial-gradient(circle, rgba(139, 0, 0, .08) 0%, rgba(139, 0, 0, 0) 70%);
            pointer-events: none;
        }

        .mini-kpi-label {
            font-size: 0.74rem;
            font-weight: 800;
            color: #7c8596;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .mini-kpi-value {
            margin-top: 18px;
            font-size: 2rem;
            font-weight: 900;
            color: #111827;
            letter-spacing: -0.03em;
        }

        .analytics-page-shell {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, .05), transparent 24%),
                linear-gradient(180deg, #fff 0%, #faf7f7 100%);
            border-radius: 24px;
        }

        .section-card {
            background: #fff;
            border: 1px solid #eee2e2;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
        }

        .inventory-shell {
            background: linear-gradient(180deg, #fff 0%, #fffafa 100%);
            border: 1px solid #f3dede;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(139, 0, 0, .04);
        }

        .kpi-grid-layout {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .fp-date-input-wrap {
            position: relative;
            width: 100%;
        }

        .fp-date-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 14px;
            pointer-events: none;
        }

        #dateFrom[readonly],
        #dateTo[readonly] {
            cursor: pointer;
        }

        @media (max-width: 767px) {

            .page-title-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 1rem !important;
            }

            .header-actions-container {
                width: 100% !important;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .header-actions-container button {
                width: 100% !important;
                justify-content: center !important;
            }

            .header-actions-container span {
                width: 100% !important;
                text-align: center !important;
            }

            .grid.grid-cols-2 {
                gap: 10px !important;
            }

            .kpi-card {
                padding: 14px 14px !important;
                gap: 12px !important;
                flex-direction: row !important;
                align-items: center !important;
                min-height: 108px !important;
            }

            .kpi-icon {
                width: 44px !important;
                height: 44px !important;
                font-size: 16px !important;
                border-radius: 12px !important;
            }

            .kpi-value {
                font-size: 1.6rem !important;
                margin-bottom: 6px !important;
            }

            .kpi-label {
                font-size: 0.62rem !important;
                white-space: normal !important;
                line-height: 1.25 !important;
            }

            .kpi-delta {
                font-size: 0.62rem !important;
            }

            .mini-kpi {
                min-height: 96px !important;
                padding: 16px !important;
            }

            .mini-kpi-value {
                font-size: 1.7rem !important;
                margin-top: 14px !important;
            }

            .kpi-arrow {
                display: none !important;
            }

            .analytics-hero {
                padding: 16px 18px;
                border-radius: 20px;
            }

            .analytics-hero-actions {
                align-items: stretch;
                margin-top: 16px;
            }

            .analytics-hero-btn {
                justify-content: center;
            }
        }

        @media only screen and (max-width: 600px) {

            .chart-card,
            .pro-card,
            .inventory-shell {
                border-radius: 18px;
                padding: 14px;
            }

            .chart-card-header {
                flex-wrap: wrap;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 12px;
            }

            .chart-title {
                font-size: 0.9rem;
                line-height: 1.3;
            }

            .metric-chip {
                font-size: 0.60rem;
                padding: 5px 8px;
                white-space: normal;
                text-align: center;
                line-height: 1.2;
            }

            .service-list {
                gap: 10px;
            }

            .service-row {
                padding: 10px 12px;
                border-radius: 12px;
                gap: 10px;
            }

            .service-rank {
                width: 24px;
                height: 24px;
                font-size: 0.68rem;
            }

            .service-name {
                font-size: 0.78rem;
                line-height: 1.2;
            }

            .service-count {
                font-size: 0.74rem;
            }

            .action-card {
                padding: 14px;
                border-radius: 14px;
                gap: 12px;
            }

            .action-icon {
                width: 38px;
                height: 38px;
                font-size: 15px;
                border-radius: 10px;
            }

            .inventory-shell .chart-card-header {
                margin-bottom: 14px;
            }

            .inventory-shell .chart-card-header a {
                font-size: 10px;
                padding: 6px 10px;
                border-radius: 10px;
            }

            .inventory-shell .grid {
                gap: 18px !important;
            }

            .inventory-shell h3 {
                font-size: 10px !important;
                margin-bottom: 10px !important;
            }

            .chart-empty>i {
                font-size: 2rem;
            }

            .chart-empty p {
                font-size: 0.8rem;
            }

            .chart-empty span {
                font-size: 0.7rem;
                line-height: 1.35;
            }

            .chart-empty button,
            .chart-empty a.mt-4 {
                margin-top: 12px !important;
            }

            .chart-empty button {
                padding: 8px 12px !important;
                font-size: 0.68rem !important;
                border-radius: 12px !important;
            }

            .chart-empty button .w-5.h-5 {
                width: 16px !important;
                height: 16px !important;
            }

            .chart-empty button i {
                font-size: 7px !important;
            }

            .inventory-shell .col-span-1.bg-gray-50 {
                padding: 14px;
                border-radius: 14px;
            }

            .stock-name {
                font-size: 0.74rem;
            }

            .stock-bar-bg {
                height: 5px;
            }

            .kpi-grid-layout {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .analytics-subgrid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-bottom: 24px;
            }

            .kpi-card {
                min-height: 96px !important;
                padding: 14px 12px !important;
                gap: 10px !important;
                border-radius: 18px !important;
            }

            .kpi-card::before {
                width: 3px;
            }

            .kpi-icon {
                width: 42px !important;
                height: 42px !important;
                font-size: 15px !important;
                border-radius: 12px !important;
            }

            .kpi-value {
                font-size: 1.55rem !important;
                margin-bottom: 4px !important;
            }

            .kpi-label {
                font-size: 0.60rem !important;
                line-height: 1.2 !important;
                letter-spacing: 0.06em !important;
            }

            .kpi-delta {
                font-size: 0.60rem !important;
                margin-top: 6px !important;
            }

            .mini-kpi {
                min-height: 92px !important;
                padding: 14px !important;
                border-radius: 16px !important;
            }

            .mini-kpi-label {
                font-size: 0.62rem !important;
                letter-spacing: 0.06em !important;
            }

            .mini-kpi-value {
                font-size: 1.55rem !important;
                margin-top: 12px !important;
            }

            .kpi-arrow {
                display: none !important;
            }

            .analytics-main-grid,
            .analytics-secondary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media only screen and (min-width: 768px) and (max-width: 991px) {
            .analytics-hero {
                padding: 20px 22px;
                border-radius: 22px;
            }

            .kpi-card {
                min-height: 110px;
                padding: 18px;
            }

            .kpi-value {
                font-size: 1.8rem;
            }

            .mini-kpi-value {
                font-size: 1.8rem;
            }
        }

        @media only screen and (min-width: 600px) {
            .kpi-grid-layout {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .analytics-subgrid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media only screen and (min-width: 768px) {

            .kpi-grid-layout {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .analytics-subgrid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .analytics-main-grid {
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }

            .analytics-secondary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }

            .analytics-main-grid .pro-card:last-child {
                grid-column: span 2;
            }

            .chart-card,
            .pro-card {
                padding: 18px;
            }

            .chart-card-header {
                margin-bottom: 14px;
            }

            .chart-title {
                font-size: 0.92rem;
            }
        }

        @media only screen and (min-width: 992px) {
            .kpi-grid-layout {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .analytics-subgrid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .analytics-main-grid {
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }

            .analytics-secondary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }

            .analytics-main-grid .pro-card:last-child {
                grid-column: span 2;
            }
        }

        @media only screen and (min-width: 992px) and (max-width: 1199px) {
            .analytics-hero {
                padding: 20px 22px;
                border-radius: 22px;
            }

            .kpi-card,
            .mini-kpi,
            .chart-card,
            .pro-card {
                padding: 18px;
            }

            .kpi-value,
            .mini-kpi-value {
                font-size: 1.85rem;
            }
        }

        @media only screen and (min-width: 1200px) {
            .kpi-grid-layout {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
            }

            .analytics-subgrid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
            }

            .analytics-main-grid {
                grid-template-columns: 1.2fr 1.2fr .9fr;
                gap: 20px;
            }

            .analytics-main-grid .pro-card:last-child {
                grid-column: auto;
            }

            .analytics-secondary-grid {
                grid-template-columns: 1.5fr .9fr;
                gap: 20px;
            }
        }


        /* Report page title/actions aligned with global dentist hero */
        .report-hero-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .summary-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 11px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(139, 0, 0, 0.10);
            color: #6B7280;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
        }

        .summary-tag-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .report-hero-actions {
            position: relative;
            z-index: 2;
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-hero-btn {
            min-height: 42px;
            padding: 0 16px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #8B0000;
            color: #FFFFFF;
            border: 1px solid rgba(139, 0, 0, 0.18);
            font-size: 13px;
            font-weight: 900;
            line-height: 1;
            box-shadow: 0 12px 26px rgba(139, 0, 0, 0.22);
            transition: all .18s ease;
        }

        .report-hero-btn:hover {
            background: #660000;
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(139, 0, 0, 0.26);
        }

        /* Dashboard-matched dark mode for report content */
        [data-theme="dark"] #mainContent,
        .dark #mainContent {
            background:
                radial-gradient(circle at 12% 4%, rgba(139, 0, 0, 0.18), transparent 30%),
                radial-gradient(circle at 88% 12%, rgba(252, 165, 165, 0.08), transparent 28%),
                #0D1117 !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] #mainContent .kpi-card,
        [data-theme="dark"] #mainContent .mini-kpi,
        [data-theme="dark"] #mainContent .chart-card,
        [data-theme="dark"] #mainContent .pro-card,
        [data-theme="dark"] #mainContent .section-card,
        [data-theme="dark"] #mainContent .inventory-shell,
        [data-theme="dark"] #mainContent .action-card,
        .dark #mainContent .kpi-card,
        .dark #mainContent .mini-kpi,
        .dark #mainContent .chart-card,
        .dark #mainContent .pro-card,
        .dark #mainContent .section-card,
        .dark #mainContent .inventory-shell,
        .dark #mainContent .action-card {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.18), transparent 36%),
                linear-gradient(145deg, rgba(13, 17, 23, 0.78), rgba(22, 27, 34, 0.66)) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            box-shadow:
                0 18px 38px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.07) !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] #mainContent .kpi-card:hover,
        [data-theme="dark"] #mainContent .action-card:hover,
        .dark #mainContent .kpi-card:hover,
        .dark #mainContent .action-card:hover {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.24), transparent 34%),
                linear-gradient(145deg, rgba(13, 17, 23, 0.88), rgba(22, 27, 34, 0.78)) !important;
            border-color: rgba(252, 165, 165, 0.22) !important;
        }

        [data-theme="dark"] #mainContent .kpi-value,
        [data-theme="dark"] #mainContent .mini-kpi-value,
        [data-theme="dark"] #mainContent .chart-title,
        [data-theme="dark"] #mainContent .service-name,
        [data-theme="dark"] #mainContent .stock-name,
        [data-theme="dark"] #mainContent .text-gray-800,
        [data-theme="dark"] #mainContent .text-gray-700,
        .dark #mainContent .kpi-value,
        .dark #mainContent .mini-kpi-value,
        .dark #mainContent .chart-title,
        .dark #mainContent .service-name,
        .dark #mainContent .stock-name,
        .dark #mainContent .text-gray-800,
        .dark #mainContent .text-gray-700 {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] #mainContent .kpi-label,
        [data-theme="dark"] #mainContent .mini-kpi-label,
        [data-theme="dark"] #mainContent .chart-empty p,
        [data-theme="dark"] #mainContent .chart-empty span,
        [data-theme="dark"] #mainContent .text-gray-500,
        [data-theme="dark"] #mainContent .text-gray-400,
        .dark #mainContent .kpi-label,
        .dark #mainContent .mini-kpi-label,
        .dark #mainContent .chart-empty p,
        .dark #mainContent .chart-empty span,
        .dark #mainContent .text-gray-500,
        .dark #mainContent .text-gray-400 {
            color: #C9D1D9 !important;
        }

        [data-theme="dark"] #mainContent .analytics-section-label,
        [data-theme="dark"] #mainContent .chart-title i,
        .dark #mainContent .analytics-section-label,
        .dark #mainContent .chart-title i {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] #mainContent .metric-chip,
        .dark #mainContent .metric-chip {
            background: rgba(139, 0, 0, 0.18) !important;
            border-color: rgba(252, 165, 165, 0.22) !important;
            color: #FCA5A5 !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06) !important;
        }

        [data-theme="dark"] #mainContent .period-select,
        .dark #mainContent .period-select {
            background-color: rgba(13, 17, 23, 0.78) !important;
            color: #C9D1D9 !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
            box-shadow: none !important;
        }

        [data-theme="dark"] #mainContent .service-row,
        [data-theme="dark"] #mainContent .stock-bar-bg,
        [data-theme="dark"] #mainContent .bg-gray-50,
        .dark #mainContent .service-row,
        .dark #mainContent .stock-bar-bg,
        .dark #mainContent .bg-gray-50 {
            background: rgba(13, 17, 23, 0.72) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
        }

        [data-theme="dark"] #mainContent .kpi-arrow,
        [data-theme="dark"] #mainContent .action-icon,
        .dark #mainContent .kpi-arrow,
        .dark #mainContent .action-icon {
            background: rgba(139, 0, 0, 0.18) !important;
            color: #FCA5A5 !important;
            border: 1px solid rgba(252, 165, 165, 0.18) !important;
        }

        [data-theme="dark"] #mainContent .kpi-card:hover .kpi-arrow,
        [data-theme="dark"] #mainContent .action-card:hover .action-icon,
        .dark #mainContent .kpi-card:hover .kpi-arrow,
        .dark #mainContent .action-card:hover .action-icon {
            background: #8B0000 !important;
            color: #FFFFFF !important;
        }

        [data-theme="dark"] #mainContent .chart-empty > i,
        .dark #mainContent .chart-empty > i {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] #mainContent .summary-tag,
        .dark #mainContent .summary-tag {
            background: rgba(13, 17, 23, 0.70) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
            color: #C9D1D9 !important;
        }

        [data-theme="dark"] #mainContent .report-hero-btn,
        .dark #mainContent .report-hero-btn {
            background: linear-gradient(135deg, #8B0000, #5F0000) !important;
            border-color: rgba(252, 165, 165, 0.18) !important;
            color: #FFFFFF !important;
            box-shadow: 0 14px 28px rgba(139, 0, 0, 0.30) !important;
        }

        [data-theme="dark"] #mainContent .inventory-shell a:not(.chart-empty),
        .dark #mainContent .inventory-shell a:not(.chart-empty) {
            background: rgba(139, 0, 0, 0.18) !important;
            color: #FCA5A5 !important;
            border: 1px solid rgba(252, 165, 165, 0.18) !important;
        }

        [data-theme="dark"] .ui-modal .ui-modal-card,
        .dark .ui-modal .ui-modal-card {
            background: #161B22 !important;
            color: #F8FAFC !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.55) !important;
        }

        [data-theme="dark"] .ui-modal label,
        [data-theme="dark"] .ui-modal .text-\[10px\],
        .dark .ui-modal label,
        .dark .ui-modal .text-\[10px\] {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .ui-modal input,
        [data-theme="dark"] .ui-modal select,
        .dark .ui-modal input,
        .dark .ui-modal select {
            background: #0D1117 !important;
            border-color: #30363D !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .ui-modal input::placeholder,
        .dark .ui-modal input::placeholder {
            color: #6E7681 !important;
        }

        [data-theme="dark"] .ui-modal .border-t,
        .dark .ui-modal .border-t {
            border-color: #30363D !important;
        }

        [data-theme="dark"] .ui-modal .bg-gray-50,
        .dark .ui-modal .bg-gray-50 {
            background: #111827 !important;
        }

        [data-theme="dark"] .ui-modal button[class*="border-gray-300"],
        .dark .ui-modal button[class*="border-gray-300"] {
            background: #0D1117 !important;
            border-color: #484F58 !important;
            color: #C9D1D9 !important;
        }

        @media (max-width: 767px) {
            .report-hero-actions {
                width: 100%;
                margin-left: 0;
            }

            .report-hero-btn {
                width: 100%;
            }
        }
    
        #mainContent.dentist-page-shell {
            background: #F9FAFB !important;
        }

        #mainContent .report-hero-meta {
            margin-top: 12px !important;
        }

        #mainContent .report-hero-actions {
            margin-left: 0 !important;
            align-self: flex-end !important;
            margin-top: 12px !important;
        }

        #mainContent .report-hero-btn {
            min-height: 46px !important;
            padding: 0 20px !important;
            border-radius: 15px !important;
        }

        #mainContent .analytics-section-label,
        #mainContent .chart-title,
        #mainContent .quick-report-title {
            letter-spacing: .08em;
        }

        #mainContent .chart-card,
        #mainContent .pro-card,
        #mainContent .inventory-shell,
        #mainContent .kpi-card,
        #mainContent .mini-kpi,
        #mainContent .action-card {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.06), transparent 35%),
                linear-gradient(145deg, #FFFFFF, #FFFDFD) !important;
            border-color: rgba(139, 0, 0, 0.12) !important;
            box-shadow:
                0 14px 34px rgba(15, 23, 42, 0.055),
                inset 0 1px 0 rgba(255, 255, 255, 0.78) !important;
        }

        #mainContent .chart-empty,
        #mainContent .chart-loading {
            background: transparent !important;
            border-radius: 16px !important;
        }

        #mainContent .chart-empty > i {
            width: 74px !important;
            height: 74px !important;
            border-radius: 22px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-bottom: 8px !important;
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.12), transparent 42%),
                linear-gradient(145deg, #FFFFFF, #FFF5F5) !important;
            border: 1px solid rgba(139, 0, 0, 0.10) !important;
            box-shadow:
                0 14px 28px rgba(139, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.80) !important;
            color: #8B0000 !important;
            font-size: 30px !important;
        }

        #mainContent .chart-empty p {
            color: #4B5563 !important;
            font-weight: 850 !important;
        }

        #mainContent .chart-empty span {
            color: #8B949E !important;
            font-weight: 600 !important;
        }

        #mainContent .action-card h4,
        #mainContent .service-name,
        #mainContent .stock-name,
        #mainContent .inventory-shell h3 {
            color: #111827 !important;
        }

        #mainContent .action-card p,
        #mainContent .mini-kpi-label,
        #mainContent .kpi-label {
            color: #6B7280 !important;
        }

        #mainContent .service-row {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.04), transparent 42%),
                #FFFFFF !important;
            border-color: rgba(139, 0, 0, 0.10) !important;
        }

        #mainContent .inventory-shell .col-span-1.bg-gray-50 {
            background: rgba(255, 255, 255, 0.74) !important;
            border: 1px solid rgba(139, 0, 0, 0.10) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72) !important;
        }

        [data-theme="dark"] #mainContent.dentist-page-shell,
        .dark #mainContent.dentist-page-shell {
            background:
                radial-gradient(circle at 12% 4%, rgba(139, 0, 0, 0.18), transparent 30%),
                radial-gradient(circle at 88% 12%, rgba(252, 165, 165, 0.08), transparent 28%),
                #0D1117 !important;
        }

        [data-theme="dark"] #mainContent .chart-card,
        [data-theme="dark"] #mainContent .pro-card,
        [data-theme="dark"] #mainContent .inventory-shell,
        [data-theme="dark"] #mainContent .kpi-card,
        [data-theme="dark"] #mainContent .mini-kpi,
        [data-theme="dark"] #mainContent .action-card,
        .dark #mainContent .chart-card,
        .dark #mainContent .pro-card,
        .dark #mainContent .inventory-shell,
        .dark #mainContent .kpi-card,
        .dark #mainContent .mini-kpi,
        .dark #mainContent .action-card {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.18), transparent 36%),
                linear-gradient(145deg, rgba(13, 17, 23, 0.82), rgba(22, 27, 34, 0.70)) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            box-shadow:
                0 18px 38px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.07) !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] #mainContent .chart-card canvas,
        [data-theme="dark"] #mainContent .pro-card canvas,
        [data-theme="dark"] #mainContent .inventory-shell canvas,
        .dark #mainContent .chart-card canvas,
        .dark #mainContent .pro-card canvas,
        .dark #mainContent .inventory-shell canvas {
            background: transparent !important;
        }

        [data-theme="dark"] #mainContent .chart-empty,
        [data-theme="dark"] #mainContent .chart-loading,
        .dark #mainContent .chart-empty,
        .dark #mainContent .chart-loading {
            background: transparent !important;
            color: #C9D1D9 !important;
        }

        [data-theme="dark"] #mainContent .chart-empty > i,
        .dark #mainContent .chart-empty > i {
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.28), transparent 42%),
                linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.92)) !important;
            color: #FCA5A5 !important;
            border-color: rgba(252, 165, 165, 0.20) !important;
            box-shadow:
                0 18px 36px rgba(0, 0, 0, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.07) !important;
        }

        [data-theme="dark"] #mainContent .chart-empty p,
        [data-theme="dark"] #mainContent .action-card h4,
        [data-theme="dark"] #mainContent .service-name,
        [data-theme="dark"] #mainContent .stock-name,
        [data-theme="dark"] #mainContent .inventory-shell h3,
        [data-theme="dark"] #mainContent .text-gray-800,
        .dark #mainContent .chart-empty p,
        .dark #mainContent .action-card h4,
        .dark #mainContent .service-name,
        .dark #mainContent .stock-name,
        .dark #mainContent .inventory-shell h3,
        .dark #mainContent .text-gray-800 {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] #mainContent .chart-empty span,
        [data-theme="dark"] #mainContent .action-card p,
        [data-theme="dark"] #mainContent .text-gray-400,
        [data-theme="dark"] #mainContent .text-gray-500,
        .dark #mainContent .chart-empty span,
        .dark #mainContent .action-card p,
        .dark #mainContent .text-gray-400,
        .dark #mainContent .text-gray-500 {
            color: #C9D1D9 !important;
        }

        [data-theme="dark"] #mainContent .service-row,
        [data-theme="dark"] #mainContent .inventory-shell .col-span-1.bg-gray-50,
        .dark #mainContent .service-row,
        .dark #mainContent .inventory-shell .col-span-1.bg-gray-50 {
            background: rgba(13, 17, 23, 0.72) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            box-shadow: none !important;
        }

        [data-theme="dark"] #mainContent .period-select,
        .dark #mainContent .period-select {
            background-color: rgba(13, 17, 23, 0.78) !important;
            color: #C9D1D9 !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }

        [data-theme="dark"] #mainContent .action-card:hover,
        .dark #mainContent .action-card:hover {
            border-color: rgba(252, 165, 165, 0.25) !important;
            background:
                radial-gradient(circle at top left, rgba(139, 0, 0, 0.26), transparent 34%),
                linear-gradient(145deg, rgba(13, 17, 23, 0.90), rgba(22, 27, 34, 0.80)) !important;
        }

        [data-theme="dark"] #mainContent .stock-bar-bg,
        .dark #mainContent .stock-bar-bg {
            background: #30363D !important;
        }

        [data-theme="dark"] #mainContent .report-hero-btn,
        .dark #mainContent .report-hero-btn {
            background: linear-gradient(135deg, #8B0000, #5F0000) !important;
            border-color: rgba(252, 165, 165, 0.18) !important;
            color: #FFFFFF !important;
        }

        @media (max-width: 767px) {

            #mainContent .report-hero-actions {
                align-self: stretch !important;
                width: 100% !important;
            }
        }

    #mainContent .report-hero-actions {
        position: relative !important;
        z-index: 4 !important;
        margin-left: auto !important;
        margin-top: 0 !important;
        align-self: center !important;
        flex: 0 0 auto !important;
        width: auto !important;
        padding-right: 128px !important;
        display: flex !important;
        justify-content: flex-end !important;
    }

    #mainContent .report-hero-btn {
        width: auto !important;
        min-width: 158px !important;
        white-space: nowrap !important;
    }

    @media (max-width: 1024px) {
        #mainContent .report-hero-actions {
            padding-right: 92px !important;
        }
    }

    @media (max-width: 767px) {
        #mainContent.dentist-page-shell {
            padding-left: 14px !important;
            padding-right: 14px !important;
            padding-bottom: 28px !important;
        }

        #mainContent .report-hero-meta {
            margin-top: 12px !important;
            display: flex !important;
            gap: 7px !important;
            width: 100% !important;
        }

        #mainContent .summary-tag {
            max-width: 100% !important;
            font-size: 10px !important;
            padding: 6px 9px !important;
            line-height: 1.15 !important;
        }

        #mainContent .report-hero-actions {
            width: 100% !important;
            padding-right: 0 !important;
            margin: 0 !important;
            align-self: stretch !important;
            justify-content: stretch !important;
        }

        #mainContent .report-hero-btn {
            width: 100% !important;
            min-width: 0 !important;
            min-height: 46px !important;
            border-radius: 14px !important;
        }

        #mainContent .analytics-section-label {
            font-size: 0.72rem !important;
            line-height: 1.3 !important;
            margin-bottom: 12px !important;
        }

        #mainContent .kpi-grid-layout,
        #mainContent .analytics-subgrid,
        #mainContent .analytics-main-grid,
        #mainContent .analytics-secondary-grid {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
            margin-bottom: 22px !important;
        }

        #mainContent .kpi-card,
        #mainContent .mini-kpi,
        #mainContent .chart-card,
        #mainContent .pro-card,
        #mainContent .inventory-shell,
        #mainContent .action-card {
            border-radius: 18px !important;
        }

        #mainContent .kpi-card {
            min-height: 104px !important;
            padding: 14px !important;
        }

        #mainContent .mini-kpi {
            min-height: 92px !important;
        }

        #mainContent .chart-card-header {
            gap: 10px !important;
            align-items: flex-start !important;
        }

        #mainContent .period-select {
            max-width: 150px !important;
            font-size: 0.7rem !important;
        }

        #mainContent #gadChartWrap,
        #mainContent #weeklyChartWrap {
            min-height: 230px !important;
        }

        #mainContent .relative.h-\[280px\] {
            height: 240px !important;
        }

        #mainContent .inventory-shell {
            padding: 16px !important;
        }

        #mainContent .inventory-shell .grid {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
        }
    }

    @media (max-width: 420px) {
        #mainContent.dentist-page-shell {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        #mainContent .report-hero-meta {
            flex-direction: column !important;
            align-items: stretch !important;
        }

        #mainContent .summary-tag {
            width: 100% !important;
            justify-content: flex-start !important;
        }

        #mainContent .chart-card,
        #mainContent .pro-card,
        #mainContent .inventory-shell {
            padding: 14px !important;
        }
    }

</style>
@endsection

@section('content')

    @php
        $notifications = collect($notifications ?? []);
        $notifCount = $notifications->count();

        $cancellationRate =
            $appointmentsToday > 0
                ? round((($cancelledAppointments ?? 0) / max($totalAppointmentsThisMonth ?? 1, 1)) * 100)
                : $cancellationRate ?? 0;

        $avgPatientsPerDay = $avgPatientsPerDay ?? 0;
        $returningPatients = $returningPatients ?? 0;
        $newPatients = $newPatients ?? 0;

        $topServices = collect($topServices ?? []);
    @endphp

    <main id="mainContent" class="dentist-page-shell page-enter">
        <div class="w-full fade-in">

            <div class="dentist-hero page-title-row mb-6">
                <div class="dentist-hero-content">
                    <div class="dentist-hero-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="dentist-hero-eyebrow">
                            <i class="fa-solid fa-tooth"></i>
                            Clinic Insights
                        </div>

                        <h2 class="dentist-hero-title">
                            Reports &amp; Analytics
                        </h2>

                        <div class="report-hero-meta">
                            <span class="summary-tag">
                                <span class="summary-tag-dot bg-red-700"></span>
                                {{ $totalAppointmentsThisMonth ?? 0 }} monthly appointments
                            </span>
                            <span class="summary-tag">
                                <span class="summary-tag-dot bg-green-500"></span>
                                {{ $completedAppointments ?? 0 }} completed
                            </span>
                            <span class="summary-tag">
                                <span class="summary-tag-dot bg-orange-500"></span>
                                Updated {{ now()->format('M d, Y h:i A') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="report-hero-actions">
                    <button onclick="openModal('createReportModal')" class="report-hero-btn" type="button">
                        <i class="fa-solid fa-plus"></i>
                        Create Report
                    </button>
                </div>
            </div>

            <div class="analytics-section-label">
                <i class="fa-solid fa-chart-line"></i>
                Clinic Performance Overview
            </div>

            <div class="kpi-grid-layout mb-10">

                <a href="{{ route('dentist.dentist.patients') }}" class="kpi-card group">
                    <div class="kpi-icon bg-red-50 group-hover:bg-[#8B0000] transition-colors"><i
                            class="fa-solid fa-users text-[#8B0000] group-hover:text-white transition-colors"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="kpi-value">{{ $patientsThisMonth }}</div>
                        <div class="kpi-label">Patients This Month</div>
                        @if (!is_null($patientsDelta))
                            <div class="kpi-delta {{ $patientsDelta >= 0 ? 'up' : 'down' }}">
                                <i class="fa-solid fa-arrow-{{ $patientsDelta >= 0 ? 'up' : 'down' }}"></i>
                                {{ abs($patientsDelta) }}%
                            </div>
                        @else
                            <div class="kpi-delta text-gray-400 font-normal">No data last month</div>
                        @endif
                    </div>
                    <i class="fa-solid fa-chevron-right kpi-arrow"></i>
                </a>

                <a href="{{ route('dentist.dentist.appointments') }}" class="kpi-card group">
                    <div class="kpi-icon bg-amber-50 group-hover:bg-amber-500 transition-colors"><i
                            class="fa-solid fa-calendar-check text-amber-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="kpi-value">{{ $appointmentsToday }}</div>
                        <div class="kpi-label">Appointments Today</div>
                        @if ($appointmentsDelta > 0)
                            <div class="kpi-delta up"><i class="fa-solid fa-arrow-up"></i> {{ $appointmentsDelta }} more
                            </div>
                        @elseif($appointmentsDelta < 0)
                            <div class="kpi-delta down"><i class="fa-solid fa-arrow-down"></i>
                                {{ abs($appointmentsDelta) }}
                                fewer
                            </div>
                        @else
                            <div class="kpi-delta text-gray-400 font-normal">Same as yesterday</div>
                        @endif
                    </div>
                    <i class="fa-solid fa-chevron-right kpi-arrow"></i>
                </a>

                <div class="kpi-card">
                    <div class="kpi-icon bg-rose-50">
                        <i class="fa-solid fa-calendar-xmark text-rose-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="kpi-value">{{ $cancellationRate }}%</div>
                        <div class="kpi-label">Cancellation Rate</div>
                        <div class="kpi-delta text-gray-400 font-normal">
                            Based on recorded appointments
                        </div>
                    </div>
                </div>

                <a href="{{ route('dentist.dentist.inventory') }}" class="kpi-card group !border-red-200 bg-red-50/30">
                    <div class="kpi-icon bg-red-100 group-hover:bg-red-600 transition-colors"><i
                            class="fa-solid fa-triangle-exclamation text-red-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="kpi-value text-red-600">{{ $lowStockItems }}</div>
                        <div class="kpi-label text-red-800">Low Stock Items</div>
                        @if ($lowStockItems > 0)
                            <div class="kpi-delta down"><i class="fa-solid fa-circle-exclamation"></i> Requires reorder
                            </div>
                        @else
                            <div class="kpi-delta up"><i class="fa-solid fa-circle-check"></i> All stocked up</div>
                        @endif
                    </div>
                    <i class="fa-solid fa-chevron-right kpi-arrow"></i>
                </a>

            </div>

            <div class="analytics-section-label">Patient and clinic insights</div>

            <div class="analytics-subgrid">
                <div class="mini-kpi">
                    <div class="mini-kpi-label">Cases This Month</div>
                    <div class="mini-kpi-value">{{ $casesThisMonth }}</div>
                </div>

                <div class="mini-kpi">
                    <div class="mini-kpi-label">Avg Patients / Day</div>
                    <div class="mini-kpi-value">{{ $avgPatientsPerDay }}</div>
                </div>

                <div class="mini-kpi">
                    <div class="mini-kpi-label">Returning Patients</div>
                    <div class="mini-kpi-value">{{ $returningPatients }}</div>
                </div>

                <div class="mini-kpi">
                    <div class="mini-kpi-label">New Patients</div>
                    <div class="mini-kpi-value">{{ $newPatients }}</div>
                </div>
            </div>

            <div class="analytics-main-grid">
                @php
                    $cleanPeriods = collect($periodOptions)
                        ->unique()
                        ->sortByDesc(function ($date) {
                            return \Carbon\Carbon::parse($date);
                        });
                @endphp

                <div class="chart-card lg:col-span-1">
                    <div class="chart-card-header">
                        <span class="chart-title"><i class="fa-solid fa-chart-column"></i> GAD Report</span>
                        <select class="period-select" id="gadPeriodSelect">
                            @foreach ($cleanPeriods as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="gadChartWrap" class="relative flex-1 min-h-[260px]">
                        <canvas id="gadChart"></canvas>
                        <div id="gadEmptyState" class="chart-empty hidden absolute inset-0">
                            <i class="fa-solid fa-chart-column"></i>
                            <p>No records found</p><span>for the selected period</span>
                        </div>
                        <div id="gadLoadingState" class="chart-loading hidden absolute inset-0"><i
                                class="fa-solid fa-spinner"></i></div>
                    </div>
                </div>

                <div class="chart-card lg:col-span-1">
                    <div class="chart-card-header">
                        <span class="chart-title"><i class="fa-solid fa-chart-line"></i> Weekly Cases</span>
                        <select class="period-select" id="weeklyPeriodSelect">
                            @foreach ($cleanPeriods as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="weeklyChartWrap" class="relative flex-1 min-h-[260px]">
                        <canvas id="weeklyDentalCasesChart"></canvas>
                        <div id="weeklyEmptyState" class="chart-empty hidden absolute inset-0">
                            <i class="fa-solid fa-chart-line"></i>
                            <p>No appointment data</p><span>for the selected period</span>
                        </div>
                        <div id="weeklyLoadingState" class="chart-loading hidden absolute inset-0"><i
                                class="fa-solid fa-spinner"></i></div>
                    </div>
                </div>

                <div class="pro-card">
                    <div class="chart-card-header">
                        <span class="chart-title">
                            <i class="fa-solid fa-user-group"></i>
                            Returning vs New Patients
                        </span>
                    </div>

                    <div class="relative h-[280px]">
                        @if (($returningPatients ?? 0) > 0 || ($newPatients ?? 0) > 0)
                            <canvas id="patientSegmentChart"></canvas>
                        @else
                            <div class="chart-empty absolute inset-0">
                                <i class="fa-solid fa-user-group"></i>
                                <p>No patient segment data</p>
                                <span>Returning and new patient insights will appear here.</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
            <div class="analytics-secondary-grid">
                <div class="pro-card">
                    <div class="chart-card-header">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="chart-title">
                                <i class="fa-solid fa-star"></i>
                                Top Dental Services
                            </span>
                        </div>
                        <span class="metric-chip">Top this month</span>
                    </div>

                    @if ($topServices->count() > 0)
                        <div class="service-list">
                            @foreach ($topServices->take(5)->values() as $index => $service)
                                <div class="service-row">
                                    <div class="service-meta">
                                        <div class="service-rank">{{ $index + 1 }}</div>
                                        <div class="service-name">
                                            {{ $service->name ?? ($service['name'] ?? 'Service') }}
                                        </div>
                                    </div>
                                    <div class="service-count">
                                        {{ $service->total ?? ($service['total'] ?? 0) }} cases
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="chart-empty py-6">
                            <i class="fa-solid fa-tooth"></i>
                            <p>No service data available</p>
                            <span>Top performed treatments will appear here.</span>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-4">
                    <h3 class="text-sm font-extrabold text-gray-800 uppercase tracking-wide px-1">Quick Reports</h3>

                    <a href="{{ route('dentist.dentist.report.dental-services') }}" class="action-card group">
                        <div class="action-icon"><i class="fa-solid fa-tooth"></i></div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 group-hover:text-[#8B0000] transition-colors">
                                Dental Services
                            </h4>
                            <p class="text-[11px] text-gray-400 mt-0.5">View and export full service logs</p>
                        </div>
                        <i
                            class="fa-solid fa-chevron-right text-gray-300 ml-auto text-xs group-hover:text-[#8B0000] transition-colors"></i>
                    </a>

                    <a href="{{ route('dentist.dentist.report.daily-treatment') }}" class="action-card group">
                        <div class="action-icon"><i class="fa-solid fa-notes-medical"></i></div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 group-hover:text-[#8B0000] transition-colors">
                                Daily Treatment Record
                            </h4>
                            <p class="text-[11px] text-gray-400 mt-0.5">Track daily patient treatments</p>
                        </div>
                        <i
                            class="fa-solid fa-chevron-right text-gray-300 ml-auto text-xs group-hover:text-[#8B0000] transition-colors"></i>
                    </a>
                </div>
            </div>
            <div class="inventory-shell p-5 md:p-6 mb-8">
                <div class="chart-card-header mb-6">
                    <span class="chart-title text-base"><i class="fa-solid fa-boxes-stacked"></i> Inventory
                        Analytics</span>
                    <a href="{{ route('dentist.dentist.inventory') }}"
                        class="text-[11px] font-bold text-[#8B0000] bg-red-50 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors">
                        Manage Inventory
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                    <div class="col-span-1">
                        <h3 class="text-center text-[11px] font-bold text-gray-500 mb-4 uppercase tracking-wider">
                            Medicine
                            Stock</h3>
                        <div class="relative h-[220px] w-full">
                            @if ($medicineItems->count() > 0)
                                <canvas id="medicinePieChart"></canvas>
                            @else
                                <a href="{{ route('dentist.dentist.inventory') }}"
                                    class="chart-empty absolute inset-0 hover:bg-gray-50 transition cursor-pointer">

                                    <i class="fa-solid fa-pills group-hover:scale-110 transition"></i>
                                    <p>No medicine stock available</p>
                                    <span>Add medicines to track inventory levels.</span>

                                    <button type="button"
                                        onclick="event.stopPropagation(); window.location='{{ route('dentist.dentist.inventory') }}'"
                                        class="mt-4 flex items-center gap-2 px-5 py-2 bg-[#8B0000] text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-[#6b0000] hover:shadow-md transition-all">
                                        <div
                                            class="w-5 h-5 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-plus text-[8px] text-white leading-none"></i>
                                        </div>
                                        <span class="text-white">Add Medicine</span>
                                    </button>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="col-span-1">
                        <h3 class="text-center text-[11px] font-bold text-gray-500 mb-4 uppercase tracking-wider">
                            Medical
                            Supplies</h3>
                        <div class="relative h-[220px] w-full">
                            @if ($suppliesItems->count() > 0)
                                <canvas id="suppliesPieChart"></canvas>
                            @else
                                <a href="{{ route('dentist.dentist.inventory') }}"
                                    class="chart-empty absolute inset-0 hover:bg-gray-50 transition cursor-pointer">

                                    <i class="fa-solid fa-box-open group-hover:scale-110 transition"></i>
                                    <p>No medical supplies found</p>
                                    <span>Add supplies to monitor usage and stock.</span>

                                    <button type="button"
                                        onclick="event.stopPropagation(); window.location='{{ route('dentist.dentist.inventory') }}'"
                                        class="mt-4 flex items-center gap-2 px-5 py-2 bg-[#8B0000] text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-[#6b0000] hover:shadow-md transition-all">
                                        <div
                                            class="w-5 h-5 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-plus text-[8px] leading-none"></i>
                                        </div>
                                        <span class="text-white">Add Supply</span>
                                    </button>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="col-span-1 bg-gray-50 rounded-xl p-5 border border-gray-100">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                            <span class="text-sm font-bold text-gray-800">Low Stock Alerts</span>
                        </div>

                        @if ($lowStockMedicine->count() > 0 || $lowStockSupplies->count() > 0)
                            <div class="overflow-y-auto max-h-[190px] pr-2 scroll-smooth">

                                @if ($lowStockMedicine->count() > 0)
                                    @foreach ($lowStockMedicine as $item)
                                        @php
                                            $remaining = $item->qty - $item->used;
                                            $pct = $item->qty > 0 ? round(($remaining / $item->qty) * 100) : 0;
                                        $barClass = $pct <= 15 ? 'bg-red-500' : 'bg-orange-400'; @endphp <div class="stock-row">
                                            <div class="stock-name">
                                                <span class="truncate pr-2">{{ $item->name }}</span>
                                                <span
                                                    class="text-red-600 font-bold text-[10px] whitespace-nowrap">{{ $remaining }}
                                                    left</span>
                                            </div>
                                            <div class="stock-bar-bg">
                                                <div class="stock-bar-fill {{ $barClass }}"
                                                    style="width:{{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if ($lowStockSupplies->count() > 0)
                                    @foreach ($lowStockSupplies as $item)
                                        @php
                                            $remaining = $item->qty - $item->used;
                                            $pct = $item->qty > 0 ? round(($remaining / $item->qty) * 100) : 0;
                                        $barClass = $pct <= 15 ? 'bg-red-500' : 'bg-orange-400'; @endphp <div class="stock-row">
                                            <div class="stock-name">
                                                <span class="truncate pr-2">{{ $item->name }}</span>
                                                <span
                                                    class="text-red-600 font-bold text-[10px] whitespace-nowrap">{{ $remaining }}
                                                    left</span>
                                            </div>
                                            <div class="stock-bar-bg">
                                                <div class="stock-bar-fill {{ $barClass }}"
                                                    style="width:{{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                            </div>
                        @else
                            @php
                                $hasAnyInventoryData = $medicineItems->count() > 0 || $suppliesItems->count() > 0;
                            @endphp

                            @if ($hasAnyInventoryData)
                                <div class="flex flex-col items-center justify-center h-[160px] text-center">
                                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                                        <i class="fa-solid fa-check text-green-500 text-xl"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700">Stock levels are good</p>
                                    <p class="text-xs text-gray-500 mt-1">No items require immediate restocking.</p>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center h-[160px] text-center">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                        <i class="fa-solid fa-boxes-stacked text-gray-400 text-lg"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700">No inventory records yet</p>
                                    <p class="text-xs text-gray-500 mt-1">Add medicine or supply items to monitor low
                                        stock
                                        alerts.</p>

                                    <a href="{{ route('dentist.dentist.inventory') }}"
                                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-[#8B0000] text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-[#6b0000] transition-all">
                                        <i class="fa-solid fa-plus text-[10px] leading-none"></i>
                                        <span>Add Item</span>
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </main>

    <div id="createReportModal" class="ui-modal" onclick="closeModalOnBackdrop(event, 'createReportModal')">
        <div class="ui-modal-card modal-box max-w-xl p-0 rounded-2xl overflow-hidden bg-white shadow-2xl flex flex-col"
            style="max-height:min(90vh,640px);">
            <div
                class="bg-gradient-to-r from-[#8B0000] to-[#660000] px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-file-circle-plus text-white text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white leading-tight">Create Custom Report</h2>
                        <p class="text-white/65 text-[11px] mt-0.5">Fields marked <span
                                class="text-yellow-300 font-bold">*</span>
                            are required</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateModal()"
                    class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/35 flex items-center justify-center text-white transition-all flex-shrink-0">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 py-5">
                <form id="reportForm" class="space-y-4" novalidate>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider">Report Name
                                <span class="text-red-500">*</span></label>
                            <span id="reportNameCounter" class="text-[11px] font-semibold text-gray-400">0 / 100</span>
                        </div>
                        <input id="reportName" type="text" maxlength="100"
                            placeholder="e.g. GAD Monthly Report — Dec 2025"
                            class="w-full px-3.5 py-2 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors placeholder-gray-400" />
                        <p id="reportNameErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> Report name is required.
                        </p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Report Type
                            <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="reportType"
                                class="w-full px-3.5 py-2 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors appearance-none pr-10 text-gray-700">
                                <option value="" disabled selected>Select a report type...</option>
                                <option class="text-gray-800">GAD Report</option>
                                <option class="text-gray-800">Medicine Supply Report</option>
                                <option class="text-gray-800">Medical Supplies Report</option>
                                <option class="text-gray-800">Daily Treatment Record</option>
                                <option class="text-gray-800">Dental Services Report</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <p id="reportTypeErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> Please select a report type.
                        </p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Date Range
                            <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] text-gray-500 font-semibold uppercase mb-1">From <span
                                        class="text-red-400">*</span>
                                </p>
                                <div class="fp-date-input-wrap">
                                    <input id="dateFrom" type="text"
                                        class="w-full px-3.5 py-2 pr-10 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors js-flatpickr-date-max-today"
                                        placeholder="Select start date" readonly />
                                    <i class="fa-regular fa-calendar fp-date-icon"></i>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 font-semibold uppercase mb-1">To <span
                                        class="text-gray-400 normal-case font-normal">(optional)</span></p>
                                <div class="fp-date-input-wrap">
                                    <input id="dateTo" type="text"
                                        class="w-full px-3.5 py-2 pr-10 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors js-flatpickr-date-max-today"
                                        placeholder="Select end date" readonly />
                                    <i class="fa-regular fa-calendar fp-date-icon"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5"><i class="fa-solid fa-circle-info mr-1"></i>Leave "To"
                            empty to
                            report on a single date.</p>
                        <p id="dateFromErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1"><i
                                class="fa-solid fa-circle-exclamation"></i> Start date is required.</p>
                        <p id="dateFutureErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1"><i
                                class="fa-solid fa-circle-exclamation"></i> Dates cannot be in the future.</p>
                        <p id="dateRangeErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1"><i
                                class="fa-solid fa-circle-exclamation"></i> End date must be on or after start date.</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Quantity
                            <span class="text-red-500">*</span></label>
                        <input id="reportQty" type="number" min="1" max="100" step="1"
                            placeholder="1 – 100"
                            class="w-36 px-3.5 py-2 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors" />
                        <span class="text-[11px] text-gray-400 ml-2">Whole numbers only</span>
                        <p id="reportQtyErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> <span id="reportQtyErrMsg">Quantity must be
                                between 1 and
                                100.</span>
                        </p>
                    </div>
                    <div id="formErrorBanner"
                        class="hidden items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 text-sm font-medium">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 flex-shrink-0"></i>
                        Please complete all required fields before downloading.
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 border-t border-gray-100 px-6 py-4 flex justify-end gap-3 bg-gray-50">
                <button type="button" onclick="closeCreateModal()"
                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 bg-white text-sm font-bold hover:bg-gray-50 transition-all">Cancel</button>
                <button type="button" id="downloadReportBtn"
                    class="px-6 py-2.5 rounded-xl bg-[#8B0000] hover:bg-[#6b0000] text-white text-sm font-bold flex items-center gap-2 shadow-sm transition-all">
                    <i class="fa-solid fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>

    <div id="downloadCompleteModal" class="ui-modal" onclick="closeModalOnBackdrop(event, 'downloadCompleteModal')">
        <div class="ui-modal-card modal-box p-0 rounded-2xl overflow-hidden bg-white shadow-2xl max-w-sm">
            <div class="h-1.5 bg-gradient-to-r from-[#8B0000] to-[#FFD700] w-full"></div>
            <div class="px-8 py-10 text-center">
                <div
                    class="w-16 h-16 bg-green-50 border-2 border-green-200 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-solid fa-check text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#8B0000] mb-2">Download Complete!</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-7">Your report has been successfully generated and
                    downloaded.</p>
                <button onclick="closeDownloadModal()"
                    class="px-8 py-2.5 rounded-xl bg-[#8B0000] hover:bg-[#6b0000] text-white font-bold text-sm shadow-sm transition-all w-full">Done</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const PATIENT_SEGMENT_DATA = {
            labels: ['Returning Patients', 'New Patients'],
            values: [{{ $returningPatients ?? 0 }}, {{ $newPatients ?? 0 }}]
        };

        const GAD_DATA = {
            labels: @json($gadLabels),
            female: @json($gadFemale),
            male: @json($gadMale)
        };
        const WEEKLY_DATA = {
            labels: @json($weekLabels),
            datasets: @json($weeklyDatasets)
        };
        const MEDICINE_ITEMS = @json($medicineItems);
        const SUPPLIES_ITEMS = @json($suppliesItems);
        const AJAX_GAD_URL = "{{ route('dentist.dentist.report.gad-data') }}";
        const AJAX_WEEKLY_URL = "{{ route('dentist.dentist.report.weekly-data') }}";
        const PIE_COLORS = ['#8B0000', '#b30000', '#cc3333', '#e06666', '#f4cccc', '#d9534f', '#c0392b', '#922b21',
            '#641e16', '#f1948a'
        ];
        const isReportDark = () => document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark');
        const reportChartTextColor = () => isReportDark() ? '#C9D1D9' : '#374151';
        const reportChartGridColor = () => isReportDark() ? 'rgba(255,255,255,0.10)' : 'rgba(148,163,184,0.22)';
        const reportChartBorderColor = () => isReportDark() ? '#161B22' : '#ffffff';

        if (window.Chart) {
            Chart.defaults.color = reportChartTextColor();
            Chart.defaults.borderColor = reportChartGridColor();
        }
    </script>

    <script>
        let patientSegmentChartInstance = null;

        function buildPatientSegmentChart() {
            const total = PATIENT_SEGMENT_DATA.values.reduce((a, b) => a + b, 0);
            if (total === 0) return;

            if (patientSegmentChartInstance) {
                patientSegmentChartInstance.destroy();
                patientSegmentChartInstance = null;
            }

            patientSegmentChartInstance = new Chart(document.getElementById('patientSegmentChart'), {
                type: 'doughnut',
                data: {
                    labels: PATIENT_SEGMENT_DATA.labels,
                    datasets: [{
                        data: PATIENT_SEGMENT_DATA.values,
                        backgroundColor: ['#8B0000', '#FCA5A5'],
                        borderColor: reportChartBorderColor(),
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 14
                            }
                        }
                    }
                }
            });
        }

        function closeCreateModal() {
            closeModal('createReportModal');
            document.getElementById('reportForm').reset();
            document.getElementById('reportNameCounter').textContent = '0 / 100';
            document.getElementById('reportNameCounter').classList.replace('text-red-500', 'text-gray-400');
            ['reportNameErr', 'reportTypeErr', 'dateFromErr', 'dateFutureErr', 'dateRangeErr', 'reportQtyErr',
                'formErrorBanner'
            ]
            .forEach(id => {
                let el = document.getElementById(id);
                if (el) {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            });
            ['reportName', 'reportType', 'dateFrom', 'dateTo', 'reportQty']
            .forEach(id => {
                let el = document.getElementById(id);
                if (el) {
                    el.classList.remove('border-red-400');
                    el.classList.add('border-gray-300');
                }
            });

            document.querySelectorAll('.flatpickr-calendar.open').forEach(cal => {
                cal.classList.remove('open');
            });
        }

        function closeDownloadModal() {
            closeModal('downloadCompleteModal');
        }

        let gadChartInstance = null,
            weeklyChartInstance = null;

        function showGadEmpty() {
            document.getElementById('gadChart').style.display = 'none';
            document.getElementById('gadEmptyState').classList.remove('hidden');
            document.getElementById('gadEmptyState').classList.add('flex');
            document.getElementById('gadLoadingState').classList.add('hidden');
        }

        function showGadLoading() {
            document.getElementById('gadChart').style.display = 'none';
            document.getElementById('gadEmptyState').classList.add('hidden');
            document.getElementById('gadLoadingState').classList.remove('hidden');
            document.getElementById('gadLoadingState').classList.add('flex');
        }

        function showGadChart() {
            document.getElementById('gadChart').style.display = 'block';
            document.getElementById('gadEmptyState').classList.add('hidden');
            document.getElementById('gadLoadingState').classList.add('hidden');
        }

        function showWeeklyEmpty() {
            document.getElementById('weeklyDentalCasesChart').style.display = 'none';
            document.getElementById('weeklyEmptyState').classList.remove('hidden');
            document.getElementById('weeklyEmptyState').classList.add('flex');
            document.getElementById('weeklyLoadingState').classList.add('hidden');
        }

        function showWeeklyLoading() {
            document.getElementById('weeklyDentalCasesChart').style.display = 'none';
            document.getElementById('weeklyEmptyState').classList.add('hidden');
            document.getElementById('weeklyLoadingState').classList.remove('hidden');
            document.getElementById('weeklyLoadingState').classList.add('flex');
        }

        function showWeeklyChart() {
            document.getElementById('weeklyDentalCasesChart').style.display = 'block';
            document.getElementById('weeklyEmptyState').classList.add('hidden');
            document.getElementById('weeklyLoadingState').classList.add('hidden');
        }

        function buildGadChart(labels, female, male) {
            if (gadChartInstance) {
                gadChartInstance.destroy();
                gadChartInstance = null;
            }
            gadChartInstance = new Chart(document.getElementById('gadChart'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Female',
                        data: female,
                        backgroundColor: '#8B0000',
                        borderRadius: 4
                    }, {
                        label: 'Male',
                        data: male,
                        backgroundColor: '#FCA5A5',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${ctx.parsed.x} cases`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [4, 4],
                                color: reportChartGridColor()
                            },
                            title: {
                                display: true,
                                text: 'Number of Cases',
                                font: {
                                    family: 'Inter',
                                    size: 10
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false,
                                color: reportChartGridColor()
                            },
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        function buildWeeklyChart(labels, datasets) {
            if (weeklyChartInstance) {
                weeklyChartInstance.destroy();
                weeklyChartInstance = null;
            }
            weeklyChartInstance = new Chart(document.getElementById('weeklyDentalCasesChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} cases`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                color: reportChartGridColor()
                            },
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 10
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [4, 4],
                                color: reportChartGridColor()
                            },
                            ticks: {
                                precision: 0,
                                font: {
                                    family: 'Inter',
                                    size: 10
                                }
                            },
                            title: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function makePieChart(canvasId, items) {
            if (!items || items.length === 0) return;
            new Chart(document.getElementById(canvasId), {
                type: 'doughnut',
                data: {
                    labels: items.map(i => i.name),
                    datasets: [{
                        data: items.map(i => Math.max(0, i.qty - i.used)),
                        backgroundColor: PIE_COLORS.slice(0, items.length),
                        borderWidth: 2,
                        borderColor: reportChartBorderColor()
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    size: 10
                                },
                                usePointStyle: true,
                                boxWidth: 6,
                                padding: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.parsed} remaining`
                            }
                        }
                    }
                }
            });
        }

        async function reloadGadChart(period) {
            showGadLoading();
            try {
                const res = await fetch(`${AJAX_GAD_URL}?period=${encodeURIComponent(period)}`);
                const data = await res.json();
                if (data.empty) {
                    showGadEmpty();
                    return;
                }
                showGadChart();
                buildGadChart(data.labels, data.female, data.male);
            } catch (e) {
                showGadEmpty();
            }
        }
        async function reloadWeeklyChart(period) {
            showWeeklyLoading();
            try {
                const res = await fetch(`${AJAX_WEEKLY_URL}?period=${encodeURIComponent(period)}`);
                const data = await res.json();
                if (data.empty || !data.datasets || data.datasets.length === 0) {
                    showWeeklyEmpty();
                    return;
                }
                showWeeklyChart();
                buildWeeklyChart(data.labels, data.datasets);
            } catch (e) {
                showWeeklyEmpty();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {

            setTimeout(function() {
                const gadHasData = GAD_DATA.female.reduce((a, b) => a + b, 0) + GAD_DATA.male.reduce((a,
                    b) => a + b, 0) > 0;
                if (gadHasData) {
                    showGadChart();
                    buildGadChart(GAD_DATA.labels, GAD_DATA.female, GAD_DATA.male);
                } else {
                    showGadEmpty();
                }
                if (WEEKLY_DATA.datasets && WEEKLY_DATA.datasets.length > 0) {
                    showWeeklyChart();
                    buildWeeklyChart(WEEKLY_DATA.labels, WEEKLY_DATA.datasets);
                } else {
                    showWeeklyEmpty();
                }
                makePieChart('medicinePieChart', MEDICINE_ITEMS);
                makePieChart('suppliesPieChart', SUPPLIES_ITEMS);
            }, 150);

            document.getElementById('gadPeriodSelect').addEventListener('change', function() {
                reloadGadChart(this.value);
            });
            document.getElementById('weeklyPeriodSelect').addEventListener('change', function() {
                reloadWeeklyChart(this.value);
            });

            const todayStr = new Date().toISOString().split('T')[0];
            document.getElementById('dateFrom').setAttribute('max', todayStr);
            document.getElementById('dateTo').setAttribute('max', todayStr);

            function setError(inputId, errId, show) {
                const input = document.getElementById(inputId),
                    err = document.getElementById(errId);
                if (!input || !err) return;
                if (show) {
                    err.classList.remove('hidden');
                    err.classList.add('flex');
                    input.classList.add('border-red-400');
                    input.classList.remove('border-gray-300');
                } else {
                    err.classList.add('hidden');
                    err.classList.remove('flex');
                    input.classList.remove('border-red-400');
                    input.classList.add('border-gray-300');
                }
            }
            const clearError = (a, b) => setError(a, b, false);

            document.getElementById('downloadReportBtn').addEventListener('click', function() {
                const name = document.getElementById('reportName').value.trim();
                const type = document.getElementById('reportType').value;
                const from = document.getElementById('dateFrom').value;
                const to = document.getElementById('dateTo').value;
                const qty = parseInt(document.getElementById('reportQty').value, 10);
                let valid = true;

                setError('reportName', 'reportNameErr', !name);
                if (!name) valid = false;
                setError('reportType', 'reportTypeErr', !type);
                if (!type) valid = false;

                ['dateFromErr', 'dateFutureErr', 'dateRangeErr'].forEach(id => {
                    let el = document.getElementById(id);
                    if (el) {
                        el.classList.add('hidden');
                        el.classList.remove('flex');
                    }
                });
                ['dateFrom', 'dateTo'].forEach(id => {
                    let el = document.getElementById(id);
                    if (el) {
                        el.classList.remove('border-red-400');
                        el.classList.add('border-gray-300');
                    }
                });

                if (!from) {
                    document.getElementById('dateFromErr').classList.remove('hidden');
                    document.getElementById('dateFromErr').classList.add('flex');
                    document.getElementById('dateFrom').classList.add('border-red-400');
                    document.getElementById('dateFrom').classList.remove('border-gray-300');
                    valid = false;
                } else {
                    const fromFuture = from > todayStr,
                        toFuture = to && to > todayStr;
                    if (fromFuture || toFuture) {
                        document.getElementById('dateFutureErr').classList.remove('hidden');
                        document.getElementById('dateFutureErr').classList.add('flex');
                        if (fromFuture) {
                            document.getElementById('dateFrom').classList.add('border-red-400');
                            document.getElementById('dateFrom').classList.remove('border-gray-300');
                        }
                        if (toFuture) {
                            document.getElementById('dateTo').classList.add('border-red-400');
                            document.getElementById('dateTo').classList.remove('border-gray-300');
                        }
                        valid = false;
                    } else if (to && new Date(to) < new Date(from)) {
                        document.getElementById('dateRangeErr').classList.remove('hidden');
                        document.getElementById('dateRangeErr').classList.add('flex');
                        document.getElementById('dateTo').classList.add('border-red-400');
                        document.getElementById('dateTo').classList.remove('border-gray-300');
                        valid = false;
                    }
                }

                const qtyInvalid = isNaN(qty) || qty < 1 || qty > 100;
                document.getElementById('reportQtyErrMsg').textContent = (isNaN(qty) || qty < 1) ?
                    'Quantity must be between 1 and 100.' : 'Quantity cannot exceed 100.';
                setError('reportQty', 'reportQtyErr', qtyInvalid);
                if (qtyInvalid) valid = false;

                const banner = document.getElementById('formErrorBanner');
                if (!valid) {
                    banner.classList.remove('hidden');
                    banner.classList.add('flex');
                    const btn = document.getElementById('downloadReportBtn');
                    btn.classList.add('animate-bounce');
                    setTimeout(() => btn.classList.remove('animate-bounce'), 600);
                } else {
                    banner.classList.add('hidden');
                    banner.classList.remove('flex');
                    closeModal('createReportModal');
                    openModal('downloadCompleteModal');
                    document.getElementById('reportForm').reset();
                    document.getElementById('reportNameCounter').textContent = '0 / 100';
                    document.getElementById('reportNameCounter').classList.remove('text-red-500');
                    document.getElementById('reportNameCounter').classList.add('text-gray-400');
                    ['reportNameErr', 'reportTypeErr', 'dateFromErr', 'dateFutureErr', 'dateRangeErr',
                        'reportQtyErr'
                    ]
                    .forEach(id => {
                        let el = document.getElementById(id);
                        if (el) {
                            el.classList.add('hidden');
                            el.classList.remove('flex');
                        }
                    });
                }
            });

            document.getElementById('reportName').addEventListener('input', function() {
                const len = this.value.length,
                    counter = document.getElementById('reportNameCounter');
                counter.textContent = `${len} / 100`;
                counter.classList.toggle('text-red-500', len >= 90);
                counter.classList.toggle('text-gray-400', len < 90);
                if (this.value.trim()) clearError('reportName', 'reportNameErr');
                document.getElementById('formErrorBanner').classList.add('hidden');
            });
            document.getElementById('reportType').addEventListener('change', function() {
                if (this.value) clearError('reportType', 'reportTypeErr');
                document.getElementById('formErrorBanner').classList.add('hidden');
            });

            function checkDates() {
                const from = document.getElementById('dateFrom').value,
                    to = document.getElementById('dateTo').value;
                ['dateFromErr', 'dateFutureErr', 'dateRangeErr'].forEach(id => {
                    let el = document.getElementById(id);
                    if (el) {
                        el.classList.add('hidden');
                        el.classList.remove('flex');
                    }
                });
                ['dateFrom', 'dateTo'].forEach(id => {
                    let el = document.getElementById(id);
                    if (el) {
                        el.classList.remove('border-red-400');
                        el.classList.add('border-gray-300');
                    }
                });
                if (!from && !to) return;
                const fromFuture = from && from > todayStr,
                    toFuture = to && to > todayStr;
                if (fromFuture || toFuture) {
                    document.getElementById('dateFutureErr').classList.remove('hidden');
                    document.getElementById('dateFutureErr').classList.add('flex');
                    if (fromFuture) {
                        document.getElementById('dateFrom').classList.add('border-red-400');
                        document.getElementById('dateFrom').classList.remove('border-gray-300');
                    }
                    if (toFuture) {
                        document.getElementById('dateTo').classList.add('border-red-400');
                        document.getElementById('dateTo').classList.remove('border-gray-300');
                    }
                    return;
                }
                if (from && to && new Date(to) < new Date(from)) {
                    document.getElementById('dateRangeErr').classList.remove('hidden');
                    document.getElementById('dateRangeErr').classList.add('flex');
                    document.getElementById('dateTo').classList.add('border-red-400');
                    document.getElementById('dateTo').classList.remove('border-gray-300');
                }
                document.getElementById('formErrorBanner').classList.add('hidden');
            }
            document.getElementById('dateFrom').addEventListener('change', checkDates);
            document.getElementById('dateTo').addEventListener('change', checkDates);

            const qtyInput = document.getElementById('reportQty');
            qtyInput.addEventListener('keydown', e => {
                if (['-', '+', 'e', 'E', '.', ','].includes(e.key)) e.preventDefault();
            });
            qtyInput.addEventListener('input', function() {
                let val = this.value.replace(/[^0-9]/g, '');
                if (val !== '' && parseInt(val, 10) > 100) val = '100';
                this.value = val;
                const qty = parseInt(val, 10);
                if (!isNaN(qty) && qty >= 1 && qty <= 100) clearError('reportQty', 'reportQtyErr');
                document.getElementById('formErrorBanner').classList.add('hidden');
            });
            qtyInput.addEventListener('paste', e => {
                e.preventDefault();
                const num = parseInt((e.clipboardData || window.clipboardData).getData('text').replace(
                    /[^0-9]/g, ''), 10);
                if (!isNaN(num)) qtyInput.value = Math.min(Math.max(num, 1), 100);
            });

            buildPatientSegmentChart();
        });
    </script>
@endsection
