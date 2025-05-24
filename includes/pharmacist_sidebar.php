<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <button id="sidebarToggle" aria-label="Toggle Sidebar">&#9776;</button>
    <h2 class="sidebar-title">Pharmacist Dashboard</h2>
  </div>
  <ul>
    <li><a href="/HMS-main/views/pharmacist/dashboard.php"><i class="fa fa-pills"></i><span class="label">Dashboard</span></a></li>
    <li><a href="/HMS-main/views/pharmacist/patientmedication.php"><i class="fa fa-notes-medical"></i><span class="label">Patient Medication</span></a></li>
    <li><a href="/HMS-main/views/pharmacist/pharmacy.php"><i class="fa fa-capsules"></i><span class="label">Pharmacy</span></a></li>
    <!-- <li><a href="/HMS-main/auth/logout.php"><i class="fa fa-sign-out-alt"></i><span class="label">Logout</span></a></li>-->
  </ul>
</div>

<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #e0f7fa;
    color: #333;
    box-sizing: border-box;
    padding-top: 60px;
  }

  .sidebar {
    position: fixed;
    top: 60px;
    width: 190px;
    height: calc(100vh - 60px);
    background-color: #9c335a;
    padding: 20px;
    color: white;
    z-index: 1;
    overflow-y: auto;
    transition: width 0.3s ease;
  }

  .sidebar.collapsed {
    width: 60px;
    padding: 20px 10px;
  }

  .sidebar-header {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-bottom: 20px;
  }

  .sidebar-title {
    margin: 0;
    font-size: 1.4em;
  }

  .sidebar.collapsed .sidebar-title {
    display: none;
  }

  #sidebarToggle {
    margin-top: 25px;
    font-size: 24px;
    color: white;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    user-select: none;
    margin-bottom: 5px;
  }

  .sidebar ul {
    list-style-type: none;
    padding: 0;
  }

  .sidebar ul li {
    margin-bottom: 10px;
  }

  .sidebar ul li a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    text-decoration: none;
    padding: 8px;
    font-size: 1em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .sidebar ul li a i {
    font-size: 1.2em;
    width: 20px;
    text-align: center;
  }

  .sidebar ul li a:hover {
    background-color: #7a0154;
    border-radius: 4px;
  }

  .sidebar.collapsed .label {
    display: none;
  }

  .content {
    margin-left: 220px;
    padding: 40px;
    transition: margin-left 0.3s ease;
  }

  .sidebar.collapsed ~ .content {
    margin-left: 60px;
  }
</style>
