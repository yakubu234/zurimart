@extends('adminlte::page')

@push('css')
    <style>
        .btn-default,
        .btn-light {
            color: #fff !important;
            background-color: #111 !important;
            border-color: #111 !important;
        }

        .btn-default:hover,
        .btn-default:focus,
        .btn-default:active,
        .btn-default:not(:disabled):not(.disabled).active,
        .btn-light:hover,
        .btn-light:focus,
        .btn-light:active,
        .btn-light:not(:disabled):not(.disabled).active {
            color: #fff !important;
            background-color: #000 !important;
            border-color: #000 !important;
        }

        .btn-default.disabled,
        .btn-default:disabled,
        .btn-light.disabled,
        .btn-light:disabled {
            color: #fff !important;
            background-color: #111 !important;
            border-color: #111 !important;
        }

        .drag-scroll-enabled {
            cursor: grab;
            -webkit-overflow-scrolling: touch;
        }

        .drag-scroll-enabled.is-dragging {
            cursor: grabbing;
            user-select: none;
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const interactiveSelector = 'a, button, input, select, textarea, label, form, [role="button"]';

            document.querySelectorAll('.table-responsive, .table-wrap').forEach((scroller) => {
                let isDragging = false;
                let hasMoved = false;
                let suppressClick = false;
                let startX = 0;
                let startScrollLeft = 0;

                const updateDragState = () => {
                    scroller.classList.toggle('drag-scroll-enabled', scroller.scrollWidth > scroller.clientWidth);
                };

                updateDragState();

                if ('ResizeObserver' in window) {
                    new ResizeObserver(updateDragState).observe(scroller);
                } else {
                    window.addEventListener('resize', updateDragState);
                }

                scroller.addEventListener('pointerdown', (event) => {
                    if (
                        event.pointerType === 'touch'
                        || event.button !== 0
                        || event.target.closest(interactiveSelector)
                        || scroller.scrollWidth <= scroller.clientWidth
                    ) {
                        return;
                    }

                    isDragging = true;
                    hasMoved = false;
                    startX = event.clientX;
                    startScrollLeft = scroller.scrollLeft;
                    scroller.classList.add('is-dragging');
                    scroller.setPointerCapture(event.pointerId);
                });

                scroller.addEventListener('pointermove', (event) => {
                    if (! isDragging) {
                        return;
                    }

                    const distance = event.clientX - startX;

                    if (Math.abs(distance) > 3) {
                        hasMoved = true;
                    }

                    if (hasMoved) {
                        event.preventDefault();
                        scroller.scrollLeft = startScrollLeft - distance;
                    }
                });

                const stopDragging = (event) => {
                    if (! isDragging) {
                        return;
                    }

                    isDragging = false;
                    suppressClick = hasMoved;
                    scroller.classList.remove('is-dragging');

                    if (scroller.hasPointerCapture(event.pointerId)) {
                        scroller.releasePointerCapture(event.pointerId);
                    }

                    setTimeout(() => {
                        suppressClick = false;
                    }, 0);
                };

                scroller.addEventListener('pointerup', stopDragging);
                scroller.addEventListener('pointercancel', stopDragging);
                scroller.addEventListener('click', (event) => {
                    if (suppressClick) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                }, true);
            });
        });
    </script>
@endpush

@section('title', trim($__env->yieldContent('title', 'ZuriMart Bakery')))

@section('css')
    <style>
        .table-actions-col {
            width: 1%;
            white-space: nowrap;
        }

        .action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: nowrap;
        }

        .action-buttons form {
            margin: 0;
        }

        .action-icon-btn {
            width: 2rem;
            height: 2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-icon-btn i {
            font-size: 0.9rem;
        }

        .action-text-btn {
            min-width: 2.5rem;
        }

        .daily-report-table .quantity-column {
            width: 132px;
            min-width: 132px;
        }

        .daily-report-table .quantity-input {
            width: 108px;
            min-width: 108px;
            max-width: 108px;
            box-sizing: border-box;
            text-align: center;
        }

        .record-table-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: .75rem;
            padding: .75rem;
            margin-bottom: .75rem;
            border: 1px solid #dee2e6;
            border-radius: .25rem;
            background: #f8f9fa;
        }

        .record-table-filters .form-group {
            min-width: 150px;
            margin-bottom: 0;
        }

        .record-table-filters .record-search-group {
            flex: 1 1 220px;
        }

        .record-table-empty {
            display: none;
        }
    </style>
@stop

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-8">
            <h1 class="m-0">@yield('page_title', 'ZuriMart Bakery')</h1>
            <p class="text-muted mb-0 mt-1">@yield('page_intro', 'Unified bakery production, outlet restocking, and wholesale order management.')</p>
        </div>
        <div class="col-sm-4">
            <div class="float-sm-right text-sm text-muted text-sm-right mt-2 mt-sm-0">
                <div><strong>Date:</strong> {{ now()->format('d M Y') }}</div>
                @auth
                    <div><strong>User:</strong> {{ auth()->user()->name }}</div>
                @endauth
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <strong>Please check the form and try again.</strong>
            <ul class="mb-0 mt-2 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('page')
@stop

@section('footer')
    <div class="float-right d-none d-sm-inline">ZuriMart Unified Bakery Management System</div>
    <strong>Website:</strong> zurimartbakeryservices.com
@stop

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const normalise = (value) => value.replace(/\s+/g, ' ').trim().toLocaleLowerCase();
            const parseDate = (value) => {
                const cleaned = value.replace(/\s+/g, ' ').trim();
                const iso = cleaned.match(/\b(\d{4})-(\d{2})-(\d{2})\b/);
                const slash = cleaned.match(/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})\b/);

                if (iso) return new Date(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3]));
                if (slash) return new Date(Number(slash[3]), Number(slash[2]) - 1, Number(slash[1]));

                const parsed = Date.parse(cleaned);
                return Number.isNaN(parsed) ? null : new Date(parsed);
            };

            document.querySelectorAll('table.js-record-table').forEach((table, tableIndex) => {
                const headers = Array.from(table.querySelectorAll('thead th'));
                const body = table.tBodies[0];
                if (! body || ! headers.length || table.dataset.filterReady === 'true') return;

                const searchableColumns = headers
                    .map((header, index) => ({ index, label: header.textContent.trim() }))
                    .filter((column) => column.label && normalise(column.label) !== 'actions');
                if (! searchableColumns.length) return;

                table.dataset.filterReady = 'true';
                const dateColumns = new Set(searchableColumns
                    .filter((column) => /\b(date|time|created|updated)\b/i.test(column.label))
                    .map((column) => column.index));

                const filters = document.createElement('div');
                filters.className = 'record-table-filters';
                filters.setAttribute('role', 'search');
                filters.setAttribute('aria-label', 'Table filters');
                filters.innerHTML = `
                    <div class="form-group record-search-group">
                        <label class="small mb-1" for="record-search-${tableIndex}">Search records</label>
                        <input id="record-search-${tableIndex}" type="search" class="form-control form-control-sm"
                            placeholder="Search all columns">
                    </div>
                    <div class="form-group">
                        <label class="small mb-1" for="record-column-${tableIndex}">Column</label>
                        <select id="record-column-${tableIndex}" class="form-control form-control-sm">
                            <option value="">All columns</option>
                            ${searchableColumns.map((column) => `<option value="${column.index}">${column.label}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small mb-1" for="record-value-${tableIndex}">Column value</label>
                        <input id="record-value-${tableIndex}" type="search" class="form-control form-control-sm"
                            placeholder="Contains...">
                    </div>
                    <div class="form-group record-date-filter">
                        <label class="small mb-1" for="record-from-${tableIndex}">Date from</label>
                        <input id="record-from-${tableIndex}" type="date" class="form-control form-control-sm">
                    </div>
                    <div class="form-group record-date-filter">
                        <label class="small mb-1" for="record-to-${tableIndex}">Date to</label>
                        <input id="record-to-${tableIndex}" type="date" class="form-control form-control-sm">
                    </div>
                    <button type="button" class="btn btn-sm btn-default record-filter-reset">Clear</button>`;

                table.parentElement.insertBefore(filters, table);
                const globalSearch = filters.querySelector(`#record-search-${tableIndex}`);
                const columnSelect = filters.querySelector(`#record-column-${tableIndex}`);
                const columnValue = filters.querySelector(`#record-value-${tableIndex}`);
                const dateFrom = filters.querySelector(`#record-from-${tableIndex}`);
                const dateTo = filters.querySelector(`#record-to-${tableIndex}`);
                const dateGroups = filters.querySelectorAll('.record-date-filter');
                const rows = Array.from(body.rows).filter((row) => row.cells.length > 1);

                if (! rows.length) {
                    filters.remove();
                    return;
                }

                const emptyRow = document.createElement('tr');
                emptyRow.className = 'record-table-empty';
                emptyRow.innerHTML = `<td colspan="${headers.length}" class="text-center text-muted py-4">No records match these filters.</td>`;
                body.appendChild(emptyRow);

                const updateDateVisibility = () => {
                    const selected = columnSelect.value;
                    const show = selected !== '' && dateColumns.has(Number(selected));
                    dateGroups.forEach((group) => group.classList.toggle('d-none', ! show));
                    if (! show) {
                        dateFrom.value = '';
                        dateTo.value = '';
                    }
                };

                const applyFilters = () => {
                    const globalNeedle = normalise(globalSearch.value);
                    const columnNeedle = normalise(columnValue.value);
                    const columnIndex = columnSelect.value === '' ? null : Number(columnSelect.value);
                    const from = dateFrom.value ? new Date(`${dateFrom.value}T00:00:00`) : null;
                    const to = dateTo.value ? new Date(`${dateTo.value}T23:59:59`) : null;
                    let visible = 0;

                    rows.forEach((row) => {
                        const cells = Array.from(row.cells);
                        const allText = normalise(cells.map((cell) => cell.textContent).join(' '));
                        const selectedText = columnIndex === null ? allText : normalise(cells[columnIndex]?.textContent || '');
                        const rowDate = columnIndex !== null && dateColumns.has(columnIndex)
                            ? parseDate(cells[columnIndex]?.textContent || '')
                            : null;
                        const matches = (! globalNeedle || allText.includes(globalNeedle))
                            && (! columnNeedle || selectedText.includes(columnNeedle))
                            && (! from || (rowDate && rowDate >= from))
                            && (! to || (rowDate && rowDate <= to));

                        row.style.display = matches ? '' : 'none';
                        if (matches) visible++;
                    });
                    emptyRow.style.display = visible ? 'none' : '';
                };

                filters.addEventListener('input', applyFilters);
                filters.addEventListener('change', (event) => {
                    if (event.target === columnSelect) updateDateVisibility();
                    applyFilters();
                });
                filters.querySelector('.record-filter-reset').addEventListener('click', () => {
                    globalSearch.value = '';
                    columnSelect.value = '';
                    columnValue.value = '';
                    dateFrom.value = '';
                    dateTo.value = '';
                    updateDateVisibility();
                    applyFilters();
                    globalSearch.focus();
                });

                updateDateVisibility();
            });
        });
    </script>
@endpush
