<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="adminEditUserForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" id="admin_edit_user_id">

                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" id="admin_edit_first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Middle Name</label>
                        <input type="text" id="admin_edit_middle_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="admin_edit_last_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employee ID</label>
                        <input type="text" id="admin_edit_employee_id" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" id="admin_edit_email" class="form-control" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select id="admin_edit_role" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select id="admin_edit_department" class="form-select"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>