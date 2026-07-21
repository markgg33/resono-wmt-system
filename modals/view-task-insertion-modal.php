<!-- Task Insertion View Modal -->
<div class="modal fade" id="viewTaskInsertionModal" tabindex="-1" aria-labelledby="viewTaskInsertionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="viewTaskInsertionModalLabel">Task Insertion Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Request ID:</strong> <span id="viewTaskRequestId">--</span>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Status:</strong> <span id="viewTaskStatus">--</span>
                    </div>
                </div>
                <hr>
                <p><strong>Requestor:</strong> <span id="viewRequestor"></span></p>
                <p><strong>Date of Task:</strong> <span id="viewTaskDate"></span></p>
                <p><strong>Request Created:</strong> <span id="viewDateRequested"></span></p>
                <p><strong>Work Mode:</strong> <span id="viewWorkMode"></span></p>
                <p><strong>Task Description:</strong> <span id="viewTaskDescription"></span></p>
                <p><strong>Start Time:</strong> <span id="viewStartTime"></span></p>
                <p><strong>End Time:</strong> <span id="viewEndTime"></span></p>
                <p><strong>Reason:</strong><br><span id="viewTaskReason" class="text-muted"></span></p>
            </div>

            <div class="modal-footer" id="actionButtons"></div>
            <div id="modalAlertContainer" class="mt-2"></div>

        </div>
    </div>
</div>