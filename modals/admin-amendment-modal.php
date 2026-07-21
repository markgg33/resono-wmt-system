<!-- View Amendment Modal -->
<div class="modal fade" id="amendmentModal" tabindex="-1" aria-labelledby="amendmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="amendmentModalLabel">Amendment Request Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Top Section -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Request ID:</strong> <span id="viewRequestId">--</span>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Status:</strong> <span id="viewStatus">--</span>
                    </div>
                </div>
                <hr>

                <!-- Dynamic content rendered by JS -->
                <div id="amendmentModalBody" class="px-1">
                    <p><strong>Requester:</strong> <span id="viewRequester">--</span></p>
                    <p><strong>Task:</strong> <span id="viewTask">--</span></p>
                    <p><strong>Date:</strong> <span id="viewDate">--</span></p>
                    <p><strong>Requested Field:</strong> <span id="viewField">--</span></p>
                    <p><strong>Old Value:</strong> <span id="viewOldValue">--</span></p>
                    <p><strong>New Value:</strong> <span id="viewNewValue">--</span></p>
                    <p><strong>Reason:</strong><br><span id="viewReason" class="text-muted">--</span></p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success rounded-pill px-4 decision-btn" id="approveBtn">Approve</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 decision-btn" id="rejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>