<div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="editTeamForm">

                    <input type="hidden" id="edit_team_id">

                    <!-- Team Name -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Team Name</label>
                            <input type="text" class="form-control" id="edit_team_name" required>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="row mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Manage Team Members</label>

                            <div class="dropdown">
                                <button class="form-control text-start dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown">
                                    Select users
                                </button>

                                <ul class="dropdown-menu w-100 p-2"
                                    id="editTeamUserDropdown"
                                    style="max-height: 300px; overflow-y: auto;">
                                    <li class="text-center text-muted">Loading users...</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Members -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="fw-semibold">Current Members</label>
                            <ul class="list-group" id="editSelectedTeamMembers"></ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>