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

                    <div class="mb-3">
                        <label>Date</label>
                        <input type="text" id="amendDate" name="date" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>Field to Amend</label>
                        <select id="field" name="field" class="form-select" required>
                            <option value="start_time">Start Time</option>
                            <option value="end_time">End Time</option>
                            <option value="date">Date</option> <!-- NEW -->
                            <option value="remarks">Remarks</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Old Value</label>
                        <input type="text" id="oldValue" name="old_value" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>New Value</label>
                        <div id="newValueWrapper">
                            <input type="text" id="newValue" name="new_value" class="form-control" required>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>

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