<!-- CREATE LEAVE REQUEST MODAL -->
<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="leaveRequestForm">
                <div class="modal-header">
                    <h5 class="modal-title">Create Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- ==========================
                         LEAVE ENTRY CONTAINER (DYNAMIC)
                    =========================== -->
                    <div id="leaveEntryContainer">

                        <!-- TEMPLATE LEAVE ITEM -->
                        <div class="leave-entry row g-3 mb-3">

                            <div class="col-md-4">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control leave-date" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                                <select class="form-select leave-type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="vacation">Vacation Leave</option>
                                    <option value="sick">Sick Leave</option>

                                    <!---option value="emergency">Emergency Leave</option>
                                    <option value="compassionate">Compassionate Leave</option--->
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Availment <span class="text-danger">*</span></label>
                                <select class="form-select leave-availment" required>
                                    <option value="">-- Select --</option>
                                    <option value="whole-day">Whole Day</option>
                                    <option value="half-day">Half Day</option>
                                </select>
                            </div>

                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm remove-leave-entry">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                        </div>
                        <!-- END TEMPLATE -->

                    </div>

                    <!-- ADD MORE BUTTON -->
                    <button type="button" id="addLeaveEntryBtn" class="btn btn-outline-primary mb-3">
                        <i class="fa-solid fa-plus"></i> Add more
                    </button>

                    <!-- REASON -->
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="leaveReason" rows="3" required></textarea>
                    </div>

                    <!-- RECIPIENT -->
                    <div class="mb-3">
                        <label class="form-label">Recipient <span class="text-danger">*</span></label>
                        <select id="leaveRecipientSelect" class="form-select" name="leaveRecipientSelect" required>
                            <option value="">-- Select Recipient --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attachments</label>
                        <input type="file" id="leaveAttachment" class="form-control" multiple>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Submit Request</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>

            </form>

        </div>
    </div>
</div>