<!-- Edit Department Modal -->
<div class="modal fade" id="editDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editDeptForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Hidden field for department ID -->
                    <input type="hidden" id="editDeptId" name="dept_id">

                    <div class="mb-3">
                        <label for="editDeptName" class="form-label">Department Name</label>
                        <input
                            type="text"
                            id="editDeptName"
                            name="dept_name"
                            class="form-control"
                            placeholder="Enter department name"
                            required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>