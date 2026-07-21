<div class="modal fade" id="editValueModal">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="fw-bold">Edit Value</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_value_id">
                <div class="mb-3">
                    <label class="fw-semibold">Value Name</label>
                    <input type="text" id="edit_value_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="fw-semibold">Description</label>
                    <textarea id="edit_value_desc" class="form-control"></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="saveValueEdit()">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>