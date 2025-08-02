# 🔥 Fire Safety Map

🏆 **1st Runners-Up — UIU CSE Project Show Fall '24**  
📚 *Database Management System Laboratory | United International University*  
👨‍💻 **Team C101**

---

## 🚀 Overview

**Fire Safety Map** is a real-time emergency reporting and decision-support system designed to assist firefighters in making **quick, accurate, and life-saving decisions**. Developed under extreme time pressure and guided by the invaluable mentorship of **Md. Enamul Haque Sir**, this project stands as a testament to teamwork, resilience, and innovation.

> ⏱️ "Every second counts – quick actions and accurate information can save a life."

---

## 🎯 Goal

To provide firefighters with **real-time data** and a **dynamic interface** to take immediate and informed actions during fire emergencies, thereby reducing response time and saving lives.

---

## 💡 Features

### 👥 Civilian Users
- 🔵 **Real-Time Location Tracking** — Civilian users are tracked dynamically on the map.
- 🚨 **Instant Fire Reporting (No Login Needed)** — In emergencies, civilians can report fire instantly.
- ✍️ **Edit Roads & Update Status** — Registered users can mark road conditions (e.g., blocked, open).
- ✅ **Community Verification** — Changes to roads or reports are verified by users in that specific location.

### 🔥 Emergency Reporting
- 🧑‍🚒 **Spectator or Victim Mode** — Civilians can report incidents based on their role.
- 📸 **Upload Photos/Videos** — Optional media attachment with the report for better decision-making.

### 🧯 Fire Department Dashboard
- 📥 **Instant Fire Notifications** — Fire officers receive reports instantly with all data.
- 🧭 **One-Click Decision Support** — Officers can assess situations and plan routes immediately.

---

## 🛠️ Technologies Used

- **Frontend**: HTML, CSS, JavaScript, Leaflet.js
- **Backend**: PHP, MySQL
- **Database**: Structured schema for users, roads, fire reports, and verification logs
- **APIs & Tools**: Geolocation API, Dynamic Map Rendering, File Upload Handling

---

## 📸 Screenshots

*(Add screenshots or GIFs here of the dashboard, fire report forms, or map interface)*  
*(Use `![Alt Text](image_path)` format)*

---

## 📁 Folder Structure

```bash
Fire-Safety-Map/
├── README.md
├── index.html
├── login.html
├── signup.html
├── map_report.html
├── civilian_dashboard.html
├── fire_dashboard.html
├── spectator.html
├── victim.html
├── contributors.html
│
├── css/
│ ├── civilian_dashboard.css
│ ├── fire_dashboard.css
│ ├── login_design.css
│ ├── signup_design.css
│ ├── signup_civilian_fireofficer.css
│ ├── signup_fire_officer.css
│ ├── spectator_victim.css
│ ├── sidebar_of_road_status.css
│ ├── index_design.css
│ ├── contributors.css
│ ├── style.css
│ └── common.css
│
├── js/
│ ├── map_script.js
│ └── common.js
│
├── php/
│ ├── db.php
│ ├── add_spectator.php
│ ├── add_victim.php
│ ├── submit_spectator.php
│ ├── submit_victim.php
│ ├── login_action.php
│ ├── signup_action.php
│ ├── get_roads.php
│ ├── get_road_status.php
│ ├── get_fire_stations.php
│ ├── get_nearest_station.php
│ ├── update_nearest_station.php
│ ├── update_user_location.php
│ ├── get_notifications.php
│ ├── add_notification.php
│ ├── add_decision.php
│ ├── update_votes.php
│ ├── get_spectator_data.php
│ ├── get_victim_data.php
│ ├── load_data.php
│ ├── save_data.php
│ └── delete_data.php
