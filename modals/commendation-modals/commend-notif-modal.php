<div class="modal fade" id="commendNotifModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="commendNotifTitle">
                    You've received a new commendation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4">

                    <!-- LEFT -->
                    <div class="col-xl-4">

                        <!-- Value and Points -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Value:</label>
                            <div id="notif_value" class="form-control bg-light" style="min-height: 38px;">-</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Points:</label>
                            <div id="notif_points" class="form-control bg-light" style="min-height: 38px;">-</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Comment</label>
                            <textarea id="notif_reason" class="form-control" rows="3" disabled></textarea>
                        </div>

                        <div class="mb-2">
                            <label id="notif_message_from_label" class="form-label fw-semibold">Message From</label>
                            <textarea id="notif_comment" class="form-control" rows="3" disabled></textarea>
                        </div>

                    </div>


                    <!-- RIGHT IMAGE -->
                    <div class="col-xl-8 d-flex align-items-center justify-content-center">
                        <img
                            src="../assets/Resono-Values.jpg"
                            class="img-fluid rounded shadow-lg"
                            style="max-height: 1400px; object-fit: cover;"
                            alt="Values Image">
                    </div>

                </div>
            </div>

            <div class="modal-footer d-flex flex-start">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    Okay
                </button>
            </div>

        </div>
    </div>
</div>