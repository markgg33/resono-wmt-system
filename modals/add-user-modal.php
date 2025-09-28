<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addUserModalLabel">Create User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="addUserForm" action="../backend/add_user.php" method="POST" enctype="multipart/form-data">
                    <div class=" row mb-3">
                        <div class="col-md-4">
                            <label for="employee_id" class="form-label">Employee ID (Optional)</label>
                            <input type="text" class="form-control" id="employee_id" name="employee_id" placeholder="e.g. 2024-0012">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">First Name <span style="color:red;">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name">
                        </div>
                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Last Name <span style="color:red;">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label for="email" class="form-label">Email <span style="color:red;">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label for="password" class="form-label">Password <span style="color:red;">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••" required>
                                <span class="input-group-text toggle-password" data-target="password">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label for="role" class="form-label">Role <span style="color:red;">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin">Admin</option>
                                <option value="executive">Executive</option>
                                <option value="hr">HR</option>
                                <option value="user">Employee</option>
                                <option value="client">Client</option>
                                <option value="supervisor">Supervisor</option>
                            </select>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        </div>

                        <div class="col-md-4 mt-3" id="departmentField" style="display: none;">
                            <label class="form-label">Departments <span style="color:red;">*</span></label>
                            <div class="dropdown w-100">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select Departments
                                </button>
                                <ul class="dropdown-menu w-100 p-2" id="departmentDropdown" style="max-height: 200px; overflow-y: auto;">
                                    <!-- checkboxes + radio buttons populated dynamically -->
                                </ul>
                            </div>
                            <!-- Hidden inputs to send selected departments and primary -->
                            <input type="hidden" id="department_ids" name="department_ids">
                            <input type="hidden" id="primary_department" name="primary_department">
                        </div>


                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Register</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById("addUserForm").addEventListener("submit", async function(e) {
        e.preventDefault(); // prevent default form submission

        // 🔹 Collect departments
        const checkboxes = document.querySelectorAll("#departmentDropdown input[type=checkbox]:checked");
        const primaryRadio = document.querySelector("#departmentDropdown input[type=radio]:checked");

        const departments = [];
        checkboxes.forEach(cb => {
            departments.push({
                id: parseInt(cb.value),
                primary: primaryRadio && parseInt(primaryRadio.value) === parseInt(cb.value) ? 1 : 0
            });
        });

        if (departments.length === 0) {
            return alert("Please select at least one department.");
        }

        if (!confirm("Register the account?")) return;

        // Prepare form data
        const form = e.target;
        const formData = new FormData(form);

        // Append departments as JSON
        formData.append("departments", JSON.stringify(departments));

        try {
            const res = await fetch(form.action, {
                method: "POST",
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message || "User added successfully.");
                // Reset form
                form.reset();
                // Close modal
                const modalEl = document.getElementById("addUserModal");
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                // Optionally refresh page or table
                location.reload(); // refresh page
            } else {
                alert(data.message || "Something went wrong.");
            }
        } catch (err) {
            console.error(err);
            alert("An error occurred. Check console for details.");
        }
    });
</script>