<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');

// Handle Add Location with Condition per Floor
if (isset($_POST['add_location'])) {
    $roomType = $_POST['RoomType'];
    $roomCapacity = $_POST['RoomCapacity'];
    $building = strtoupper($_POST['Building']);
    $floor = $_POST['Floor'];
    $roomNumber = $_POST['RoomNumber'];
    $locationName = "Building $building, Floor $floor, Room $roomNumber";

    // Assign condition based on Building and Floor
    $conditionMap = [
        'A' => [1 => 'General', 2 => 'Orthopedic', 3 => 'Maternity'],
        'B' => [1 => 'Pediatrics', 2 => 'ICU', 3 => 'Surgery'],
        'C' => [1 => 'Emergency', 2 => 'Cardiology', 3 => 'Neurology']
    ];

    $condition = isset($conditionMap[$building][$floor]) ? $conditionMap[$building][$floor] : 'General';

    $stmt = $conn->prepare("INSERT INTO locations (RoomType, RoomCapacity, Building, Floor, RoomNumber, LocationName, Condition) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sississ", $roomType, $roomCapacity, $building, $floor, $roomNumber, $locationName, $condition);
    $stmt->execute();
    header("Location: location.php");
    exit();
}

// Handle Delete Location
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM locations WHERE LocationID = $id");
    header("Location: location.php");
    exit();
}

// Handle Edit Location
if (isset($_POST['edit_location'])) {
    $locationID = intval($_POST['LocationID']);
    $roomType = $_POST['RoomType'];
    $roomCapacity = $_POST['RoomCapacity'];
    $building = strtoupper($_POST['Building']);
    $floor = $_POST['Floor'];
    $roomNumber = $_POST['RoomNumber'];
    $locationName = "Building $building, Floor $floor, Room $roomNumber";

    // Recalculate condition
    $conditionMap = [
        'A' => [1 => 'General', 2 => 'Orthopedic', 3 => 'Maternity'],
        'B' => [1 => 'Pediatrics', 2 => 'ICU', 3 => 'Surgery'],
        'C' => [1 => 'Emergency', 2 => 'Cardiology', 3 => 'Neurology']
    ];
    $condition = isset($conditionMap[$building][$floor]) ? $conditionMap[$building][$floor] : 'General';

    $stmt = $conn->prepare("UPDATE locations SET RoomType=?, RoomCapacity=?, Building=?, Floor=?, RoomNumber=?, LocationName=?, Condition=? WHERE LocationID=?");
    $stmt->bind_param("sississi", $roomType, $roomCapacity, $building, $floor, $roomNumber, $locationName, $condition, $locationID);
    $stmt->execute();
    header("Location: location.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Location Management - Map Layout</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
     body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }
    .content {
        padding: 40px;
    }
    .form-container {
        max-width: 600px;
        margin: 20px auto;
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
    }
    /* Form inputs & selects full width by default for mobile */
    .form-container input,
    .form-container select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
    }
    .form-container button {
        width: 100%;
        padding: 10px;
        background-color:rgb(125, 129, 125);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 0.8rem;
        margin-top: 15px;
        border-radius: 4px;
    }
    .form-container button:hover {
            background-color: #45a049;
        }

        .form-row-1,
    .form-row-2 {
        display: flex;
        gap: 20px;
        margin-bottom: 10px;
    }

    .form-row-1 select,
    .form-row-2 .form-group input[type=number] {
        flex: 1;              
        width: 100%;           
        padding: 12px 14px;    
        font-size: 1.0rem;     
        height: 45px;          
        border-radius: 6px;    
        border: 1.5px solid #bbb;
        box-sizing: border-box;
        transition: border-color 0.2s ease;
    }

    .form-row-1 select:focus,
    .form-row-2 .form-group input[type=number]:focus {
        border-color: #4CAF50;
        outline: none;
    }
    .form-group-column {
    display: flex;
    flex-direction: column;
    flex: 1;  /* equal width for each column */
    gap: 6px; /* spacing between label and select */
    }

    /* Map layout styles */
    .building {
        margin-bottom: 40px;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 8px;
        background: #fff;
    }
    .building h2 {
        margin-top: 0;
        border-bottom: 1px solid #ccc;
        padding-bottom: 5px;
    }
    .floor {
        margin: 20px 0;
    }
    .floor h3 {
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #ddd;
        padding-bottom: 3px;
    }
    /* Display rooms in grid: 5 per row */
    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 15px;
    }
    .room {
        min-height: 110px;
        border-radius: 8px;
        padding: 10px;
        color: white;
        cursor: pointer;
        box-shadow: 0 0 6px rgba(0,0,0,0.1);
        user-select: none;
        transition: transform 0.15s ease-in-out;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        font-family: Arial, sans-serif;
    }
    .room:hover {
        transform: scale(1.05);
    }
    .available { background-color: #28a745; } /* green */
    .full { background-color: #dc3545; }      /* red */
    .inactive { background-color: #6c757d; }  /* gray */

    .room .room-number {
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    .room .room-info {
        font-size: 0.9rem;
        line-height: 1.3;
    }

    /* Modal styles */
    .edit-modal {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 10px;
        overflow-y: auto;
    }
      .edit-modal[aria-hidden="false"] {
        display: flex;
    }
    .edit-modal-content {
        background: #fff;
        border-radius: 8px;
        max-width: 600px;
        width: 100%;
        padding: 30px 40px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        position: relative;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

     .modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        border: none;
        font-size: 28px;
        color: #666;
        cursor: pointer;
        transition: color 0.2s ease-in-out;
    }
    .modal-close:hover {
        color: red;
    }
    .room.available {
    background-color: #4CAF50; /* Green for available */
    }
    .room.full {
        background-color: #e74c3c; /* Red for full */
    }
    .room.inactive {
        background-color: #95a5a6; /* Gray for inactive */
    }

</style>
</head>
<body>

<div class="content">
    <h2>Location Management</h2>

    <!-- Add Location Form -->
    <div class="form-container">
        <form method="post" action="">
            <div class="form-row-1">
                <select name="RoomType" required>
                    <option value="" disabled selected>Select Room Type</option>
                    <option value="Ward">Ward</option>
                    <option value="Private">Private</option>
                    <option value="Semi-Private">Semi-Private</option>
                </select>

                <select name="Building" required>
                    <option value="" disabled selected>Select Building</option>
                    <option value="A">Building A</option>
                    <option value="B">Building B</option>
                    <option value="C">Building C</option>
                </select>
            </div>

            <div class="form-row-2">
                <select name="Floor" required>
                    <option value="" disabled selected>Select Floor</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>


                <select name="RoomNumber" required>
                    <option value="" disabled selected>Select Room Number</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>


                <input type="number" name="RoomCapacity" placeholder="Enter Room Capacity" min="1" required>
            </div>

            <button type="submit" name="add_location">Add Location</button>
        </form>
    </div>

    <!-- Locations Map Layout -->
    <?php
    // Fetch all locations grouped by Building and Floor
    $locations = [];
    $result = $conn->query("SELECT * FROM locations ORDER BY Building, Floor, RoomNumber");

    while ($row = $result->fetch_assoc()) {
        $locations[$row['Building']][$row['Floor']][] = $row;
    }

    if (empty($locations)) {
        echo "<p>No locations found.</p>";
    } else {
        foreach ($locations as $building => $floors) {
            echo "<div class='building'>";
            echo "<h2>Building: " . htmlspecialchars($building) . "</h2>";

            foreach ($floors as $floor => $rooms) {
                echo "<div class='floor'>";
                echo "<h3>Floor: " . htmlspecialchars($floor) . "</h3>";
                echo "<div class='rooms-grid'>";

                foreach ($rooms as $room) {
                    $locationID = $room['LocationID'];
                    $capacity = $room['RoomCapacity'];

                    // Count occupied beds
                    $sqlCount = "SELECT COUNT(*) as count FROM inpatients WHERE LocationID = $locationID";
                    $resCount = $conn->query($sqlCount);
                    $count = $resCount->fetch_assoc()['count'];

                    $availableSpots = $capacity - $count;
                    $statusClass = 'available';

                    if ($availableSpots <= 0) {
                        $statusClass = 'full';
                    }

                    // If RoomType is inactive (assume stored in DB), can add inactive class. Here, we assume always active.
                    // Build room block
                    echo "<div class='room $statusClass' data-id='{$locationID}' data-roomtype='" . htmlspecialchars($room['RoomType']) . "' data-capacity='{$capacity}' data-building='" . htmlspecialchars($room['Building']) . "' data-floor='{$floor}' data-roomnumber='{$room['RoomNumber']}'>";
                    echo "<div class='room-number'>Room {$room['RoomNumber']}</div>";
                    echo "<div class='room-info'>";
                    echo "Type: " . htmlspecialchars($room['RoomType']) . "<br>";
                    echo "Capacity: {$capacity}<br>";
                    echo "Occupied: {$count}<br>";
                    echo "Available: {$availableSpots}";
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div></div>";
            }

            echo "</div>";
        }
    }
    ?>

</div>

<!-- Edit Location Modal -->
<div class="edit-modal" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle" tabindex="-1">
    <div class="edit-modal-content" role="document">
        <button class="modal-close" id="modalClose" aria-label="Close edit form">&times;</button>
        <h2 id="editModalTitle">Edit Location</h2>
        <form method="post" action="" id="editLocationForm" novalidate>
            <input type="hidden" name="LocationID" id="editLocationID">

           <div class="form-row-1">
                <div class="form-group-column">
                    <label for="editRoomType">Room Type</label>
                    <select name="RoomType" id="editRoomType" required>
                    <option value="" disabled>Select Room Type</option>
                    <option value="Ward">Ward</option>
                    <option value="Private">Private</option>
                    <option value="Semi-Private">Semi-Private</option>
                    </select>
                </div>

                <div class="form-group-column">
                    <label for="editBuilding">Building</label>
                    <select name="Building" id="editBuilding" required>
                    <option value="" disabled>Select Building</option>
                    <option value="A">Building A</option>
                    <option value="B">Building B</option>
                    <option value="C">Building C</option>
                    </select>
                </div>
                </div>


            <div class="form-row-2">
                <div class="form-group">
                    <label for="editFloor">Floor</label>
                    <input type="number" name="Floor" id="editFloor" min="1" max="3" placeholder="1 - 3" required>
                </div>
                <div class="form-group">
                    <label for="editRoomNumber">Room Number</label>
                    <input type="number" name="RoomNumber" id="editRoomNumber" min="1" placeholder="Room Number" required>
                </div>
                <div class="form-group">
                    <label for="editRoomCapacity">Room Capacity</label>
                    <input type="number" name="RoomCapacity" id="editRoomCapacity" min="1" placeholder="Capacity" required>
                </div>
            </div>

            <button type="submit" name="edit_location" class="btn-primary" style="margin-top: 20px;">Save Changes</button>
        </form>
    </div>
</div>

<script>
    // Handle room click to open edit modal
    document.querySelectorAll('.room').forEach(room => {
        room.addEventListener('click', () => {
            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';

            document.getElementById('editLocationID').value = room.dataset.id;
            document.getElementById('editRoomType').value = room.dataset.roomtype;
            document.getElementById('editBuilding').value = room.dataset.building;
            document.getElementById('editFloor').value = room.dataset.floor;
            document.getElementById('editRoomNumber').value = room.dataset.roomnumber;
            document.getElementById('editRoomCapacity').value = room.dataset.capacity;
        });
    });

    // Close modal handler
    document.getElementById('modalClose').addEventListener('click', () => {
        document.getElementById('editModal').style.display = 'none';
    });

    // Close modal when clicking outside modal content
    window.addEventListener('click', e => {
        const modal = document.getElementById('editModal');
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
</script>

<script>
    const roomTypeSelect = document.querySelector('select[name="RoomType"]');
    const capacityInput = document.querySelector('input[name="RoomCapacity"]');

    const capacityMap = {
        "Ward": 4,
        "Semi-Private": 2,
        "Private": 1
    };

    roomTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        if (capacityMap[selectedType]) {
            capacityInput.value = capacityMap[selectedType];
            capacityInput.readOnly = true;
        } else {
            capacityInput.value = '';
            capacityInput.readOnly = false;
        }
    });
</script>


</body>
</html>
