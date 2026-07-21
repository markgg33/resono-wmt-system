<div class="modal fade" id="commendModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Commend Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="commendForm" enctype="multipart/form-data">

                <input type="hidden" id="commend_type" name="type" value="commend">
                <div class="modal-body">
                    <div class="row g-4">

                        <!-- LEFT SIDE : FORM -->
                        <div class="col-xl-5">

                            <!-- USER CHECKBOX DROPDOWN -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Users</label>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100"
                                        type="button"
                                        data-bs-toggle="dropdown">
                                        Select users
                                    </button>

                                    <ul class="dropdown-menu w-100 p-2"
                                        id="commendUserDropdown"
                                        style="max-height: 250px; overflow-y: auto;">
                                        <!-- populated by JS -->
                                    </ul>
                                </div>

                                <ul class="list-group mt-2" id="selectedCommendUsers"></ul>
                            </div>


                            <!-- POINTS BUTTONS -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Points</label>
                                <div class="d-flex gap-2">
                                    <input type="hidden" id="commend_points" name="points" value="">
                                    <button type="button" class="btn btn-outline-success point-btn" data-point="1">1</button>
                                    <button type="button" class="btn btn-outline-success point-btn" data-point="2">2</button>
                                    <button type="button" class="btn btn-outline-success point-btn" data-point="3">3</button>
                                    <button type="button" class="btn btn-outline-success point-btn" data-point="4">4</button>
                                    <button type="button" class="btn btn-outline-success point-btn" data-point="5">5</button>
                                </div>
                            </div>

                            <!-- VALUES -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Value</label>
                                <select id="commend_value" name="value_id" class="form-select" required>
                                    <option value="">-- Select Value --</option>
                                </select>
                            </div>


                            <!-- REASON -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Reason<span style="color:red;">*</span></label>
                                <textarea id="commend_reason" name="reason" class="form-control" rows="3" required></textarea>
                            </div>

                            <!-- ATTACHMENT -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Attachment (optional)</label>
                                <input type="file" id="commend_attachment" name="attachment" class="form-control">
                            </div>

                        </div>
                        <!-- RIGHT SIDE : IMAGE -->
                        <div class="col-xl-7 d-flex align-items-center justify-content-center">
                            <img
                                src="../assets/Resono-Values.jpg"
                                class="img-fluid rounded shadow-lg"
                                style="max-height: 1400px; object-fit: cover;"
                                alt="Values Image">
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex flex-start">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>

        </div>
    </div>
</div>