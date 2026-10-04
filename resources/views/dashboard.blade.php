<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('laravel-forge-deployments::deployments.title') }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --lfd-bg: #f5f3ef;
            --lfd-surface: #fffdfa;
            --lfd-surface-muted: #efede7;
            --lfd-text: #201f1d;
            --lfd-muted: #6d6860;
            --lfd-border: #d9d4cb;
            --lfd-accent: #e54824;
            --lfd-accent-hover: #c93617;
            --lfd-accent-contrast: #fff;
            --lfd-success: #177245;
            --lfd-warning: #9b5a00;
            --lfd-danger: #b42318;
            --lfd-info: #275d91;
            --lfd-shadow: 0 18px 48px rgba(31, 26, 20, .08);
            --lfd-radius: 12px;
            --lfd-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            --lfd-sans: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --lfd-bg: #161514;
                --lfd-surface: #201f1d;
                --lfd-surface-muted: #292724;
                --lfd-text: #f5f1eb;
                --lfd-muted: #b4ada4;
                --lfd-border: #3f3b36;
                --lfd-accent: #ff6845;
                --lfd-accent-hover: #ff7c5e;
                --lfd-accent-contrast: #21130e;
                --lfd-success: #61c991;
                --lfd-warning: #f4b860;
                --lfd-danger: #ff8b82;
                --lfd-info: #83b6e8;
                --lfd-shadow: 0 20px 60px rgba(0, 0, 0, .28);
            }
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-width: 320px;
            background: var(--lfd-bg);
            color: var(--lfd-text);
            font-family: var(--lfd-sans);
            font-size: 15px;
            line-height: 1.55;
        }
        button { font: inherit; }
        .lfd-shell { width: min(1180px, calc(100% - 32px)); margin: 0 auto; padding: 52px 0 72px; }
        .lfd-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
        .lfd-eyebrow { margin: 0 0 6px; color: var(--lfd-accent); font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .lfd-title { margin: 0; font-size: clamp(30px, 5vw, 52px); line-height: 1.05; letter-spacing: -.04em; }
        .lfd-forge-link { color: var(--lfd-muted); font-size: 13px; text-decoration: none; border-bottom: 1px solid var(--lfd-border); }
        .lfd-forge-link:hover { color: var(--lfd-text); border-color: var(--lfd-text); }
        .lfd-panel { background: var(--lfd-surface); border: 1px solid var(--lfd-border); border-radius: var(--lfd-radius); box-shadow: var(--lfd-shadow); }
        .lfd-control { display: grid; grid-template-columns: minmax(0, 1fr) minmax(260px, .42fr); }
        .lfd-control-copy { padding: clamp(24px, 5vw, 48px); }
        .lfd-control-copy h2, .lfd-history-head h2 { margin: 0; font-size: clamp(21px, 3vw, 28px); letter-spacing: -.025em; }
        .lfd-control-copy > p { max-width: 720px; margin: 12px 0 28px; color: var(--lfd-muted); }
        .lfd-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px; overflow: hidden; border: 1px solid var(--lfd-border); border-radius: 8px; background: var(--lfd-border); }
        .lfd-fact { min-width: 0; padding: 15px 17px; background: var(--lfd-surface); }
        .lfd-fact span { display: block; margin-bottom: 3px; color: var(--lfd-muted); font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .lfd-fact strong { display: block; overflow: hidden; font-family: var(--lfd-mono); font-size: 14px; text-overflow: ellipsis; white-space: nowrap; }
        .lfd-action { display: flex; flex-direction: column; justify-content: space-between; gap: 30px; padding: clamp(24px, 5vw, 42px); background: var(--lfd-surface-muted); border-inline-start: 1px solid var(--lfd-border); border-radius: 0 var(--lfd-radius) var(--lfd-radius) 0; }
        [dir="rtl"] .lfd-action { border-radius: var(--lfd-radius) 0 0 var(--lfd-radius); }
        .lfd-status-label { display: block; margin-bottom: 9px; color: var(--lfd-muted); font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .lfd-status { display: inline-flex; align-items: center; gap: 9px; font-size: 18px; font-weight: 750; }
        .lfd-status::before { width: 10px; height: 10px; border-radius: 50%; background: currentColor; content: ""; }
        .lfd-status[data-status="ready"], .lfd-status[data-status="finished"] { color: var(--lfd-success); }
        .lfd-status[data-status="failed"], .lfd-status[data-status="failed-build"], .lfd-status[data-status="cancelled"] { color: var(--lfd-danger); }
        .lfd-status[data-status="requesting"], .lfd-status[data-status="pending"], .lfd-status[data-status="queued"], .lfd-status[data-status="deploying"] { color: var(--lfd-warning); }
        .lfd-status[data-status="unknown"] { color: var(--lfd-muted); }
        .lfd-status[data-active="true"]::before { animation: lfd-pulse 1.4s ease-in-out infinite; }
        .lfd-checked { margin: 7px 0 0; color: var(--lfd-muted); font-size: 12px; }
        .lfd-button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; gap: 8px; padding: 10px 17px; border: 1px solid transparent; border-radius: 7px; background: var(--lfd-accent); color: var(--lfd-accent-contrast); cursor: pointer; font-weight: 750; text-decoration: none; transition: background-color .15s ease, transform .15s ease, opacity .15s ease; }
        .lfd-button:hover:not(:disabled) { background: var(--lfd-accent-hover); transform: translateY(-1px); }
        .lfd-button:focus-visible, .lfd-link-button:focus-visible, .lfd-forge-link:focus-visible { outline: 3px solid color-mix(in srgb, var(--lfd-accent) 35%, transparent); outline-offset: 3px; }
        .lfd-button:disabled { cursor: not-allowed; opacity: .45; }
        .lfd-button-secondary { border-color: var(--lfd-border); background: var(--lfd-surface); color: var(--lfd-text); }
        .lfd-button-secondary:hover:not(:disabled) { background: var(--lfd-surface-muted); }
        .lfd-alert { margin-top: 18px; padding: 13px 16px; border-inline-start: 3px solid var(--lfd-warning); background: color-mix(in srgb, var(--lfd-warning) 10%, var(--lfd-surface)); color: var(--lfd-text); border-radius: 4px; }
        .lfd-alert[hidden] { display: none; }
        .lfd-history { margin-top: 24px; overflow: hidden; }
        .lfd-history-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; padding: 26px 28px 22px; border-bottom: 1px solid var(--lfd-border); }
        .lfd-history-head p { max-width: 680px; margin: 7px 0 0; color: var(--lfd-muted); }
        .lfd-link-button { padding: 5px 0; border: 0; background: none; color: var(--lfd-accent); cursor: pointer; font-weight: 750; }
        .lfd-table-wrap { overflow-x: auto; }
        .lfd-table { width: 100%; min-width: 880px; border-collapse: collapse; }
        .lfd-table th { padding: 12px 18px; background: var(--lfd-surface-muted); color: var(--lfd-muted); font-size: 11px; letter-spacing: .06em; text-align: start; text-transform: uppercase; }
        .lfd-table td { padding: 16px 18px; border-top: 1px solid var(--lfd-border); vertical-align: top; }
        .lfd-table tbody tr:first-child td { border-top: 0; }
        .lfd-table tbody tr:hover { background: color-mix(in srgb, var(--lfd-surface-muted) 55%, transparent); }
        .lfd-table .lfd-status { font-size: 13px; }
        .lfd-primary { display: block; max-width: 280px; overflow: hidden; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .lfd-secondary { display: block; max-width: 280px; margin-top: 3px; overflow: hidden; color: var(--lfd-muted); font-family: var(--lfd-mono); font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .lfd-empty { padding: 42px 24px !important; color: var(--lfd-muted); text-align: center; }
        .lfd-load-more { display: flex; justify-content: center; padding: 18px; border-top: 1px solid var(--lfd-border); }
        .lfd-load-more[hidden] { display: none; }
        .lfd-dialog { width: min(590px, calc(100% - 32px)); max-height: calc(100vh - 48px); padding: 0; overflow: hidden; border: 1px solid var(--lfd-border); border-radius: var(--lfd-radius); background: var(--lfd-surface); color: var(--lfd-text); box-shadow: 0 28px 100px rgba(0, 0, 0, .28); }
        .lfd-dialog::backdrop { background: rgba(20, 18, 16, .64); backdrop-filter: blur(2px); }
        .lfd-dialog-body { padding: 28px; }
        .lfd-dialog h2 { margin: 0; font-size: 23px; }
        .lfd-dialog p { color: var(--lfd-muted); }
        .lfd-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .lfd-output { min-height: 180px; max-height: 55vh; margin: 20px 0 0; padding: 18px; overflow: auto; border: 1px solid var(--lfd-border); border-radius: 7px; background: #11100f; color: #e8e2da; font: 12px/1.6 var(--lfd-mono); text-align: start; white-space: pre-wrap; }

        @keyframes lfd-pulse { 50% { opacity: .3; transform: scale(.72); } }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
        @media (max-width: 760px) {
            .lfd-shell { width: min(100% - 20px, 1180px); padding: 28px 0 48px; }
            .lfd-header { align-items: flex-start; flex-direction: column; }
            .lfd-control { grid-template-columns: 1fr; }
            .lfd-action { border-top: 1px solid var(--lfd-border); border-inline-start: 0; border-radius: 0 0 var(--lfd-radius) var(--lfd-radius); }
            [dir="rtl"] .lfd-action { border-radius: 0 0 var(--lfd-radius) var(--lfd-radius); }
            .lfd-history-head { align-items: flex-start; flex-direction: column; padding: 22px 20px; }
        }
        @media (max-width: 480px) { .lfd-facts { grid-template-columns: 1fr; } .lfd-dialog-actions { flex-direction: column-reverse; } .lfd-button { width: 100%; } }
    </style>
</head>
<body>
<main class="lfd-shell">
    <header class="lfd-header">
        <div>
            <p class="lfd-eyebrow">{{ __('laravel-forge-deployments::deployments.eyebrow') }}</p>
            <h1 class="lfd-title">{{ __('laravel-forge-deployments::deployments.title') }}</h1>
        </div>
        <a class="lfd-forge-link" href="https://forge.laravel.com" target="_blank" rel="noopener noreferrer">Laravel Forge ↗</a>
    </header>

    <section class="lfd-panel lfd-control" aria-labelledby="lfd-control-title">
        <div class="lfd-control-copy">
            <h2 id="lfd-control-title">{{ __('laravel-forge-deployments::deployments.control_title') }}</h2>
            <p>{{ __('laravel-forge-deployments::deployments.control_description') }}</p>
            <div class="lfd-facts">
                <div class="lfd-fact">
                    <span>{{ __('laravel-forge-deployments::deployments.target') }}</span>
                    <strong>{{ $deploymentState['target']['label'] ?: '—' }}</strong>
                </div>
                <div class="lfd-fact">
                    <span>{{ __('laravel-forge-deployments::deployments.branch') }}</span>
                    <strong>{{ $deploymentState['target']['branch'] ?: '—' }}</strong>
                </div>
            </div>
            <div id="lfd-alert" class="lfd-alert" role="status" aria-live="polite" @if (!$deploymentState['configuration_message']) hidden @endif>
                {{ $deploymentState['configuration_message'] }}
            </div>
        </div>
        <div class="lfd-action">
            <div>
                <span class="lfd-status-label">{{ __('laravel-forge-deployments::deployments.current_status') }}</span>
                <div id="lfd-current-status" class="lfd-status" data-status="{{ $deploymentState['current']['value'] }}" data-active="{{ $deploymentState['current']['active'] ? 'true' : 'false' }}">
                    {{ $deploymentState['current']['label'] }}
                </div>
                <p class="lfd-checked"><span>{{ __('laravel-forge-deployments::deployments.last_checked') }}</span>: <time id="lfd-last-checked">—</time></p>
            </div>
            <button id="lfd-deploy" class="lfd-button" type="button" @disabled(!$deploymentState['available'] || $deploymentState['current']['active'])>
                {{ __('laravel-forge-deployments::deployments.deploy') }}
            </button>
        </div>
    </section>

    <section class="lfd-panel lfd-history" aria-labelledby="lfd-history-title">
        <div class="lfd-history-head">
            <div>
                <h2 id="lfd-history-title">{{ __('laravel-forge-deployments::deployments.history_title') }}</h2>
                <p>{{ __('laravel-forge-deployments::deployments.history_description') }}</p>
            </div>
            <button id="lfd-refresh" class="lfd-link-button" type="button">{{ __('laravel-forge-deployments::deployments.refresh') }}</button>
        </div>
        <div class="lfd-table-wrap">
            <table class="lfd-table">
                <thead>
                <tr>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.status') }}</th>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.deployment') }}</th>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.commit') }}</th>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.actioned_by') }}</th>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.started') }}</th>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.duration') }}</th>
                    <th>{{ __('laravel-forge-deployments::deployments.columns.result') }}</th>
                </tr>
                </thead>
                <tbody id="lfd-history-body"></tbody>
            </table>
        </div>
        <div id="lfd-load-more-wrap" class="lfd-load-more" hidden>
            <button id="lfd-load-more" class="lfd-button lfd-button-secondary" type="button">{{ __('laravel-forge-deployments::deployments.load_more') }}</button>
        </div>
    </section>
</main>

<dialog id="lfd-confirm-dialog" class="lfd-dialog">
    <div class="lfd-dialog-body">
        <h2>{{ __('laravel-forge-deployments::deployments.confirm_deploy') }}</h2>
        <p>{{ __('laravel-forge-deployments::deployments.confirmation_warning') }}</p>
        <div class="lfd-dialog-actions">
            <button class="lfd-button lfd-button-secondary" type="button" data-close-confirm>{{ __('laravel-forge-deployments::deployments.cancel') }}</button>
            <button id="lfd-confirm-deploy" class="lfd-button" type="button">{{ __('laravel-forge-deployments::deployments.confirm_deploy') }}</button>
        </div>
    </div>
</dialog>

<dialog id="lfd-output-dialog" class="lfd-dialog">
    <div class="lfd-dialog-body">
        <h2>{{ __('laravel-forge-deployments::deployments.deployment_output') }}</h2>
        <p>{{ __('laravel-forge-deployments::deployments.output_description') }}</p>
        <pre id="lfd-output" class="lfd-output"></pre>
        <div class="lfd-dialog-actions">
            <button class="lfd-button lfd-button-secondary" type="button" data-close-output>{{ __('laravel-forge-deployments::deployments.close_output') }}</button>
        </div>
    </div>
</dialog>

<script>
(() => {
    'use strict';

    const state = {{ Illuminate\Support\Js::from($deploymentState) }};
    const elements = {
        alert: document.getElementById('lfd-alert'),
        status: document.getElementById('lfd-current-status'),
        checked: document.getElementById('lfd-last-checked'),
        deploy: document.getElementById('lfd-deploy'),
        refresh: document.getElementById('lfd-refresh'),
        history: document.getElementById('lfd-history-body'),
        loadWrap: document.getElementById('lfd-load-more-wrap'),
        loadMore: document.getElementById('lfd-load-more'),
        confirmDialog: document.getElementById('lfd-confirm-dialog'),
        confirm: document.getElementById('lfd-confirm-deploy'),
        outputDialog: document.getElementById('lfd-output-dialog'),
        output: document.getElementById('lfd-output'),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const dateFormatter = new Intl.DateTimeFormat(state.locale, { dateStyle: 'medium', timeStyle: 'short' });
    let current = state.current;
    let nextCursor = state.history.next_cursor;
    let pollingTimer = null;

    function showAlert(message) {
        elements.alert.textContent = message || '';
        elements.alert.hidden = !message;
    }

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
    }

    function formatDuration(seconds) {
        if (!Number.isFinite(seconds)) return '—';
        const minutes = Math.floor(seconds / 60);
        const remainder = Math.floor(seconds % 60);
        return minutes > 0
            ? state.messages.duration_minutes.replace(':minutes', minutes).replace(':seconds', remainder)
            : state.messages.duration_seconds.replace(':seconds', remainder);
    }

    function updateCurrent(payload) {
        current = payload;
        elements.status.textContent = payload.label;
        elements.status.dataset.status = payload.value;
        elements.status.dataset.active = payload.active ? 'true' : 'false';
        elements.checked.textContent = dateFormatter.format(new Date());
        elements.deploy.disabled = !state.available || payload.active;
        schedulePoll();
    }

    function node(tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = text;
        return element;
    }

    function cell(primary, secondary) {
        const td = node('td');
        td.append(node('span', 'lfd-primary', primary));
        if (secondary) td.append(node('span', 'lfd-secondary', secondary));
        return td;
    }

    function deploymentRow(item) {
        const row = document.createElement('tr');
        const statusCell = node('td');
        const status = node('span', 'lfd-status', item.status.label);
        status.dataset.status = item.status.value;
        status.dataset.active = item.status.active ? 'true' : 'false';
        statusCell.append(status);
        row.append(statusCell);
        row.append(cell(item.id ? `#${item.id}` : state.messages.pending_id, item.type || item.source_label));

        const commit = item.commit_message || state.messages.unknown_commit;
        const commitMeta = [item.branch, item.commit_hash ? item.commit_hash.slice(0, 8) : null].filter(Boolean).join(' · ');
        row.append(cell(commit, commitMeta));
        row.append(cell(item.actor_name || state.messages.external_actor, item.actor_email || item.commit_author || null));
        row.append(cell(formatDate(item.started_at || item.requested_at)));
        row.append(cell(formatDuration(item.duration_seconds)));

        const result = node('td');
        if (item.output_url) {
            const button = node('button', 'lfd-link-button', @json(__('laravel-forge-deployments::deployments.view_output')));
            button.type = 'button';
            button.addEventListener('click', () => openOutput(item.output_url));
            result.append(button);
        } else {
            result.textContent = '—';
        }
        row.append(result);

        return row;
    }

    function renderHistory(items, append = false) {
        if (!append) elements.history.replaceChildren();
        if (!append && items.length === 0) {
            const row = document.createElement('tr');
            const empty = node('td', 'lfd-empty', @json(__('laravel-forge-deployments::deployments.no_history')));
            empty.colSpan = 7;
            row.append(empty);
            elements.history.append(row);
            return;
        }
        items.forEach(item => elements.history.append(deploymentRow(item)));
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.headers || {}),
            },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || state.messages.unexpected_error);
        return payload;
    }

    async function refreshCurrent() {
        try {
            const payload = await request(state.endpoints.current);
            state.available = true;
            updateCurrent(payload.current);
            showAlert('');
            if (payload.current.terminal) await refreshHistory();
        } catch (error) {
            showAlert(error.message);
            schedulePoll();
        }
    }

    async function refreshHistory() {
        elements.refresh.disabled = true;
        try {
            const payload = await request(state.endpoints.history);
            nextCursor = payload.history.next_cursor;
            renderHistory(payload.history.items);
            elements.loadWrap.hidden = !nextCursor;
            showAlert('');
        } catch (error) {
            showAlert(error.message);
        } finally {
            elements.refresh.disabled = false;
        }
    }

    async function loadMore() {
        if (!nextCursor) return;
        elements.loadMore.disabled = true;
        elements.loadMore.textContent = state.messages.loading_more;
        try {
            const url = new URL(state.endpoints.history, window.location.origin);
            url.searchParams.set('cursor', nextCursor);
            const payload = await request(url);
            nextCursor = payload.history.next_cursor;
            renderHistory(payload.history.items, true);
            elements.loadWrap.hidden = !nextCursor;
        } catch (error) {
            showAlert(error.message);
        } finally {
            elements.loadMore.disabled = false;
            elements.loadMore.textContent = state.messages.load_more;
        }
    }

    async function triggerDeployment() {
        elements.confirm.disabled = true;
        try {
            const payload = await request(state.endpoints.trigger, { method: 'POST', body: '{}' });
            elements.confirmDialog.close();
            showAlert(payload.message);
            if (payload.deployment && payload.deployment.status) updateCurrent(payload.deployment.status);
            await refreshHistory();
        } catch (error) {
            elements.confirmDialog.close();
            showAlert(error.message);
            await refreshCurrent();
        } finally {
            elements.confirm.disabled = false;
        }
    }

    async function openOutput(url) {
        elements.output.textContent = @json(__('laravel-forge-deployments::deployments.loading_output'));
        elements.outputDialog.showModal();
        try {
            const payload = await request(url);
            elements.output.textContent = payload.output || state.messages.empty_output;
        } catch (error) {
            elements.output.textContent = error.message;
        }
    }

    function schedulePoll() {
        window.clearTimeout(pollingTimer);
        if (current && current.active) {
            pollingTimer = window.setTimeout(refreshCurrent, state.poll_interval_ms);
        }
    }

    elements.deploy.addEventListener('click', () => elements.confirmDialog.showModal());
    elements.confirm.addEventListener('click', triggerDeployment);
    elements.refresh.addEventListener('click', async () => { await refreshCurrent(); await refreshHistory(); });
    elements.loadMore.addEventListener('click', loadMore);
    document.querySelector('[data-close-confirm]').addEventListener('click', () => elements.confirmDialog.close());
    document.querySelector('[data-close-output]').addEventListener('click', () => elements.outputDialog.close());

    renderHistory(state.history.items);
    updateCurrent(current);
    elements.loadWrap.hidden = !nextCursor;
})();
</script>
</body>
</html>
