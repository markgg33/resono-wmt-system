<!-- Task Insertion Modal -->
<div class="modal fade" id="taskInsertionModal" tabindex="-1" aria-labelledby="taskInsertionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="taskInsertionModalLabel">New Task Insertion Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="taskInsertionForm">
                    <div class="mb-3">
                        <label for="insertDate" class="form-label fw-semibold">Date</label>
                        <input type="date" class="form-control" id="insertDate" name="date" required>
                    </div>

                    <div class="mb-3">
                        <label for="insertWorkMode" class="form-label fw-semibold">Work Mode</label>
                        <select class="form-select" id="insertWorkMode" name="work_mode_id" required>
                            <option value="">Select Work Mode</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="insertTaskDescription" class="form-label fw-semibold">Task Description</label>
                        <select class="form-select" id="insertTaskDescription" name="task_description_id" required>
                            <option value="">Select Task</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="insertStartTime" class="form-label fw-semibold">Start Time</label>
                            <input type="time" class="form-control" id="insertStartTime" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="insertEndTime" class="form-label fw-semibold">End Time</label>
                            <input type="time" class="form-control" id="insertEndTime" name="end_time">
                        </div>
                    </div>

                    <!-- Recipient Dropdown -->
                    <div class="mb-3">
                        <label for="RecipientSelectInsertion" class="form-label fw-semibold">Recipient</label>
                        <select class="form-select" id="RecipientSelectInsertion" name="recipient_id" required>
                            <option value="">Select Recipient</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="insertReason" class="form-label fw-semibold">Reason for Task Insertion</label>
                        <textarea class="form-control" id="insertReason" name="reason" rows="3" required></textarea>
                    </div>

                    <div id="insertTaskAlert" class="alert d-none mt-2" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitTaskInsertionBtn" class="btn btn-success">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </div>
    </div>
</div>