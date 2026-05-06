@extends('layouts.admin')

@section('title', 'Admin Dashboard | PUP Taguig Dental Clinic')

@section('styles')
    <style>
        * {
            box-sizing: border-box;
        }

        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #bbb;
        }

        /* =========================================================
           TOKENS + ANIMATIONS
        ========================================================= */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes inventoryBubbleFloat {

            0%,
            100% {
                transform: translateY(0) translateX(0) scale(1);
            }

            50% {
                transform: translateY(-6px) translateX(-4px) scale(1.06);
            }
        }

        @keyframes inventoryBubbleDrift {

            0%,
            100% {
                transform: translateY(0) translateX(0);
            }

            50% {
                transform: translateY(4px) translateX(-6px);
            }
        }

        @keyframes inventoryPulsePop {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1.015);
            }
        }

        /* HEADER / PAGE BANNER */
        .page-banner {
            background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 60%, #c0392b 100%);
            padding: 2rem 2rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .page-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .page-banner::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -60px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .04);
            pointer-events: none;
        }

        .page-banner-inner {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-greeting {
            font-size: .75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .65);
            letter-spacing: .05em;
            text-transform: uppercase;
            margin-bottom: .3rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
        }

        .page-subtitle {
            font-size: .78rem;
            color: rgba(255, 255, 255, .6);
            margin-top: .4rem;
        }

        .period-pill {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 14px;
            padding: .75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            backdrop-filter: blur(8px);
            flex-wrap: wrap;
        }

        .period-item {
            text-align: left;
        }

        .period-label {
            font-size: .6rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .55);
            text-transform: uppercase;
            letter-spacing: .08em;
            display: block;
        }

        .period-value {
            font-size: .95rem;
            font-weight: 800;
            color: #fff;
            display: block;
            margin-top: 2px;
        }

        .period-divider {
            width: 1px;
            height: 32px;
            background: rgba(255, 255, 255, .2);
        }

        .manage-btn {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .25);
            color: #fff;
            padding: .6rem 1.1rem;
            border-radius: 10px;
            font-size: .75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: .5rem;
            white-space: nowrap;
            font-family: 'Inter', sans-serif;
        }

        .manage-btn:hover {
            background: rgba(255, 255, 255, .25);
            transform: translateY(-1px);
        }

        .content-lift {
            margin-top: -2rem;
            padding: 0 1.75rem 2rem;
            position: relative;
            z-index: 2;
        }

        /* TOP STAT CARDS */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            border: 1px solid rgba(0, 0, 0, .05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06), 0 1px 3px rgba(0, 0, 0, .04);
            transition: transform .2s, box-shadow .2s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, .1), 0 2px 6px rgba(0, 0, 0, .05);
        }

        @keyframes statCornerDrift {
            0%, 100% {
                transform: translate(0, 0);
            }
            50% {
                transform: translate(-6px, -4px);
            }
        }

        .stat-card::after {
            content: '';
            position: absolute;
            width: 145px;
            height: 145px;
            right: -52px;
            bottom: -62px;
            border-radius: 999px;
            pointer-events: none;
            opacity: .20;
            animation: statCornerDrift 7s ease-in-out infinite;
        }

        .stat-card:nth-child(1)::after {
            background: var(--crimson);
        }

        .stat-card:nth-child(2)::after {
            background: #3b82f6;
            animation-delay: .4s;
        }

        .stat-card:nth-child(3)::after {
            background: #22c55e;
            animation-delay: .8s;
        }

        .stat-card-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .stat-badge {
            font-size: .68rem;
            font-weight: 700;
            padding: .3rem .75rem;
            border-radius: 20px;
        }

        .stat-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9ca3af;
            margin-bottom: .3rem;
        }

        .stat-value {
            font-size: 2.4rem;
            font-weight: 900;
            line-height: 1;
            color: #1a202c;
            letter-spacing: -.03em;
            margin-bottom: .5rem;
        }

        .stat-footer {
            font-size: .7rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        /*  LAYOUT  */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.25rem;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, .05);
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            overflow: hidden;
        }

        .card-header {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .card-header-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--crimson-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: var(--crimson);
        }

        .card-title {
            font-size: .82rem;
            font-weight: 800;
            color: #1a202c;
        }

        .card-link {
            font-size: .72rem;
            color: var(--crimson);
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .3rem;
            transition: gap .15s;
        }

        .card-link:hover {
            gap: .5rem;
        }

        .card-header-actions {
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .view-toggle {
            display: inline-flex;
            align-items: center;
            background: #FAFAF9;
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            padding: 3px;
            gap: 3px;
            height: 38px;
        }

        .view-toggle-btn {
            width: 32px;
            height: 30px;
            padding: 0;
            border: none;
            background: transparent;
            color: #6b7280;
            border-radius: 9px;
            font-size: .82rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s ease;
            flex-shrink: 0;
        }

        .view-toggle-btn:hover {
            background: #f3f4f6;
            color: var(--crimson);
        }

        .view-toggle-btn.active {
            background: var(--crimson);
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
        }

        /* SYSTEM LOGS */
        .logs-view[hidden] {
            display: none !important;
        }

        .logs-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .log-card {
            background: #fff;
            border: 1px solid #f0eaea;
            border-radius: 16px;
            padding: 1rem;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            display: flex;
            flex-direction: column;
            gap: .8rem;
            min-width: 0;
        }

        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
            border-color: #ead6d6;
        }

        .log-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .log-card-id {
            font-size: .72rem;
            font-weight: 800;
            color: var(--crimson);
            line-height: 1.2;
            word-break: break-word;
        }

        .log-card-date {
            font-size: .68rem;
            color: #9ca3af;
            white-space: nowrap;
        }

        .log-card-desc {
            font-size: .8rem;
            color: #374151;
            line-height: 1.4;
            word-break: break-word;
        }

        .log-card-user {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .log-card-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--crimson), var(--crimson-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 700;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .log-card-user-info {
            min-width: 0;
            flex: 1;
        }

        .log-card-user-name {
            font-size: .8rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .log-card-user-role {
            font-size: .68rem;
            color: #9ca3af;
            margin-top: .15rem;
            text-transform: capitalize;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .log-stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: .75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .log-stat {
            text-align: center;
            padding: .7rem .5rem;
            border-radius: 10px;
            cursor: pointer;
            transition: transform .15s;
        }

        .log-stat:hover {
            transform: translateY(-2px);
        }

        .log-stat-value {
            font-size: 1.4rem;
            font-weight: 900;
            line-height: 1;
        }

        .log-stat-label {
            font-size: .58rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-top: 4px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .76rem;
        }

        .data-table thead th {
            padding: .7rem 1rem;
            text-align: left;
            font-weight: 700;
            color: #9ca3af;
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: #fafafa;
            border-bottom: 1px solid #f3f4f6;
        }

        .data-table tbody td {
            padding: .8rem 1rem;
            border-bottom: 1px solid #f9fafb;
        }

        .data-table tbody tr:hover td {
            background: #fafafa;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.4rem;
            color: #d1d5db;
        }

        /* INVENTORY OVERVIEW */
        .inventory-chart-card-body {
            padding: 1rem;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, .08), transparent 35%),
                radial-gradient(circle at top right, rgba(239, 68, 68, .06), transparent 38%),
                linear-gradient(180deg, #ffffff 0%, #fcfcfc 100%);
        }

        .inventory-empty {
            min-height: 220px;
            border-radius: 14px;
            border: 1px dashed #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fafafa, #fff);
            text-align: center;
        }

        .inventory-empty>div {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .inventory-top-layout {
            display: grid;
            grid-template-columns: 118px 1fr;
            gap: 1rem;
            align-items: center;
            margin-bottom: .9rem;
        }

        .inventory-donut-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 118px;
        }

        .inventory-donut-box {
            width: 108px;
            height: 108px;
            position: relative;
            margin: 0 auto;
            border-radius: 50%;
            background:
                radial-gradient(circle at center, #ffffff 56%, #f8fafc 100%);
            box-shadow:
                0 10px 24px rgba(17, 24, 39, .09),
                0 0 0 6px rgba(255, 255, 255, .80);
            transition: transform .18s ease, box-shadow .18s ease;
            overflow: visible;
        }

        .inventory-donut-center {
            position: absolute;
            inset: 50%;
            transform: translate(-50%, -50%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 1;
            text-align: center;
            pointer-events: none;
        }

        .inventory-donut-center span {
            font-size: 1.15rem;
            font-weight: 900;
            color: #111827;
            letter-spacing: -.03em;
        }

        .inventory-donut-center small {
            margin-top: 4px;
            font-size: .58rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .inventory-donut-box::before {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            background: conic-gradient(from 220deg,
                    rgba(34, 197, 94, .95) 0deg,
                    rgba(16, 185, 129, .92) 105deg,
                    rgba(245, 158, 11, .92) 210deg,
                    rgba(239, 68, 68, .95) 300deg,
                    rgba(34, 197, 94, .95) 360deg);
            z-index: 0;
            filter: saturate(1.05);
            opacity: .95;
        }

        .inventory-donut-box::after {
            content: '';
            position: absolute;
            inset: 8px;
            border-radius: 50%;
            background: radial-gradient(circle, #ffffff 62%, #f8fafc 100%);
            z-index: 1;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        #inventoryDonutChart {
            position: relative;
            z-index: 2;
        }

        .inventory-donut-box:hover {
            transform: scale(1.04);
        }

        #inventoryDonutChart {
            width: 108px !important;
            height: 108px !important;
            max-width: 108px !important;
            max-height: 108px !important;
            display: block;
            margin: 0 auto;
        }

        .inventory-legend {
            display: grid;
            grid-template-columns: 1fr;
            gap: .62rem;
        }

        .inventory-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .72rem .9rem;
            border: 1px solid #efe8e8;
            border-radius: 16px;
            background: linear-gradient(135deg, #ffffff, #fafafa);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }

        .inventory-legend-item::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(139, 0, 0, .04), rgba(255, 255, 255, 0));
            opacity: 0;
            transition: opacity .18s ease;
            pointer-events: none;
            z-index: 1;
        }

        .inventory-legend-item::before,
        .inventory-legend-item .legend-bubble,
        .inventory-legend-item .legend-bubble-sm {
            content: '';
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            z-index: 0;
        }

        .inventory-legend-item::before {
            width: 68px;
            height: 68px;
            top: -24px;
            right: -14px;
            opacity: .10;
            animation: inventoryBubbleFloat 6.5s ease-in-out infinite;
        }

        .inventory-legend-item .legend-bubble {
            width: 36px;
            height: 36px;
            bottom: -12px;
            left: 12px;
            opacity: .08;
            animation: inventoryBubbleFloat 7.5s ease-in-out infinite reverse;
        }

        .inventory-legend-item .legend-bubble-sm {
            width: 18px;
            height: 18px;
            top: 10px;
            right: 34px;
            opacity: .10;
            animation: inventoryBubbleDrift 5.8s ease-in-out infinite;
        }

        .inventory-legend-item[data-stock-filter="in-stock"]::before,
        .inventory-legend-item[data-stock-filter="in-stock"] .legend-bubble,
        .inventory-legend-item[data-stock-filter="in-stock"] .legend-bubble-sm {
            background: #22c55e;
        }

        .inventory-legend-item[data-stock-filter="low-stock"]::before,
        .inventory-legend-item[data-stock-filter="low-stock"] .legend-bubble,
        .inventory-legend-item[data-stock-filter="low-stock"] .legend-bubble-sm {
            background: #f59e0b;
        }

        .inventory-legend-item[data-stock-filter="out-stock"]::before,
        .inventory-legend-item[data-stock-filter="out-stock"] .legend-bubble,
        .inventory-legend-item[data-stock-filter="out-stock"] .legend-bubble-sm {
            background: #ef4444;
        }

        .inventory-legend-item:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 24px rgba(0, 0, 0, .08);
            border-color: #e7dede;
        }

        .inventory-legend-item:hover::after,
        .inventory-legend-item.active::after {
            opacity: 1;
        }

        .inventory-legend-item.active {
            border-color: #e5caca;
            box-shadow: 0 12px 24px rgba(139, 0, 0, .10);
        }

        .inventory-legend-item.pulse-pop {
            animation: inventoryPulsePop .28s ease;
        }

        .inventory-legend-left {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .inventory-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .9);
        }

        .inventory-legend-dot.in-stock {
            background: #22c55e;
        }

        .inventory-legend-dot.low-stock {
            background: #f59e0b;
        }

        .inventory-legend-dot.out-stock {
            background: #ef4444;
        }

        .inventory-legend-label {
            font-size: .82rem;
            font-weight: 800;
            color: #374151;
            line-height: 1.1;
        }

        .inventory-legend-sub {
            font-size: .68rem;
            color: #9ca3af;
            margin-top: 2px;
            line-height: 1.1;
        }

        .inventory-legend-value {
            font-size: 1.2rem;
            font-weight: 900;
            color: #111827;
            flex-shrink: 0;
            line-height: 1;
        }

        .inventory-mini-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .62rem;
            margin-top: .8rem;
        }

        .inventory-mini-pill {
            border: 1px solid #edf0f3;
            border-radius: 14px;
            padding: .72rem .45rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .03);
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .inventory-mini-pill:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 20px rgba(0, 0, 0, .07);
        }

        .inventory-mini-pill.total {
            background: linear-gradient(135deg, #f7f1eb 0%, #ffffff 100%);
        }

        .inventory-mini-pill.medicine {
            background: linear-gradient(135deg, #eef8f5 0%, #ffffff 100%);
        }

        .inventory-mini-pill.supplies {
            background: linear-gradient(135deg, #f2f6fb 0%, #ffffff 100%);
        }

        .inventory-mini-pill-label {
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9ca3af;
            margin-bottom: .18rem;
            line-height: 1.1;
        }

        .inventory-mini-pill-value {
            font-size: 1.1rem;
            font-weight: 900;
            line-height: 1;
            color: #111827;
        }

        [data-theme="dark"] .inventory-chart-card-body {
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, .10), transparent 35%),
                radial-gradient(circle at top right, rgba(239, 68, 68, .08), transparent 38%),
                linear-gradient(180deg, #161b22 0%, #11161d 100%);
        }

        [data-theme="dark"] .inventory-donut-box {
            background: radial-gradient(circle, #1f2937 58%, #111827 100%);
            box-shadow:
                0 8px 22px rgba(0, 0, 0, .25),
                0 0 0 6px rgba(22, 27, 34, .9);
        }

        [data-theme="dark"] .inventory-donut-box::after {
            background: radial-gradient(circle, #1f2937 62%, #111827 100%);
        }

        [data-theme="dark"] .inventory-donut-center span {
            color: #f3f4f6;
        }

        [data-theme="dark"] .inventory-donut-center small {
            color: #9ca3af;
        }

        [data-theme="dark"] .inventory-legend-item,
        [data-theme="dark"] .inventory-mini-pill {
            background: linear-gradient(180deg, #161b22, #111827);
            border-color: #21262d;
        }

        [data-theme="dark"] .inventory-legend-label,
        [data-theme="dark"] .inventory-legend-value,
        [data-theme="dark"] .inventory-mini-pill-value {
            color: #f3f4f6;
        }

        [data-theme="dark"] .inventory-legend-sub,
        [data-theme="dark"] .inventory-mini-pill-label {
            color: #9ca3af;
        }

        @media (max-width: 767px) {
            .inventory-top-layout {
                grid-template-columns: 1fr;
                gap: .8rem;
            }

            .inventory-donut-wrap {
                height: 104px;
            }

            .inventory-donut-box {
                width: 96px;
                height: 96px;
            }

            #inventoryDonutChart {
                width: 96px !important;
                height: 96px !important;
                max-width: 96px !important;
                max-height: 96px !important;
            }

            .inventory-legend-item {
                padding: .68rem .8rem;
            }

            .inventory-legend-label {
                font-size: .76rem;
            }

            .inventory-legend-sub {
                font-size: .62rem;
            }

            .inventory-legend-value {
                font-size: 1.05rem;
            }

            .inventory-mini-pill-label {
                font-size: .56rem;
            }

            .inventory-mini-pill-value {
                font-size: .95rem;
            }
        }

        /*  QUICK ACTIONS AND BACKUP */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-top: 1.25rem;
        }

        .qa-btn {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: .85rem 1rem;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            background: #fff;
            cursor: pointer;
            transition: all .15s;
            text-align: left;
            width: 100%;
            margin-bottom: .6rem;
            font-family: 'Inter', sans-serif;
        }

        .qa-btn:last-child {
            margin-bottom: 0;
        }

        .qa-btn:hover {
            border-color: var(--crimson-mid);
            background: var(--crimson-light);
            transform: translateX(3px);
        }

        .qa-btn:hover .qa-icon {
            background: var(--crimson);
            color: #fff;
        }

        .qa-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--crimson-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--crimson);
            font-size: .95rem;
            flex-shrink: 0;
            transition: all .15s;
        }

        .qa-text {
            flex: 1;
        }

        .qa-title {
            font-size: .8rem;
            font-weight: 700;
            color: var(--crimson);
            display: block;
        }

        .qa-sub {
            font-size: .68rem;
            color: #9ca3af;
            display: block;
            margin-top: 1px;
        }

        .qa-arrow {
            color: #d1d5db;
            font-size: .7rem;
            transition: all .15s;
        }

        .qa-btn:hover .qa-arrow {
            color: var(--crimson);
        }

        .backup-status {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1rem 1.1rem;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            margin-bottom: .75rem;
        }

        .backup-check {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #86efac;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #16a34a;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .backup-label {
            font-size: .6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #16a34a;
            display: block;
        }

        .backup-date {
            font-size: .85rem;
            font-weight: 800;
            color: #1a202c;
            display: block;
            margin-top: 2px;
        }

        .backup-sub {
            font-size: .65rem;
            color: #4ade80;
            margin-top: 1px;
            display: block;
        }

        .next-backup {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: .75rem;
        }

        .next-icon {
            color: #9ca3af;
            font-size: .85rem;
        }

        .next-label {
            font-size: .62rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .next-date {
            font-size: .8rem;
            font-weight: 800;
            color: #374151;
            margin-top: 1px;
        }

        .run-backup-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
            color: #fff;
            font-weight: 800;
            font-size: .8rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: all .2s;
            box-shadow: 0 4px 14px rgba(139, 0, 0, .3);
            font-family: 'Inter', sans-serif;
        }

        .run-backup-btn:hover {
            box-shadow: 0 6px 20px rgba(139, 0, 0, .4);
            transform: translateY(-1px);
        }

        .stat-grid .stat-card {
            animation: fadeSlideUp .4s ease both;
        }

        .stat-grid .stat-card:nth-child(1) {
            animation-delay: .05s;
        }

        .stat-grid .stat-card:nth-child(2) {
            animation-delay: .1s;
        }

        .stat-grid .stat-card:nth-child(3) {
            animation-delay: .15s;
        }

        .card {
            animation: fadeSlideUp .4s ease .2s both;
        }

        .gad-placeholder {
            height: 220px;
            border-radius: 14px;
            border: 1px dashed #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(135deg, #fafafa, #fff);
        }

        .gad-placeholder>div {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .gad-placeholder-icon {
            font-size: 2rem;
            color: #e5e7eb;
            display: block;
            margin-bottom: .5rem;
        }

        .gad-placeholder-text {
            font-size: .72rem;
            color: #b0b7c3;
            font-weight: 600;
        }

        /* DARK MODE / GLASS THEME */
        [data-theme="dark"] .stat-card {
            background:
                radial-gradient(circle at bottom right, rgba(255, 255, 255, .08), transparent 42%),
                linear-gradient(135deg, rgba(22, 27, 34, .72), rgba(17, 24, 39, .52)) !important;
            border: 1px solid rgba(255, 255, 255, .10);
            color: #fff;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .10),
                0 0 0 1px rgba(255, 255, 255, .025),
                0 10px 24px rgba(0, 0, 0, .18);
        }

        [data-theme="dark"] .stat-card:nth-child(1) {
            background:
                radial-gradient(circle at bottom right, rgba(248, 113, 113, .14), transparent 44%),
                linear-gradient(135deg, rgba(127, 0, 0, .48), rgba(17, 24, 39, .58)) !important;
            border-color: rgba(248, 113, 113, .18);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .10),
                0 0 18px rgba(248, 113, 113, .08),
                0 10px 24px rgba(0, 0, 0, .18);
        }

        [data-theme="dark"] .stat-card:nth-child(2) {
            background:
                radial-gradient(circle at bottom right, rgba(147, 197, 253, .14), transparent 44%),
                linear-gradient(135deg, rgba(29, 78, 216, .45), rgba(17, 24, 39, .58)) !important;
            border-color: rgba(147, 197, 253, .18);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .10),
                0 0 18px rgba(147, 197, 253, .08),
                0 10px 24px rgba(0, 0, 0, .18);
        }

        [data-theme="dark"] .stat-card:nth-child(3) {
            background:
                radial-gradient(circle at bottom right, rgba(74, 222, 128, .14), transparent 44%),
                linear-gradient(135deg, rgba(21, 128, 61, .45), rgba(17, 24, 39, .58)) !important;
            border-color: rgba(74, 222, 128, .18);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .10),
                0 0 18px rgba(74, 222, 128, .08),
                0 10px 24px rgba(0, 0, 0, .18);
        }

        [data-theme="dark"] .stat-card::after {
            content: '';
            position: absolute;
            width: 130px;
            height: 130px;
            right: -48px;
            bottom: -58px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
            pointer-events: none;
        }

        [data-theme="dark"] .stat-card:hover {
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .13),
                0 0 22px rgba(255, 255, 255, .055),
                0 14px 28px rgba(0, 0, 0, .22);
        }

        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .stat-footer {
            color: rgba(255,255,255,.75) !important;
        }

        [data-theme="dark"] .stat-value {
            color: #fff !important;
        }

        [data-theme="dark"] .stat-icon,
        [data-theme="dark"] .stat-badge {
            background: rgba(255,255,255,.12) !important;
            border: 1px solid rgba(255,255,255,.18);
            color: #fff !important;
        }

        [data-theme="dark"] .stat-icon i {
            color: #fff !important;
        }
        [data-theme="dark"] .page-banner {
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.03);
        }

        [data-theme="dark"] .page-subtitle {
            color: rgba(255, 255, 255, 0.72);
        }

        [data-theme="dark"] .period-pill {
            background: rgba(17, 24, 39, 0.42);
            border-color: rgba(255, 255, 255, 0.08);
        }

        [data-theme="dark"] .period-label {
            color: rgba(255, 255, 255, 0.58);
        }

        [data-theme="dark"] .period-divider {
            background: rgba(255, 255, 255, 0.12);
        }

        [data-theme="dark"] .manage-btn {
            background: rgba(255, 255, 255, 0.10);
            border-color: rgba(255, 255, 255, 0.12);
        }

        [data-theme="dark"] .manage-btn:hover {
            background: rgba(255, 255, 255, 0.16);
        }

        [data-theme="dark"] .log-card {
            background: #161b22;
            border-color: #21262d;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22);
        }

        [data-theme="dark"] .log-card:hover {
            border-color: #2d3748;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.28);
        }

        [data-theme="dark"] .log-card-desc,
        [data-theme="dark"] .log-card-user-name {
            color: #e5e7eb;
        }

        [data-theme="dark"] .log-card-date,
        [data-theme="dark"] .log-card-user-role,
        [data-theme="dark"] .log-stat-label {
            color: #9ca3af;
        }

        [data-theme="dark"] .log-card-avatar {
            background: linear-gradient(135deg, var(--crimson), var(--crimson-dark));
        }

        [data-theme="dark"] .log-stat {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            background:
                radial-gradient(circle at bottom right, rgba(255,255,255,.07), transparent 42%),
                linear-gradient(135deg, rgba(22, 27, 34, .68), rgba(17, 24, 39, .50)) !important;
            border: 1px solid rgba(255,255,255,.10);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.10),
                0 0 0 1px rgba(255,255,255,.025),
                0 8px 18px rgba(0,0,0,.16);
        }


        [data-theme="dark"] .log-stat:nth-child(1) {
            border-color: rgba(124, 58, 237, .22);
        }


        [data-theme="dark"] .log-stat:nth-child(2) {
            border-color: rgba(37, 99, 235, .22);
        }


        [data-theme="dark"] .log-stat:nth-child(3) {
            border-color: rgba(245, 158, 11, .22);
        }


        [data-theme="dark"] .log-stat:nth-child(4) {
            border-color: rgba(34, 197, 94, .22);
        }


        [data-theme="dark"] .log-stat:nth-child(5) {
            border-color: rgba(239, 68, 68, .22);
        }


        [data-theme="dark"] .log-stat:hover {
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.13),
                0 0 18px rgba(255,255,255,.045),
                0 12px 24px rgba(0,0,0,.20);
        }

        .log-stat::after {
            content: none !important;
            display: none !important;
        }

        [data-theme="dark"] .view-toggle {
            background: #111827;
            border-color: #2a3441;
        }

        [data-theme="dark"] .view-toggle-btn {
            color: #9ca3af;
        }

        [data-theme="dark"] .view-toggle-btn:hover {
            background: #1f2937;
            color: #f3f4f6;
        }

        [data-theme="dark"] .view-toggle-btn.active {
            background: var(--crimson);
            color: #fff;
        }

        [data-theme="dark"] .card {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .card-header,
        [data-theme="dark"] .log-stats-row {
            background: #0d1117 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .stat-value {
            color: #f3f4f6;
        }

        [data-theme="dark"] .card-title {
            color: #f3f4f6;
        }

        [data-theme="dark"] .data-table thead th {
            background: #0d1117;
            color: #6b7280;
            border-color: #21262d;
        }

        [data-theme="dark"] .data-table tbody td {
            color: #d1d5db;
            border-color: #1c2128;
        }

        [data-theme="dark"] .data-table tbody tr:hover td {
            background: #1c2128;
        }

        [data-theme="dark"] .next-backup,
        [data-theme="dark"] .qa-btn {
            background: #1c2128;
            border-color: #21262d;
        }

        [data-theme="dark"] .log-card-id {
            color: #cdcdcd;
        }

        [data-theme="dark"] .qa-title {
            color: #fca5a5;
        }

        [data-theme="dark"] .qa-sub {
            color: #adb1b8;
        }

        [data-theme="dark"] .qa-btn:hover {
            background: rgba(139, 0, 0, .15);
            border-color: #5b2020;
        }

        [data-theme="dark"] .next-date {
            color: #e5e7eb;
        }

        [data-theme="dark"] .empty-icon {
            background: #21262d;
        }

        [data-theme="dark"] .period-pill {
            background: rgba(255, 255, 255, .08);
        }

        [data-theme="dark"] .gad-placeholder {
            background: linear-gradient(135deg, #161b22, #111827);
            border-color: #2a3441;
        }

        [data-theme="dark"] .gad-placeholder-icon {
            color: #374151;
        }

        [data-theme="dark"] .gad-placeholder-text {
            color: #9ca3af;
        }


        [data-theme="dark"] .backup-status {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.10), rgba(22, 163, 74, 0.06));
            border-color: rgba(34, 197, 94, 0.18);
        }

        [data-theme="dark"] .backup-check {
            background: #111827;
            border-color: rgba(34, 197, 94, 0.24);
            color: #4ade80;
        }

        [data-theme="dark"] .backup-label {
            color: #86efac;
        }

        [data-theme="dark"] .backup-date {
            color: #f3f4f6;
        }

        [data-theme="dark"] .backup-sub,
        [data-theme="dark"] .next-label,
        [data-theme="dark"] .next-icon {
            color: #9ca3af;
        }


        [data-theme="dark"] .empty-state p {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .inventory-empty {
            background: linear-gradient(135deg, #161b22, #111827);
            border-color: #2a3441;
        }

        [data-theme="dark"] #inventoryOverviewEmptyText {
            color: #9ca3af !important;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .stat-grid {
                grid-template-columns: 1fr 1fr;
            }

            .stat-grid .stat-card:last-child {
                grid-column: span 2;
            }

            .content-lift {
                padding: 0 1rem 2rem;
            }

            .page-banner {
                padding: 1.5rem 1rem 3rem;
            }

            .period-pill {
                gap: .75rem;
            }

            .log-stats-row {
                grid-template-columns: repeat(3, 1fr);
            }

            .bottom-grid {
                grid-template-columns: 1fr;
            }

            #dashboardLogsViewToggle {
                display: none !important;
            }

            #dashboardLogsListView {
                display: none !important;
            }

            #dashboardLogsGridView {
                display: block !important;
            }

            .logs-grid {
                grid-template-columns: 1fr;
            }

            .card-header-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }

        @media (max-width: 480px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }

            .stat-grid .stat-card:last-child {
                grid-column: span 1;
            }
        }
    </style>
@endsection

@section('content')

    @php $logs = $logs ?? collect([]); @endphp

    <main id="mainContent" style="padding-top: var(--header-h); min-height: 100vh;">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <div class="page-greeting">
                        <i id="currentDateIcon" class="fa-solid fa-calendar-day" style="color:#fcd34d;"></i>
                        <span id="currentDate"></span>
                    </div>
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Welcome back, Administrator. Here's what's happening today.</p>
                </div>

                <div class="period-pill">
                    <div class="period-item">
                        <span class="period-label"><i class="fa-solid fa-calendar" style="margin-right:3px;"></i>
                            Semester</span>
                        <span class="period-value">
                            {{ $activePeriod?->semester ?? 'No Active Period' }}
                        </span>
                    </div>
                    <div class="period-divider"></div>
                    <div class="period-item">
                        <span class="period-label"><i class="fa-solid fa-graduation-cap" style="margin-right:3px;"></i>
                            Academic
                            Year</span>
                        <span class="period-value">
                            {{ $activePeriod?->academic_year ?? 'Not Set' }}
                        </span>
                    </div>
                    <div class="period-divider"></div>
                    <div class="period-item">
                        <span class="period-label"><i class="fa-solid fa-clock" style="margin-right:3px;"></i> Period
                            Ends</span>
                        <span class="period-value">
                            {{ $activePeriod?->end_date ? $activePeriod->end_date->format('F d, Y') : 'Not Set' }}
                        </span>
                    </div>
                    <a href="{{ route('admin.academic_periods') }}" class="manage-btn">
                        <i class="fa-solid fa-gear"></i> Manage
                    </a>
                </div>

            </div>
        </div>

        <div class="content-lift">

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-card-accent" style="background: linear-gradient(90deg, var(--crimson), #c0392b);">
                    </div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#fef2f2;">
                            <i class="fa-solid fa-users" style="color:var(--crimson);"></i>
                        </div>
                        <span class="stat-badge" style="background:#fef2f2;color:var(--crimson);">All time</span>
                    </div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value">{{ number_format($totalPatients) }}</div>
                    <div class="stat-footer">
                        <i class="fa-solid fa-user-plus" style="font-size:.65rem;color:var(--crimson);"></i>
                        All registered patients
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-accent" style="background: linear-gradient(90deg, #3b82f6, #2563eb);"></div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#eff6ff;">
                            <i class="fa-solid fa-calendar-check" style="color:#3b82f6;"></i>
                        </div>
                        <span class="stat-badge"
                            style="background:#eff6ff;color:#3b82f6;">{{ \Carbon\Carbon::now()->format('F Y') }}</span>
                    </div>
                    <div class="stat-label">Appointments</div>
                    <div class="stat-value">{{ $appointmentsThisMonth }}</div>
                    <div class="stat-footer">
                        <i class="fa-solid fa-clock" style="font-size:.65rem;color:#3b82f6;"></i>
                        This month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-accent" style="background: linear-gradient(90deg, #22c55e, #16a34a);"></div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#f0fdf4;">
                            <i class="fa-solid fa-file-arrow-up" style="color:#22c55e;"></i>
                        </div>
                        <span class="stat-badge"
                            style="background:#f0fdf4;color:#16a34a;">{{ \Carbon\Carbon::now()->format('F Y') }}</span>
                    </div>
                    <div class="stat-label">Documents Issued</div>
                    <div class="stat-value">{{ $documentsThisMonth }}</div>
                    <div class="stat-footer">
                        <i class="fa-solid fa-file-lines" style="font-size:.65rem;color:#22c55e;"></i>
                        This month
                    </div>
                </div>
            </div>

            <div class="main-grid">

                <div style="display:flex;flex-direction:column;gap:1.25rem;">

                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                                <span class="card-title">System Logs Overview</span>
                            </div>

                            <div class="card-header-actions">
                                <div class="view-toggle" id="dashboardLogsViewToggle">
                                    <button type="button" class="view-toggle-btn active" data-view="list"
                                        id="dashboardLogsListBtn" title="List view" aria-label="List view">
                                        <i class="fa-solid fa-table-list"></i>
                                    </button>
                                    <button type="button" class="view-toggle-btn" data-view="grid"
                                        id="dashboardLogsGridBtn" title="Grid view" aria-label="Grid view">
                                        <i class="fa-solid fa-grip"></i>
                                    </button>
                                </div>

                                <a href="{{ route('admin.system_logs') }}" class="card-link">
                                    View All <i class="fa-solid fa-arrow-right" style="font-size:.65rem;"></i>
                                </a>
                            </div>
                        </div>

                        <div class="log-stats-row">
                            <div class="log-stat" style="background:#f5f3ff;">
                                <div class="log-stat-value" style="color:#7c3aed;">{{ $logThisMonth ?? 0 }}</div>
                                <div class="log-stat-label" style="color:#7c3aed;">This Month</div>
                            </div>
                            <div class="log-stat" style="background:#eff6ff;">
                                <div class="log-stat-value" style="color:#2563eb;">{{ $logInfo ?? 0 }}</div>
                                <div class="log-stat-label" style="color:#3b82f6;">Views</div>
                            </div>
                            <div class="log-stat" style="background:#fffbeb;">
                                <div class="log-stat-value" style="color:#d97706;">{{ $logWarnings ?? 0 }}</div>
                                <div class="log-stat-label" style="color:#f59e0b;">Logins</div>
                            </div>
                            <div class="log-stat" style="background:#f0fdf4;">
                                <div class="log-stat-value" style="color:#16a34a;">{{ $logBackups ?? 0 }}</div>
                                <div class="log-stat-label" style="color:#22c55e;">Backups</div>
                            </div>
                            <div class="log-stat" style="background:#fef2f2;">
                                <div class="log-stat-value" style="color:var(--crimson);">{{ $logErrors ?? 0 }}</div>
                                <div class="log-stat-label" style="color:#ef4444;">Errors</div>
                            </div>
                        </div>

                        @if (($recentLogs ?? collect())->isEmpty())
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                                <p style="font-size:.82rem;font-weight:700;color:#6b7280;margin-bottom:.25rem;">
                                    No logs yet
                                </p>
                                <p style="font-size:.72rem;color:#b0b7c3;">System activity will appear here</p>
                            </div>
                        @else
                            <div class="logs-view" id="dashboardLogsListView">
                                <div style="overflow-x:auto;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th style="width:60px;">ID</th>
                                                <th style="width:160px;">Date & Time</th>
                                                <th>Description</th>
                                                <th style="width:120px;">User</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recentLogs ?? [] as $log)
                                                @php
                                                    $logId = data_get($log, 'id', '—');
                                                    $logDate = data_get($log, 'created_at');
                                                    $logDesc = data_get(
                                                        $log,
                                                        'description',
                                                        'No description provided.',
                                                    );
                                                    $logActor = data_get($log, 'actor_identifier', '—');
                                                    $logRole = data_get($log, 'actor_role', '');
                                                @endphp
                                                <tr>
                                                    <td style="color:#9ca3af;font-size:.72rem;">#{{ $logId }}</td>
                                                    <td>
                                                        <div style="font-size:.74rem;font-weight:600;">
                                                            {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('M j, Y') : '—' }}
                                                        </div>
                                                        <div style="font-size:.68rem;color:#9ca3af;">
                                                            {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('h:i:s A') : '—' }}
                                                        </div>
                                                    </td>
                                                    <td style="font-size:.76rem;">{{ $logDesc }}</td>
                                                    <td>
                                                        <span
                                                            style="font-size:.72rem;font-weight:600;">{{ $logActor }}</span>
                                                        <div
                                                            style="font-size:.65rem;color:#9ca3af;text-transform:capitalize;">
                                                            {{ $logRole }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="logs-view" id="dashboardLogsGridView" hidden>
                                <div class="logs-grid">
                                    @foreach ($recentLogs ?? [] as $log)
                                        @php
                                            $logId = data_get($log, 'id', '—');
                                            $logDate = data_get($log, 'created_at');
                                            $logDesc = data_get($log, 'description', 'No description provided.');
                                            $logActor = data_get($log, 'actor_identifier', '—');
                                            $logRole = data_get($log, 'actor_role', '');
                                            $logInitial = strtoupper(substr(trim($logActor), 0, 1));
                                        @endphp

                                        <div class="log-card">
                                            <div class="log-card-top">
                                                <div class="log-card-id">#{{ $logId }}</div>
                                                <div class="log-card-date">
                                                    {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('M d, Y h:i A') : '—' }}
                                                </div>
                                            </div>

                                            <div class="log-card-desc">
                                                {{ $logDesc }}
                                            </div>

                                            <div class="log-card-user">
                                                <div class="log-card-avatar">{{ $logInitial ?: '—' }}</div>
                                                <div class="log-card-user-info">
                                                    <div class="log-card-user-name">{{ $logActor }}</div>
                                                    <div class="log-card-user-role">{{ $logRole ?: 'No role' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bottom-grid">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-chart-pie"></i></div>
                                    <span class="card-title">GAD Analytics</span>
                                </div>
                                <a href="#" class="card-link">View <i class="fa-solid fa-arrow-right"
                                        style="font-size:.65rem;"></i></a>
                            </div>
                            <div style="padding:1.25rem;">
                                <div class="gad-placeholder">
                                    <div style="text-align:center;">
                                        <i class="fa-solid fa-chart-area gad-placeholder-icon"></i>
                                        <span class="gad-placeholder-text">Chart coming soon</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card inventory-overview-card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                                    <span class="card-title">Inventory Overview</span>
                                </div>
                                <a href="{{ route('admin.inventory') }}" class="card-link">
                                    View <i class="fa-solid fa-arrow-right" style="font-size:.65rem;"></i>
                                </a>
                            </div>

                            <div class="inventory-chart-card-body">

                                <div id="inventoryOverviewEmpty" class="inventory-empty" style="display:none;">
                                    <div>
                                        <i id="inventoryOverviewEmptyIcon" class="fa-solid fa-box-open"
                                            style="font-size:2rem;color:#e5e7eb;display:block;margin-bottom:.5rem;"></i>
                                        <span id="inventoryOverviewEmptyText"
                                            style="font-size:.72rem;color:#b0b7c3;font-weight:600;">
                                            No inventory records yet
                                        </span>
                                    </div>
                                </div>

                                <div id="inventoryOverviewContent" style="display:none;">
                                    <div class="inventory-top-layout">
                                        <div class="inventory-donut-wrap">
                                            <div class="inventory-donut-box">
                                                <div class="inventory-donut-center">
                                                    <span id="inventoryDonutTotal">0</span>
                                                    <small>Items</small>
                                                </div>
                                                <canvas id="inventoryDonutChart"></canvas>
                                            </div>
                                        </div>

                                        <div class="inventory-legend">
                                            <div class="inventory-legend-item" data-stock-filter="in-stock">
                                                <span class="legend-bubble"></span>
                                                <span class="legend-bubble-sm"></span>
                                                <div class="inventory-legend-left">
                                                    <span class="inventory-legend-dot in-stock"></span>
                                                    <div>
                                                        <div class="inventory-legend-label">In Stock</div>
                                                        <div class="inventory-legend-sub">Sufficient supply</div>
                                                    </div>
                                                </div>
                                                <div class="inventory-legend-value" id="inventoryInStockValue">0</div>
                                            </div>

                                            <div class="inventory-legend-item" data-stock-filter="low-stock">
                                                <span class="legend-bubble"></span>
                                                <span class="legend-bubble-sm"></span>
                                                <div class="inventory-legend-left">
                                                    <span class="inventory-legend-dot low-stock"></span>
                                                    <div>
                                                        <div class="inventory-legend-label">Low Stock</div>
                                                        <div class="inventory-legend-sub">Replenishment required</div>
                                                    </div>
                                                </div>
                                                <div class="inventory-legend-value" id="inventoryLowStockValue">0</div>
                                            </div>

                                            <div class="inventory-legend-item" data-stock-filter="out-stock">
                                                <span class="legend-bubble"></span>
                                                <span class="legend-bubble-sm"></span>
                                                <div class="inventory-legend-left">
                                                    <span class="inventory-legend-dot out-stock"></span>
                                                    <div>
                                                        <div class="inventory-legend-label">Out of Stock</div>
                                                        <div class="inventory-legend-sub">Currently unavailable</div>
                                                    </div>
                                                </div>
                                                <div class="inventory-legend-value" id="inventoryOutStockValue">0</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="inventory-mini-stats-row">
                                        <div class="inventory-mini-pill total">
                                            <div class="inventory-mini-pill-label">Total</div>
                                            <div class="inventory-mini-pill-value" id="inventoryTotalValue">0</div>
                                        </div>

                                        <div class="inventory-mini-pill medicine">
                                            <div class="inventory-mini-pill-label">Medicine</div>
                                            <div class="inventory-mini-pill-value" id="inventoryMedicineValue">0</div>
                                        </div>

                                        <div class="inventory-mini-pill supplies">
                                            <div class="inventory-mini-pill-label">Supplies</div>
                                            <div class="inventory-mini-pill-value" id="inventorySuppliesValue">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:1.25rem;">

                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-bolt"></i></div>
                                <span class="card-title">Quick Actions</span>
                            </div>
                        </div>
                        <div style="padding:1rem;">
                            <button class="qa-btn">
                                <div class="qa-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
                                <div class="qa-text">
                                    <span class="qa-title">New Template</span>
                                    <span class="qa-sub">Create document format</span>
                                </div>
                                <i class="fa-solid fa-chevron-right qa-arrow"></i>
                            </button>
                            <button class="qa-btn">
                                <div class="qa-icon"><i class="fa-solid fa-file-invoice"></i></div>
                                <div class="qa-text">
                                    <span class="qa-title">Generate Report</span>
                                    <span class="qa-sub">Create report documents</span>
                                </div>
                                <i class="fa-solid fa-chevron-right qa-arrow"></i>
                            </button>
                            <button class="qa-btn">
                                <div class="qa-icon"><i class="fa-solid fa-chart-column"></i></div>
                                <div class="qa-text">
                                    <span class="qa-title">View Reports</span>
                                    <span class="qa-sub">All reports & analytics</span>
                                </div>
                                <i class="fa-solid fa-chevron-right qa-arrow"></i>
                            </button>
                            <a href="{{ route('admin.user_management') }}" class="qa-btn">
                                <div class="qa-icon"><i class="fa-solid fa-user-plus"></i></div>
                                <div class="qa-text">
                                    <span class="qa-title">Add User</span>
                                    <span class="qa-sub">Register new account</span>
                                </div>
                                <i class="fa-solid fa-chevron-right qa-arrow"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-database"></i></div>
                                <span class="card-title">Data Backup</span>
                            </div>

                            @if ($autoBackupEnabled)
                                <span
                                    style="font-size:.65rem;font-weight:700;background:#f0fdf4;color:#16a34a;
                                        padding:.25rem .6rem;border-radius:20px;border:1px solid #bbf7d0;">
                                    Active
                                </span>
                            @else
                                <span
                                    style="font-size:.65rem;font-weight:700;background:#fef3c7;color:#a16207;
                                        padding:.25rem .6rem;border-radius:20px;border:1px solid #fcd34d;">
                                    Paused
                                </span>
                            @endif
                        </div>

                        <div style="padding:1rem;">
                            <div class="backup-status">
                                <div class="backup-check">
                                    <i class="fa-solid {{ $lastBackup ? 'fa-check' : 'fa-clock' }}"></i>
                                </div>
                                <div>
                                    <span class="backup-label">Last Backup</span>
                                    <span class="backup-date">
                                        {{ $lastBackup ? $lastBackup->created_at->format('F d, Y h:i A') : 'No backup yet' }}
                                    </span>
                                    <span class="backup-sub">
                                        {{ $lastBackup ? '✓ Completed successfully' : 'No completed backup found' }}
                                    </span>
                                </div>
                            </div>

                            <div class="next-backup">
                                <i class="fa-regular fa-clock next-icon"></i>
                                <div>
                                    <div class="next-label">Next Scheduled</div>
                                    <div class="next-date">
                                        {{ $nextBackupDate ? $nextBackupDate->format('F d, Y h:i A') : 'No schedule set' }}
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('admin.data_backup') }}" class="run-backup-btn"
                                style="text-decoration:none;">
                                <i class="fa-solid fa-database"></i>
                                View Backups ({{ $totalBackups }})
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

@endsection

@section('scripts')
    @if (session('activeAppointmentModal'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var modal = document.getElementById("activeAppointmentModal");
                var closeBtn = document.getElementById("closeActiveApptModalBtn");
                if (!modal) return;
                modal.showModal();
                modal.addEventListener('click', function(e) {
                    var box = modal.querySelector('.modal-box');
                    if (box && !box.contains(e.target)) e.preventDefault();
                });
                modal.addEventListener('cancel', function(e) {
                    e.preventDefault();
                });
                if (closeBtn) closeBtn.addEventListener("click", function() {
                    modal.close();
                });
            });
        </script>
    @endif

    <script>
        function getPreferredDashboardLogsView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('dashboardLogsView') || 'list';
        }

        function applyDashboardLogsView(view, save = true) {
            const listView = document.getElementById('dashboardLogsListView');
            const gridView = document.getElementById('dashboardLogsGridView');
            const listBtn = document.getElementById('dashboardLogsListBtn');
            const gridBtn = document.getElementById('dashboardLogsGridBtn');

            if (!listView || !gridView) return;

            const finalView = window.innerWidth <= 767 ? 'grid' : view;

            if (finalView === 'grid') {
                listView.hidden = true;
                gridView.hidden = false;
            } else {
                listView.hidden = false;
                gridView.hidden = true;
            }

            if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
            if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

            if (save && window.innerWidth > 767) {
                localStorage.setItem('dashboardLogsView', finalView);
            }
        }

        function initDashboardLogsViewToggle() {
            const listBtn = document.getElementById('dashboardLogsListBtn');
            const gridBtn = document.getElementById('dashboardLogsGridBtn');

            applyDashboardLogsView(getPreferredDashboardLogsView(), false);

            if (listBtn && !listBtn.dataset.bound) {
                listBtn.dataset.bound = '1';
                listBtn.addEventListener('click', () => applyDashboardLogsView('list', true));
            }

            if (gridBtn && !gridBtn.dataset.bound) {
                gridBtn.dataset.bound = '1';
                gridBtn.addEventListener('click', () => applyDashboardLogsView('grid', true));
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initDashboardLogsViewToggle();

            window.addEventListener('resize', function() {
                applyDashboardLogsView(getPreferredDashboardLogsView(), false);
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let adminInventoryOverviewChart = null;

        function animateInventoryLegendCard(card) {
            if (!card) return;
            card.classList.remove('pulse-pop');
            void card.offsetWidth;
            card.classList.add('pulse-pop');
        }

        function setInventoryOverviewEmptyState(message, isError = false) {
            const emptyEl = document.getElementById('inventoryOverviewEmpty');
            const contentEl = document.getElementById('inventoryOverviewContent');
            const textEl = document.getElementById('inventoryOverviewEmptyText');
            const iconEl = document.getElementById('inventoryOverviewEmptyIcon');

            if (!emptyEl || !contentEl) return;

            emptyEl.style.display = 'flex';
            contentEl.style.display = 'none';

            if (textEl) {
                textEl.textContent = message || 'No inventory records yet';
            }

            if (iconEl) {
                iconEl.className = isError ?
                    'fa-solid fa-triangle-exclamation' :
                    'fa-solid fa-box-open';
                iconEl.style.color = isError ? '#f59e0b' : '#e5e7eb';
            }
        }

        function showInventoryOverviewContent() {
            const emptyEl = document.getElementById('inventoryOverviewEmpty');
            const contentEl = document.getElementById('inventoryOverviewContent');

            if (emptyEl) emptyEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'block';
        }

        function bindInventoryLegendClicks() {
            document.querySelectorAll('.inventory-legend-item').forEach(card => {
                if (card.dataset.bound === '1') return;

                card.dataset.bound = '1';
                card.addEventListener('click', function() {
                    document.querySelectorAll('.inventory-legend-item').forEach(el => el.classList.remove(
                        'active'));
                    this.classList.add('active');
                    animateInventoryLegendCard(this);

                    const filter = this.dataset.stockFilter || '';
                    const target = "{{ route('admin.inventory') }}";
                    const url = filter ? `${target}?stock_filter=${filter}` : target;

                    setTimeout(() => {
                        window.location.href = url;
                    }, 120);
                });
            });
        }

        async function loadAdminDashboardInventoryOverview() {
            const ctx = document.getElementById('inventoryDonutChart');
            if (!ctx) return;

            try {
                const res = await fetch("{{ route('admin.inventory.data') }}", {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = res.headers.get('content-type') || '';

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                if (!contentType.includes('application/json')) {
                    throw new Error('Inventory endpoint did not return JSON');
                }

                const items = await res.json();

                if (!Array.isArray(items)) {
                    throw new Error('Inventory response is not an array');
                }

                const total = items.length;
                const medicine = items.filter(item => item.category === 'Medicine').length;
                const supplies = items.filter(item => item.category === 'Supplies').length;
                const inStock = items.filter(item => Number(item.qty) - Number(item.used) > 5).length;
                const lowStock = items.filter(item => {
                    const bal = Number(item.qty) - Number(item.used);
                    return bal >= 1 && bal <= 5;
                }).length;
                const outStock = items.filter(item => Number(item.qty) - Number(item.used) <= 0).length;

                if (total <= 0) {
                    setInventoryOverviewEmptyState('No inventory records yet', false);
                    return;
                }

                showInventoryOverviewContent();

                document.getElementById('inventoryInStockValue').textContent = inStock;
                document.getElementById('inventoryLowStockValue').textContent = lowStock;
                document.getElementById('inventoryOutStockValue').textContent = outStock;
                document.getElementById('inventoryTotalValue').textContent = total;
                const donutTotalEl = document.getElementById('inventoryDonutTotal');
                if (donutTotalEl) donutTotalEl.textContent = total;
                document.getElementById('inventoryMedicineValue').textContent = medicine;
                document.getElementById('inventorySuppliesValue').textContent = supplies;

                if (adminInventoryOverviewChart) {
                    adminInventoryOverviewChart.destroy();
                }

                adminInventoryOverviewChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                        datasets: [{
                            data: [inStock, lowStock, outStock],
                            backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                            hoverBackgroundColor: ['#16a34a', '#d97706', '#dc2626'],
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                bindInventoryLegendClicks();

                console.log('Dashboard inventory overview loaded:', {
                    total,
                    medicine,
                    supplies,
                    inStock,
                    lowStock,
                    outStock
                });

            } catch (error) {
                console.error('Dashboard inventory overview error:', error);
                setInventoryOverviewEmptyState('Failed to load inventory overview', true);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadAdminDashboardInventoryOverview();
        });

        window.addEventListener('focus', function() {
            loadAdminDashboardInventoryOverview();
        });
    </script>
@endsection
