<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Dashboard</title>
  <!-- Font Awesome CDN for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
    top: 70px;
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

    .dropdown-content {
      display: none;
      list-style-type: none;
      padding-left: 10px;
      background-color: #923f78;
      margin-top: 5px;
    }

    .dropdown-content li a {
      display: block;
      padding: 6px 8px;
      font-size: 0.85em !important;
      opacity: 0.9;
      color: white;
      text-decoration: none;
    }

    .dropdown-content li a:hover {
      background-color: #7a0154;
      border-radius: 4px;
    }

    .sidebar .dropdown-content li a {
      padding-left: 30px !important;
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
    <h2 class="sidebar-title">Doctor Dashboard</h2>
  </div>
  <ul>
    <li><a href="/HMS-main/views/doctors/dashboard.php"><i class="fa fa-chart-line"></i><span class="label">Dashboard</span></a></li>
    <li><a href="/HMS-main/views/doctors/appointments.php"><i class="fa fa-calendar-check"></i><span class="label">Patient Confirmation</span></a></li>
    
    <li class="dropdown">
      <a href="javascript:void(0);" class="dropdown-btn">
        <i class="fa fa-user-cog"></i><span class="label">My Schedule</span>
      </a>
      <ul class="dropdown-content">
        <li><a href="/HMS-main/views/doctors/doctorschedule.php">Appointment</a></li> 
        <li><a href="/HMS-main/views/doctors/inpatient.php">Inpatient</a></li>
        <li><a href="/HMS-main/views/doctors/outpatient.php">Outpatient</a></li>
        <li style="text-align: center;">⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯</li>
        <li><a href="/HMS-main/views/doctors/pastschedules.php">View Past Schedule</a></li>
      </ul>
    </li>

    <li><a href="/HMS-main/views/doctors/labprocedure.php"><i class="fa fa-vials"></i><span class="label">Laboratory Procedure</span></a></li>
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

      dropdownBtns.forEach(otherBtn => {
        otherBtn.classList.remove('active');
        const otherDropdown = otherBtn.nextElementSibling;
        if (otherDropdown) {
          otherDropdown.style.display = 'none';
        }
      });

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
