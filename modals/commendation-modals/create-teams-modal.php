<div class="modal fade" id="createTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Create Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="createTeamForm">

                    <!-- Team Name -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Team Name</label>
                            <input type="text" class="form-control" id="team_name" required>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="row mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Select Team Members</label>

                            <div class="dropdown">
                                <button class="form-control text-start dropdown-toggle"
                                    type="button"
                                    id="teamUserDropdownBtn"
                                    data-bs-toggle="dropdown">
                                    Select users
                                </button>

                                <ul class="dropdown-menu w-100 p-2"
                                    id="teamUserDropdown"
                                    style="max-height: 300px; overflow-y: auto;">
                                    <li class="text-center text-muted">Loading users...</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Members -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="fw-semibold">Selected Members</label>
                            <ul class="list-group" id="selectedTeamMembers">
                                <li class="list-group-item text-muted">
                                    No members selected
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            Create Team
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>