<div class="modal fade" id="createAnnouncementModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Announcement</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" id="announcementTitle" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="announcementDescription" class="form-control" rows="5"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Image (optional)</label>
                    <input type="file" id="announcementImage" class="form-control" accept="image/*">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-success" onclick="submitAnnouncement()">Post Announcement</button>
            </div>

        </div>
    </div>
</div>