<!-- Edit User Modal ADMIN ACCESS-->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="adminEditUserForm">
                <input type="hidden" id="admin_edit_current_photo" name="current_photo">
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
                        <input type="email" id="admin_edit_email" name="email" class="form-control" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select id="admin_edit_role" class="form-select" required></select>
                    </div>
                    <div class="col-md-6" id="adminEditDepartmentField">
                        <label class="form-label">Departments</label>
                        <div class="dropdown w-100">
                            <button
                                class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Select Departments
                            </button>
                            <ul class="dropdown-menu w-100" id="adminEditDepartmentDropdown"></ul>
                        </div>
                        <input type="hidden" id="admin_edit_departments" name="departments">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Profile Image</label>
                        <input type="file" id="admin_edit_profile_image" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>