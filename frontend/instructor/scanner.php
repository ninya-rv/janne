<?php
session_start();
include "../../backend/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../sign_in.html");
    exit;
}

// Redirect if not instructor
if ($_SESSION['role'] !== 'instructor') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

// Get logged-in instructor details
$instructorQuery = "SELECT name, email FROM users WHERE id = '$instructor_id' AND role = 'instructor' LIMIT 1";
$instructorResult = mysqli_query($conn, $instructorQuery);

if ($instructorResult && mysqli_num_rows($instructorResult) > 0) {
    $instructorData = mysqli_fetch_assoc($instructorResult);
    $instructorName = $instructorData['name'];
    $instructorEmail = $instructorData['email'];

    // Create initials
    $nameParts = explode(" ", trim($instructorName));
    $initials = "";
    foreach ($nameParts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) break;
    }
} else {
    $instructorName = "Instructor";
    $instructorEmail = "No email found";
    $initials = "IN";
}

// Use instructor name for class assignments
$instructor_name = $instructorName;

// Get all assignments for this instructor
$classQuery = "SELECT * FROM instructor_assignment 
               WHERE instructor_name = '$instructor_name'
               ORDER BY start_time ASC";

$classResult = mysqli_query($conn, $classQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EVSU-BSIT Dashboard - Face Scanner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">

    <!-- Load Face API -->
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script defer src="/api/instructorScanner.js"></script>

    <style>
        #video {
            width: 400px;
            height: 300px;
            border: 2px solid #8B0000;
            border-radius: 8px;
            background: #000;
        }

        #status {
            margin-top: 10px;
            font-weight: bold;
            color: #8B0000;
        }

        #classSelect {
            padding: 10px;
            width: 100%;
            max-width: 700px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        #scannerArea {
            display: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png" alt="EVSU Logo">
        <h2>EVSU-BSIT</h2>
    </div>
    <div class="profile-wrapper">
        <div class="profile" id="profileBtn"><?php echo htmlspecialchars($initials); ?></div>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-header">
                <div class="profile-circle"><?php echo htmlspecialchars($initials); ?></div>
                <br>
                <h4><?php echo htmlspecialchars($instructorName); ?></h4>
                <p><?php echo htmlspecialchars($instructorEmail); ?></p>
                <span class="badge">INSTRUCTOR</span>
            </div>
            

            <div class="profile-actions">
                <a href="../sign_in.html">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <ul>
            <li>
                <a href="/frontend/instructor/dashboard.php">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/frontend/instructor/attendance.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li>
                <a href="/frontend/instructor/scanner.php" class="active">
                    <i class="fa-solid fa-camera"></i>
                    <span>Face Scanner</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <h3>Face Scanner</h3>

        <div class="student-section">
            <h4>Select Class Assignment</h4>
            <br>

            <select id="classSelect" required disabled>
                <option value="">Select your class assignment</option>

                <?php if ($classResult && mysqli_num_rows($classResult) > 0): ?>
                    <?php while ($class = mysqli_fetch_assoc($classResult)): ?>
                        <option 
                            value="<?php echo $class['id']; ?>"
                            data-year="<?php echo htmlspecialchars($class['year_level']); ?>"
                            data-section="<?php echo htmlspecialchars($class['section']); ?>"
                            data-subject="<?php echo htmlspecialchars($class['subject']); ?>"
                            data-start="<?php echo htmlspecialchars($class['start_time']); ?>"
                            data-end="<?php echo htmlspecialchars($class['end_time']); ?>"
                        >
                            <?php 
                            echo htmlspecialchars($class['subject']) . " | " . 
                                 htmlspecialchars($class['year_level']) . " | Section " . 
                                 htmlspecialchars($class['section']) . " | " . 
                                 date("g:i A", strtotime($class['start_time'])) . " - " . 
                                 date("g:i A", strtotime($class['end_time']));
                            ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="">No class assignments found</option>
                <?php endif; ?>
            </select>

            <button id="startScannerBtn" class="assign-btn" type="button" disabled>Open Scanner</button>
            <div style="margin-bottom: 10px;">
                <button id="timeInBtn">Time In</button>
                <button id="timeOutBtn">Time Out</button>
            </div>
            <div id="scannerArea">
                <video id="video" autoplay muted></video>
                <div id="status">Scanner ready...</div>
            </div>
        </div>
    </main>
</div>

<script>
let selectedClass = null;
let scanMode = null;

// =========================
// DISABLE CLASS FIRST
// =========================
const classSelect = document.getElementById("classSelect");
const startBtn = document.getElementById("startScannerBtn");

classSelect.disabled = true;
startBtn.disabled = true;

// =========================
// TIME BUTTONS FIRST
// =========================
document.getElementById("timeInBtn").addEventListener("click", () => {
    scanMode = "time_in";
    document.getElementById("status").innerText = "Mode: Time In selected";

    classSelect.disabled = false;
});

document.getElementById("timeOutBtn").addEventListener("click", () => {
    scanMode = "time_out";
    document.getElementById("status").innerText = "Mode: Time Out selected";

    classSelect.disabled = false;
});

// =========================
// PH TIME FUNCTION
// =========================
function getPHTime() {
    const now = new Date();
    return new Date(now.toLocaleString("en-US", { timeZone: "Asia/Manila" }));
}

// =========================
// BLOCK PAST CLASS
// =========================
classSelect.addEventListener("change", () => {
    const option = classSelect.options[classSelect.selectedIndex];

    if (!option.value) return;

    const phNow = getPHTime();

    const endTime = option.dataset.end; // HH:MM:SS
    const today = phNow.toISOString().split("T")[0];

    const classEnd = new Date(`${today}T${endTime}`);

    if (phNow > classEnd) {
        alert("❌ This class already ended. You cannot select it.");
        classSelect.value = "";
        startBtn.disabled = true;
        return;
    }

    if (scanMode) {
        startBtn.disabled = false;
    }
});

// =========================
// START SCANNER
// =========================
document.getElementById("startScannerBtn").addEventListener("click", function () {
    if (!scanMode) {
        alert("Please select Time In or Time Out first.");
        return;
    }

    const select = document.getElementById("classSelect");
    const option = select.options[select.selectedIndex];

    if (!select.value || option.text === "No class assignments found") {
        alert("Please select your class first.");
        return;
    }

    selectedClass = {
        assignment_id: select.value,
        year_level: option.dataset.year,
        section: option.dataset.section,
        subject: option.dataset.subject,
        start_time: option.dataset.start,
        end_time: option.dataset.end,
        mode: () => scanMode
    };

    document.getElementById("scannerArea").style.display = "block";
    document.getElementById("status").innerText =
        "Scanner ready (" + scanMode.replace("_", " ") + ") - " + selectedClass.subject;

    if (typeof setSelectedClass === "function") {
        setSelectedClass(selectedClass);
    } else {
        console.error("setSelectedClass() is not found.");
    }
});

// =========================
// PROFILE DROPDOWN
// =========================
const profileBtn = document.getElementById("profileBtn");
const dropdown = document.getElementById("profileDropdown");

profileBtn.addEventListener("click", () => {
    dropdown.style.display =
        dropdown.style.display === "block" ? "none" : "block";
});

document.addEventListener("click", function (e) {
    if (!profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = "none";
    }
});
</script>

</body>
</html>