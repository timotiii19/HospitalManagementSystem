<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Nurse Dashboard</title>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
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
      top: 50px;
      width: 190px;
      height: calc(100vh - 60px);
      background-color: #9c335a;
      padding: 20px;
      color: white;
      z-index: 1;
      overflow-y: auto;
      transition: width 0.3s ease;
      margin-top: 30px;
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
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar ul li {
      margin-bottom: 10px;
    }

    .sidebar ul li a {
      display: flex;
      align-items: center;
      gap: 7px;
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
      flex-shrink: 0;
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

    /* Dropdown styling */
    .dropdown-content {
      display: none;
      list-style: none;
      padding-left: 0;   /* remove left padding */
      margin: 0;         /* remove margin to avoid spacing */
      background-color: #923f78;
      position: static;  /* ensure it's inline inside the flow */
    }


    .dropdown-content li a {
      display: block;
      padding: 6px 8px;
      font-size: 0.85em !important;
      opacity: 0.9;
      color: white;
      text-decoration: none;
      padding-left: 30px !important;
    }

    .dropdown-content li a:hover {
      background-color: #7a0154;
      border-radius: 4px;
    }

    .dropdown-btn::after {
      content: " ▼";
      font-size: 0.7em;
      margin-left: auto;
    }

    .dropdown-btn.active::after {
      content: " ▲";
    }

    .sidebar.collapsed .dropdown-btn::after {
      display: none;
    }
  </style>
</head>
<body>

  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <button id="sidebarToggle" aria-label="Toggle Sidebar">&#9776;</button>
      <h2 class="sidebar-title">Hospital System</h2>
    </div>

    <ul>
      <li>
        <a href="/HMS-main/views/nurse/dashboard.php">
          <i class="fa fa-tachometer-alt"></i><span class="label">Dashboard</span>
        </a>
      </li>

     <li>
      <a href="javascript:void(0);" class="dropdown-btn">
        <i class="fa fa-procedures"></i><span class="label">Patient Management</span>
      </a>
      <ul class="dropdown-content">
        <li><a href="/HMS-main/views/nurse/add_patient.php"><i class="fa fa-user-plus"></i> Add Patient</a></li>
        <li><a href="/HMS-main/views/nurse/patient.php"><i class="fa fa-users"></i> View Patients</a></li>
        <!-- divider line -->
        <li style="text-align:center; color:#fff3;">──────────</li>
        <li><a href="/HMS-main/views/nurse/inpatient.php"><i class="fa fa-bed"></i> Inpatients</a></li>
        <li><a href="/HMS-main/views/nurse/outpatient.php"><i class="fa fa-walking"></i> Outpatients</a></li>
      </ul>
    </li>


      <li>
        <a href="/HMS-main/views/nurse/department.php">
          <i class="fa fa-building"></i><span class="label">Departments</span>
        </a>
      </li>
      <li>
        <a href="/HMS-main/views/nurse/doctorschedule.php">
          <i class="fa fa-user-clock"></i><span class="label">Doctor Schedule</span>
        </a>
      </li>
      <li>
        <a href="/HMS-main/views/nurse/location.php">
          <i class="fa fa-map-marker-alt"></i><span class="label">Location</span>
        </a>
      </li>
      <li>
        <a href="/HMS-main/views/nurse/emergency.php">
          <i class="fa fa-ambulance"></i><span class="label">Emergency</span>
        </a>
      </li>
     <!-- <li><a href="/HMS-main/auth/logout.php"><i class="fa fa-sign-out-alt"></i><span class="label">Logout</span></a></li>-->
    </ul>
  </div>

<script>
  const dropdownBtns = document.querySelectorAll('.dropdown-btn');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.content');

  dropdownBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const dropdownContent = btn.nextElementSibling;
      const isOpen = btn.classList.contains('active');

      // Close all dropdowns
      dropdownBtns.forEach(otherBtn => {
        otherBtn.classList.remove('active');
        const otherDropdown = otherBtn.nextElementSibling;
        if (otherDropdown) {
          otherDropdown.style.display = 'none';
        }
      });

      // Toggle current dropdown
      if (!isOpen) {
        btn.classList.add('active');
        dropdownContent.style.display = 'block';
      }
    });
  });

  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
      content.style.marginLeft = '60px';
    } else {
      content.style.marginLeft = '220px';
    }

    // Close all dropdowns when sidebar toggled
    dropdownBtns.forEach(btn => {
      btn.classList.remove('active');
      const dropdownContent = btn.nextElementSibling;
      if (dropdownContent) {
        dropdownContent.style.display = 'none';
      }
    });
  });
</script>

</body>
</html>
