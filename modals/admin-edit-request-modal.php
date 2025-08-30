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

                    <div class="mb-3">
                        <label>Date</label>
                        <input type="text" id="adminEditDate" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>Field to Amend</label>
                        <select id="adminEditField" name="field" class="form-select" required>
                            <option value="start_time">Start Time</option>
                            <option value="end_time">End Time</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Old Value</label>
                        <input type="text" id="adminEditOldValue" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>New Value</label>
                        <input type="text" id="adminEditNewValue" name="new_value" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea id="adminEditReason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label>Recipient</label>
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