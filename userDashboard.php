<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">         
<title>Barangay Peñafrancia Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>


/* ===============================
   3D DRAWER BUTTON STYLE
================================ */

/* Base 3D button look */
button,
.card button {
  position: relative;
  background: linear-gradient(145deg, #6ea0fc, #0c31c5);
  box-shadow:
    0 6px 0 #0031cc,          /* bottom depth */
    0 12px 20px rgba(0,0,0,0.25); /* outer shadow */
  transform: translateY(0);
  transition: all 0.2s ease;
}

/* Drawer depth (side illusion) */
button::after,
.card button::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -6px;
  width: 100%;
  background: linear-gradient(145deg, #0031cc, #001f80);

}

/* Hover = lifted drawer */
button:hover,
.card button:hover {
  transform: translateY(-3px);
  box-shadow:
    0 8px 0 #0031cc,
    0 18px 30px rgba(0,0,0,0.35);
}

/* Active = pressed drawer */
button:active,
.card button:active {
  transform: translateY(4px);
  box-shadow:
    0 2px 0 #0031cc,
    0 6px 12px rgba(0,0,0,0.35);
}

/* ===============================
   CARD AS 3D DRAWER
================================ */
.card {
  position: relative;
  box-shadow:
    0 10px 0 rgba(0,0,0,0.12),
    0 20px 35px rgba(0,0,0,0.25);
}

/* Card bottom depth */
.card::after {
  content: "";
  position: absolute;
  left: 8px;
  right: 8px;
  bottom: -10px;
  height: 10px;
  background: rgba(0,0,0,0.18);
  filter: blur(6px);
  border-radius: 50%;
  z-index: -1;
}

/* ===============================
   INPUTS 3D SOFT DEPTH
================================ */
.form-group input,
.form-group select,
.form-group textarea {
  box-shadow:
    inset 0 2px 4px rgba(0,0,0,0.12),
    0 3px 6px rgba(0,0,0,0.18);
}
.form-group textarea {
  min-height: 140px;
  resize: vertical;
}

/* Focus pop-up effect */
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  transform: translateY(-2px);
}

/* ===============================
   FLOATING ANNOUNCEMENT 3D
================================ */
#floatingAnnouncement {
  box-shadow:
    0 6px 0 #0031cc,
    0 16px 30px rgba(0,0,0,0.35);
}

#floatingAnnouncement:active {
  transform: translateY(4px);
  box-shadow:
    0 2px 0 #0031cc,
    0 8px 15px rgba(0,0,0,0.3);
}

body {
  font-family: 'Arial', sans-serif;
  margin: 0;
  background: linear-gradient(180deg, #e6f2ff, #6292df);
}

/* ===== HEADER ===== */
header {
  background: linear-gradient(135deg, #76a4f8, #0a3cff);
  color: white;
  padding: 24px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 8px 24px rgba(0,0,0,0.18);
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
  backdrop-filter: blur(8px);
}
header h1 {
  margin: 0;
  font-size: clamp(20px, 5vw, 28px);
  font-weight: 700;
}

/* ===== ACCOUNT ICON ===== */
.account { position: relative; cursor: pointer; }
.account i {
  font-size: clamp(32px, 6vw, 40px);
  transition: transform 0.25s ease, opacity 0.25s ease;
}
.account:hover i {
  transform: scale(1.15);
  opacity: 1;
}

/* ===== MODAL FORM ===== */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(10,60,255,0.25);
  justify-content: center;
  align-items: center;
  z-index: 999;
  backdrop-filter: blur(6px);
}

.modal-content {
  background: rgba(255,255,255,0.7);
  border-radius: 20px;
  padding: 30px 40px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 45px rgba(0,0,0,0.25);
  position: relative;
  backdrop-filter: blur(18px);
}

.modal-content h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #1e6bff;
}

.close-btn {
  position: absolute;
  top: 15px;
  right: 20px;
  font-size: 1.5rem;
  cursor: pointer;
  color: #777;
}
.close-btn:hover { color: #e74c3c; }

/* ===== FORM GROUP ===== */
.form-group {
  position: relative;
  margin-bottom: 20px;
}
.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 18px 14px;     /* FIX overlap */
  font-size: 1rem;
  line-height: 1.4;      /* IMPORTANT */
  border-radius: 12px;
  border: 1.5px solid #cfd9ff;
  background: rgba(255,255,255,0.9);
  box-sizing: border-box;
  transition: all 0.25s ease;
}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #1e6bff;
  box-shadow: 0 0 0 3px rgba(30,107,255,0.2);
}

/* Floating labels */
.form-group label {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  color: #5a6b8c;
  background: rgba(255,255,255,0.9);
  padding: 0 6px;
  pointer-events: none;
  transition: 0.2s ease;
}

.form-group input:focus + label,
.form-group input:not(:placeholder-shown) + label,
.form-group textarea:focus + label,
.form-group textarea:not(:placeholder-shown) + label {
  top: -10px;
  font-size: 0.75rem;
  color: #1e6bff; /* SAME BLUE */
}

/* ===== FIELDSET ===== */
fieldset {
  margin-bottom: 15px;
  border: 1px solid #cfd9ff;
  padding: 10px;
  border-radius: 10px;
  background: rgba(255,255,255,0.6);
}

legend {
  padding: 0 5px;
  font-weight: bold;
  color: #1e6bff;
}

/* ===== BUTTONS ===== */
button {
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #1e6bff, #0a3cff);
  color: #fff;
  font-weight: bold;
  cursor: pointer;
  margin-top: 10px;
  box-shadow: 0 8px 18px rgba(30,107,255,0.35);
  transition: all 0.3s ease;
}

button:hover {
  transform: translateY(-5px) scale(1.02);
  background: linear-gradient(135deg, #0a3cff, #0031cc);
}

button:active,
button.clicked {
  transform: scale(0.97);
  background: #0031cc;
}

/* ===== IMAGE PREVIEW ===== */
img#previewImg {
  max-width: 120px;
  margin-top: 5px;
  display: none;
  border-radius: 10px;
}

/* ===== CONTAINER CARDS ===== */
.container {
  max-width: 1100px;
  margin: 30px auto 100px;
  padding: 0 16px;
  text-align: center;
}

.cards {
  display: flex;
  flex-wrap: wrap;
  gap: 26px;
  justify-content: center;
}

.card {
  background: rgba(255,255,255,0.7);
  backdrop-filter: blur(16px);
  border-radius: 20px;
  width: clamp(260px, 90vw, 300px);
  padding: 30px 20px;
  box-shadow: 0 10px 28px rgba(0,0,0,0.18);
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-10px) scale(1.04);
  box-shadow: 0 18px 42px rgba(0,0,0,0.25);
}

.card i {
  font-size: clamp(44px, 10vw, 56px);
  margin-bottom: 16px;
  color: #1e6bff;
}

.card h3 {
  font-size: clamp(18px, 5vw, 22px);
  margin-bottom: 22px;
  color: #1f2d3d;
}

.card button {
  padding: 16px;
  border-radius: 14px;
}

/* ===== FOOTER ===== */
.footer {
  text-align: center;
  font-size: 0.9rem;
  color: #0a3cff;
  background: rgba(255,255,255,0.8);
  backdrop-filter: blur(10px);
  padding: 12px 0;
  width: 100%;
  bottom: 0;
  border-top: 1px solid rgba(255,255,255,0.4);
}
.footer b { color: #1e6bff; }

/* ===== LOGO ===== */
.logo-container {
  display: flex;
  align-items: center;
  margin-right: 15px;
}
.logo {
  width: 60px;
  animation: fadeIn 2s ease-in-out;
}

/* ===== BACKGROUND LOGO ===== */
body::before {
  content: "";
  position: fixed;
  inset: 0;
  background: url('logo.png') center/300px no-repeat;
  opacity: 0.05;
  pointer-events: none;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.85); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes pulse {
  0% { transform: scale(1); }
  100% { transform: scale(1.05); }
}

@keyframes slideIn {
  from { transform: translateY(80px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* ===== FLOATING ANNOUNCEMENT ===== */
#floatingAnnouncement {
  animation: slideIn 0.6s ease forwards;
}
#floatingAnnouncement.new-notification {
  animation: pulse 1s infinite;
}

/* ===== BANNER ===== */
.banner-link { text-decoration: none; }
.banner {
  background: linear-gradient(135deg, #1e6dff85, #0c35db);
  color: white;
  text-align: center;
  padding: 12px 0;
  font-size: 1.2rem;
  font-weight: bold;
  border-radius: 10px;
  margin: 10px 0;
  width: 100%;
  animation: slideIn 1s ease forwards, pulse 2s infinite alternate;
}
.banner:hover { background: #0031cc; }

/* ===== RESPONSIVE ===== */
@media (max-width:600px) {
  .cards { flex-direction: column; align-items: center; }
}
.card {
  background:
    linear-gradient(
      145deg,
      rgba(255,255,255,0.85),
      rgba(220,235,255,0.85)
    );
}
.modal-content {
  background:
    linear-gradient(
      160deg,
      rgba(255,255,255,0.85),
      rgba(230,242,255,0.85)
    );
}


/* Container */
#chatContainer {
  max-width: 600px;
  margin: 20px auto;
  font-family: Arial, sans-serif;
}

/* Header */
#chatContainer h3 {
  text-align: center;
  color: #1e6bff;
  margin-bottom: 15px;
}

/* Chat box */
#aiChat {
  background: #f5f5f5;
  padding: 10px;
  border-radius: 10px;
  height: 300px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
  border: 1px solid #ddd;
}

/* Input box */
#aiQuestion {
  width: 100%;
  padding: 10px;
  margin-top: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  box-sizing: border-box;
  font-size: 14px;
}

/* Ask button */
#aiAskBtn {
  padding: 10px 20px;
  margin-top: 10px;
  border: none;
  border-radius: 8px;
  background: #1e6bff;
  color: white;
  cursor: pointer;
  font-weight: bold;
  transition: background 0.3s ease;
}

#aiAskBtn:hover {
  background: #155ab6;
}

/* User message bubble */
.userBubble {
  align-self: flex-end;
  background: #1e6bff;
  color: white;
  padding: 8px 12px;
  border-radius: 12px;
  max-width: 80%;
  word-wrap: break-word;
}

/* AI message bubble */
.aiBubble {
  align-self: flex-start;
  background: #e0e0e0;
  color: #333;
  padding: 8px 12px;
  border-radius: 12px;
  max-width: 80%;
  word-wrap: break-word;
}

</style>
</head>
<body>

<header>
    <div class="logo-container">
    <img src="logo.png" alt="Logo" class="logo">
  </div>
  <h1>Welcome to Barangay Peñafrancia Certificate Request</h1>


  <div class="account" onclick="openManageAccount()">
    <i class="fas fa-user-circle"></i>
  </div>
</header>
<!-- Animated Banner -->
<a href="https://www.facebook.com/profile.php?id=61553631606696" target="_blank" class="banner-link">
  <div class="banner">
    <i class="fab fa-facebook-f"></i> Visit our Facebook Page!
  </div>
</a>





<!-- CENTERED MANAGE ACCOUNT FORM -->
<div class="modal" id="manageModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeForm()">&times;</span>
    <h2>Manage Account</h2>
    <form id="manageForm">
      <div class="form-group">
        <input type="text" id="username" placeholder=" " required>
        <label>Username</label>
      </div>
      <div class="form-group">
        <input type="password" id="password" placeholder="Leave blank to keep current password">
<label>New Password</label>
      </div>
      <div class="form-group">
        <input type="text" id="fname" placeholder=" " required>
        <label>First Name</label>
      </div>
      <div class="form-group">
        <input type="text" id="mname" placeholder=" " required>
        <label>Middle name</label>
      </div>
       <div class="form-group">
        <input type="text" id="lname" placeholder=" " required>
        <label>Last name</label>
      </div>
      <div class="form-group">
        <input type="text" id="mPhone" placeholder=" " required>
        <label>CP Number</label>
      </div>
      <div class="form-group">
        <input type="number" id="age" placeholder=" " min="1" required>
        <label>Age</label>
      </div>
      <div class="form-group">
        <select id="sex" required>
          <option value="" disabled selected hidden></option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
        <label>Sex</label>
      </div>
      <div class="form-group">
        <input type="date" id="birthday" placeholder=" " required>
        <label>Birthday</label>
      </div>
      <div class="form-group">
        <input type="text" id="address" placeholder=" " required>
        <label>Street, Barangay, City/Municipality, Province/State</label>
      </div>
      <div class="form-group">
        <select id="status" required>
          <option value="" disabled selected hidden></option>
          <option value="Single">Single</option>
          <option value="Married">Married</option>
          <option value="Widowed">Widowed</option>
          <option value="Separated">Separated</option>
        </select>
        <label>Civil Status</label>
      </div>
      <div class="form-group">
        <select id="pwd" required>
          <option value="" disabled selected hidden></option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
        <label>PWD</label>
      </div>
      <div class="form-group">
        <select id="mFourPs" required>
          <option value="" disabled selected hidden></option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
        <label>4Ps</label>
      </div>
      <fieldset>
        <label><input type="checkbox" id="seniorCitizen"> Senior Citizen</label>
      </fieldset>
      <fieldset>
        <legend>School Level (Select at least one)</legend>
        <label><input type="checkbox" class="school" value="Elementary"> Elementary</label>
        <label><input type="checkbox" class="school" value="Junior High"> Junior High</label>
        <label><input type="checkbox" class="school" value="Senior High"> Senior High</label>
        <label><input type="checkbox" class="school" value="College"> College</label>
        <span class="error" id="schoolError"></span>
      </fieldset>
      <div class="form-group">
        <input type="text" id="schoolName" placeholder=" " required>
        <label>Name of School</label>
      </div>
      <div class="form-group">
        <input type="text" id="occupation" placeholder=" " required>
        <label>Occupation</label>
      </div>
      <fieldset>
        <label><input type="checkbox" id="vaccinated"> Vaccinated</label>
      </fieldset>
      <fieldset>
        <label><input type="checkbox" id="voter"> Voter</label>
      </fieldset>
      <div class="form-group">
        <input type="file" id="validId" accept="image/*">
        <label>Upload Valid ID</label>
        <img id="previewImg" alt="ID Preview">
      </div>
      <button type="submit">Update Account</button>
      <button type="button" style="background:#e74c3c; margin-top:10px;" onclick="logout()">Logout</button>
    </form>
  </div>
</div>

<div class="container">
  <div class="cards">
    <div class="card">
      <i class="fas fa-id-card"></i>
      <h3>Barangay Clearance</h3>
      <p class="cert-fee" data-type="clearance">₱0</p>
      <button onclick="openCertificate('clearance')">Manage Requests</button>
    </div>
    <div class="card">
      <i class="fas fa-home"></i>
      <h3>Certificate of Residency</h3>
      <p class="cert-fee" data-type="residency">₱0</p>
      <button onclick="openCertificate('residency')">Manage Requests</button>
    </div>
    <div class="card">
      <i class="fas fa-hand-holding-heart"></i>
      <h3>Certificate of Indigency</h3>
      <p class="cert-fee" data-type="indigency">₱0</p>
      <button onclick="openCertificate('indigency')">Manage Requests</button>
    </div>
    <div class="card">
      <i class="fas fa-briefcase"></i>
      <h3>Business Clearance</h3>
      <p class="cert-fee" data-type="business">₱0</p>
      <button onclick="openCertificate('business')">Manage Requests</button>
    </div>
  </div>
</div>
<!-- Toast Container -->
<div id="toastContainer" style="
  position: fixed;
  bottom: 90px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 1000;
"></div>
<!-- Floating Announcement HTML -->
<div id="floatingAnnouncement" style="
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #3498db;
  color: #fff;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 999;
  font-size: 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
">
  <i class="fas fa-bullhorn"></i>
  <span id="floatingCount" style="
    position: absolute;
    top: -5px;
    right: -5px;
    background: red;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-size: 0.8rem;
    display: none;
    align-items: center;
    justify-content: center;
  ">0</span>
</div>
<div class="modal" id="announcementModal">
  <div class="modal-content">
    <span class="close-btn" onclick="announcementModal.style.display='none'">&times;</span>
    <h2>Announcements</h2>
    <ul id="userAnnouncements" style="list-style:none; padding:0;"></ul>
  </div>
</div>



<div id="chatContainer">
  <h3>Ask AI Kap </h3>
  <div id="aiChat"></div>
  <input id="aiQuestion" placeholder="I-type an imo hapot...">
  <button id="aiAskBtn" onclick="askAI()">Tanong</button>
</div>

<div class = "footer">  
  Developed by <b>@SylvanDR2</b>
</div>




<script>
const token = localStorage.getItem("token");
const userId = localStorage.getItem("userId"); // 🔑 kailangan para sa update

async function openManageAccount() {
  document.getElementById("manageModal").style.display = "flex"; // open modal first

  try {
    const res = await fetch(`authController.php?action=getResident&email=${encodeURIComponent(localStorage.getItem("currentEmail"))}`, {
      headers: { "Authorization": "Bearer " + token }
    });

    const user = await res.json();
    if (!res.ok) throw new Error(user.message || "Failed to load account info");

    // --- BASIC INFO ---
    document.getElementById("username").value = user.email;
    document.getElementById("fname").value = user.name;
    document.getElementById("mname").value = user.middlename;
    document.getElementById("lname").value = user.lastname;
    document.getElementById("mPhone").value = user.phone;
    document.getElementById("age").value = user.age;
    document.getElementById("sex").value = user.sex;
    document.getElementById("birthday").value = user.birthday;
    document.getElementById("address").value = user.address;
    document.getElementById("status").value = user.status;
    document.getElementById("pwd").value = user.pwd;
    document.getElementById("mFourPs").value = user.fourPs;
    document.getElementById("schoolName").value = user.schoolName;
    document.getElementById("occupation").value = user.occupation;

    // --- CHECKBOXES ---
    document.getElementById("seniorCitizen").checked = user.seniorCitizen == 1;
    document.getElementById("vaccinated").checked = user.vaccinated == 1;
    document.getElementById("voter").checked = user.voter == 1;

    // --- SCHOOL LEVELS ---
    const levels = user.schoolLevels?.split(",") || [];
    document.querySelectorAll(".school").forEach(cb => cb.checked = levels.includes(cb.value));

    // --- IMAGE ---
    if (user.validId) {
      document.getElementById("previewImg").src = user.validId.startsWith("uploads/") ? user.validId : "uploads/" + user.validId;
    }

  } catch (err) {
    alert(err.message);
  }
}

// --- UPDATE ACCOUNT ---
document.getElementById("manageForm").addEventListener("submit", async function(e) {
  e.preventDefault();

  const formData = new FormData();
  formData.append("id", userId);
  formData.append("email", document.getElementById("username").value); // dito email na
   // ✅ Only append password if user typed something
  const password = document.getElementById("password").value.trim();
  if (password !== "") {
    formData.append("password", password);
  }
  formData.append("name", document.getElementById("fname").value);
  formData.append("middlename", document.getElementById("mname").value);
  formData.append("lastname", document.getElementById("lname").value);
  formData.append("phone", document.getElementById("mPhone").value);
  formData.append("age", document.getElementById("age").value);
  formData.append("sex", document.getElementById("sex").value);
  formData.append("birthday", document.getElementById("birthday").value);
  formData.append("address", document.getElementById("address").value);
  formData.append("status", document.getElementById("status").value);
  formData.append("pwd", document.getElementById("pwd").value);
  formData.append("fourPs", document.getElementById("mFourPs").value);
  formData.append("seniorCitizen", document.getElementById("seniorCitizen").checked ? 1 : 0);
  formData.append("schoolLevels", Array.from(document.querySelectorAll(".school:checked")).map(cb => cb.value).join(","));
  formData.append("schoolName", document.getElementById("schoolName").value);
  formData.append("occupation", document.getElementById("occupation").value);
  formData.append("vaccinated", document.getElementById("vaccinated").checked ? 1 : 0);
  formData.append("voter", document.getElementById("voter").checked ? 1 : 0);

  const file = document.getElementById("validId").files[0];
  if (file) formData.append("validId", file);

  try {
    const res = await fetch("authController.php?action=updateResident", {
      method: "POST",
      body: formData
    });

    const data = await res.json();
    if (!res.ok) throw new Error(data.message || "Update failed");

    alert(data.message || "Account updated successfully!");
    closeForm();
  } catch (err) {
    console.error(err);
    alert("Error updating account: " + err.message);
  }
});

// --- FILE PREVIEW ---
document.getElementById("validId").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById("previewImg").src = e.target.result;
    reader.readAsDataURL(file);
  }
});

// --- CLOSE MODAL ---
function closeForm() {
  document.getElementById("manageModal").style.display = "none";
}



////////////


// Open/close modal
function openForm() { document.getElementById("manageModal").style.display = "flex"; }
function closeForm() { document.getElementById("manageModal").style.display = "none"; }

// Logout
function logout() {
  localStorage.removeItem("currentResident");
  window.location.href = 'index.html';
}

// Certificate buttons


function openCertificate(type) {
  switch(type) {
    case 'clearance': window.location.href = 'barangayClearance.html'; break;
    case 'residency': window.location.href = 'certificate-residency.html'; break;
    case 'indigency': window.location.href = 'certificate-indigency.html'; break;
    case 'business': window.location.href = 'business-clearance.html'; break;
    default: alert('Page not found');
  }
}


const defaultFees = { clearance: 50, residency: 30, indigency: 20, business: 100 };

async function loadCertificateFees() {
  let fees = { ...defaultFees };

  try {
    // Subukan kunin sa server
    const res = await fetch("authController.php?action=getCertificateFees");
    const data = await res.json();

    if (data && Object.keys(data).length > 0) {
      fees = data;
      // optional: i-save sa localStorage para offline
      localStorage.setItem("certificate_fees", JSON.stringify(fees));
    } else {
      // fallback sa localStorage kung empty
      fees = JSON.parse(localStorage.getItem("certificate_fees")) || defaultFees;
    }
  } catch (e) {
    console.error("Failed to fetch certificate fees:", e);
    fees = JSON.parse(localStorage.getItem("certificate_fees")) || defaultFees;
  }

  // Update all fees in DOM
  document.querySelectorAll(".cert-fee").forEach(el => {
    el.textContent = `₱${fees[el.dataset.type] || 0}`;
  });
}

window.addEventListener("DOMContentLoaded", loadCertificateFees);





// ======================
// USER ANNOUNCEMENTS
// ======================
const floatingAnnouncement = document.getElementById("floatingAnnouncement");
const floatingCount = document.getElementById("floatingCount");
const announcementModal = document.getElementById("announcementModal");
const userAnnouncements = document.getElementById("userAnnouncements");

let readAnnouncements = JSON.parse(localStorage.getItem("readAnnouncements") || "[]");

// ======================
// LOAD ANNOUNCEMENTS FROM SERVER
// ======================
async function loadUserAnnouncements() {
  try {
    const res = await fetch("authController.php?action=getAnnouncements");
    const data = await res.json();

    if (!Array.isArray(data)) return;

    // Filter unread
    const unread = data.filter(a => !readAnnouncements.includes(a.id));

    // Update floating badge
    if (unread.length > 0) {
      floatingCount.textContent = unread.length;
      floatingCount.style.display = "flex";
      floatingAnnouncement.classList.add("new-notification");
    } else {
      floatingCount.style.display = "none";
      floatingAnnouncement.classList.remove("new-notification");
    }

    // Render list inside modal
    userAnnouncements.innerHTML = data.map(a => {
      const isRead = readAnnouncements.includes(a.id);
      return `
        <li style="padding:10px; border-bottom:1px solid #ddd; background:${isRead ? 'white':'#dff9db'}">
          <strong>${a.sender}</strong>: ${a.message}<br>
          <small>${new Date(a.date_sent).toLocaleString()}</small>
        </li>
      `;
    }).join("");

  } catch (err) {
    console.error("Failed to load announcements:", err);
  }
}

// ======================
// MARK ALL AS READ
// ======================
floatingAnnouncement.addEventListener("click", () => {
  announcementModal.style.display = "flex";

  // Mark all as read
  fetch("authController.php?action=getAnnouncements")
    .then(res => res.json())
    .then(data => {
      data.forEach(a => {
        if (!readAnnouncements.includes(a.id)) readAnnouncements.push(a.id);
      });
      localStorage.setItem("readAnnouncements", JSON.stringify(readAnnouncements));
      loadUserAnnouncements();
    });
});

// ======================
// CLOSE MODAL WHEN CLICK OUTSIDE
// ======================
window.addEventListener("click", e => {
  if (e.target === announcementModal) {
    announcementModal.style.display = "none";
  }
});

// ======================
// TOAST NOTIFICATIONS FOR NEW MESSAGES
// ======================
function showToast(message) {
  const toastContainer = document.getElementById("toastContainer");
  const toast = document.createElement("div");
  toast.textContent = message;
  toast.style.cssText = `
    background: #3498db;
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    animation: slideIn 0.5s ease, fadeOut 0.5s 3s forwards;
    font-weight: bold;
    max-width: 250px;
    margin-bottom:10px;
  `;
  toastContainer.appendChild(toast);
  setTimeout(() => toastContainer.removeChild(toast), 3500);
}

// Animations
const style = document.createElement("style");
style.innerHTML = `
@keyframes slideIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
@keyframes fadeOut { to { opacity: 0; transform: translateX(100%); } }
`;
document.head.appendChild(style);

// ======================
// PERIODIC CHECK FOR NEW ANNOUNCEMENTS
// ======================
setInterval(async () => {
  try {
    const res = await fetch("authController.php?action=getAnnouncements");
    const data = await res.json();

    if (!Array.isArray(data)) return;

    const newMessages = data.filter(a => !readAnnouncements.includes(a.id));
    newMessages.forEach(a => showToast(`${a.sender}: ${a.message}`));

    if (newMessages.length > 0) {
      newMessages.forEach(a => readAnnouncements.push(a.id));
      localStorage.setItem("readAnnouncements", JSON.stringify(readAnnouncements));
      loadUserAnnouncements();
    }
  } catch (err) {
    console.error("Error checking announcements:", err);
  }
}, 5000);

// ======================
// INITIAL LOAD
// ======================
document.addEventListener("DOMContentLoaded", () => {
  loadUserAnnouncements();
});

const hardcodedAnswers = {
  "hi": "Matiano ka man, just reply: request clearance, office hours",
  "request clearance": "Mag request sa site ko at mag hintay ng approved at pumunta sa opisina ng Barangay, at isumite ang valid ID mo.",
  "office hours": "An amo opisina ay bukas Lunes hanggang Biyernes, 8:00 AM hanggang 5:00 PM.",
};

// Function to find the best match for similar questions
function findAnswer(question) {
  const q = question.toLowerCase();
  let bestMatch = null;
  let maxScore = 0;

  for (const key in hardcodedAnswers) {
    const keyWords = key.split(" ");
    let score = 0;
    keyWords.forEach(word => {
      if (q.includes(word)) score++;
    });
    if (score > maxScore) {
      maxScore = score;
      bestMatch = key;
    }
  }
return bestMatch 
    ? hardcodedAnswers[bestMatch] 
    : "Pasensya na, dai ako makasagot sa hapot na ini. Pwede ka maghapot sa opisina kan Barangay. or Reply mo nalng ito here: office hours, request clearance";

}

function askAI() {
  const questionInput = document.getElementById("aiQuestion");
  const chatDiv = document.getElementById("aiChat");
  const question = questionInput.value.trim();
  if (!question) return;

  // Add user bubble
  const userBubble = document.createElement("div");
  userBubble.style.cssText = "align-self:flex-end;background:#1e6bff;color:white;padding:8px 12px;border-radius:12px;max-width:80%;";
  userBubble.textContent = question;
  chatDiv.appendChild(userBubble);

  // Get AI answer
  const answer = findAnswer(question);

  // Add AI bubble
  const aiBubble = document.createElement("div");
  aiBubble.style.cssText = "align-self:flex-start;background:#e0e0e0;color:#333;padding:8px 12px;border-radius:12px;max-width:80%;";
  aiBubble.textContent = answer;
  chatDiv.appendChild(aiBubble);

  // Scroll to bottom
  chatDiv.scrollTop = chatDiv.scrollHeight;

  questionInput.value = ""; // clear input
}


</script>

</body>
</html>
