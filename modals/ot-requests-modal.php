<!-- 🕒 Overtime Request Modal -->
<div class="modal fade" id="overtimeRequestModal" tabindex="-1" aria-labelledby="overtimeRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="overtimeRequestModalLabel">Overtime Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="overtimeRequestForm">
                    <div class="mb-3">
                        <label for="otTrackerDate" class="form-label fw-semibold">Tracker Date</label>
                        <input type="date" class="form-control" id="otTrackerDate" name="tracker_date" required>
                    </div>

                    <div class="mb-3">
                        <label for="otHours" class="form-label fw-semibold">Number of Hours</label>
                        <input
                            type="text"
                            class="form-control"
                            id="otHours"
                            name="hours"
                            placeholder="HH:MM"
                            maxlength="5"
                            pattern="^([0-9]{1,2}):([0-5][0-9])$"
                            title="Please enter duration as HH:MM (e.g., 02:30). Hours: 0-23, Minutes: 00-59."
                            required>
                        <small class="text-muted">Enter duration as HH:MM — e.g., 01:30 for 1 hour 30 minutes</small>

                    </div>



                    <div class="mb-3">
                        <label for="otReason" class="form-label fw-semibold">Reason</label>
                        <textarea class="form-control" id="otReason" name="reason" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="otRecipient" class="form-label fw-semibold">Recipient / Approver</label>
                        <select class="form-select" id="otRecipient" name="recipient_id" required>
                            <option value="">Select Recipient</option>
                        </select>
                    </div>

                    <div id="otRequestAlert" class="alert d-none mt-2" role="alert"></div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitOvertimeRequestBtn" class="btn btn-success">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </div>
    </div>
</div>