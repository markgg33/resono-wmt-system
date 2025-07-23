function toggleDepartmentField() {
  const role = document.getElementById("role").value;
  const deptField = document.getElementById("departmentField");

  // Show department field only for 'user' and 'admin'
  deptField.style.display =
    role === "user" || role === "admin" ? "block" : "none";
}

function loadDepartments() {
  fetch("../backend/get_departments.php")
    .then((res) => res.json())
    .then((data) => {
      const select = document.getElementById("department_id");
      select.innerHTML = '<option value="">-- Select Department --</option>';
      data.forEach((dept) => {
        const option = document.createElement("option");
        option.value = dept.id;
        option.textContent = dept.name;
        select.appendChild(option);
      });
    });
}

document.addEventListener("DOMContentLoaded", () => {
  loadDepartments();
  toggleDepartmentField();
});
