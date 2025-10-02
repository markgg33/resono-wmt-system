<!-- User Amendment Modal -->
<div class="modal fade" id="userAmendmentModal" tabindex="-1" aria-labelledby="userAmendmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="userAmendmentModalLabel">Request Amendment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="amendmentForm">
                    <input type="hidden" id="logId" name="log_id">

                    <!-- Hidden fields for backend -->
                    <input type="hidden" id="oldDate" name="old_date">
                    <input type="hidden" id="oldStartTimeHidden" name="old_start_time">
                    <input type="hidden" id="oldEndTimeHidden" name="old_end_time">

                    <!-- Always show original date -->
                    <div class="mb-3">
                        <label>Original Date</label>
                        <input type="text" id="amendDate" class="form-control" readonly>
                    </div>

                    <!-- Field to amend -->
                    <div class="mb-3">
                        <label>Field to Amend</label>
                        <select id="field" name="field" class="form-select" required>
                            <option value="start_time">Start Time</option>
                            <option value="end_time">End Time</option>
                            <option value="date">Date</option>
                        </select>
                    </div>

                    <!-- Old Values -->
                    <div class="row mb-3">
                        <div class="col">
                            <label>Old Start Time</label>
                            <input type="text" id="oldStartTime" class="form-control" readonly>
                        </div>
                        <div class="col">
                            <label>Old End Time</label>
                            <input type="text" id="oldEndTime" class="form-control" readonly>
                        </div>
                    </div>

                    <!-- New Values -->
                    <div id="newDateWrapper" class="mb-3 d-none">
                        <label>New Date</label>
                        <input type="date" id="newDate" name="new_date" class="form-control">
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label>New Start Time</label>
                            <input type="time" id="newStartTime" name="new_start_time" class="form-control">
                        </div>
                        <div class="col">
                            <label>New End Time</label>
                            <input type="time" id="newEndTime" name="new_end_time" class="form-control">
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Recipient -->
                    <div class="mb-3">
                        <label for="recipientSelect" class="form-label">Send To</label>
                        <select id="recipientSelect" name="recipient_id" class="form-select" required>
                            <option value="">-- Select Recipient --</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>