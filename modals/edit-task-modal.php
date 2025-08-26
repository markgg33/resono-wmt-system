<!-- Edit Task Log Modal -->
<div class="modal fade" id="editTaskLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Task Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTaskLogForm">
                    <input type="hidden" id="editLogId">

                    <!-- Work Mode Selector -->
                    <div class="mb-3">
                        <label for="editWorkMode" class="form-label">Work Mode</label>
                        <select class="form-select" id="editWorkMode" required>
                            <option value="">Select Work Mode</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>

                    <!-- Task Description Selector -->
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Task Description</label>
                        <select class="form-select" id="editDescription" required>
                            <option value="">Select Task</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTaskLogBtn">Save</button>
            </div>
        </div>
    </div>
</div>