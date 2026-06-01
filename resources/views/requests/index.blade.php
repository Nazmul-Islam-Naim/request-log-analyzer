{{-- ============================================================
     requests/index.blade.php  â€“  Paginated request list
     ============================================================ --}}
@extends('request-log-analyzer::_layout')

@section('page-title', 'Requests')

@push('head')
<style>
/* â”€â”€ Advanced filter panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.filter-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
    gap: 1rem;
    flex-wrap: wrap;
}
.filter-panel-title {
    font-size: .75rem;
    font-weight: 700;
    color: #374151;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.filter-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #fff;
    font-size: .6rem;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 9999px;
    padding: 0 4px;
}
.filter-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: .75rem 1rem;
    padding: 1rem 1.25rem;
}
@media (max-width: 1200px) { .filter-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width:  700px) { .filter-grid { grid-template-columns: repeat(2, 1fr); } }

.fg-field {
    display: flex;
    flex-direction: column;
    gap: .3rem;
}
.fg-field label {
    font-size: .62rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 700;
    color: #6b7280;
}
.fg-field input,
.fg-field select {
    height: 34px;
    padding: 0 .65rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: .8rem;
    color: #0f172a;
    background: #fff;
    outline: none;
    width: 100%;
    box-sizing: border-box;
}
.fg-field input[type="date"] { padding: 0 .5rem; }
.fg-field input:focus,
.fg-field select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}
.filter-actions {
    display: flex;
    align-items: center;
    gap: .625rem;
    padding: .75rem 1.25rem;
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
    flex-wrap: wrap;
}
.filter-result-count {
    margin-left: auto;
    font-size: .72rem;
    color: #9ca3af;
}

/* â”€â”€ Active filter chips â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.filter-chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    padding: .6rem 1.25rem;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
}
.filter-chips-label {
    font-size: .65rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-right: .15rem;
}
.chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .2rem .65rem;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 9999px;
    font-size: .69rem;
    color: #1d4ed8;
    font-weight: 500;
    text-decoration: none;
    transition: background .1s;
}
.chip:hover { background: #dbeafe; }
.chip-x {
    font-size: .8rem;
    font-weight: 700;
    color: #93c5fd;
    line-height: 1;
}
.chip:hover .chip-x { color: #1d4ed8; }

/* ── Full-text search bar ─────────────────────────────────────────────── */
.search-bar-wrap {
    padding: .85rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
}
.search-bar-inner {
    position: relative;
    max-width: 560px;
}
.search-bar-icon {
    position: absolute;
    left: .75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
    width: 15px; height: 15px;
}
.search-bar-input {
    width: 100%;
    height: 36px;
    padding: 0 2.2rem 0 2.3rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: .82rem;
    color: #0f172a;
    outline: none;
    background: #f8fafc;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.search-bar-input:focus {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.search-bar-clear {
    position: absolute;
    right: .6rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    font-size: 1rem;
    line-height: 1;
    padding: 2px 4px;
    border-radius: 4px;
}
.search-bar-clear:hover { color: #374151; background: #f1f5f9; }
.search-hint {
    margin-top: .35rem;
    font-size: .68rem;
    color: #9ca3af;
}
</style>
@endpush

@section('content')

@php
    $activeFilters = array_filter($filters ?? [], fn($v) => $v !== null && $v !== '');
    $filterCount   = count($activeFilters);

    $chipLabels = [
        'search'      => 'Search: '.($activeFilters['search'] ?? ''),
        'method'      => 'Method: '.strtoupper($activeFilters['method'] ?? ''),
        'status'      => 'Status: '.($activeFilters['status'] ?? ''),
        'status_code' => 'Code: '.($activeFilters['status_code'] ?? ''),
        'uri'         => 'URI: '.($activeFilters['uri'] ?? ''),
        'tag'         => 'Tag: '.($activeFilters['tag'] ?? ''),
        'date_from'   => 'From: '.($activeFilters['date_from'] ?? ''),
        'date_to'     => 'To: '.($activeFilters['date_to'] ?? ''),
        'rt_min'      => 'Time >= '.($activeFilters['rt_min'] ?? '').' ms',
        'rt_max'      => 'Time <= '.($activeFilters['rt_max'] ?? '').' ms',
    ];
@endphp

<div class="card" style="margin-bottom:1.25rem;">

    {{-- Search bar --}}
    <div class="search-bar-wrap">
        <form method="GET" action="{{ route('request-log-analyzer.requests') }}" id="search-form">
            @foreach(array_diff_key($activeFilters, ['search' => '']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach
            <div class="search-bar-inner">
                <svg class="search-bar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    id="search-input"
                    class="search-bar-input"
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search URL, error message, or SQL query&hellip;"
                    autocomplete="off"
                    spellcheck="false">
                @if(!empty($filters['search']))
                    <button type="button" class="search-bar-clear" onclick="clearSearch()" title="Clear search">&times;</button>
                @endif
            </div>
            <div class="search-hint">
                Searches across request URLs, exception messages, and SQL queries.
                Press <kbd style="font-size:.66rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;padding:1px 4px;">Enter</kbd> to search.
            </div>
        </form>
    </div>

    {{-- â”€â”€ Panel header â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="filter-panel-head">
        <div class="filter-panel-title">
            <svg style="width:13px;height:13px;color:#6b7280;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            Filters
            @if($filterCount)
                <span class="filter-count-badge">{{ $filterCount }}</span>
            @endif
        </div>
        @if($filterCount)
            <a href="{{ route('request-log-analyzer.requests') }}"
               style="font-size:.72rem;color:#6b7280;text-decoration:none;">
                Clear all filters &times;
            </a>
        @endif
    </div>

    {{-- â”€â”€ Filter grid â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <form method="GET" action="{{ route('request-log-analyzer.requests') }}">
        {{-- Preserve active search term when applying filters --}}
        @if(!empty($filters['search']))
            <input type="hidden" name="search" value="{{ $filters['search'] }}">
        @endif
        <div class="filter-grid">

            {{-- Row 1: HTTP basics --}}
            <div class="fg-field">
                <label for="f-method">Method</label>
                <select id="f-method" name="method">
                    <option value="">All methods</option>
                    @foreach(['GET','POST','PUT','PATCH','DELETE','HEAD'] as $m)
                        <option value="{{ $m }}" {{ ($filters['method'] ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fg-field">
                <label for="f-status">Status range</label>
                <select id="f-status" name="status">
                    <option value="">All statuses</option>
                    <option value="2xx" {{ ($filters['status'] ?? '') === '2xx' ? 'selected' : '' }}>2xx &mdash; Success</option>
                    <option value="3xx" {{ ($filters['status'] ?? '') === '3xx' ? 'selected' : '' }}>3xx &mdash; Redirect</option>
                    <option value="4xx" {{ ($filters['status'] ?? '') === '4xx' ? 'selected' : '' }}>4xx &mdash; Client Error</option>
                    <option value="5xx" {{ ($filters['status'] ?? '') === '5xx' ? 'selected' : '' }}>5xx &mdash; Server Error</option>
                </select>
            </div>

            <div class="fg-field">
                <label for="f-status-code">Exact status code</label>
                <input id="f-status-code" type="number" name="status_code"
                       value="{{ $filters['status_code'] ?? '' }}"
                       placeholder="e.g. 404"
                       min="100" max="599">
            </div>

            <div class="fg-field">
                <label for="f-uri">Route / URI</label>
                <input id="f-uri" type="text" name="uri"
                       value="{{ $filters['uri'] ?? '' }}"
                       placeholder="/api/users&hellip;">
            </div>

            <div class="fg-field">
                <label for="f-tag">Tag</label>
                <input id="f-tag" type="text" name="tag"
                       value="{{ $filters['tag'] ?? '' }}"
                       placeholder="payment&hellip;">
            </div>

            {{-- Row 2: Time & identity --}}
            <div class="fg-field">
                <label for="f-date-from">Date from</label>
                <input id="f-date-from" type="date" name="date_from"
                       value="{{ $filters['date_from'] ?? '' }}"
                       max="{{ now()->toDateString() }}">
            </div>

            <div class="fg-field">
                <label for="f-date-to">Date to</label>
                <input id="f-date-to" type="date" name="date_to"
                       value="{{ $filters['date_to'] ?? '' }}"
                       max="{{ now()->toDateString() }}">
            </div>

            <div class="fg-field">
                <label for="f-rt-min">Response time &ge; (ms)</label>
                <input id="f-rt-min" type="number" name="rt_min"
                       value="{{ $filters['rt_min'] ?? '' }}"
                       placeholder="e.g. 500"
                       min="0">
            </div>

            <div class="fg-field">
                <label for="f-rt-max">Response time &le; (ms)</label>
                <input id="f-rt-max" type="number" name="rt_max"
                       value="{{ $filters['rt_max'] ?? '' }}"
                       placeholder="e.g. 2000"
                       min="0">
            </div>

        </div>

        {{-- â”€â”€ Active filter chips â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        @if($filterCount)
        <div class="filter-chips">
            <span class="filter-chips-label">Active:</span>
            @foreach($chipLabels as $key => $label)
                @if(isset($activeFilters[$key]))
                <a href="{{ route('request-log-analyzer.requests', array_merge(request()->except([$key, 'page']), [])) }}"
                   class="chip" title="Remove this filter">
                    {{ $label }}&nbsp;<span class="chip-x">&times;</span>
                </a>
                @endif
            @endforeach
        </div>
        @endif

        {{-- â”€â”€ Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary" style="height:32px;">Apply filters</button>
            @if($filterCount)
                <a href="{{ route('request-log-analyzer.requests') }}" class="btn btn-ghost" style="height:32px;">Clear</a>
            @endif
            <span class="filter-result-count">{{ number_format($logs->total()) }} result{{ $logs->total() === 1 ? '' : 's' }}</span>
        </div>
    </form>

    {{-- â”€â”€ Table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    @if($logs->isEmpty())
        <div class="empty-state">No requests match the current filters.</div>
    @else
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Method</th>
                        <th>URI</th>
                        <th>Status</th>
                        <th>Time (ms)</th>
                        <th>Tags</th>
                        <th>Memory</th>
                        <th>Queries</th>
                        <th>Errors</th>
                        <th>IP</th>
                        <th>Country</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $mc = in_array($log->method, ['GET','POST','PUT','PATCH','DELETE','HEAD'])
                            ? 'badge-'.$log->method
                            : 'badge-other';
                    @endphp
                    <tr>
                        <td class="text-muted" style="font-variant-numeric:tabular-nums;">{{ $log->id }}</td>
                        <td><span class="badge {{ $mc }}">{{ $log->method }}</span></td>
                        <td class="trunc" title="{{ $log->url }}">{{ $log->uri }}</td>
                        <td class="{{ $log->statusBadgeClass() }}">{{ $log->status_code }}</td>
                        <td style="font-variant-numeric:tabular-nums;white-space:nowrap;">
                            {{ $log->response_time_ms !== null ? number_format($log->response_time_ms).' ms' : '-' }}
                            @if($log->isSlow())<span class="slow-marker">SLOW</span>@endif
                        </td>
                        <td style="white-space:nowrap;">
                            @forelse((array)($log->tags ?? []) as $tag)
                                <a href="{{ route('request-log-analyzer.requests', array_merge(request()->except('page'), ['tag' => $tag])) }}"
                                   style="display:inline-block;margin:1px 2px;padding:1px 7px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;font-size:.65rem;text-decoration:none;white-space:nowrap;"
                                   title="Filter by tag: {{ $tag }}">{{ $tag }}</a>
                            @empty
                                <span class="text-muted">&mdash;</span>
                            @endforelse
                        </td>
                        <td class="text-muted">
                            {{ $log->memory_usage_bytes ? number_format($log->memory_usage_bytes / 1024 / 1024, 1).' MB' : '-' }}
                        </td>
                        <td>{{ $log->queries_count ?: '-' }}</td>
                        <td>
                            @if($log->errors_count > 0)
                                <span style="color:#dc2626;font-weight:600;">{{ $log->errors_count }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $log->ip ?? '-' }}</td>
                        <td class="text-muted" style="white-space:nowrap;">
                            @if($log->country)
                                {{ $log->city ? $log->city.', '.$log->country : $log->country }}
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td class="text-muted" style="white-space:nowrap;" title="{{ $log->created_at }}">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('request-log-analyzer.show', $log->id) }}" class="tag-link">Detail</a>
                            &ensp;
                            <a href="{{ route('request-log-analyzer.timeline', $log->id) }}" class="tag-link">Timeline</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- â”€â”€ Pagination â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    @if($logs->hasPages())
        <div class="pagination">
            {{-- Prev --}}
            @if($logs->onFirstPage())
                <span class="page-btn disabled">&lsaquo;</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
            @endif

            {{-- Page window --}}
            @php
                $current  = $logs->currentPage();
                $last     = $logs->lastPage();
                $window   = 2;
                $start    = max(1, $current - $window);
                $end      = min($last, $current + $window);
            @endphp

            @if($start > 1)
                <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                @if($start > 2)<span class="page-btn disabled" style="border:none;">&hellip;</span>@endif
            @endif

            @for($p = $start; $p <= $end; $p++)
                @if($p === $current)
                    <span class="page-btn current">{{ $p }}</span>
                @else
                    <a href="{{ $logs->url($p) }}" class="page-btn">{{ $p }}</a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)<span class="page-btn disabled" style="border:none;">&hellip;</span>@endif
                <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            {{-- Next --}}
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
            @else
                <span class="page-btn disabled">&rsaquo;</span>
            @endif

            <span class="pagination-info">
                {{ $logs->firstItem() }}&ndash;{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
            </span>
        </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
function clearSearch() {
    var input = document.getElementById('search-input');
    input.value = '';
    document.getElementById('search-form').submit();
}
// Auto-submit search form on native clear (clicking the × in type="search")
document.getElementById('search-input').addEventListener('search', function () {
    if (this.value === '') {
        document.getElementById('search-form').submit();
    }
});
</script>
@endpush

