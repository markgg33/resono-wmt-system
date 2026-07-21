<!-- ========================= -->
<!-- VIEW TEAM MODAL -->
<!-- ========================= -->
<div
    class="modal fade"
    id="viewTeamModal"
    tabindex="-1"
    aria-labelledby="viewTeamModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="viewTeamModalLabel">Team Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <strong>Team Name:</strong>
                    <div id="viewTeamName" class="text-muted"></div>
                </div>

                <div class="mb-3">
                    <strong>Created By:</strong>
                    <div id="viewTeamCreatedBy" class="text-muted"></div>
                </div>

                <div class="mb-3">
                    <strong>Date Created:</strong>
                    <div id="viewTeamCreatedAt" class="text-muted"></div>
                </div>

                <hr />

                <strong>Team Members</strong>
                <ul id="viewTeamMembers" class="list-group mt-2"></ul>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>