const video = document.getElementById("video");
const statusDiv = document.getElementById("status");

let faceMatcher;
let modelsLoaded = false;

// ADD: prevent duplicate scans
let recentScans = {};

// ADD: receive selected class from scanner.php
function setSelectedClass(classData) {
    selectedClass = classData; // use selectedClass from scanner.php
    console.log("Selected class received:", selectedClass);
}

// Load Face API models
async function loadModels() {
    if (modelsLoaded) return;

    statusDiv.innerText = "Loading face models...";

    await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('models');

    modelsLoaded = true;
}

// Start webcam
async function startCamera() {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;

    return new Promise(resolve => {
        video.onloadedmetadata = () => resolve();
    });
}

// Load students from DB
async function loadStudentFaces() {
    statusDiv.innerText = "Loading registered students...";

    const res = await fetch("/backend/get_student.php");
    const students = await res.json();

    const labeledDescriptors = students.map(student => {
        // Ensure label is string: use "Name (ID)" format
        const label = `${student.name} ${student.student_id}`;

        // Ensure descriptor exists and is array
        const descriptor = Array.isArray(student.face_descriptor) 
            ? new Float32Array(student.face_descriptor) 
            : null;

        if (!descriptor) {
            console.warn("Skipping student without valid descriptor:", label);
            return null;
        }

        return new faceapi.LabeledFaceDescriptors(label, [descriptor]);
    }).filter(Boolean); // remove nulls

    if (labeledDescriptors.length === 0) {
        alert("⚠️ No student faces found in the database!");
        statusDiv.innerText = "No student faces loaded";
        return;
    }

    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
}

// ADD: find full student data using matched label
async function getStudentByLabel(label) {
    try {
        const res = await fetch("/backend/get_student.php");
        const students = await res.json();

        return students.find(student => {
            const studentLabel = `${student.name} ${student.student_id}`;
            return studentLabel === label;
        }) || null;
    } catch (error) {
        console.error("Error finding student by label:", error);
        return null;
    }
}

// ADD: save attendance to backend
async function saveAttendance(studentData) {
    if (!selectedClass) {
        console.warn("No class selected yet.");
        statusDiv.innerText = "⚠️ Please select class first.";
        return;
    }

    try {
        const response = await fetch("/backend/save_attendance.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
            student_id: studentData.student_id,
            name: studentData.name,
            email: studentData.email,
            subject: selectedClass.subject,
            year_level: selectedClass.year_level,
            section: selectedClass.section,
            assignment_id: selectedClass.assignment_id,
            start_time: selectedClass.start_time,
            end_time: selectedClass.end_time,
            mode: scanMode
        })
        });

        const result = await response.json();
        console.log("Attendance save result:", result);

        if (result.success) {
            statusDiv.innerText = `✅ ${studentData.name} - ${result.message}`;
        } else {
            statusDiv.innerText = `⚠️ ${result.message}`;
        }

    } catch (error) {
        console.error("Error saving attendance:", error);
        statusDiv.innerText = "❌ Failed to save attendance";
    }
}

// Start face recognition
function startRecognition() {
    statusDiv.innerText = "Scanner ready.";

    setInterval(async () => {
        const detections = await faceapi
            .detectAllFaces(video)
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length === 0) {
            statusDiv.innerText = "No face detected";
            return;
        }

        for (const detection of detections) {
            const match = faceMatcher.findBestMatch(detection.descriptor);

            if (match.label !== "unknown") {
                statusDiv.innerText = `✅ ${match.label}`;

                const now = Date.now();

                // Prevent duplicate save within 10 seconds
                if (!recentScans[match.label] || (now - recentScans[match.label] > 10000)) {
                    recentScans[match.label] = now;

                    const student = await getStudentByLabel(match.label);
                    if (student) {
                        await saveAttendance(student);
                    } else {
                        console.warn("Matched student not found in get_student.php");
                    }
                }

            } else {
                statusDiv.innerText = "❌ Unknown face detected";
            }
        }
    }, 1000);
}

// Initialize scanner
async function init() {
    await loadModels();
    await startCamera();
    await loadStudentFaces();

    if (faceMatcher) {
        startRecognition();
    }
}

init();