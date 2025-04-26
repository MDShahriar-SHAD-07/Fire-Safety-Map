// Initialize Leaflet map
const map = L.map("map").setView([23.8103, 90.4125], 13); // Center on Dhaka

// Set up map tile layer
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap",
}).addTo(map);

// Add a sample marker for the fire station
const marker = L.marker([23.8103, 90.4125]).addTo(map);
marker.bindPopup("Fire Station Example").openPopup();

// Capture User_ID from localStorage
const UserId = localStorage.getItem("User_id");

if (!UserId) {
    console.error("User ID not found in localStorage.");
} else {
    console.log("User ID found:", UserId);
}

// Custom fire icon
const fireIcon = L.icon({
    iconUrl: "images/fire_icon.png", // Replace with your fire icon path
    iconSize: [32, 37],
    iconAnchor: [16, 37],
    popupAnchor: [0, -28],
});

// Fetch and display notifications
function loadNotifications() {
    fetch("get_notifications.php") // Replace with your endpoint
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const notificationList = document.getElementById("notification-list");
                notificationList.innerHTML = ""; // Clear previous notifications

                data.notifications.forEach((notification) => {
                    const button = document.createElement("button");
                    button.textContent = notification.Notification_Text;
                    button.classList.add("notification-button");

                    // Handle button click
                    button.onclick = function () {
                        const lat = notification.Latitude;
                        const lon = notification.Longitude;

                        // Zoom to the location
                        map.setView([lat, lon], 15);

                        // Add fire marker
                        const fireMarker = L.marker([lat, lon], { icon: fireIcon }).addTo(map);
                        fireMarker.bindPopup("Fire at this location").openPopup();

                        // Handle spectator or victim logic
                        if (notification.Notification_Text.includes("spectator")) {
                            fetchSpectatorData(notification.Related_ID); // Spec_ID
                        } else if (notification.Notification_Text.includes("victim")) {
                            fetchVictimData(notification.Related_ID); // Vic_ID
                        }
                    };

                    notificationList.appendChild(button);
                });
            } else {
                console.error("Error fetching notifications:", data.message);
            }
        })
        .catch((error) => console.error("Error fetching notifications:", error));
}

// Fetch Spectator Data by Spec_ID
function fetchSpectatorData(specID) {
    fetch(`get_spectator_data.php?spec_id=${specID}`) // Replace with your endpoint
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const spectatorBox = document.getElementById("responses_spectator");
                const content = spectatorBox.querySelector("#response-content");
                content.innerHTML = ""; // Clear previous content

                const translations = {
                    "intensity of the fire_Low": "আগুনের তীব্রতা কম",
                    "intensity of the fire_Mid": "আগুনের তীব্রতা মধ্যম",
                    "intensity of the fire_High": "আগুনের তীব্রতা উচ্চ",
                    "injured_No": "কোনও হতাহত নেই",
                    "injured_Few": "কিছু হতাহত আছে",
                    "injured_Huge": "অনেক হতাহত আছে",
                    "Need_Resource_Water": "পানি প্রয়োজন",
                    "Need_Resource_Treatement": "চিকিৎসা সহায়তা প্রয়োজন",
                    "Need_Resource_Rescue": "উদ্ধার প্রয়োজন",
                };

                for (const [key, value] of Object.entries(data.spectatorData)) {
                    if (value === 1) {
                        const row = document.createElement("div");
                        row.textContent = translations[key];
                        content.appendChild(row);
                    }
                }
            } else {
                console.error("Error fetching spectator data:", data.message);
            }
        })
        .catch((error) => console.error("Error fetching spectator data:", error));
}

// Fetch Victim Data by Vic_ID
function fetchVictimData(vicID) {
    fetch(`get_victim_data.php?vic_id=${vicID}`) // Replace with your endpoint
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const victimBox = document.getElementById("responses_victim");
                const content = victimBox.querySelector("#response-content");
                content.innerHTML = ""; // Clear previous content

                const translations = {
                    "smoke_low": "ধোঁয়ার পরিমান কম",
                    "smoke_mid": "ধোঁয়ার পরিমান মধ্যম",
                    "smoke_high": "ধোঁয়ার পরিমান উচ্চ",
                    "victim_num_1_to_5": "একসাথে আছে ১ থেকে ৫ জন",
                    "victim_num_5_to_10": "একসাথে আছে ৫ থেকে ১০ জন",
                    "victim_num_10_plus": "একসাথে আছে ১০ জনের অধিক",
                    "building_location_ground_floor": "ভবনে অবস্থান নিচ তলায়",
                    "building_location_2ndfloor_to_4thfloor": "ভবনে অবস্থান ২য়-৪র্থ তলায়",
                    "building_location_above_4thfloor": "ভবনে অবস্থান ৪র্থ তলার উপরে",
                };

                for (const [key, value] of Object.entries(data.victimData)) {
                    if (value === 1) {
                        const row = document.createElement("div");
                        row.textContent = translations[key];
                        content.appendChild(row);
                    }
                }

                // Fetch road status using Vic_ID
                checkRoadStatus(vicID);
            } else {
                console.error("Error fetching victim data:", data.message);
            }
        })
        .catch((error) => console.error("Error fetching victim data:", error));
}

// Fetch and check road status near a given coordinate
function checkRoadStatus(vicID) {
    fetch(`get_road_status.php?Vic_ID=${vicID}`)
        .then((response) => response.json())
        .then((data) => {
            const roadStatusBox = document.getElementById("road-status");
            roadStatusBox.innerHTML = ""; // Clear previous content

            if (data.success) {
                const roads = data.roads;
                let roadStatusMessage = "";

                roads.forEach((road) => {
                    const underConstructionPercentage =
                        (road.under_construction_No_Vote_Count / road.under_construction_Total_Vote_Count) * 100;

                    if (underConstructionPercentage >= 80) {
                        roadStatusMessage += `${road.Road_Name} রাস্তাটি নির্মাণাধীন। পরিহার করুন। ❌<br><br>`;
                    } else {
                        const tooNarrowYesPercentage =
                            (road.too_narrow_Yes_Vote_Count / road.too_narrow_Total_Vote_Count) * 100 || 0;
                        const tooNarrowNoPercentage =
                            (road.too_narrow_No_Vote_Count / road.too_narrow_Total_Vote_Count) * 100 || 0;

                        if (tooNarrowYesPercentage >= tooNarrowNoPercentage) {
                            roadStatusMessage += `${road.Road_Name} রাস্তাটি খুব সরু। পরিহার করুন। ❌<br><br>`;
                        } else {
                            roadStatusMessage += `${road.Road_Name} রাস্তাটি প্রশস্ত। ব্যবহার করুন। ✅<br><br>`;
                        }

                        const heavyTruckYesPercentage =
                            (road.heavy_truck_pass_easily_Yes_Vote_Count / road.heavy_truck_pass_easily_Total_Vote_Count) * 100 || 0;
                        const heavyTruckNoPercentage =
                            (road.heavy_truck_pass_easily_No_Vote_Count / road.heavy_truck_pass_easily_Total_Vote_Count) * 100 || 0;

                        if (heavyTruckYesPercentage >= heavyTruckNoPercentage) {
                            roadStatusMessage += `${road.Road_Name} রাস্তাটি ভারী ট্রাক চালনায় উপযোগী। ✅<br><br>`;
                        } else {
                            roadStatusMessage += `${road.Road_Name} রাস্তাটি ভারী ট্রাক চলাচল যোগ্য নয়। ❌<br><br>`;
                        }
                    }
                });

                roadStatusBox.innerHTML = roadStatusMessage || "No road status found within 3km.";
            } else {
                roadStatusBox.innerHTML = "Could not retrieve road status.";
            }
        })
        .catch((error) => {
            console.error("Error fetching road status:", error);
            document.getElementById("road-status").innerHTML = "Could not retrieve road status.";
        });
}


// Load notifications on page load
window.onload = function () {
    loadNotifications();
};
