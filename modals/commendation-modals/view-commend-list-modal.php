<!-- VIEW-ONLY COMMENDATION MODAL (for Received List) -->
<div class="modal fade" id="viewReceivedCommendationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-2">
                    <span class="fw-semibold text-nowrap">Nominator:</span>
                    <span id="recvViewNominator" class="flex-grow-1">-</span>
                </div>

                <div class="my-4">
                    <span class="fw-semibold text-nowrap">Type:</span>
                    <span id="recvViewType" class="flex-grow-1 text-uppercase">-</span>
                </div>

                <div class="my-4">
                    <span class="fw-semibold text-nowrap">Value:</span>
                    <span id="recvViewValue" class="flex-grow-1">-</span>
                </div>

                <div class="my-4">
                    <span class="fw-semibold text-nowrap">Points:</span>
                    <span id="recvViewPoints" class="flex-grow-1 fw-bold">-</span>
                </div>


                <div class="mb-4">
                    <label class="form-label fw-semibold">Comment:</label>
                    <textarea id="recvViewReason" class="form-control" rows="3" disabled></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Message From:</label>
                    <span id="recvViewApprover">-</span>

                    <textarea
                        id="recvViewRemarks"
                        class="form-control"
                        rows="3"
                        disabled></textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold">Attachments</label>
                    <div id="recvViewAttachmentWrap"></div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<!-- COMMENDATION ATTACHMENT PREVIEW MODAL -->
<div class="modal fade" id="commendAttachmentPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="commendAttachmentPreviewTitle">Attachment Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <div id="commendAttachmentPreviewBody" class="w-100 text-center"></div>
            </div>

        </div>
    </div>
</div>