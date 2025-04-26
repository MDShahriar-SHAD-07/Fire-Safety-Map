// Initialize the map with Faridabad coordinates
const map = L.map("map").setView([23.7024, 90.4129], 18);

// Add OpenStreetMap tiles
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap",
}).addTo(map);

// Access user's location
// Fetch user_id from localStorage or session (modify based on how you store it)
// Fetch user_id from localStorage (ensure it's set elsewhere in your app)
// Fetch user_id from localStorage (ensure it's set elsewhere in your app)
const UserId = localStorage.getItem("User_id");

if (!UserId) {
    console.error("User ID not found in localStorage.");
} else {
    console.log("User ID found:", UserId);
    // Call the nearest fire station update function
    fetchNearestFireStation(UserId);
}

// Access user's location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;

            // Set the map view to the user's location
            map.setView([latitude, longitude], 18);

            // Add a marker for the user's location
            const userMarker = L.marker([latitude, longitude]).addTo(map);
            userMarker.bindPopup("আমার অবস্থান এখানে!").openPopup();

            // Send the coordinates to the backend to update the user's location
            fetch("update_user_location.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    User_id: UserId, // Send correct parameter name
                    latitude: latitude,
                    longitude: longitude,
                }),
            })
                .then((response) => response.json()) // Parse JSON response
                .then((data) => {
                    if (data.success) {
                        console.log(data.message); // Success message
                    } else {
                        console.error(data.message); // Error message
                    }
                })
                .catch((error) => console.error("Error:", error));
        },
        (error) => {
            console.error("Unable to access location. Error:", error.message);
            map.setView([23.7024, 90.4129], 18); // Default to Faridabad if geolocation fails
        }
    );
} else {
    console.error("Geolocation is not supported by this browser.");
    map.setView([23.7024, 90.4129], 18); // Default to Faridabad if geolocation is unsupported
}

fetch("get_roads.php")
    .then((response) => response.json())
    .then((roads) => {
        roads.forEach((road) => {
            const { Midpoint, Road_Name, Road_ID } = road; // Extract Road_ID and Road_Name

            // Add a stylish red dot at the midpoint
            const marker = L.marker([Midpoint.lat, Midpoint.lng], {
                icon: L.divIcon({
                    className: "stylish-red-dot",
                    iconSize: [16, 16], // Adjust size
                }),
            }).addTo(map);

            // Add a popup with the road name and button
            const popupContent = `
                <div>
                    <strong>${Road_Name}</strong>
                    <br />
                    <button class="popup-btn" onclick="openSidebar(${Road_ID}, '${Road_Name}')">Road Status</button>
                </div>
            `;
            marker.bindPopup(popupContent);

            // Optionally open the popup by default (comment out if not needed)
            // marker.openPopup();
        });
    })
    .catch((error) => console.error("Error fetching roads:", error));


// Custom CSS for stylish red dots
const style = document.createElement("style");
style.innerHTML = `
    .stylish-red-dot {
        background-color: red;
        border: 2px solid white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        transform: translate(-8px, -8px); /* Center the dot */
        box-shadow: 0 0 8px rgba(255, 0, 0, 0.7);
        cursor: pointer;
    }
    .stylish-red-dot:hover {
        background-color: darkred;
        transform: translate(-8px, -8px) scale(1.2); /* Add hover animation */
        transition: 0.3s;
    }
    .popup-btn {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 14px;
    }
    .popup-btn:hover {
        background-color: #0056b3;
    }
`;
document.head.appendChild(style);



function openSidebar(roadId, roadName) {
    const sidebar = document.getElementById("road-status-sidebar");
    const title = document.getElementById("road-status-title");

    // Update the sidebar title with both road name and id
    title.textContent = `${roadName} (ID: ${roadId}) - রাস্তার অবস্থা আপডেট করুন`;

    sidebar.classList.add("visible");

    // Store roadId globally to use it later for saving votes
    window.currentRoadId = roadId;

    // Set the hidden input field for road ID
    document.getElementById("road_id").value = roadId;

    // Reset the form when opening the sidebar
    document.getElementById("road-status-form").reset();
    resetVoteSummary();
}

document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("close-sidebar").addEventListener("click", function () {
        console.log("Close button clicked!");
        const sidebar = document.getElementById("road-status-sidebar");
        sidebar.classList.add("hidden");
    });
});


// Function to reset vote summary progress bars
function resetVoteSummary() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(progressBar => {
        progressBar.style.width = "0%"; // Reset the progress bars
    });
}


// Listen for change events on the radio buttons
document.querySelectorAll('input[type="radio"]').forEach((radio) => {
    radio.addEventListener("change", function () {
        const roadId = document.getElementById("road_id").value; // Get the Road ID from the hidden input
        const question = this.name; // The name of the question (e.g., "busy_all_time")
        const answer = this.value; // The selected value ("yes", "no", "sometimes")

        // Send the data to the server using AJAX
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "update_votes.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        // Prepare the data to be sent
        const data = {
            road_id: roadId,
            question: question,
            answer: answer
        };

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === "success") {
                        alert("ভোট সফলভাবে আপডেট হয়েছে!");
                        // Optionally, update progress bars or other UI elements based on server response
                        updateProgressBars(response.data); // Assuming the server sends updated data
                    } else {
                        console.error("Error from server:", response.message);
                    }
                } catch (e) {
                    console.error("Parsing error:", e);
                }
            } else {
                console.error("Failed to update vote:", xhr.statusText);
            }
        };

        xhr.onerror = function () {
            console.error("Network error occurred.");
        };

        xhr.send(JSON.stringify(data)); // Send data to the server
    });
});

// Function to update progress bars (optional)
function updateProgressBars(data) {
    // Assuming the server response includes updated vote percentages
    document.getElementById("busy-progress").style.width = `${data.busy_all_time_percentage}%`;
    document.getElementById("narrow-progress").style.width = `${data.too_narrow_percentage}%`;
    document.getElementById("truck-progress").style.width = `${data.heavy_truck_percentage}%`;
    document.getElementById("construction-progress").style.width = `${data.under_construction_percentage}%`;
}






// Fire stations array (populated dynamically)
let fireStations = [];

// Fetch fire stations from the server
fetch("get_fire_stations.php")
    .then((response) => {
        if (!response.ok) {
            throw new Error("Network response was not ok");
        }
        return response.json();
    })
    .then((data) => {
        fireStations = data; // Store fire station data
        console.log("Fire Stations:", fireStations);
    })
    .catch((error) => {
        console.error("Error fetching fire stations:", error);
    });

// Add "Find Nearest Fire Station" button
const nearestStationButton = L.control({ position: "topright" });

const fireStationIcon = L.icon({
    iconUrl: "images/fire_station_icon.png",
    iconSize: [32, 32], // Size of the icon
    iconAnchor: [16, 32], // Point of the icon which will correspond to marker's location
    popupAnchor: [0, -32], // Point where the popup should open relative to the iconAnchor
});

nearestStationButton.onAdd = function () {
    const btn = L.DomUtil.create(
        "button",
        "leaflet-bar leaflet-control leaflet-control-custom"
    );
    btn.innerHTML =
        '<img src="https://cdn-icons-png.flaticon.com/512/68/68587.png" alt="Find Nearest" style="width:24px; height:24px;">';
    btn.title = "নিকটতম ফায়ার স্টেশন খুঁজুন";
    btn.style.cursor = "pointer";

    L.DomEvent.on(btn, "click", function () {
        if (!navigator.geolocation) {
            alert("আপনার ব্রাউজার জিওলোকেশন সাপোর্ট করে না।");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                console.log("User Location:", { lat: userLat, lng: userLng });

                if (fireStations.length === 0) {
                    alert("ফায়ার স্টেশনের তথ্য পাওয়া যায়নি।");
                    return;
                }

                let nearestStation = null;
                let minDistance = Infinity;

                fireStations.forEach((station) => {
                    console.log("Station:", station);

                    const distance = map.distance(
                        [userLat, userLng],
                        [parseFloat(station.lat), parseFloat(station.lng)]
                    );
                    console.log(`Distance to ${station.name}: ${distance}`);

                    if (distance < minDistance) {
                        minDistance = distance;
                        nearestStation = station;
                    }
                });

                if (nearestStation) {
                    console.log("Nearest Station:", nearestStation);

                    const marker = L.marker(
                        [
                            parseFloat(nearestStation.lat),
                            parseFloat(nearestStation.lng),
                        ],
                        { icon: fireStationIcon }
                    ).addTo(map);

                    marker
                        .bindPopup(
                            `
                           <b>${nearestStation.name}</b><br>
                           ঠিকানা: ${nearestStation.address}<br>
                           দায়িত্বপ্রাপ্ত কর্মকর্তা: ${nearestStation.fire_officer}<br>
                           যোদ্ধাদের সংখ্যা: ${nearestStation.num_of_fighters}<br>
                           ফোন: ${nearestStation.phone}<br>
                           <button onclick="alert('কল করুন: ${nearestStation.phone}')">কল করুন</button>
                       `
                        )
                        .openPopup();

                    map.setView(
                        [
                            parseFloat(nearestStation.lat),
                            parseFloat(nearestStation.lng),
                        ],
                        15
                    );
                } else {
                    console.log("No nearest station found.");
                    alert("নিকটতম ফায়ার স্টেশন পাওয়া যায়নি।");
                }
            },
            function (error) {
                console.error("Geolocation error:", error);
                alert("আপনার অবস্থান পাওয়া যায়নি।");
            }
        );
    });

    return btn;
};

nearestStationButton.addTo(map);

// Show the modal
document
    .getElementById("report-fire-btn")
    .addEventListener("click", () => {
        document
            .getElementById("fire-report-modal")
            .classList.remove("hidden");
        document.getElementById("fire-report-modal").classList.add("visible");
    });

// Close the modal
document
    .getElementById("close-modal-btn")
    .addEventListener("click", () => {
        document.getElementById("fire-report-modal").classList.add("hidden");
        document
            .getElementById("fire-report-modal")
            .classList.remove("visible");
    });


// Function to call `update_nearest_station.php`
function fetchNearestFireStation(userId) {
    fetch("update_nearest_station.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ User_id: userId }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log(
                    `Nearest fire station updated successfully: Station ID ${data.Nearest_Station_ID}, Distance: ${data.Distance}`
                );
            } else {
                console.error("Error updating nearest fire station:", data.message);
            }
        })
        .catch((error) => {
            console.error("Error:", error);
        });
}

// Navigation buttons
document.getElementById("spectator-btn").addEventListener("click", () => {
    fetch("add_spectator.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Check the data object for Spec_ID before setting it in localStorage
                console.log("Data received from server:", data); // Debug log

                // Set Spec_ID in localStorage
                if (data.Spec_ID) {
                    localStorage.setItem("Spec_ID", data.Spec_ID);
                    console.log("Spec_ID saved to localStorage:", data.Spec_ID); // Debug log
                    alert("Record successfully added.");
                    window.location.href = "spectator.html";
                } else {
                    alert("Spec_ID was not returned.");
                }
            } else {
                alert(data.message || "An error occurred.");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            alert("An error occurred while processing your request.");
        });
});




// Victim button functionality
document.getElementById("victim-btn").addEventListener("click", () => {
    fetch("add_victim.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Check the data object for Vic_ID before setting it in localStorage
                console.log("Data received from server:", data); // Debug log

                // Set Vic_ID in localStorage
                if (data.Vic_ID) {
                    localStorage.setItem("Vic_ID", data.Vic_ID);
                    console.log("Vic_ID saved to localStorage:", data.Vic_ID); // Debug log
                    alert("Record successfully added.");
                    window.location.href = "victim.html";
                } else {
                    alert("Vic_ID was not returned.");
                }
            } else {
                alert(data.message || "An error occurred.");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            alert("An error occurred while processing your request.");
        });
});






// Initialize drawn items and add them to the map
var drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

var drawControl = new L.Control.Draw({
    edit: { featureGroup: drawnItems },
    draw: {
        polyline: true,
        polygon: false,
        rectangle: false,
        circle: false,
        marker: false,
    },
});
map.addControl(drawControl);

map.on("draw:created", function (e) {
    const layer = e.layer;
    drawnItems.addLayer(layer);

    const roadCoordinates = layer.getLatLngs(); // Get coordinates as LatLng array
    const roadName = prompt("Enter road name:");

    if (!roadName) {
        alert("Road name required!");
        return;
    }

    // Question sequence
    const questions = [
        {
            question: "রাস্তা সব সময় ব্যস্ত?",
            options: ["yes", "no", "sometimes"],
        },
        { question: "রাস্তা খুব সরু?", options: ["yes", "no"] },
        {
            question: "ভারী ট্রাক এই রাস্তা দিয়ে যেতে পারে?",
            options: ["yes", "no"],
        },
        { question: "রাস্তা নির্মাণাধীন?", options: ["yes", "no"] },
    ];

    let currentQuestionIndex = 0;
    const responses = {};

    function showPopup(questionData) {
        const { question, options } = questionData;

        // Create modal with backdrop
        const modalBackdrop = document.createElement("div");
        modalBackdrop.style = `
position: fixed;
top: 0;
left: 0;
right: 0;
bottom: 0;
background: rgba(0, 0, 0, 0.5);
display: flex;
align-items: center;
justify-content: center;
z-index: 1400;
`;

        const modal = document.createElement("div");
        modal.id = "custom-modal";
        modal.style = `
background: white;
padding: 24px;
border-radius: 12px;
box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
text-align: center;
width: 90%;
max-width: 400px;
animation: slideIn 0.3s ease-out;
`;

        // Add CSS animation
        const style = document.createElement("style");
        style.textContent = `
@keyframes slideIn {
 from { transform: translateY(-20px); opacity: 0; }
 to { transform: translateY(0); opacity: 1; }
}
`;
        document.head.appendChild(style);

        // Add question text with improved styling
        const title = document.createElement("h3");
        title.style = `
font-size: 1.25rem;
color: #333;
margin: 0 0 20px 0;
line-height: 1.4;
`;
        title.textContent = question;
        modal.appendChild(title);

        // Add options as radio buttons with improved styling
        const form = document.createElement("form");
        form.id = "question-form";
        form.style = "margin-top: 20px;";

        options.forEach((option) => {
            const label = document.createElement("label");
            label.style = `
 display: flex;
 align-items: center;
 margin-bottom: 12px;
 padding: 12px;
 border: 2px solid #e5e7eb;
 border-radius: 8px;
 font-size: 16px;
 cursor: pointer;
 transition: all 0.2s ease;
`;

            // Add hover effect
            label.onmouseover = () => {
                label.style.backgroundColor = "#f3f4f6";
            };
            label.onmouseout = () => {
                label.style.backgroundColor = "transparent";
            };

            const radio = document.createElement("input");
            radio.type = "radio";
            radio.name = "answer";
            radio.value = option;
            radio.required = true;
            radio.style = `
 margin-right: 12px;
 width: 18px;
 height: 18px;
`;

            const text = document.createElement("span");
            text.textContent =
                option === "yes" ? "হ্যাঁ" : option === "no" ? "না" : "কখনও কখনও";

            label.appendChild(radio);
            label.appendChild(text);
            form.appendChild(label);
        });

        // Add Next button with improved styling
        const nextButton = document.createElement("button");
        nextButton.type = "submit";
        nextButton.style = `
width: 100%;
margin-top: 24px;
padding: 12px 24px;
background: #4caf50;
color: white;
border: none;
border-radius: 8px;
font-size: 16px;
font-weight: 500;
cursor: pointer;
transition: background-color 0.2s ease;
`;
        nextButton.textContent = "পরবর্তী";

        // Add hover effect for button
        nextButton.onmouseover = () => {
            nextButton.style.backgroundColor = "#45a049";
        };
        nextButton.onmouseout = () => {
            nextButton.style.backgroundColor = "#4caf50";
        };

        form.appendChild(nextButton);
        modal.appendChild(form);
        modalBackdrop.appendChild(modal);
        document.body.appendChild(modalBackdrop);

        // Handle form submission
        form.addEventListener("submit", (event) => {
            event.preventDefault();
            const answer = form.answer.value;
            responses[question] = answer;

            // Fade out animation
            modal.style.opacity = "0";
            modal.style.transform = "translateY(-20px)";
            modal.style.transition = "all 0.3s ease-out";
            modalBackdrop.style.opacity = "0";
            modalBackdrop.style.transition = "opacity 0.3s ease-out";

            setTimeout(() => {
                document.body.removeChild(modalBackdrop);

                currentQuestionIndex++;
                if (currentQuestionIndex < questions.length) {
                    showPopup(questions[currentQuestionIndex]);
                } else {
                    // Show waiting popup before saving data
                    const waitingPopup = document.getElementById("waiting-popup");
                    waitingPopup.classList.remove("hidden");

                    // Save data and update map after waiting
                    saveRoadToDatabase(roadName, responses, roadCoordinates);

                    // Disable button interaction for 5 seconds
                    setTimeout(() => {
                        waitingPopup.classList.add("hidden");

                        // Show success popup
                        const successPopup = document.getElementById("success-popup");
                        successPopup.classList.remove("hidden");

                        // After success, add the road to the map and bind the custom popup
                        layer
                            .bindPopup(
                                `<b>${roadName}</b><br>${JSON.stringify(responses)}`
                            )
                            .openPopup();
                    }, 5000);
                }
            }, 300);
        });
    }
    // Start with the first question
    showPopup(questions[currentQuestionIndex]);
});

// Show the waiting popup
function showWaitingPopup() {
    const waitingPopup = document.getElementById("waiting-popup");
    waitingPopup.classList.add("show");
}

// Hide the waiting popup
function hideWaitingPopup() {
    const waitingPopup = document.getElementById("waiting-popup");
    waitingPopup.classList.remove("show");
}

// Show the success popup
function showSuccessPopup() {
    const successPopup = document.getElementById("success-popup");
    successPopup.classList.add("show");

    // Add event listener to the close button
    const closeButton = document.getElementById("close-success-popup-btn");
    closeButton.onclick = function () {
        successPopup.classList.remove("show");
        location.reload(); // Reload the page
    };
}

// Simulate verification process with 5-second delay
function verifyRoad() {
    showWaitingPopup(); // Show "Wait for Verification" popup

    setTimeout(() => {
        hideWaitingPopup(); // Hide the waiting popup after 5 seconds
        showSuccessPopup(); // Show the success popup
    }, 5000); // 5-second delay
}

// Enhance saveRoadToDatabase to include verification
function saveRoadToDatabase(name, responses, coordinates) {
    const data = {
        road_name: name,
        road_coordinates: JSON.stringify(coordinates),
        busy_all_time_Yes_Vote_Count:
            responses["রাস্তা সব সময় ব্যস্ত?"] === "yes" ? 1 : 0,
        busy_all_time_No_Vote_Count:
            responses["রাস্তা সব সময় ব্যস্ত?"] === "no" ? 1 : 0,
        busy_all_time_Sometimes_Vote_Count:
            responses["রাস্তা সব সময় ব্যস্ত?"] === "sometimes" ? 1 : 0,
        too_narrow_Yes_Vote_Count:
            responses["রাস্তা খুব সরু?"] === "yes" ? 1 : 0,
        too_narrow_No_Vote_Count:
            responses["রাস্তা খুব সরু?"] === "no" ? 1 : 0,
        heavy_truck_pass_easily_Yes_Vote_Count:
            responses["ভারী ট্রাক এই রাস্তা দিয়ে যেতে পারে?"] === "yes"
                ? 1
                : 0,
        heavy_truck_pass_easily_No_Vote_Count:
            responses["ভারী ট্রাক এই রাস্তা দিয়ে যেতে পারে?"] === "no" ? 1 : 0,
        under_construction_Yes_Vote_Count:
            responses["রাস্তা নির্মাণাধীন?"] === "yes" ? 1 : 0,
        under_construction_No_Vote_Count:
            responses["রাস্তা নির্মাণাধীন?"] === "no" ? 1 : 0,
    };

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "save_data.php", true);
    xhr.setRequestHeader("Content-Type", "application/json");

    xhr.onload = function () {
        console.log("XHR Status:", xhr.status);
        console.log("Raw Response:", xhr.responseText);

        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                console.log("Parsed Response:", response);

                if (response.status === "success") {
                    alert("Vote saved successfully!");
                } else {
                    console.error("Error from server:", response.message);
                }
            } catch (error) {
                console.error("JSON Parsing Error:", error, xhr.responseText);
            }
        } else {
            console.error("Request Failed. Status:", xhr.status, "Response:", xhr.statusText);
        }
    };

    xhr.onerror = function () {
        console.error("Network Error Occurred. Please check your connection.");
    };


    // Send the JSON data as a string
    xhr.send(JSON.stringify(data));
}

// Function to load saved roads from the database
function loadSavedRoads() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "load_data.php", true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                var roads = JSON.parse(xhr.responseText);

                roads.forEach(function (road) {
                    try {
                        // Parse road coordinates and ensure they are valid
                        var coordinates = JSON.parse(road.Road_Coordinates);
                        if (Array.isArray(coordinates) && coordinates.length > 0) {
                            var polyline = L.polyline(
                                coordinates.map((coord) => [coord.lat, coord.lng]),
                                { color: "blue" }
                            ).addTo(map);

                            polyline.bindPopup(`<b>${road.Road_Name}</b>`).openPopup();
                        }
                    } catch (e) {
                        console.error(
                            "Invalid coordinates for road:",
                            road.Road_Name,
                            e
                        );
                    }
                });
            } catch (e) {
                console.error("Error parsing roads data:", e);
            }
        } else {
            console.error("Error loading roads:", xhr.statusText);
        }
    };
    xhr.onerror = function () {
        console.error("Network error while loading roads.");
    };
    xhr.send();
}

// Load saved roads when the page is loaded
window.onload = loadSavedRoads;

// Close the success popup when the "Close" button is clicked
document
    .getElementById("close-success-popup-btn")
    .addEventListener("click", function () {
        const successPopup = document.getElementById("success-popup");
        successPopup.classList.add("hidden");
    });

// Function to disable button interaction
function disableInteraction() {
    document.querySelectorAll("button").forEach((btn) => {
        btn.disabled = true;
    });
}

// Function to re-enable button interaction
function enableInteraction() {
    document.querySelectorAll("button").forEach((btn) => {
        btn.disabled = false;
    });
}


document.addEventListener("DOMContentLoaded", () => {
    // Show sidebar when a road is clicked
    function showSidebar(roadId, roadName) {
        document.getElementById("road_id").value = roadId;
        document.getElementById("road-status-title").textContent = `রাস্তার অবস্থা: ${roadName}`;
        document.getElementById("road-status-sidebar").classList.remove("hidden");
    }

    // Example: Simulate clicking a road
    document.querySelectorAll(".road-marker").forEach((marker) => {
        marker.addEventListener("click", (e) => {
            const roadId = marker.dataset.roadId; // Assume marker has data-road-id
            const roadName = marker.dataset.roadName; // Assume marker has data-road-name
            showSidebar(roadId, roadName);
        });
    });

    // Handle form submission
    document.getElementById("road-status-form").addEventListener("submit", async (e) => {
        e.preventDefault();

        // Gather form data
        const formData = new FormData(e.target);
        const roadId = formData.get("road_id");

        try {
            // Send the vote to your backend
            const response = await fetch("update_votes.php", {
                method: "POST",
                body: formData,
            });

            if (!response.ok) throw new Error("Failed to update votes.");

            // Fetch updated results and update the progress bars
            const result = await response.json(); // Assuming the backend sends updated percentages
            updateProgressBars(result);

            alert("ভোট সফলভাবে জমা হয়েছে!");
        } catch (error) {
            console.error(error);
            alert("ভোট জমা করতে ব্যর্থ হয়েছে!");
        }
    });

    // Update the progress bars
    function updateProgressBars(data) {
        document.getElementById("busy-progress").style.width = `${data.busy_all_time_percentage}%`;
        document.getElementById("narrow-progress").style.width = `${data.too_narrow_percentage}%`;
        document.getElementById("truck-progress").style.width = `${data.heavy_truck_percentage}%`;
        document.getElementById("construction-progress").style.width = `${data.under_construction_percentage}%`;
    }
});


// "Report Fire" button functionality
document
    .getElementById("report-fire-btn")
    .addEventListener("click", () => {
        showPopup("ফায়ার রিপোর্ট করার জন্য অনুরোধ পাঠানো হয়েছে!");
    });








// "Delete Report Fire" button functionality
document
    .getElementById("delete-fire-btn")
    .addEventListener("click", () => {
        showPopup("ফায়ার রিপোর্ট মুছে ফেলার জন্য অনুরোধ পাঠানো হয়েছে!");
    });

// Popup functionality
function showPopup(message) {
    const popup = document.getElementById("popup");
    const popupMessage = document.getElementById("popup-message");

    popupMessage.textContent = message;
    popup.classList.remove("hidden");
    popup.classList.add("visible");
}



function goBack() {
    // Redirect to the landing page
    window.location.href = "login.html"; // Replace with your landing page URL
}