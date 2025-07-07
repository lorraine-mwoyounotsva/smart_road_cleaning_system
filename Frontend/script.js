// File: Frontend/script.js
// This file contains JavaScript functions to handle form visibility in the login page.
// It allows toggling between the login and registration forms.
function showForm(formId) {
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
} 