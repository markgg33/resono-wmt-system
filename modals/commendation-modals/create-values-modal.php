<div class="modal fade" id="createValueModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="fw-bold">Create Value</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createValueForm">
                    <div class="mb-3">
                        <label class="fw-semibold">Value Name</label>
                        <input type="text" id="value_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold">Description</label>
                        <textarea id="value_description" class="form-control"></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>