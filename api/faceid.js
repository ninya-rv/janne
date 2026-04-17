const video = document.getElementById('video');
const statusDiv = document.getElementById('status');
const warningDiv = document.getElementById('warning');

let modelsLoaded = false;

// Load AI models
async function loadModels() {

    if (modelsLoaded) return;

    statusDiv.innerText = "Loading face models...";

    await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('models');

    modelsLoaded = true;

    statusDiv.innerText = "Ready to register face.";
}


// Start camera
async function startCamera() {

    const stream = await navigator.mediaDevices.getUserMedia({ video: true });

    video.srcObject = stream;
    video.style.display = "block";

    return new Promise(resolve => {
        video.onloadedmetadata = () => {
            resolve();
        };
    });
}


// Register student face
async function registerFace() {

    warningDiv.innerText="";

    const student_id = document.getElementById('student_id').value.trim();
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const year = document.getElementById('year').value;
    const section = document.getElementById('section').value;

    if (!student_id || !name || !email || !year || !section) {
        alert("⚠️ Please fill all fields first.");
        return;
    }

    const agree = document.getElementById("agree_terms");

    if(!agree.checked){
        warningDiv.innerText = "⚠️ You must agree to the Terms & Conditions before registering your face.";
        return;
    }

    try {

        await loadModels();
        await startCamera();

        statusDiv.innerText = "Look at the camera";

        await new Promise(r => setTimeout(r, 1000));

        const detection = await faceapi
            .detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {

            statusDiv.innerText = "❌ No face detected.";
            alert("❌ No face detected. Please try again.");

            return;
        }

        const descriptor = Array.from(detection.descriptor);

        statusDiv.innerText = "Uploading face data...";

        const response = await fetch("/backend/upload_face.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                student_id,
                name,
                email,
                year,
                section,
                descriptor
            })

        });

        const result = await response.json();

        if (result.success) {

            statusDiv.innerText = "✅ Student Registered Successfully";
            alert("✅ Face registered and saved to database!");

            document.getElementById("registrationForm").reset();

        } else if (result.type === "duplicate_id") {

            statusDiv.innerText = "⚠️ Student ID already registered!";
            alert("⚠️ This Student ID is already registered.");

        } else if (result.type === "duplicate_face") {

            statusDiv.innerText = "⚠️ This face is already registered!";
            alert("⚠️ This face already exists in the system.");

        } else {

            statusDiv.innerText = "❌ " + result.msg;
            alert("❌ Registration failed: " + result.msg);

        }

    } catch (error) {

        console.error(error);

        statusDiv.innerText = "❌ System error.";
        alert("❌ System error occurred.");

    }
}


// TERMS MODAL FUNCTIONS

function showTerms(){
document.getElementById("termsModal").style.display="block";
}

function closeTerms(){
document.getElementById("termsModal").style.display="none";
}


// CLEAR WARNING WHEN CHECKED

document.getElementById("agree_terms").addEventListener("change", function(){

warningDiv.innerText="";

});