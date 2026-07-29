<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Date and Branch Filters</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ $filterAction }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_from">From</label>
                        <input id="date_from" type="date" name="date_from" class="form-control" value="{{ $dateFrom->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_to">To</label>
                        <input id="date_to" type="date" name="date_to" class="form-control" value="{{ $dateTo->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="branch_ids">Branches</label>
                        <select id="branch_ids" name="branch_ids[]" class="form-control" multiple size="{{ min(max($filterBranches->count(), 2), 5) }}" @disabled(auth()->user()?->isBranchRestricted())>
                            @foreach ($filterBranches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchIds->contains($branch->id))>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @if (auth()->user()?->isBranchRestricted())
                            @foreach ($selectedBranchIds as $branchId)<input type="hidden" name="branch_ids[]" value="{{ $branchId }}">@endforeach
                        @endif
                        <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select several branches.</small>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block mb-3">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>
