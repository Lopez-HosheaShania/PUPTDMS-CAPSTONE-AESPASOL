<style>
    #mobileMenuBtn {
        display: none;
        background: rgba(255, 255, 255, .12);
        border: none;
        color: #fff;
        width: 36px;
        height: 36px;
        border-radius: 9px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: background .15s;
        flex-shrink: 0;
    }

    #mobileMenuBtn:hover {
        background: rgba(255, 255, 255, .22);
    }

    #mobileDrawerOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 998;
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity .25s;
    }

    #mobileDrawerOverlay.open {
        opacity: 1;
    }

    #mobileDrawer {
        position: fixed;
        top: 0;
        left: 0;
        width: 280px;
        height: 100vh;
        background: #fff;
        z-index: 999;
        display: flex;
        flex-direction: column;
        transform: translateX(-100%);
        transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        box-shadow: 4px 0 32px rgba(0, 0, 0, .15);
        overflow: hidden;
    }

    #mobileDrawer.open {
        transform: translateX(0);
    }

    .drawer-header {
        background: linear-gradient(135deg, #6b0000 0%, #8B0000 100%);
        padding: 20px 18px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .drawer-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .drawer-logo {
        width: 30px;
        height: 30px;
        object-fit: contain;
    }

    .drawer-title {
        font-size: .82rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: .01em;
        line-height: 1.2;
    }

    .drawer-subtitle {
        font-size: .68rem;
        color: rgba(255, 255, 255, .6);
        font-style: italic;
    }

    .drawer-close {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: background .15s;
    }

    .drawer-close:hover {
        background: rgba(255, 255, 255, .28);
    }

    .drawer-user {
        padding: 14px 18px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fdf9f9;
        flex-shrink: 0;
    }

    .drawer-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        object-fit: cover;
        flex-shrink: 0;
    }

    .drawer-user-name {
        font-size: .82rem;
        font-weight: 700;
        color: #1f2937;
    }

    .drawer-user-role {
        font-size: .68rem;
        color: #9ca3af;
        font-style: italic;
    }

    .drawer-inner {
        flex: 1;
        overflow-y: auto;
        padding: 10px 0 6px;
    }

    .drawer-inner::-webkit-scrollbar {
        width: 4px;
    }

    .drawer-inner::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 4px;
    }

    .drawer-group {
        margin: 0 8px 2px;
    }

    .drawer-group-header {
        display: flex;
        align-items: center;
        padding: 6px 8px 4px;
        color: #6b7280;
    }

    .drawer-group-icon {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #8B0000;
        flex-shrink: 0;
    }

    .drawer-group-label {
        font-size: .68rem;
        font-weight: 700;
        color: #8B0000;
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-left: 8px;
    }

    .drawer-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px 8px 40px;
        border-radius: 8px;
        margin: 1px 4px;
        font-size: .78rem;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        transition: all .15s;
    }

    .drawer-link:hover {
        background: #fef2f2;
        color: #8B0000;
        padding-left: 44px;
    }

    .drawer-link.active {
        background: #8B0000;
        color: #fff;
        box-shadow: 0 2px 8px rgba(139, 0, 0, .2);
    }

    .drawer-link.active:hover {
        padding-left: 40px;
    }

    .drawer-link i {
        width: 15px;
        text-align: center;
        font-size: 11px;
    }

    .drawer-sep {
        height: 1px;
        background: #f3f4f6;
        margin: 6px 12px;
    }

    .drawer-bottom {
        padding: 10px 12px 14px;
        border-top: 1px solid #f3f4f6;
        flex-shrink: 0;
    }

    [data-theme="dark"] body {
        background-color: #000D1A;
        color: #E5E7EB;
    }

    [data-theme="dark"] #mobileDrawer {
        background: #0d1117;
    }

    [data-theme="dark"] .drawer-user {
        background: #161b22;
        border-color: #21262d;
    }

    [data-theme="dark"] .drawer-user-name {
        color: #e5e7eb;
    }

    [data-theme="dark"] .drawer-link {
        color: #d1d5db;
    }

    [data-theme="dark"] .drawer-link:hover {
        background: rgba(139, 0, 0, .2);
        color: #fff;
    }

    [data-theme="dark"] .drawer-sep {
        background: #21262d;
    }

    [data-theme="dark"] .drawer-bottom {
        border-color: #21262d;
    }

    [data-theme="dark"] .drawer-group-label {
        color: #6b7280;
    }

    @media (max-width: 767px) {
        #mobileMenuBtn {
            display: flex;
        }
    }

    @media (min-width: 768px) {

        #mobileDrawerOverlay,
        #mobileDrawer {
            display: none !important;
        }
    }

    :root {
        --crimson: #8B0000;
        --crimson-dark: #6b0000;
        --crimson-light: #fef2f2;
        --header-h: 64px;
        --sidebar-w: 240px;
    }

    #sidebar {
        position: fixed;
        left: 0;
        top: var(--header-h);
        width: var(--sidebar-w, 240px);
        height: calc(100vh - var(--header-h));
        background: #fff;
        border-right: 1px solid #eff0f2;
        box-shadow: 4px 0 24px rgba(0, 0, 0, .04);
        z-index: 40;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .sidebar-inner {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 16px 10px 8px;
    }

    .nav-section-label {
        font-size: .6rem;
        font-weight: 800;
        color: #b0b7c3;
        text-transform: uppercase;
        letter-spacing: .1em;
        padding: 0 8px 6px;
        margin-top: 4px;
    }

    .nav-group {
        margin-bottom: 2px;
    }

    .group-trigger {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 10px;
        cursor: default;
    }

    .group-icon-wrap {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--crimson-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: var(--crimson);
        flex-shrink: 0;
        transition: all .2s;
    }

    .active-group .group-icon-wrap {
        background: var(--crimson);
        color: #fff;
        box-shadow: 0 4px 12px rgba(139, 0, 0, .3);
    }

    .group-text {
        flex: 1;
        overflow: hidden;
    }

    .group-label {
        font-size: .7rem;
        font-weight: 800;
        color: var(--crimson);
        display: block;
        text-transform: uppercase;
        letter-spacing: .06em;
        white-space: nowrap;
    }

    .group-sublabel {
        font-size: .62rem;
        color: #adb5bd;
        display: block;
        margin-top: 1px;
        white-space: nowrap;
    }

    .group-body {
        padding: 2px 0 6px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 7px 10px 7px 42px;
        border-radius: 9px;
        margin: 1px 2px;
        font-size: .76rem;
        font-weight: 500;
        color: #4a5568;
        text-decoration: none;
        transition: all .15s;
        white-space: nowrap;
    }

    .nav-link:hover {
        background: var(--crimson-light);
        color: var(--crimson);
    }

    .nav-link.active {
        background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
        color: #fff;
        box-shadow: 0 3px 10px rgba(139, 0, 0, .25);
        font-weight: 600;
    }

    .nav-link.active:hover {
        padding-left: 14px;
        background: #8B0000;
    }

    .nav-link i {
        width: 14px;
        text-align: center;
        font-size: 11px;
        flex-shrink: 0;
    }

    .nav-sep {
        height: 1px;
        background: #f3f4f6;
        margin: 10px 6px;
    }

    .sidebar-bottom {
        padding: 10px 10px 14px;
        border-top: 1px solid #f3f4f6;
        flex-shrink: 0;
    }

    .theme-toggle-container {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
        height: 36px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 40px;
        padding: 3px;
    }

    .theme-option {
        position: relative;
        z-index: 2;
        flex: 1;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        cursor: pointer;
        color: #9ca3af;
        transition: color .2s;
        border-radius: 40px;
        font-size: 13px;
    }

    .theme-option.active {
        color: #374151;
    }

    .theme-indicator {
        position: absolute;
        background: #fff;
        border-radius: 40px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
        transition: all .3s cubic-bezier(.4, 0, .2, 1);
        pointer-events: none;
        width: calc(50% - 3px);
        height: calc(100% - 6px);
        left: 3px;
        top: 3px;
    }

    .theme-indicator.dark-mode {
        transform: translateX(calc(100% + 0px));
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 8px 10px;
        border-radius: 10px;
        border: none;
        background: none;
        cursor: pointer;
        color: #ef4444;
        font-size: .76rem;
        font-weight: 600;
        transition: background .15s;
        margin-top: 6px;
    }

    .logout-btn:hover {
        background: #fef2f2;
    }

    .logout-icon {
        width: 28px;
        height: 28px;
        background: #fef2f2;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 11px;
    }

    [data-theme="dark"] #sidebar {
        background: linear-gradient(180deg, #0b1220 0%, #09111d 100%);
        border-right: 1px solid #1f2937;
        box-shadow: 4px 0 24px rgba(0, 0, 0, .22);
    }

    [data-theme="dark"] .sidebar-inner::-webkit-scrollbar-thumb {
        background: #334155;
    }

    [data-theme="dark"] .nav-section-label {
        color: #cbd5e1;
    }

    [data-theme="dark"] .group-trigger {
        background: transparent;
    }

    [data-theme="dark"] .group-icon-wrap {
        background: rgba(139, 0, 0, .16);
        color: #fca5a5;
    }

    [data-theme="dark"] .active-group .group-icon-wrap {
        background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(139, 0, 0, .32);
    }

    [data-theme="dark"] .group-label {
        color: #e5e7eb;
    }

    [data-theme="dark"] .group-sublabel {
        color: #94a3b8;
    }

    [data-theme="dark"] .nav-link {
        color: #94a3b8;
    }

    [data-theme="dark"] .nav-link i {
        color: #64748b;
    }

    [data-theme="dark"] .nav-link:hover {
        background: rgba(131, 131, 131, 0.18);
        color: #ffffff;
    }

    [data-theme="dark"] .nav-link:hover i {
        color: #fca5a5;
    }

    [data-theme="dark"] .nav-link.active {
        background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(139, 0, 0, .28);
    }

    [data-theme="dark"] .nav-link.active i {
        color: #ffffff;
    }

    [data-theme="dark"] .nav-link.active:hover {
        background: linear-gradient(135deg, #9f0000 0%, #6b0000 100%);
    }

    [data-theme="dark"] .nav-sep {
        background: #1f2937;
    }

    [data-theme="dark"] .sidebar-bottom {
        border-top: 1px solid #1f2937;
    }

    [data-theme="dark"] .theme-toggle-container {
        background: #111827;
        border-color: #1f2937;
    }

    [data-theme="dark"] .theme-option {
        color: #94a3b8;
    }

    [data-theme="dark"] .theme-option.active {
        color: #f8fafc;
    }

    [data-theme="dark"] .theme-indicator {
        background: #1f2937;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .3);
    }

    [data-theme="dark"] .logout-btn {
        color: #f87171;
    }

    [data-theme="dark"] .logout-btn:hover {
        background: rgba(239, 68, 68, .10);
    }

    [data-theme="dark"] .logout-icon {
        background: rgba(239, 68, 68, .10);
    }

    #mainContent {
        margin-left: var(--sidebar-w);
    }

    @media (max-width: 767px) {
        #sidebar {
            display: none !important;
        }

        #mainContent {
            margin-left: 0 !important;
        }
    }

    #termsModal {
        border: none;
        padding: 0;
        border-radius: 18px;
        width: min(94vw, 480px);
        box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
        overflow: hidden;
    }

    #termsModal::backdrop {
        background: rgba(0, 0, 0, .55);
        backdrop-filter: blur(4px);
    }

    .terms-header {
        background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 100%);
        padding: 20px 22px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .terms-header-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .15);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .terms-header-icon i {
        font-size: 15px;
        color: rgba(255, 255, 255, .9);
    }

    .terms-header h2 {
        color: #fff;
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }

    .terms-header p {
        color: rgba(255, 255, 255, .6);
        font-size: .7rem;
        margin: 2px 0 0;
    }

    .terms-body {
        padding: 20px 22px 18px;
    }

    .terms-body p {
        font-size: .83rem;
        color: #4b5563;
        line-height: 1.75;
        margin-bottom: 10px;
    }

    .terms-body strong {
        color: #1f2937;
        font-weight: 700;
    }

    .terms-divider {
        height: 1px;
        background: #f0e8e8;
        margin: 4px 0 14px;
    }

    .terms-checkbox-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #fdf5f5;
        border: 1px solid #fce8e8;
        border-radius: 10px;
        padding: 11px 13px;
        margin-bottom: 18px;
        cursor: pointer;
    }

    .terms-checkbox-row input[type="checkbox"] {
        margin-top: 2px;
        cursor: pointer;
        accent-color: var(--crimson);
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }

    .terms-checkbox-row span {
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        line-height: 1.5;
    }

    .terms-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .terms-cancel-btn {
        padding: 8px 18px;
        border-radius: 9px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        font-weight: 600;
        font-size: .8rem;
        cursor: pointer;
        transition: all .15s;
    }

    .terms-cancel-btn:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .terms-continue-btn {
        padding: 8px 20px;
        border-radius: 9px;
        border: none;
        background: #9ca3af;
        color: white;
        font-weight: 700;
        font-size: .8rem;
        cursor: not-allowed;
        transition: all .2s;
    }

    .terms-continue-btn:not(:disabled) {
        background: var(--crimson);
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(139, 0, 0, .3);
    }

    .terms-continue-btn:not(:disabled):hover {
        background: var(--crimson-dark);
        box-shadow: 0 4px 14px rgba(139, 0, 0, .4);
    }

    [data-theme="dark"] #termsModal {
        background: #161b22;
    }

    [data-theme="dark"] .terms-body p {
        color: #9ca3af;
    }

    [data-theme="dark"] .terms-body strong {
        color: #e5e7eb;
    }

    [data-theme="dark"] .terms-divider {
        background: #21262d;
    }

    [data-theme="dark"] .terms-checkbox-row {
        background: #1c1c1c;
        border-color: #2d1a1a;
    }

    [data-theme="dark"] .terms-checkbox-row span {
        color: #d1d5db;
    }

    [data-theme="dark"] .terms-cancel-btn {
        background: #1f2937;
        border-color: #374151;
        color: #9ca3af;
    }

    [data-theme="dark"] .terms-cancel-btn:hover {
        background: #374151;
        color: #e5e7eb;
    }
</style>
