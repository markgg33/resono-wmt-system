<!-- 📝 VIEW / APPROVE OVERTIME REQUEST MODAL -->
<div class="modal fade" id="viewOTRequestModal" tabindex="-1" aria-labelledby="viewOTRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-3">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="viewOTRequestModalLabel">Overtime Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="viewOTRequestForm">

                    <!-- Hidden ID -->
                    <input type="hidden" id="otViewRequestId">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee</label>
                        <input type="text" class="form-control" id="otViewEmployee" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tracker Date</label>
                        <input type="date" class="form-control" id="otViewTrackerDate">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Hours</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="otViewHours" 
                            placeholder="HH:MM"
                            maxlength="5"
                            pattern="^([0-9]{1,2}):([0-5][0-9])$"
                            title="Please enter duration as HH:MM (e.g., 02:30). Hours: 0-23, Minutes: 00-59.">
                        <small class="text-muted">Enter duration as HH:MM — e.g. 01:30</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason</label>
                        <textarea class="form-control" id="otViewReason" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recipient / Approver</label>
                        <select class="form-select" id="otViewRecipient"></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <input type="text" class="form-control" id="otViewStatus" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" id="otViewRemarks" rows="2" placeholder="Enter remarks (optional)"></textarea>
                    </div>

                    <div id="viewOTAlert" class="alert d-none mt-2" role="alert"></div>
                </form>
            </div>

            <!-- FOOTER BUTTONS -->
            <div class="modal-footer">

                <!-- Save -->
                <button type="button" id="saveOTRequestBtn" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Changes
                </button>

                <!-- Reject (Higher roles only) -->
                <button type="button" id="rejectOTRequestBtn" class="btn btn-danger d-none">
                    <i class="fa-solid fa-xmark"></i> Reject
                </button>

                <!-- Approve (Higher roles only) -->
                <button type="button" id="approveOTRequestBtn" class="btn btn-success d-none">
                    <i class="fa-solid fa-check"></i> Approve
                </button>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

            </div>

        </div>
    </div>
</div>