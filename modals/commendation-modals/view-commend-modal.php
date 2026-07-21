<div class="modal fade" id="viewCommendationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Commendation Request</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="viewCommendationForm">
                <input type="hidden" id="commendViewId">

                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label">Nominees</label>
                        <ul class="list-group" id="commendViewNominees"></ul>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Employee</label>
                        <input id="commendViewEmployee" class="form-control" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <input id="commendViewType" class="form-control" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Value</label>
                        <select id="commendViewValue" class="form-select"></select>
                    </div>

                    <!-- POINTS BUTTONS -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Points</label>
                        <div class="d-flex gap-2">
                            <input type="hidden" id="commendViewPoints" name="points" value="">
                            <button type="button" class="btn btn-outline-success point-btn" data-point="1">1</button>
                            <button type="button" class="btn btn-outline-success point-btn" data-point="2">2</button>
                            <button type="button" class="btn btn-outline-success point-btn" data-point="3">3</button>
                            <button type="button" class="btn btn-outline-success point-btn" data-point="4">4</button>
                            <button type="button" class="btn btn-outline-success point-btn" data-point="5">5</button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Reason</label>
                        <textarea id="commendViewReason" class="form-control"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Attachment</label>
                        <div id="commendViewAttachmentWrap"></div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <input id="commendViewStatus" class="form-control" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Comment</label>
                        <textarea id="commendViewRemarks" class="form-control"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="saveCommendBtn" class="btn btn-success">Save</button>
                    <button type="button" id="approveCommendBtn" class="btn btn-success">Approve</button>
                    <button type="button" id="rejectCommendBtn" class="btn btn-danger">Reject</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>

        </div>
    </div>
</div>