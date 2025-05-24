<?php
// nurse_header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/HMS-main/config/db.php';

// Check if user is logged in and role is Nurse
if (!isset($_SESSION['UserID'], $_SESSION['role']) || $_SESSION['role'] !== 'Nurse') {
    // Not logged in as Nurse, redirect to login page
    header('Location: /HMS-main/auth/nurse_login.php');
    exit();
}

$userId = $_SESSION['UserID'];
$user = [
    'full_name' => '',
    'email' => '',
    'ContactNumber' => '',
    'role' => 'Nurse',
    'username' => '',
];

// Fetch fresh user data from DB
if ($conn) {
    $stmt = $conn->prepare("SELECT full_name, email, ContactNumber, username FROM users WHERE UserID = ? AND role = 'Nurse' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user['full_name'] = $row['full_name'];
            $user['email'] = $row['email'];
            $user['ContactNumber'] = $row['ContactNumber'];
            $user['username'] = $row['username'];

            if (!isset($_SESSION['full_name']) || $_SESSION['full_name'] !== $user['full_name']) {
                $_SESSION['full_name'] = $user['full_name'];
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Nurse Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
  /* All styles exactly as doctor_header.php */
  body {
    margin: 0;
    font-family: Arial, sans-serif;
  }
  .header {
    position: fixed;
    top: 0; width: 100%; height: 60px;
    background-color: #eb6d9b;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    z-index: 10;
    box-sizing: border-box;
    height: 80px;
  }
  .left-section, .right-section {
    display: flex;
    align-items: center;
    gap: 15px;
  }
  .logo {
    height: 40px;
  }
  .search-section {
    display: flex;
    align-items: center;
    background: #fcc0ef;
    border-radius: 20px;
    padding: 5px 10px;
  }
  .search-section input {
    background: transparent;
    border: none;
    outline: none;
    color: white;
    padding: 5px;
    width: 200px;
  }
  .search-icon {
    margin-left: 5px;
    color: #cc8383;
    cursor: pointer;
  }
  .avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 2px solid #5bcf5f;
  }
  .user-dropdown {
    position: relative;
    cursor: pointer;
    font-size: 14px;
    z-index: 9999;
  }
  .user-dropdown span {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .user-dropdown .dropdown-content {
    display: none;
    position: absolute;
    background-color: #3e4a56;
    min-width: 140px;
    right: 0;
    top: 40px;
    z-index: 999;
  }
  .user-dropdown:hover .dropdown-content {
    display: block;
  }
  .user-dropdown .dropdown-content a,
  .user-dropdown .dropdown-content button {
    color: white;
    padding: 10px;
    text-decoration: none;
    display: block;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    font-size: 14px;
  }
  .user-dropdown .dropdown-content a:hover,
  .user-dropdown .dropdown-content button:hover {
    background-color: #555;
  }
  .modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
  }
  .modal.show {
    display: flex;
  }
  .modal-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    position: relative;
    font-family: Arial, sans-serif;
  }
  .modal-content h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #eb6d9b;
  }
  .modal-content label {
    display: block;
    margin: 12px 0 6px 0;
    font-weight: bold;
  }
  .modal-content input[type="text"],
  .modal-content input[type="email"] {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 14px;
  }
  .modal-content button.save-btn {
    margin-top: 20px;
    background-color: #eb6d9b;
    border: none;
    padding: 10px 20px;
    color: white;
    cursor: pointer;
    font-size: 16px;
    border-radius: 4px;
  }
  .modal-content button.save-btn:hover {
    background-color: #d1527e;
  }
  .modal-content .close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 22px;
    color: #999;
    cursor: pointer;
    border: none;
    background: none;
  }
  .modal-content .close-btn:hover {
    color: #333;
  }
  .modal-message {
    margin-top: 10px;
    font-size: 14px;
    color: green;
    display: none;
  }
  .modal-message.error {
    color: red;
  }
</style>
</head>
<body>

<div class="header">
  <div class="left-section">
    <img src="/HMS-main/images/hosplogo.png" alt="Hospital Logo" class="logo" />
  </div>

  <div class="search-section">
    <input type="text" placeholder="Search..." id="searchInput" />
    <i class="fas fa-search search-icon" id="searchBtn"></i>
  </div>

  <div class="right-section">
    <img src="/HMS-main/images/profile1.png" alt="User Avatar" class="avatar" />
    <div class="user-dropdown" id="userDropdownToggle">
      <span>
        Nurse: <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Unknown'); ?> <i class="fas fa-chevron-down"></i>
      </span>
      <div class="dropdown-content" id="userDropdownMenu">
        <button id="openProfileBtn" type="button">My Profile</button>
        <a href="/HMS-main/auth/logout.php">Logout</a>
      </div>
    </div>
  </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="profileModalTitle" aria-modal="true">
  <div class="modal-content">
    <button class="close-btn" aria-label="Close modal" id="closeModalBtn">&times;</button>
    <h3 id="profileModalTitle">My Profile</h3>
    <form id="profileForm">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>" />

      <label for="full_name">Full Name</label>
      <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>" />

      <label for="email">Email</label>
      <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>" />

      <label for="ContactNumber">Contact Number</label>
      <input type="text" id="ContactNumber" name="ContactNumber" required value="<?php echo htmlspecialchars($user['ContactNumber']); ?>" />

      <label for="role">Role</label>
      <input type="text" id="role" name="role" readonly value="<?php echo htmlspecialchars($user['role']); ?>" />

      <button type="submit" class="save-btn">Save Changes</button>
      <div id="modalMessage" class="modal-message"></div>
    </form>
  </div>
</div>

<script>
  // Search functionality
  const searchInput = document.getElementById('searchInput');
  const searchBtn = document.getElementById('searchBtn');

  function performSearch(query) {
    if (!query.trim()) {
      alert('Please enter a search term');
      return;
    }
    window.location.href = `/HMS-main/views/nurse/search.php?query=${encodeURIComponent(query)}`;
  }

  searchInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      performSearch(this.value);
    }
  });

  searchBtn.addEventListener('click', function () {
    performSearch(searchInput.value);
  });

  // User dropdown toggle
  const userToggle = document.getElementById('userDropdownToggle');
  const userMenu = document.getElementById('userDropdownMenu');

  userToggle.addEventListener('click', function (e) {
    e.stopPropagation();
    userMenu.style.display = userMenu.style.display === 'block' ? 'none' : 'block';
  });

  document.addEventListener('click', function () {
    userMenu.style.display = 'none';
  });

  // Modal controls
  const openProfileBtn = document.getElementById('openProfileBtn');
  const profileModal = document.getElementById('profileModal');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const profileForm = document.getElementById('profileForm');
  const modalMessage = document.getElementById('modalMessage');

  openProfileBtn.addEventListener('click', () => {
    userMenu.style.display = 'none';
    profileModal.classList.add('show');
    profileModal.setAttribute('aria-hidden', 'false');
    document.getElementById('full_name').focus();
  });

  closeModalBtn.addEventListener('click', () => {
    profileModal.classList.remove('show');
    profileModal.setAttribute('aria-hidden', 'true');
    modalMessage.style.display = 'none';
    modalMessage.textContent = '';
  });

  profileForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const formData = new FormData(profileForm);

    fetch('/HMS-main/includes/update_profile.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        modalMessage.style.color = 'green';
        modalMessage.textContent = 'Profile updated successfully!';
        modalMessage.style.display = 'block';

        // Update header username immediately
        const userSpan = userToggle.querySelector('span');
        userSpan.childNodes[0].nodeValue = 'Nurse: ' + data.full_name + ' ';
      } else {
        modalMessage.style.color = 'red';
        modalMessage.textContent = data.message || 'Update failed.';
        modalMessage.style.display = 'block';
      }
    })
    .catch(() => {
      modalMessage.style.color = 'red';
      modalMessage.textContent = 'Network error.';
      modalMessage.style.display = 'block';
    });
  });
</script>

</body>
</html>
