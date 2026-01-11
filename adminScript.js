

// ===============================
// RESIDENTS INLINE
// ===============================
function loadResidentsPage() {
  const mainContent = document.getElementById("mainContent");
  if (!mainContent) return;

  fetch("residents-content.html")
    .then(r => r.text())
    .then(html => {
      mainContent.innerHTML = html;
      initResidents(); // 🔥 DITO lang i-call ang init
      
        const btnResidents = document.getElementById("btnResidents");
      if (btnResidents&& typeof setActiveSidebar === "function") {
        setActiveSidebar(btnResidents);
      }
      
    })
    .catch(err => console.error("Error loading residents page:", err));

    
}
 // ---------------- MODAL FUNCTIONS ----------------
  function openAddResident() {
    editResidentId = null;
    modalTitle.textContent = "Add Resident";
    residentForm.reset();
    residentModal.style.display = "block";
    // ---------------- RESET TABS WHEN OPENING EDIT ----------------
document.querySelectorAll("#residentModal .tab-content")
  .forEach(tc => tc.style.display = "none");

document.getElementById("formTab").style.display = "block";

document.querySelectorAll("#residentModal .tabBtn")
  .forEach(b => b.classList.remove("active"));

document.querySelector('#residentModal [data-tab="formTab"]')
  .classList.add("active");

// Show modal
residentModal.style.display = "block";



  }

  
// --- Close Modal ---
function closeModal() {
  residentModal.style.display = "none";
}
 
  
function initResidents() {

  
  // ---------------- DOM ELEMENTS ----------------
  const residentTable = document.getElementById("residentTable");
  const residentModal = document.getElementById("residentModal");
  const residentForm = document.getElementById("residentForm");
  const modalTitle = document.getElementById("modalTitle");


  // ---------------- RECORDS MODAL ----------------
  // ✅ Make sure button exists
  const openRecords = document.getElementById("openRecords");
  const closeRecords = document.getElementById("closeRecords");
  const recordsModal = document.getElementById("recordsModal");

  if (openRecords && closeRecords && recordsModal) {
    openRecords.addEventListener("click", () => {
      recordsModal.classList.add("active");
    });

    closeRecords.addEventListener("click", () => {
      recordsModal.classList.remove("active");
    });

    // click outside to close
    recordsModal.addEventListener("click", e => {
      if (e.target === recordsModal) {
        recordsModal.classList.remove("active");
      }
    });

    // ESC key closes modal
    document.addEventListener("keydown", e => {
      if (e.key === "Escape" && recordsModal.classList.contains("active")) {
        recordsModal.classList.remove("active");
      }
    });
  } else {
    console.warn("Records modal elements not found yet!");
  }



  
  if (!residentTable || !residentForm || !residentModal) return; // safety

  let editResidentId = null;
  let residentsData = [];
  let filteredData = [];
  let currentSort = null;

  

  // ---------------- LOAD RESIDENTS ----------------
  async function loadResidents() {
    try {
      const res = await fetch("authController.php?action=adminGetResidents");
      residentsData = await res.json();
      filteredData = [...residentsData];
      renderTable(filteredData);
      attachRecordButtons(); // 🔥 call it here
    } catch (err) {
      console.error(err);
      alert("Failed to load residents");
    }
  }
  
function attachRecordButtons() {
  const recordNamesDiv = document.getElementById("recordNames");
  const recordItems = document.querySelectorAll(".record-item");

  if (!recordNamesDiv || recordItems.length === 0) {
    setTimeout(attachRecordButtons, 100); // try again after 100ms
    return;
  }

recordItems.forEach(item => {
  item.onclick = null;
  item.addEventListener("click", () => {
    const recordField = item.dataset.record;
    const filtered = residentsData.filter(r => r[recordField] === "Yes");

    // Highlight active button
    recordItems.forEach(b => b.classList.remove("active"));
    item.classList.add("active");

    // Render names
    if (filtered.length) {
      recordNamesDiv.innerHTML = `
        <div style="margin-bottom:5px; font-weight:600;">
          Total residents: ${filtered.length}
        </div>
        <ol>
          ${filtered.map(r => `<li>${r.name} ${r.middlename} ${r.lastname}</li>`).join("")}
        </ol>
      `;
    } else {
      recordNamesDiv.innerHTML = "<i>No residents with this record.</i>";
    }
  });
});

}



  // ---------------- RENDER TABLE ----------------
 // ---------------- RENDER TABLE ----------------
function renderTable(data) {
  const tbody = residentTable.querySelector("tbody");
  tbody.innerHTML = "";

  data.forEach(r => {
    const senior = Number(r.seniorcitizen) === 1 ? "Yes" : "No";
    const vaccinated = Number(r.vaccinated) === 1 ? "Yes" : "No";
    const voter = Number(r.voter) === 1 ? "Yes" : "No";

    // Use the validid path directly (already starts with /)
    const validIdPath = r.validid ?? "";

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${r.email ?? ""}</td>
      <td>********</td>
      <td>${r.name ?? ""}</td>
      <td>${r.middlename ?? ""}</td>
      <td>${r.lastname ?? ""}</td>
      <td>${r.phone ?? ""}</td>
      <td>${r.age ?? 0}</td>
      <td>${r.sex ?? ""}</td>
      <td>${r.birthday ?? ""}</td>
      <td>${r.address ?? ""}</td>
      <td>${r.status ?? ""}</td>
      <td>${r.pwd ?? "No"}</td>
      <td>${r.fourps ?? "No"}</td>
      <td>${senior}</td>
      <td>${r.schoollevels ?? ""}</td>
      <td>${r.schoolname ?? ""}</td>
      <td>${r.occupation ?? ""}</td>
      <td>${vaccinated}</td>
      <td>${voter}</td>
      <td>${r.validid ? `<img src="/${r.validid.replace(/^\/+/, '')}" width="50" />` : ""}</td>
      <td>
        <button class="editBtn" data-id="${r.id}">Edit</button>
        <button class="deleteBtn" data-id="${r.id}">Delete</button>
      </td>
    `;
      
    tbody.appendChild(tr);
  });

  // Attach edit/delete buttons
  tbody.querySelectorAll(".editBtn").forEach(btn =>
    btn.onclick = () => editResident(btn.dataset.id)
  );

  tbody.querySelectorAll(".deleteBtn").forEach(btn =>
    btn.onclick = () => deleteResident(btn.dataset.id)
  );
}

  

  // ---------------- SEARCH ----------------
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", e => {
      const q = e.target.value.toLowerCase();
      filteredData = residentsData.filter(r =>
        r.name.toLowerCase().includes(q) ||
        r.middlename.toLowerCase().includes(q) ||
        r.lastname.toLowerCase().includes(q)
      );
      currentSort ? sortResidents(currentSort) : renderTable(filteredData);
    });
  }

  // ---------------- SORT ----------------
  function sortResidents(order) {
    currentSort = order;
    filteredData.sort((a,b) => order === "old" ? a.id - b.id : b.id - a.id);
    renderTable(filteredData);
  }
  const sortOld = document.getElementById("sortOld");
  const sortNew = document.getElementById("sortNew");
  if (sortOld) sortOld.onclick = () => sortResidents("old");
  if (sortNew) sortNew.onclick = () => sortResidents("new");

  // ---------------- FILTER ----------------
  const openFilter = document.getElementById("openFilter");
  const closeFilter = document.getElementById("closeFilter");
  const applyFilter = document.getElementById("applyFilter");

  if (openFilter) openFilter.onclick = () => document.getElementById("filterModal").style.display = "block";
  if (closeFilter) closeFilter.onclick = () => document.getElementById("filterModal").style.display = "none";

  if (applyFilter) applyFilter.onclick = () => {
    const checkboxes = document.querySelectorAll(".filterCheckbox:checked");
    if (checkboxes.length === 0) filteredData = [...residentsData];
    else filteredData = residentsData.filter(r =>
      Array.from(checkboxes).every(cb => {
        switch(cb.value) {
          case "pwd": return r.pwd === "Yes";
          case "seniorcitizen": return r.seniorcitizen == 1;
          case "college": return r.schoollevels?.includes("College");
          case "voter": return r.voter == 1;
          case "fourps": return r.fourps === "Yes";
          case "occupation": return r.occupation && r.occupation.trim() !== "";
        }
      })
    );
    currentSort ? sortResidents(currentSort) : renderTable(filteredData);
    document.getElementById("filterModal").style.display = "none";
  };

async function editResident(id) {
  editResidentId = id;
  modalTitle.textContent = "Edit Resident";

  try {
    const res = await fetch("authController.php?action=adminGetResidents");
    const data = await res.json(); // ⬅️ parse JSON directly

    const resident = data.find(r => String(r.id) === String(id));
    if (!resident) throw new Error("Resident not found");

    // ---------------- TEXT ----------------
    document.getElementById("username").value = resident.email ?? "";
    document.getElementById("password").value = "";
    document.getElementById("fname").value = resident.name ?? "";
    document.getElementById("mname").value = resident.middlename ?? "";
    document.getElementById("lname").value = resident.lastname ?? "";
    document.getElementById("mPhone").value = resident.phone ?? "";
    document.getElementById("age").value = resident.age ?? 0;
    document.getElementById("sex").value = resident.sex ?? "";
    document.getElementById("birthday").value = resident.birthday ?? "";
    document.getElementById("address").value = resident.address ?? "";
    document.getElementById("status").value = resident.status ?? "";
      
    // ---------------- TEXT INPUTS ----------------
    document.getElementById("schoolName").value = resident.schoolname ?? "";
    document.getElementById("occupation").value = resident.occupation ?? "";

    // ---------------- SELECTS ----------------
    document.getElementById("pwd").value = resident.pwd ?? "No";
    document.getElementById("mFourPs").value = resident.fourps ?? "No";

    // ---------------- CHECKBOXES (FIXED) ----------------
    document.getElementById("seniorCitizen").checked = Number(resident.seniorcitizen) === 1;
    document.getElementById("vaccinated").checked   = Number(resident.vaccinated) === 1;
    document.getElementById("voter").checked        = Number(resident.voter) === 1;

    // ---------------- SCHOOL LEVELS ----------------
    const levels = (resident.schoollevels ?? "")
      .split(",")
      .map(v => v.trim())
      .filter(Boolean);

    document.querySelectorAll(".school").forEach(cb => {
      cb.checked = levels.includes(cb.value);
    });

    // ---------------- BLOTTERS ----------------
    document.getElementById("blotter1").checked = resident.blottertheft === "Yes";
    document.getElementById("blotter2").checked = resident.blotterdisturbance === "Yes";
    document.getElementById("blotter3").checked = resident.blotterother === "Yes";

    // ---------------- FILE PREVIEW ----------------
    const previewImg = document.getElementById("previewImg");
    if (resident.validid) {
      previewImg.src = resident.validid; // ✅ show image in modal
    } else {
      previewImg.src = ""; // no image
    }

    residentModal.style.display = "block";

  } catch(err) {
    console.error(err);
    alert(err.message);
  }
}



  async function deleteResident(id) {
    if (!confirm("Are you sure?")) return;
    try {
      const res = await fetch(`authController.php?action=adminDeleteResident&id=${id}`);
      const data = await res.json();
      alert(data.message);
      loadResidents();
    } catch(err) {
      console.error(err);
      alert("Failed to delete resident");
    }
  }

    
residentForm.addEventListener("submit", async e => {
  e.preventDefault();

  const formData = new FormData();

  // ---------------- TEXT INPUTS ----------------
  formData.set("username", document.getElementById("username").value.trim());
  if (document.getElementById("password").value.trim()) {
    formData.set("password", document.getElementById("password").value);
  }

  formData.set("fname", document.getElementById("fname").value.trim());
  formData.set("mname", document.getElementById("mname").value.trim());
  formData.set("lname", document.getElementById("lname").value.trim());
  formData.set("mPhone", document.getElementById("mPhone").value.trim());
  formData.set("age", document.getElementById("age").value || 0);
  formData.set("sex", document.getElementById("sex").value);
  formData.set("birthday", document.getElementById("birthday").value);
  formData.set("address", document.getElementById("address").value.trim());
  formData.set("status", document.getElementById("status").value);

  // ---------------- SELECTS ----------------
  formData.set("pwd", document.getElementById("pwd").value);
  formData.set("fourps", document.getElementById("mFourPs").value);

  // ---------------- CHECKBOXES (FORCE 1 / 0) ----------------
  formData.set("seniorcitizen", document.getElementById("seniorCitizen").checked ? "1" : "0");
  formData.set("vaccinated", document.getElementById("vaccinated").checked ? "1" : "0");
  formData.set("voter", document.getElementById("voter").checked ? "1" : "0");

    // ---------------- SCHOOL NAME & OCCUPATION ----------------
  formData.set("schoolname", document.getElementById("schoolName").value.trim());
  formData.set("occupation", document.getElementById("occupation").value.trim());
    
  // ---------------- SCHOOL LEVELS (RESET FIRST) ----------------
  formData.set("schoollevels", ""); // 🔥 IMPORTANT RESET
  document.querySelectorAll(".school:checked").forEach(cb => {
    formData.append("schoollevels[]", cb.value);
  });

  // ---------------- BLOTTERS ----------------
  formData.set("blotter1", document.getElementById("blotter1").checked ? "Yes" : "No");
  formData.set("blotter2", document.getElementById("blotter2").checked ? "Yes" : "No");
  formData.set("blotter3", document.getElementById("blotter3").checked ? "Yes" : "No");

  // ---------------- FILE UPLOAD ----------------
  const validIdInput = document.getElementById("validId");
  if (validIdInput.files.length > 0) {
    formData.set("validId", validIdInput.files[0]); // ✅ file included only if selected
  }

  // ---------------- EDIT ID ----------------
  if (editResidentId) {
    formData.set("id", editResidentId);
  }

  // ---------------- SEND ----------------
  try {
    const res = await fetch("authController.php?action=adminSaveResident", {
      method: "POST",
      body: formData
    });

    const data = await res.json();

    if (data.status === "success") {
      alert(data.message);
      residentModal.style.display = "none";
      editResidentId = null;
      loadResidents(); // 🔥 reload fresh DB values
    } else {
      throw new Error(data.message);
    }
  } catch (err) {
    console.error(err);
    alert(err.message);
  }
});



  // ---------------- FILE PREVIEW ----------------
  const validIdInput = document.getElementById("validId");
  if (validIdInput) validIdInput.addEventListener("change", function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById("previewImg").src = e.target.result;
    reader.readAsDataURL(file);
  });

  function updateFilterCounts() {
  const checkboxes = document.querySelectorAll(".filterCheckbox");
  checkboxes.forEach(cb => {
    let count = 0;
    switch(cb.value) {
      case "pwd":
        count = residentsData.filter(r => r.pwd === "Yes").length;
        break;
      case "seniorCitizen":
        count = residentsData.filter(r => r.seniorcitizen == 1).length;
        break;
      case "college":
        count = residentsData.filter(r => r.schoollevels?.includes("College")).length;
        break;
      case "voter":
        count = residentsData.filter(r => r.voter == 1).length;
        break;
      case "fourPs":
        count = residentsData.filter(r => r.fourps === "Yes").length;
        break;
      case "occupation":
        count = residentsData.filter(r => r.occupation && r.occupation.trim() !== "").length;
        break;
    }
    cb.parentElement.querySelector(".filter-count").textContent = `(${count})`;
  });
}

if (openFilter) openFilter.onclick = () => {
  document.getElementById("filterModal").style.display = "block";
  updateFilterCounts(); // 🔥 update counts every time modal opens
};



  // ---------------- TAB SYSTEM (FIXED) ----------------
const tabButtons = residentModal.querySelectorAll(".tabBtn");
const tabContents = residentModal.querySelectorAll(".tab-content");

tabButtons.forEach(btn => {
  btn.addEventListener("click", () => {
    const target = btn.dataset.tab;

    tabContents.forEach(tc => tc.style.display = "none");
    tabButtons.forEach(b => b.classList.remove("active"));

    document.getElementById(target).style.display = "block";
    btn.classList.add("active");
  });
});



  // ---------------- INIT ----------------
  loadResidents();
}
