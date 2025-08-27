<!-- Edit Request Modal -->
<div class="modal fade" id="editRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Amendment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-request-form">
                    <input type="hidden" id="edit-request-id">

                    <div class="mb-3">
                        <label for="edit-new-value" class="form-label">New Value</label>
                        <input type="text" class="form-control" id="edit-new-value" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="edit-reason" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>