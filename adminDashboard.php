<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: adminLogin.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Barangay Penafrancia - Residents</title>
  
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  /* ============================= */
/* GLOBAL STYLES                 */
/* ============================= */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Segoe UI", sans-serif;
}

body {
  font-family: "Poppins", sans-serif;
  background: #f5f6fa;
  color: #333;
  margin: 0;
  padding: 0;
  height: 100%;
}

/* ============================= */
/* HEADER                        */
/* ============================= */

header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 10px 20px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 10;
}

.logo-section {
  display: flex;
  align-items: center;
}

.logo {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 10px;
}

/* ============================= */
/* LAYOUT                         */
/* ============================= */

.container {
  display: flex;
  height: calc(100vh - 120px); /* full height minus header & footer */
}

.sidebar {
  width: 240px;
  background: #f4f5f7;
  padding: 20px;
  border-right: 1px solid #ddd;
  overflow: hidden;
  position: relative; /* para maayos ang active indicator */
}

.sidebar h4 {
  margin-bottom: 10px;
  font-size: 13px;
  color: #777;
}

.sidebar ul {
  list-style: none;
  margin-bottom: 20px;
}

.sidebar ul li {
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  color: #333;
  border-radius: 5px;
  position: relative;
  z-index: 2;
  transition:
    background 0.2s ease,
    transform 0.15s ease,
    box-shadow 0.2s ease;
}

.sidebar ul li:hover {
  background: #e6f0ff;
  color: #0056d6;
  transform: translateY(-3px);
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Active item sa sidebar */
.sidebar ul li.active {
  background: #2563eb;
  color: #ffffff;
  font-weight: 600;
  transform: scale(0.97);
}

.sidebar ul li i {
  opacity: 0.6;
  transform: translateX(-6px);
  transition:
    transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1),
    opacity 0.25s ease;
}

.sidebar ul li.active i {
  opacity: 1;
  transform: translateX(0);
  transition-delay: 0.08s;
}

.sidebar ul li span {
  display: inline-block;
  transform: translateX(-2px);
  transition: transform 0.3s ease;
}

.sidebar ul li.active span {
  transform: translateX(0);
}

/* Active indicator sa sidebar */
.active-indicator {
  position: absolute;
  left: 0;
  width: 5px;
  background-color: #0056d6;
  border-radius: 3px;
  top: 0;
  height: 40px;
  transition:
    top 1s cubic-bezier(0.34, 1.56, 0.64, 1),
    height 1s ease;
  z-index: 1;
}

/* ============================= */
/* MAIN CONTENT                  */
/* ============================= */

.main-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;   /* scroll kung mahaba */
  background: #f5f6fa;
  transition: all 0.3s ease;
}

.main-content h2 {
  margin-bottom: 15px;
  font-size: 24px;
}

.content-box {
  background: #fdfdfd;
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 50px;
  text-align: center;
  transition: transform 0.3s ease;
}

.main-content:active .content-box {
  transform: scale(0.98);
}

/* ============================= */
/* DASHBOARD CARDS               */
/* ============================= */

.dashboard-container {
  padding: 20px 40px;
}

.dashboard-content {
  display: flex;
  gap: 20px;
  align-items: stretch;
}

.dashboard-left {
  flex: 2;
}

.chart-card {
  flex: 2;
}

.dashboard-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
  justify-content: space-between;
}

.dashboard-right .dashboard-card {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  font-size: 16px;
  border-radius: 12px;
  color: #fff;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.3s ease;
}

.dashboard-card {
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  padding: 20px;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
}

.dashboard-card:hover {
  transform: translateY(-6px) scale(1.03);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.dashboard-card.red {
  cursor: default;
  pointer-events: none;
  background: linear-gradient(145deg, #f76c6c, #e44d4d);
}

.dashboard-card.red:hover {
  transform: none;
  box-shadow: none;
}

/* Click effect */
.dashboard-card:active {
  transform: scale(0.97);
}

/* “View” button */
.view-btn {
  background: #b3b872;
  color: #fff;
  border: none;
  border-radius: 25px;
  padding: 6px 14px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.view-btn:hover {
  background: #0b5ed7;
  transform: scale(1.05);
}

/* Card status colors */
.dashboard-card.pending { 
  background: linear-gradient(145deg, #5aa9f8, #2f80ed); 
}

.dashboard-card.online { 
  background: linear-gradient(145deg, #f8d15c, #e7b93f);
}

.dashboard-card.rejected { 
  background: linear-gradient(145deg, #b3b2b2, #969696);
}

.dashboard-card.pending:hover {
  background: linear-gradient(145deg, #7fbfff, #1f70d0) !important;
}

.dashboard-card.online:hover {
  background: linear-gradient(145deg, #fce57c, #d9a70a) !important;
}

.dashboard-card.rejected:hover {
  background: linear-gradient(145deg, #d6d6d6, #8a8a8a) !important;
}

/* Card text & number */
.dashboard-card, 
.dashboard-card span, 
.dashboard-card .count, 
.dashboard-card .view-btn {
  text-decoration: none !important;
}

.dashboard-card .count {
  font-size: 50px;
  font-weight: bold;
}

.dashboard-card .view-btn {
  width: 100%;
  max-width: 120px;
  font-size: 14px;
  padding: 8px 0;
  margin-top: 10px;
}

/* ============================= */
/* CHARTS                         */
/* ============================= */

.chart-card canvas {
  max-width: 400px;
  max-height: 400px;
  width: 100%;
  height: auto;
  margin: 10px auto;
}

/* ============================= */
/* RESIDENT PAGE                  */
/* ============================= */

.resident-page h1 { margin-bottom: 15px; }
.resident-page #searchInput { width: 300px; padding: 8px; margin-bottom: 15px; }
.resident-page .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
.resident-page .btn-add { background: #27ae60; color: white; }
.resident-page .btn-edit { background: #f39c12; color: white; }
.resident-page .btn-delete { background: #c0392b; color: white; }
.resident-page .btn-back { background: #2980b9; color: white; margin-top: 10px; }
.resident-page table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; }
.resident-page th, .resident-page td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
.resident-page th { background: #ecf0f1; }
.resident-page img { width: 80px; border-radius: 5px; }

.resident-page .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; z-index: 10; }
.resident-page .modal.hidden { display: none; }
.resident-page .modal-content { background: white; padding: 20px; width: 450px; max-width: 95%; border-radius: 6px; max-height: 90vh; overflow-y: auto; }
.resident-page label { display: block; margin-top: 10px; font-weight: bold; }
.resident-page input, .resident-page select, .resident-page textarea { width: 100%; padding: 7px; margin-top: 5px; }
.resident-page fieldset { margin-top: 10px; padding: 10px; border-radius: 6px; border: 1px solid #ccc; }
.resident-page .form-actions { text-align: right; margin-top: 15px; }

/* ============================= */
/* FOOTER                         */
/* ============================= */

footer {
  text-align: center;
  padding: 10px;
  background: #f4f4f4;
  border-top: 1px solid #ddd;
}

/* ============================= */
/* LOGOUT BUTTON                  */
/* ============================= */

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: 1px solid #e5e7eb;
  color: #374151;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s ease;
}

.logout-btn i {
  font-size: 14px;
}

.logout-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #b91c1c;
  transform: translateY(-1px);
}

.logout-btn:active {
  transform: scale(0.96);
}

</style>
</head>
<body>
  <header>
    <div class="logo-section" id="btnHome">
      <img src="logo.png" alt="Logo" class="logo" />
      <div class="title">
        <h2>Barangay  Peñafrancia</h2>
        <p>Sta.Magdalena, Sorsogon</p>
      </div>
    </div>
    <div class="profile" id="btnProfile">
    </div>
    
  <button id="logoutBtn" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i>
    <span>Logout</span>
  </button>

  </header>

  <div class="container">
    <aside class="sidebar">
      <h4>GENERAL</h4>
      <ul>
        
      
        <li id="btnDashboard"><i class="fas fa-home"></i> Dashboard</li>
        <li id="btnOfficials"><i class="fas fa-users"></i> Barangay Officials</li>
        <li id="btnResidents"><i class="fas fa-user-friends"></i> Residents</li>
          <li id="btnAnnouncements"><i class="fas fa-bullhorn"></i> Announcement</li>
        <li id="btnCertification"><i class="fas fa-certificate"></i> Certification</li>
        <li id="btnTableManager"><i class="fas fa-cogs"></i> Table Manager</li>
 
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <h2>Residents</h2>
      <div class="content-box">
        <p>No data available yet.</p>
      </div>
    </main>
  </div>

  <footer>
    <p id="date"></p>
    <p></p>
  </footer>
  
  

  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  
  <script src="adminScript.js"></script>
</body>

        
</html>
