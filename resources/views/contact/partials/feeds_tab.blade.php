<div class="card mb-5 mb-xl-10">
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">Google Related News</span>
            <span class="text-muted mt-1 fw-semibold fs-7">Latest saved Google news stories related to this contact</span>
        </h3>
        <div class="card-toolbar d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-primary" id="update_contact_feeds_btn">
                <span class="indicator-label">Refresh Google News</span>
                <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </div>
    <div class="card-body pt-3">
        <div id="contact_feeds_summary" class="alert alert-light-primary d-none py-3 px-4 mb-5"></div>
        <div id="contact_feeds_div" class="min-h-200px">
            <div class="text-center text-muted fw-semibold py-10">
                Open this tab to load Google related news.
            </div>
        </div>
    </div>
</div>
