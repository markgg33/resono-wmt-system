<!-- Edit Amendment Modal -->
<div class="modal fade" id="adminEditAmendmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Edit Amendment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adminEditAmendmentForm">
                    <input type="hidden" id="adminEditRequestId" name="id">

                    <!-- Original Date -->
                    <div class="mb-3">
                        <label class="form-label">Original Date</label>
                        <input type="text" id="adminEditDate" class="form-control" readonly>
                    </div>

                    <!-- Field to Amend -->
                    <div class="mb-3">
                        <label class="form-label">Field to Amend</label>
                        <select id="adminEditField" name="field" class="form-select" required>
                            <option value="start_time">Start Time</option>
                            <option value="end_time">End Time</option>
                            <option value="date">Date</option>
                        </select>
                    </div>

                    <!-- Old Values -->
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Old Start Time</label>
                            <input type="text" id="adminEditOldStartTime" class="form-control" readonly>
                        </div>
                        <div class="col">
                            <label class="form-label">Old End Time</label>
                            <input type="text" id="adminEditOldEndTime" class="form-control" readonly>
                        </div>
                    </div>

                    <!-- Old Date -->
                    <div class="mb-3">
                        <label class="form-label">Old Date</label>
                        <input type="text" id="adminEditOldDate" class="form-control" readonly>
                    </div>

                    <!-- Wrapper for date amendment -->
                    <div class="mb-3 d-none" id="adminEditDateWrapper">
                        <label class="form-label">New Date</label>
                        <input type="date" id="adminEditNewDate" name="new_date" class="form-control">
                    </div>

                    <!-- New Times -->
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">New Start Time</label>
                            <input type="time" id="adminEditNewStartTime" name="new_start_time" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">New End Time</label>
                            <input type="time" id="adminEditNewEndTime" name="new_end_time" class="form-control">
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea id="adminEditReason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Recipient -->
                    <div class="mb-3">
                        <label class="form-label">Recipient</label>
                        <select id="adminEditRecipientSelect" name="recipient_id" class="form-select" required>
                            <option value="">-- Select Recipient --</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Update Request</button>
                </form>
            </div>
        </div>
    </div>
</div>