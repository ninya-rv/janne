<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /frontend/sign_in.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EVSU-BSIT Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="logo-title">
        <img src="/css/EVSU_Official_Logo.png" alt="EVSU Logo">
        <h2>EVSU-BSIT</h2>
    </div>
   <div class="profile-wrapper">
        <div class="profile" id="profileBtn">SA</div>

        <div class="profile-dropdown" id="profileDropdown">

            <div class="profile-header">
                <div class="profile-circle">SA</div>
                <br>
                <h4>System Administrator</h4>
                <p>evsuoccadmin@gmail.com</p>
                <span class="badge">ADMINISTRATOR</span>
            </div>

            <div class="profile-actions">
                <a href="../sign_in.html">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>

</div>
</header>

<div class="container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <ul>
             <li>
                <a href="/frontend/admin/dashboard.php" class="active">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="attendance.html" class="active">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>

            <li>
                <a href="users.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Users</span>
                </a>
            </li>

            <li>
                <a href="instructor_assignment.php">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Instructor Assignment</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <h3>Dashboard</h3>

        <!-- Cards -->
        <div class="cards">
            <div class="card">
                <h4>Total Students</h4>
                <p>100</p>
            </div>
            <div class="card">
                <h4>Total Teachers</h4>
                <p>15</p>
            </div>
            <div class="card">
                <h4>Active Students</h4>
                <p>39</p>
            </div>
            <div class="card">
                <h4>Inactive Students</h4>
                <p>61</p>
            </div>
        </div>

        <!-- Student Section -->
        <section class="student-section">
            <h4>Student List</h4>
            <br>
            <div class="search-filter">
                <input type="text" placeholder="Search students...">

                <button class="filter-btn" id="filterToggle">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>

            <!-- FILTER PANEL (Placed BELOW search-filter) -->
            <div class="filter-panel" id="filterPanel">
                <div class="filter-grid">

                    <!-- Course Filter -->
                    <div class="filter-group">
                        <label>Course</label>
                        <select id="filterCourse">
                            <option value="">All</option>
                            <option value="BSIT">BSIT</option>
                            <!-- Only courses present in table -->
                        </select>
                    </div>

                    <!-- Year Level Filter -->
                    <div class="filter-group">
                        <label>Year Level</label>
                        <select id="filterYear">
                            <option value="">All</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <!-- Only years present in table -->
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label>Status</label>
                        <select id="filterStatus">
                            <option value="">All</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Optional: Date Filter -->
                    <div class="filter-group">
                        <label>Date</label>
                        <input type="date" id="filterDate">
                    </div>

                </div>
            </div>

            <table class="student-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2020-001</td>
                        <td>Juan Dela Cruz</td>
                        <td>juan.delacruz@evsu.edu.ph</td>
                        <td>BSIT</td>
                        <td>2nd Year</td>
                        <td>Active</td>
                    </tr>
                    <tr>
                        <td>2020-002</td>
                        <td>Maria Santos</td>
                        <td>maria.santos@evsu.edu.ph</td>
                        <td>BSIT</td>
                        <td>3rd Year</td>
                        <td>Inactive</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

</div>
</body>
    <script src="/backend/script.js"></script>
    <script>
        const profileBtn = document.getElementById("profileBtn");
        const dropdown = document.getElementById("profileDropdown");

        profileBtn.addEventListener("click", () => {
            dropdown.style.display =
                dropdown.style.display === "block" ? "none" : "block";
        });

        // close when clicking outside
        document.addEventListener("click", function(e){
            if(!profileBtn.contains(e.target) && !dropdown.contains(e.target)){
                dropdown.style.display = "none";
            }
        });
    </script>
</html>