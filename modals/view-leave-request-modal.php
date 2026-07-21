<!-- VIEW / EDIT LEAVE REQUEST MODAL -->
<div class="modal fade" id="viewLeaveRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="viewLeaveRequestForm">

                <div class="modal-header">
                    <h5 class="modal-title">View Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- LEAVE ENTRY CONTAINER -->
                    <div id="viewLeaveEntryContainer"></div>

                    <!-- ADD MORE (only if editable) -->
                    <button
                        type="button"
                        id="viewAddLeaveEntryBtn"
                        class="btn btn-outline-primary mb-3 d-none">
                        <i class="fa-solid fa-plus"></i> Add more
                    </button>

                    <!-- REASON -->
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea
                            class="form-control"
                            id="viewLeaveReason"
                            rows="3"></textarea>
                    </div>

                    <!-- RECIPIENT -->
                    <div class="mb-3">
                        <label class="form-label">Recipient</label>
                        <select
                            id="viewLeaveRecipientSelect"
                            class="form-select">
                        </select>
                    </div>

                    <!-- EXISTING ATTACHMENTS -->
                    <div class="mb-3">
                        <label class="form-label">Existing Attachments</label>
                        <div id="existingLeaveAttachments"></div>
                    </div>

                    <!-- ADD ATTACHMENTS -->
                    <div class="mb-3">
                        <label class="form-label">Add Attachments</label>
                        <input type="file" id="viewLeaveAttachment" class="form-control" multiple>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success d-none" id="saveLeaveChangesBtn">
                        <i class="fa-solid fa-floppy-disk" style="font-size: 20px;"></i> Save
                    </button>

                    <button type="button" class="btn btn-primary d-none" id="approveLeaveBtn">
                        <i class="fa-solid fa-check" style="font-size: 20px;"></i> Approve
                    </button>

                    <button type="button" class="btn btn-danger d-none" id="rejectLeaveBtn">
                        <i class="fa-solid fa-x" style="font-size: 20px;"></i> Reject
                    </button>

                    <div class="dropdown d-inline-block d-none" id="leaveActionsDropdown">
                        <button class="btn btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown">
                            Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button type="button" class="dropdown-item text-warning" id="cancelLeaveBtn">
                                    <i class="fa-solid fa-ban me-2"></i> Cancel
                                </button>
                            </li>
                            <!--li>
                                <button class="dropdown-item text-danger" id="deleteLeaveBtn">
                                    <i class="fa-solid fa-trash me-2"></i> Delete
                                </button>
                            </li-->
                        </ul>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- ATTACHMENT PREVIEW MODAL -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="attachmentPreviewTitle">Attachment Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <div id="attachmentPreviewBody" class="w-100 text-center"></div>
            </div>

        </div>
    </div>
</div>