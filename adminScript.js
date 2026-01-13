
// LOGIN LOGIC
if (document.getElementById("loginForm")) {
    document.getElementById("loginForm").addEventListener("submit", e => {
        e.preventDefault();

        fetch("api.php?action=adminLogin", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                username: username.value,
                password: password.value
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                window.location.href = "adminDashboard.php";
            } else {
                alert("Wrong credentials");
            }
        });
    });
}

// LOGOUT LOGIC
if (document.getElementById("logoutBtn")) {
    document.getElementById("logoutBtn").addEventListener("click", () => {
        fetch("api.php?action=logout")
            .then(() => window.location.href = "adminLogin.html");
    });
}



// ------------------ DATE FOOTER ------------------
const dateElement = document.getElementById("date");
function updateDate() {
  const now = new Date();
  const options = { weekday: 'long', year: 'numeric', month: '2-digit', day: '2-digit' };
  dateElement.textContent = "Today is " + now.toLocaleDateString('en-CA', options) + " | Developed by: @SylvanDR2";
}
updateDate();
setInterval(updateDate, 1000); // live clock

// ------------------ DASHBOARD FUNCTIONS ------------------
async function updateDashboardCounts() {
  try {
    const res = await fetch("authController.php?action=getAllResi");
    const residents = await res.json();

    const pending = residents.filter(
      r => r.accountstatus === "pending"
    ).length;

    const approved = residents.filter(
      r => r.accountstatus === "approved"
    ).length;

    const rejected = residents.filter(
      r => r.accountstatus === "rejected"
    ).length;

    document.getElementById("pendingCount").textContent = pending;
    document.getElementById("approvedCount").textContent = approved;
    document.getElementById("rejectedCount").textContent = rejected;

  } catch (err) {
    console.error("Error fetching dashboard data:", err);
  }
}


// ------------------ Online Request Count ------------------
async function updatePendingClearance() {
  try {
    const res = await fetch("authController.php?action=getPendingClearanceCount");
    const data = await res.json();

    console.log("Pending clearances:", data);

    const el = document.getElementById("PendingCountClearance");
    if (el) el.textContent = data.pendingClearance ?? 0;

  } catch (err) {
    console.error("Error fetching pending clearance count:", err);
  }
}


// Run on page load and every 5 seconds

// ✅ Run on page load
document.addEventListener("DOMContentLoaded", () => {
    updateDashboardCounts();
    updatePendingClearance();
    setInterval(updateDashboardCounts, 5000);       // update resident counts
    setInterval(updatePendingClearance, 5000);      // update pending clearance
});



// ------------------ CHART.JS ------------------
let residentsChartInstance = null;

function loadDashboardChart() {
  const ctx = document.getElementById("residentsChart");
  if (!ctx) return;

  async function getCounts() {
    try {
      const res = await fetch("authController.php?action=adminGetResidents");
      const residents = await res.json();

      return {
    "College Undergraduate": residents.filter(r =>
  typeof r.schoollevels === "string" &&
  r.schoollevels.toLowerCase().includes("college undergraduate") // lowercase match
).length,


        "Senior Citizen": residents.filter(r =>
          Number(r.seniorcitizen) === 1
        ).length,

        "4Ps": residents.filter(r =>
          (r.fourps || "").toLowerCase() === "yes"
        ).length,

        Voters: residents.filter(r =>
          Number(r.voter) === 1
        ).length,

        PWD: residents.filter(r =>
          (r.pwd || "").toLowerCase() === "yes"
        ).length,
      };

    } catch (err) {
      console.error("Error fetching residents:", err);
      return {
        "College Undergraduate": 0,
        "Senior Citizen": 0,
        "4Ps": 0,
        Voters: 0,
        PWD: 0
      };
    }
  }


  async function renderChart() {
    const counts = await getCounts();

    if (residentsChartInstance) {
      residentsChartInstance.data.labels = Object.keys(counts);
      residentsChartInstance.data.datasets[0].data = Object.values(counts);
      residentsChartInstance.update();
    } else {
      residentsChartInstance = new Chart(ctx, {
        type: "pie",
        data: {
          labels: Object.keys(counts),
          datasets: [{
            data: Object.values(counts),
            backgroundColor: ["#5aa9f8", "#4caf50", "#f8d15c", "#f76c6c", "#a569bd"],
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: "bottom" } }
        }
      });
    }
  }

  if (!window.Chart) {
    const script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/chart.js";
    script.onload = renderChart;
    document.body.appendChild(script);
  } else {
    renderChart();
  }

  // live update every 2 seconds
  setInterval(renderChart, 2000);
}



// ------------------ SIDEBAR ------------------
function setActiveSidebar(activeBtn) {
  const sidebarItems = document.querySelectorAll(".sidebar ul li");
  const indicator = document.querySelector(".active-indicator");

  sidebarItems.forEach(btn => btn.classList.remove("active"));
  if (activeBtn) activeBtn.classList.add("active");

  if (activeBtn && indicator) {
    indicator.style.top = activeBtn.offsetTop + "px";
    indicator.style.height = activeBtn.offsetHeight + "px";
  }
}

// ------------------ LOAD DASHBOARD ------------------
let dashboardInterval = null;
function loadDashboard() {
  fetch("dashboard.html")
    .then(res => res.text())
    .then(html => {
      const mainContent = document.getElementById("mainContent");
      mainContent.innerHTML = html;

      loadDashboardChart();

      // ✅ Update counts AFTER dashboard elements exist
      updateDashboardCounts();
      if (dashboardInterval) clearInterval(dashboardInterval);
      dashboardInterval = setInterval(updateDashboardCounts, 2000);

      const btnDashboard = document.getElementById("btnDashboard");
      setActiveSidebar(btnDashboard);
    })
    .catch(err => console.error("Error loading dashboard:", err));
}

// ===============================
// GLOBAL FUNCTIONS
// ===============================
window.saveOfficial = saveOfficial;
window.editOfficial = editOfficial;
window.clearOfficialForm = clearOfficialForm; // ito din ata
window.printOfficials = printOfficials;
window.deleteOfficial = deleteOfficial;
window.renderOfficials = renderOfficials;

// ===============================
// LOAD OFFICIALS PAGE
// ===============================
function loadOfficials() {
  const mainContent = document.getElementById("mainContent");
  if (!mainContent) return;

  fetch("officials-content.html")
    .then(res => res.text())
    .then(html => {
      mainContent.innerHTML = `<h2>Barangay Officials</h2>` + html;

      // Highlight sidebar
      const btnOfficials = document.getElementById("btnOfficials");
      if (btnOfficials && typeof setActiveSidebar === "function") {
        setActiveSidebar(btnOfficials);
      }

      // Bind buttons AFTER content loaded
      const saveBtn = document.getElementById("saveOfficialBtn");
      if (saveBtn) saveBtn.onclick = saveOfficial;

      const printBtn = document.querySelector("button[onclick='printOfficials()']");
      if (printBtn) printBtn.onclick = printOfficials;

      // Render officials list
      renderOfficials();
    })
    .catch(err => console.error("Error loading officials page:", err));
}

// ===============================
// STORAGE
// ===============================
function getOfficials() {
  return JSON.parse(localStorage.getItem("barangayOfficials_v1") || "[]");
}

function setOfficials(data) {
  localStorage.setItem("barangayOfficials_v1", JSON.stringify(data));
}

// ===============================
// SAVE / UPDATE
// ===============================
function saveOfficial() {
  const id = document.getElementById("officialId").value;
  const name = document.getElementById("officialName").value.trim();
  const role = document.getElementById("officialRole").value;
  const photoInput = document.getElementById("officialPhoto");

  if (!name || !role) {
    alert("Please complete all fields");
    return;
  }

  const reader = new FileReader();
  reader.onload = function () {
    const officials = getOfficials();
    const data = { name, role, photo: reader.result || null };

    if (id === "") {
      officials.push(data);
    } else {
      officials[id] = data;
    }

    setOfficials(officials);
    clearOfficialForm();
    renderOfficials();
  };

  if (photoInput.files[0]) {
    reader.readAsDataURL(photoInput.files[0]);
  } else {
    reader.onload(); // save without new photo
  }
}

// ===============================
// RENDER
// ===============================
function renderOfficials() {
  const list = document.getElementById("officialsList");
  if (!list) return;

  const officials = getOfficials();
  list.innerHTML = "";

  if (officials.length === 0) {
    list.innerHTML = "<p>No officials added yet.</p>";
    return;
  }

officials.forEach((o, i) => {
  const card = document.createElement("div");
  card.className = "official-card";

  // ✅ Robust image handling
  let imagePath = o.photo ? o.photo : 'default-avatar.png';

  card.innerHTML = `
    <img src="${imagePath}" alt="${o.name}" onerror="this.src='default-avatar.png'">
    <h4>${o.name}</h4>
    <span class="role-tag">${o.role}</span>
    <br>
    <div class="actions">
      <button class="primary" onclick="editOfficial(${i})">Edit</button>
      <button class="danger" onclick="deleteOfficial(${i})">Delete</button>
    </div>
  `;

  list.appendChild(card);
  });
}

function deleteOfficial(index) {
  if (!confirm("Are you sure you want to delete this official?")) return;

  const officials = getOfficials();
  if (!officials[index]) return;

  officials.splice(index, 1); // ❌ remove item
  setOfficials(officials);    // 💾 save changes
  clearOfficialForm();
  renderOfficials();
}

// ===============================
// EDIT
// ===============================
function editOfficial(index) {
  const o = getOfficials()[index];
  if (!o) return;

  document.getElementById("officialId").value = index;
  document.getElementById("officialName").value = o.name;
  document.getElementById("officialRole").value = o.role;
}

// ===============================
// CLEAR FORM // delete ito
// ===============================
function clearOfficialForm() {
  document.getElementById("officialId").value = "";
  document.getElementById("officialName").value = "";
  document.getElementById("officialRole").value = "";
  document.getElementById("officialPhoto").value = "";
}

// ===============================
// PRINT
// ===============================
function printOfficials() {
  window.print();
}



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

  if (checkboxes.length === 0) {
    filteredData = [...residentsData];
} else {
  filteredData = residentsData.filter(r =>
    Array.from(checkboxes).every(cb => {
      const val = cb.value.trim().toLowerCase(); // lowercase the checkbox value for consistent comparison
      switch(val) {
        case "pwd": 
          return (r.pwd || "").toLowerCase() === "yes";
        case "seniorcitizen": 
          return Number(r.seniorcitizen) === 1;
        case "college undergraduate": // lowercase to match val
          return typeof r.schoollevels === "string" &&
                 r.schoollevels.toLowerCase().includes("college undergraduate");
        case "voter": 
          return Number(r.voter) === 1;
        case "fourps": 
          return (r.fourps || "").toLowerCase() === "yes";
        case "occupation": 
          return r.occupation && r.occupation.trim() !== "";
        default:
          return true; // unknown filters won't block
      }
    })
  );
}

  if (currentSort) sortResidents(currentSort);
  else renderTable(filteredData);

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
    /*const previewImg = document.getElementById("previewImg");
    if (resident.validid) {
      previewImg.src = resident.validid; 
    } else {
      previewImg.src = "";
    }*/
      const previewImg = document.getElementById("previewImg");

// Show existing ID when opening modal
if (resident.validid) {
  previewImg.src = resident.validid; // existing image
} else {
  previewImg.src = ""; // no image
}

// Preview selected file immediately
document.getElementById("validId").addEventListener("change", e => {
    if (e.target.files && e.target.files[0]) {
        previewImg.src = URL.createObjectURL(e.target.files[0]);
    } else {
        previewImg.src = "";
    }
});


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
   formData.set("validid", validIdInput.files[0]);
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
      case "College Undergraduate":
        count = residentsData.filter(r => r.schoollevels?.includes("College Undergraduate")).length;
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





// ===============================
// LOAD ANNOUNCEMENTS PAGE
// ===============================
function loadAnnouncementsPage() {
  const mainContent = document.getElementById("mainContent");
  if (!mainContent) return;

  fetch("announcement.html")
    .then(res => res.text())
    .then(html => {
      mainContent.innerHTML = html;

      // Highlight sidebar
      const btnAnnouncements = document.getElementById("btnAnnouncements");
      if (btnAnnouncements && typeof setActiveSidebar === "function") {
        setActiveSidebar(btnAnnouncements);
      }

      // ===============================
      // INLINE ANNOUNCEMENT SCRIPT
      // ===============================
      const residentList = document.getElementById("residentList");
      const searchResident = document.getElementById("searchResident");
      const announcementMessage = document.getElementById("announcementMessage");
      const sentMessages = document.getElementById("sentMessages");

      let residents = [];

      // LOAD RESIDENTS
      async function loadResidents() {
        const res = await fetch("authController.php?action=getResidents");
        residents = await res.json();
        renderResidents();
      }

      // RENDER RESIDENT LIST
      function renderResidents() {
        const key = searchResident.value.toLowerCase();
        residentList.innerHTML = residents
          .filter(r =>
            (r.name + " " + r.lastname).toLowerCase().includes(key)
          )
          .map(r => `
            <label style="display:block; margin-bottom:5px;">
              <input type="checkbox" value="${r.email}">
              ${r.name} ${r.lastname}
            </label>
          `).join("");
      }

      // SEND ANNOUNCEMENT
      async function sendAnnouncement(recipients) {
        const msg = announcementMessage.value.trim();
        if (!msg) return alert("Message empty");

        const fd = new FormData();
        fd.append("message", msg);
        fd.append("recipients", JSON.stringify(recipients));

        const res = await fetch("authController.php?action=sendAnnouncement", {
          method: "POST",
          body: fd
        });

        const data = await res.json();
        alert(data.message);

        announcementMessage.value = "";
        loadSent();
      }

      // BUTTONS
      document.getElementById("sendBtn").onclick = () => {
        const selected = [...residentList.querySelectorAll("input:checked")]
          .map(i => i.value);

        if (!selected.length) return alert("Select resident");
        sendAnnouncement(selected);
      };

      document.getElementById("sendAllBtn").onclick = () =>
        sendAnnouncement(["all"]);


      // LOAD SENT ANNOUNCEMENTS
async function loadSent() {
  const res = await fetch("authController.php?action=getAnnouncements");
  const data = await res.json();

  // Get all currently checked IDs
  const checkedIds = [...document.querySelectorAll(".msgCheck:checked")].map(cb => cb.value);

  // Render all announcements for admin
  sentMessages.innerHTML = data.map(a => `
    <li style="
        border-bottom:1px solid #ddd;
        padding:10px;
        display:flex;
        align-items:flex-start;
        gap:10px;
    ">
      <input
        type="checkbox"
        class="msgCheck"
        value="${a.id}"
        style="transform:scale(1.5); cursor:pointer;"
        ${checkedIds.includes(String(a.id)) ? "checked" : ""}
      >
      <div>
        <b>${a.recipient}</b> — 
        ${a.message}<br>
        <small>${new Date(a.date_sent).toLocaleString()}</small>
      </div>
    </li>
  `).join("");
}


document.getElementById("deleteSelectedBtn").onclick = async () => {
  const ids = [...document.querySelectorAll(".msgCheck:checked")]
    .map(cb => cb.value);

  if (!ids.length) return alert("No announcements selected");

  const fd = new FormData();
  fd.append("ids", JSON.stringify(ids));

  const res = await fetch(
    "authController.php?action=deleteAnnouncements",
    { method: "POST", body: fd }
  );

  const data = await res.json();
  alert(data.message);
  loadSent();
};


      searchResident.addEventListener("input", renderResidents);

      // INIT
      loadResidents();
      loadSent();
      setInterval(loadSent, 5000);
    })
    .catch(err => console.error("Error loading announcements page:", err));
}






// ===============================
// LOAD CERTIFICATES PAGE
// ===============================
function loadCertificatesPage() {
  const mainContent = document.getElementById("mainContent");
  if (!mainContent) return;

  fetch("Certification-content.html")
    .then(res => res.text())
    .then(html => {
      mainContent.innerHTML = html;

      // Highlight sidebar
      const btnCertification = document.getElementById("btnCertification");
      if (btnCertification && typeof setActiveSidebar === "function") {
        setActiveSidebar(btnCertification);
      }

      // ===============================
      // INLINE CERTIFICATES SCRIPT
      // ===============================

      // ----- CERTIFICATE BUTTONS -----
      initCertificationPage(); // call the restored function

      // ----- CERTIFICATE FEES -----
      const editFeesBtn = document.getElementById("editFeesBtn");
      const saveFeesBtn = document.getElementById("saveFeesBtn");

      const feeInputs = {
        clearance: document.getElementById("feeClearance"),
        residency: document.getElementById("feeResidency"),
        indigency: document.getElementById("feeIndigency"),
        business: document.getElementById("feeBusiness")
      };

      // Enable editing
      editFeesBtn.addEventListener("click", () => {
        Object.values(feeInputs).forEach(input => input.disabled = false);
        editFeesBtn.hidden = true;
        saveFeesBtn.hidden = false;
      });

      // Save fees to DB
      saveFeesBtn.addEventListener("click", async () => {
        Object.values(feeInputs).forEach(input => input.disabled = true);
        editFeesBtn.hidden = false;
        saveFeesBtn.hidden = true;

        const fees = {
          clearance: feeInputs.clearance.value,
          residency: feeInputs.residency.value,
          indigency: feeInputs.indigency.value,
          business: feeInputs.business.value
        };

        try {
          const res = await fetch("authController.php?action=updateCertificateFees", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({fees})
          });
          const data = await res.json();
          alert(data.message || "Fees saved!");
        } catch(e) {
          console.error("Failed to save fees:", e);
          alert("Failed to save fees");
        }
      });

  // Load fees from DB
async function loadFees() {
  try {
    const res = await fetch("authController.php?action=getCertificateFees");
    const data = await res.json();

    feeInputs.clearance.value = data.clearance ?? 0;
    feeInputs.residency.value = data.residency ?? 0;
    feeInputs.indigency.value = data.indigency ?? 0;
    feeInputs.business.value = data.business ?? 0;
  } catch(e) {
    console.error("Failed to load fees:", e);
    // fallback values
    feeInputs.clearance.value = 50;
    feeInputs.residency.value = 30;
    feeInputs.indigency.value = 20;
    feeInputs.business.value = 100;
  }
}

loadFees(); // call on load

    })
    .catch(err => console.error("Error loading certificates page:", err));
}

// ------------------ CERTIFICATION BUTTONS ------------------
function initCertificationPage() {
  const mainContent = document.getElementById("mainContent");
  if (!mainContent) return;

  const buttons = mainContent.querySelectorAll("[data-cert]");
  
  buttons.forEach(btn => {
    btn.addEventListener("click", () => {
      switch (btn.dataset.cert) {
        case "clearance":
          window.location.href = "printClearance.html"; // Go to new page
          break;
        case "residency":
          window.location.href = "printResidency.html";
          break;
        case "indigency":
          window.location.href = "printIndigency.html";
          break;
        case "business":
          window.location.href = "printBusiness.html";
          break;
      }
    });
  });
}


// ===============================
// BIND SIDEBAR BUTTONS
// ===============================

// ------------------ TABLE MANAGER BUTTON ------------------

function loadTableManager() {
  const mainContent = document.getElementById("mainContent");
  if (!mainContent) return;

  fetch("tableManager-content.html")
    .then(res => res.text())
    .then(html => {
      mainContent.innerHTML = html;

      initTableManager();

      if (btnTableManager && typeof setActiveSidebar === "function") {
        setActiveSidebar(btnTableManager);
      }
    })
    .catch(err => console.error("Error loading table manager:", err));
}


function initTableManager() {
  const btnGenerate = document.getElementById("btnGenerateTable");
  const btnPrint = document.getElementById("btnPrintTable");
  const btnAddRow = document.getElementById("btnAddRow");
  const filterInput = document.getElementById("filterInput");
  const table = document.getElementById("dynamicTable");
  const btnAddColumn = document.getElementById("btnAddColumn");
  const btnToggleDelete = document.getElementById("btnToggleDelete");
  const btnSetTitle = document.getElementById("btnSetTitle");
  const btnUndo = document.getElementById("btnUndo");
  const btnRedo = document.getElementById("btnRedo");
  const btnSaveTable = document.getElementById("btnSaveTable");
    const filter30Plus = document.getElementById("filter30Plus");

  let tableData = [];
  let customColumns = [];
  let deleteMode = false;

  let undoStack = [];
  let redoStack = [];

  // ===============================
  // FETCH FROM DB (READ-ONLY)
  // ===============================
  async function getResidents() {
    const res = await fetch("authController.php?action=adminGetResidents");
    return await res.json();
  }

  function getSelectedColumns() {
    return [...document.querySelectorAll(".column-option:checked")].map(cb => cb.value);
  }

 document.querySelectorAll(".column-option").forEach(cb => {
  if (["name", "lastname", "schoolname"].includes(cb.value)) {
    cb.checked = true;
  }
});

  // ===============================
  // SAVE STATE FOR UNDO
  // ===============================
function saveState() {
  // Save both tableData and customColumns
  undoStack.push(JSON.stringify({
    tableData: tableData,
    customColumns: customColumns
  }));
  redoStack = [];
}


function undo() {
  if (!undoStack.length) return alert("Walang ma-undo!");

  // Save current state to redo stack
  redoStack.push(JSON.stringify({
    tableData: tableData,
    customColumns: customColumns
  }));

  const prev = JSON.parse(undoStack.pop());
  tableData = prev.tableData;
  customColumns = prev.customColumns;
  renderTable();
}
function redo() {
  if (!redoStack.length) return alert("Walang ma-redo!");

  // Save current state to undo stack
  undoStack.push(JSON.stringify({
    tableData: tableData,
    customColumns: customColumns
  }));

  const next = JSON.parse(redoStack.pop());
  tableData = next.tableData;
  customColumns = next.customColumns;
  renderTable();
}
  btnUndo.onclick = undo;
  btnRedo.onclick = redo;

  // ===============================
function renderTable(data = tableData) {
  const cols = [...getSelectedColumns(), ...customColumns];
  const thead = table.querySelector("thead");
  const tbody = table.querySelector("tbody");

  thead.innerHTML = "";
  tbody.innerHTML = "";

  // ===== 30+ AGE filter =====
  const filter30PlusChecked = document.getElementById("filter30Plus")?.checked;
  if (filter30PlusChecked) {
    data = data.filter(r => parseInt(r.age) >= 30);
  }

  // ===== HEADER =====
  const trHead = document.createElement("tr");
  trHead.innerHTML = "<th>No.</th>";

  cols.forEach((c) => {
    const th = document.createElement("th");
    th.textContent = c.charAt(0).toUpperCase() + c.slice(1);

    // Custom column delete button in deleteMode
    if (deleteMode && customColumns.includes(c)) {
      const btnDelCol = document.createElement("button");
      btnDelCol.textContent = "Delete";
      btnDelCol.style.marginLeft = "5px";
      btnDelCol.style.background = "red";
      btnDelCol.style.color = "white";
      btnDelCol.style.border = "none";
      btnDelCol.style.cursor = "pointer";

      btnDelCol.onclick = () => {
        if (!confirm(`Delete column "${c}"?`)) return;

        saveState();
        const colIndexInCustom = customColumns.indexOf(c);
        if (colIndexInCustom > -1) customColumns.splice(colIndexInCustom, 1);
        tableData.forEach(r => delete r[c]);

        renderTable();
      };

      th.appendChild(btnDelCol);
    }

    trHead.appendChild(th);
  });

  // Row delete header
  if (deleteMode) {
    const thDel = document.createElement("th");
    thDel.textContent = "Action";
    trHead.appendChild(thDel);
  }

  thead.appendChild(trHead);

  // ===== BODY =====
  data.forEach((row, rowIndex) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `<td>${rowIndex + 1}</td>`; // dynamic row number

    cols.forEach(c => {
      const td = document.createElement("td");

      if (c === "schoollevels") {
        // display actual schoollevels text
        td.textContent = row.schoollevels || "";
        td.contentEditable = false;

      } else if ([
        "College Graduate",
        "College Undergraduate",
        "High School Graduate",
        "High School Undergraduate",
        "Elementary Graduate",
        "Elementary Undergraduate",
        "None"
      ].includes(c)) {
        // Yes/No cells for school levels
        const levels = (row.schoollevels || "")
          .split(",")
          .map(s => s.trim().toLowerCase());

        td.textContent = levels.includes(c.toLowerCase()) ? "Yes" : "No";
        td.dataset.level = c.toLowerCase(); // used for radio filter
        td.contentEditable = false;

      } else if (["voter", "seniorcitizen"].includes(c)) {
        td.textContent = row[c] === 1 || row[c] === "1" ? "Yes" : "No";
        td.contentEditable = false;

      } else if (c === "schoolname") {
        td.textContent = row.schoolname || "";
        td.contentEditable = false;

      } else if (c === "fourps") {
        td.textContent = row.fourps === 1 || row.fourps === "1" || row.fourps === "Yes" ? "Yes" : "No";
        td.contentEditable = false;

      } else {
        td.textContent = row[c] ?? "";
      }

      tr.appendChild(td);
    });

    // Row delete button
    if (deleteMode) {
      const tdDel = document.createElement("td");
      const btnDel = document.createElement("button");
      btnDel.textContent = "Delete";
      btnDel.style.background = "red";
      btnDel.style.color = "white";
      btnDel.style.padding = "2px 5px";
      btnDel.style.border = "none";
      btnDel.style.cursor = "pointer";

      btnDel.onclick = () => {
        if (!confirm("Are you sure you want to delete this row?")) return;
        saveState();
        tableData.splice(rowIndex, 1);
        renderTable();
      };

      tdDel.appendChild(btnDel);
      tr.appendChild(tdDel);
    }

    tbody.appendChild(tr);
  });

  // ===== RESULT COUNT =====
  const yesCountEl = document.getElementById("yesCount");
  if (yesCountEl) yesCountEl.textContent = `Showing: ${data.length} result(s)`; 
}

// ===== SCHOOL LEVEL RADIO FILTER =====
document.querySelectorAll('input[name="schoolFilter"]').forEach(radio => {
  radio.addEventListener("change", () => {

    // If no radio selected, show all rows
    const selectedRadio = document.querySelector('input[name="schoolFilter"]:checked');
    if (!selectedRadio) {
      document.querySelectorAll("tbody tr").forEach(tr => tr.style.display = "");
      return;
    }

    const value = selectedRadio.value.toLowerCase();

    document.querySelectorAll("tbody tr").forEach(tr => {
      const cells = tr.querySelectorAll("td");
      let show = false;

      cells.forEach(td => {
        if (td.textContent.toLowerCase() === "yes" && td.dataset.level === value) {
          show = true;
        }
      });

      tr.style.display = show ? "" : "none";
    });
  });
});


  // ===============================
  // SORT
  // ===============================
  function sortTableByColumn(col) {
    saveState();
    tableData.sort((a, b) =>
      (a[col] ?? "").toString().localeCompare((b[col] ?? "").toString())
    );
    renderTable();
  }

  // ===============================
  // FILTER
  // ===============================
  filterInput.addEventListener("input", () => {
    const v = filterInput.value.toLowerCase();
    renderTable(
      tableData.filter(r =>
        Object.values(r).some(val =>
          String(val).toLowerCase().includes(v)
        )
      )
    );
  });

  // ===============================
  // BUTTONS
  // ===============================
  btnGenerate.onclick = async () => {
    tableData = await getResidents();
    renderTable();
  };

  btnAddRow.onclick = () => {
    saveState();
    const row = {};
    [...getSelectedColumns(), ...customColumns].forEach(c => (row[c] = ""));
    tableData.push(row);
    renderTable();
  };

  btnAddColumn.onclick = () => {
    const name = prompt("Column name:");
    if (!name || customColumns.includes(name)) return;
    saveState();
    customColumns.push(name);
    tableData.forEach(r => (r[name] = ""));
    renderTable();
  };

  btnToggleDelete.onclick = () => {
    deleteMode = !deleteMode;
    renderTable();
  };

btnSetTitle.onclick = () => {
  const t = prompt("Enter table title:", window.customTitle);
  if (t) {
    window.customTitle = t;  // <-- store in global variable
    alert("Table title set to: " + window.customTitle);
  }
};


  // ===============================
  // AUTO LOAD
  // ===============================
  (async () => {
    tableData = await getResidents();
    renderTable();
  })();
}


document.addEventListener("click", function (e) {
  const btn = e.target.closest("li");
  if (!btn) return;

  if (btn.id === "btnDashboard") {
    loadDashboard();
    setActiveSidebar(btn);
  }

  if (btn.id === "btnOfficials") {
    loadOfficials();
    setActiveSidebar(btn);
  }

  if (btn.id === "btnResidents") {
    loadResidentsPage();
    setActiveSidebar(btn);
  }

  if (btn.id === "btnAnnouncements") {
    loadAnnouncementsPage();
    setActiveSidebar(btn);
  }

  if (btn.id === "btnCertification") {
    loadCertificatesPage();
    setActiveSidebar(btn);
  }

  if (btn.id === "btnTableManager") {
    loadTableManager();      // ✅ ITO ANG HINAHANAP MO
    setActiveSidebar(btn);   // ✅ ACTIVE SIDEBAR
  }
});
// Global table title
window.customTitle = "Resident Table";


// ------------------ PASTE HERE ------------------
// ---------------- PRINT BUTTON (Full layout, works for dynamic table) ----------------
document.addEventListener("click", function(e) {
  if (e.target.id === "btnPrintTable") {
    const table = document.getElementById("dynamicTable");
    if (!table) return alert("Table not found!");

    const today = new Date().toLocaleDateString();
   const customTitle = window.customTitle || "Resident Table";


    const styles = `
      <style>
        @page { size: legal portrait; margin: 25mm 20mm; }
        body { font-family: "Times New Roman", serif; color: #000; margin: 0; position: relative; }

        /* ===== WATERMARK ===== */
        .watermark {
          position: fixed;
          top: 50%;
          left: 50%;
          width: 420px;
          opacity: 0.08;
          transform: translate(-50%, -50%);
          z-index: -1;
        }

        /* ===== HEADER ===== */
        .print-header {
          display: grid;
          grid-template-columns: 120px 1fr 120px;
          align-items: center;
          margin-bottom: 15px;
        }
        .print-header img { width: 120px; }
        .print-center { text-align: center; }
        .print-center p { margin: 2px 0; font-size: 14px; }
        .print-center h2 { margin-top: 8px; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
        hr { border: none; border-top: 2px solid black; margin: 10px 0 15px; }

        /* ===== TABLE ===== */
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid black; padding: 6px; font-size: 12px; text-align: left; }
        th { font-weight: bold; }

        /* ===== FOOTER ===== */
        .footer {
          display: flex;
          justify-content: space-between;
          margin-top: 35px;
          font-size: 12px;
        }
        .signature-line {
          margin-top: 45px;
          border-top: 1px solid black;
          padding-top: 5px;
          text-align: right;
          width: 200px;
        }
      </style>
    `;

    const win = window.open("", "_blank");
    if (!win) return alert("Popup blocked. Allow popups.");

    win.document.write(`
      <html>
        <head>
          <title>${customTitle}</title>
          ${styles}
        </head>
        <body>

          <!-- WATERMARK -->
          <img src="logo.png" class="watermark">

          <!-- HEADER -->
          <div class="print-header">
            <img src="logo.png">
            <div class="print-center">
              <p>Province of Sorsogon</p>
              <p>Municipality of Sta. Magdalena</p>
              <p>Barangay Peñafrancia</p>
              <h2>${customTitle}</h2>
            </div>
            <img src="Bagong-Pilipinas-Logo.png">
          </div>

          <!-- TABLE -->
          ${table.outerHTML}

          <!-- FOOTER -->
          <div class="footer">
            <div class="signature-line">Prepared by</div>
            <div class="signature-line">Date: ${today}</div>
          </div>

        </body>
      </html>
    `);

    win.document.close();

    // Wait for images to load before printing
    const imgs = win.document.images;
    let loaded = 0;

    if (imgs.length === 0) {
      win.print();
      return;
    }

    [...imgs].forEach(img => {
      if (img.complete) {
        loaded++;
      } else {
        img.onload = img.onerror = () => {
          loaded++;
          if (loaded === imgs.length) win.print();
        };
      }
    });

    // Fallback: if all images already loaded
    if (loaded === imgs.length) win.print();
  }
});









document.addEventListener("DOMContentLoaded", () => {
  const btnDashboard     = document.getElementById("btnDashboard");
  const btnOfficials     = document.getElementById("btnOfficials");
  const btnResidents     = document.getElementById("btnResidents");
  const btnAnnouncements = document.getElementById("btnAnnouncements");
  const btnCertification = document.getElementById("btnCertification");
  const btnTableManager  = document.getElementById("btnTableManager"); // ✅ FIX

  if (btnDashboard)     btnDashboard.onclick = loadDashboard;
  if (btnOfficials)     btnOfficials.onclick = loadOfficials;
  if (btnResidents)     btnResidents.onclick = loadResidentsPage;
  if (btnAnnouncements) btnAnnouncements.onclick = loadAnnouncementsPage;
  if (btnCertification) btnCertification.onclick = loadCertificatesPage;
  if (btnTableManager)  btnTableManager.onclick = loadTableManager; // ✅ FIX

  // Default page
  loadDashboard();
});
























