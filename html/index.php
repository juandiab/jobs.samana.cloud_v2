
<?php

// Register here the page information
$page_title = "Jobs Dashboard";

// Load configuration
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/google-login/config.php';

session_start();
function checkSessionTimeout() {
    $timeout_duration = 3600; // 1 hour in seconds

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        // Last request was more than 1 hour ago
        session_unset();     // Unset $_SESSION variable for the run-time
        session_destroy();   // Destroy session data in storage
        header("Location: /google-login/logout.php");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time stamp
}


// Database connection
$conn = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['db']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}




//Profile Management Section

function fetchProfile($conn, $email) {
    $sql = "SELECT * FROM candidate_profiles WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function saveProfile($conn, $name, $email, $phone_number, $location, $english_level, $profile_photo) {
    $sql = "INSERT INTO candidate_profiles (name, email, phone_number, location, english_level, profile_photo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $name, $email, $phone_number, $location, $english_level, $profile_photo);
    $stmt->execute();
}

function deleteProfile($conn, $email) {
    $sql = "DELETE FROM candidate_profiles WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
}






$profile = [];
if (isset($_SESSION['email'])) {
    $profile = fetchProfile($conn, $_SESSION['email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile'])) {
    deleteProfile($conn, $_SESSION['email']);
    $profile = []; // Clear profile after deletion
    header("Location: index.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile']) && isset($_SESSION['email'])) {
    $name = $_POST['name'] ?? '';
    $email = $_SESSION['email'];
    $phone_number = $_POST['phone_number'] ?? '';
    $location = $_POST['location'] ?? '';
    $english_level = $_POST['english_level'] ?? '';
    $profile_photo = ''; // Implement file upload if needed

    saveProfile($conn, $name, $email, $phone_number, $location, $english_level, $profile_photo);
    header("Location: index.php");
    exit();
}


//Profile Certification Section

function getCertifications($email, $conn) {
    $sql = "SELECT certification FROM candidate_certifications WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $certifications = [];
    while ($row = $result->fetch_assoc()) {
        $certifications[] = $row['certification'];
    }
    return $certifications;
}

function addCertification($email, $certification, $conn) {
    // Check if the certification already exists
    $sql = "SELECT * FROM candidate_certifications WHERE email = ? AND certification = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $email, $certification);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $stmt->close();
        $sql = "INSERT INTO candidate_certifications (email, certification) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $email, $certification);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        echo "<script>alert('Certification already exists.');</script>";
    }
}

function removeCertification($email, $certification, $conn) {
    $sql = "DELETE FROM candidate_certifications WHERE email = ? AND certification = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $email, $certification);
    $stmt->execute();
    $stmt->close();
}

function hasCertifications($conn) {
    $sql = "SELECT COUNT(*) AS count FROM candidate_certifications";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_certification'])) {
        addCertification($_SESSION['email'], $_POST['add_certification'], $conn);
    } elseif (isset($_POST['remove_certification'])) {
        removeCertification($_SESSION['email'], $_POST['remove_certification'], $conn);
    }
}

$isLoggedIn = isset($_SESSION['email']);
$certifications = $isLoggedIn ? getCertifications($_SESSION['email'], $conn) : [];
$hasCertifications = hasCertifications($conn);







$conn->close();
//end of profile management
// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']);
$name = $isLoggedIn ? $_SESSION['name'] : '';

// Google Client Configuration
$client = new Google_Client();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_uri']);
$client->addScope('email');
$client->addScope('profile');

$loginUrl = $client->createAuthUrl();
$logoutUrl = 'https://jobs.samana.cloud/google-login/logout.php';

// Database connection
$conn = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['db']);
if ($conn->connect_error) {
    die('Connection error (' . $conn->connect_errno . ') ' . $conn->connect_error);
}

// List job postings, sorted by ID in descending order
$sql = "SELECT id, job_category, job_title, job_details, post_date, linkedin_url FROM job_postings  WHERE enabled = 1 ORDER BY id DESC";
$result = $conn->query($sql);

// Function to get logo image based on job category
function getLogo($category) {
    $logos = [
        'netscaler' => 'images/netscaler.png',
        'citrix' => 'images/citrix.png',
        'aws' => 'images/aws.png'
    ];
    $category_lower = strtolower($category);
    return isset($logos[$category_lower]) ? $logos[$category_lower] : 'images/default.png';
}


function isMobile() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $mobileAgents = ['iphone', 'android', 'blackberry', 'webos', 'windows phone'];

    foreach ($mobileAgents as $agent) {
        if (strpos($userAgent, $agent) !== false) {
            return true;
        }
    }
    return false;
}


checkSessionTimeout();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Default Title'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <style>
        .header, .footer {
            background-color: #143154;
            color: #f8f9fa;
            padding: 10px 0;
        }
        .header .logo {
            max-width: 150px;
        }
        .footer {
            text-align: center;
        }
        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .job-icon {
            font-size: 2em;
            margin-right: 10px;
        }
        .job-category-netscaler {
            color: #ff5733; /* Example color for netscaler */
        }
        .job-category-citrix {
            color: #33c1ff; /* Example color for citrix */
        }
        .job-category-aws {
            color: #ffbb33; /* Example color for aws */
        }
        .job-posting {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
        }
        .job-posting .job-title {
            font-weight: bold;
        }
        .job-posting .post-date {
            font-size: 0.9em;
            color: #888;
        }
        .job-posting .job-details {
            font-size: 1em;
        }
        @media (max-width: 767.98px) {
            .header .logo {
                max-width: 100px;
            }
            .job-posting .job-title,
            .job-posting .post-date,
            .job-posting .job-details {
                font-size: 0.9em;
            }
        }
		.list-group-item:not(:last-child) { /* Don't apply to the last item */
			border-bottom: none;
}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <img src="../images/samana-logo.png" alt="Samana Group Logo" class="logo">
                </div>
                <div class="col">
                    <h3 class='mb-0'><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Default Title'; ?></h3>
                </div>
                <div class="col text-right">
                    <?php if ($isLoggedIn): ?>
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                        <a href="<?php echo $logoutUrl; ?>" class="btn btn-danger ml-2">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo $loginUrl; ?>" class="btn btn-primary ml-2">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
<?php echo htmlspecialchars($_SESSION['name']); ?>

    <div class="container mt-5">
        <!-- Intro Section -->
        <section id="intro" class="mt-5">
            <h2>Welcome to Our Job Listings</h2>
            <p>Explore the latest job opportunities available at Samana Group. We are excited to have talented individuals join our team.</p>
        </section>

        <!-- Available Jobs Section -->
        <section id="jobs" class="mt-5">
            <h2>Available Jobs</h2>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $logo = getLogo($row['job_category']);
                    echo "
                    <div class='job-posting row'>
                        <div class='col-md-2 text-center'>
                            <img src='$logo' alt='{$row['job_category']}' class='img-fluid' style='max-width: 80px;'>
                        </div>
                        <div class='col-md-7'>
                            <div class='job-title'>{$row['job_title']}</div>
                            <div class='post-date'>Posted on: {$row['post_date']}</div>
                            <div class='job-details'>{$row['job_details']}</div>
                        </div>
                        <div class='col-md-3 text-center'>"; ?>

							 <?php if ($isLoggedIn): ?>
                       <a href='job_description?id=<?php echo $row['id']; ?>' target='_self' class='btn btn-primary mb-2'>Job Description</a> <br>
                            <a href='https://docs.google.com/forms/d/e/1FAIpQLSd9pgQ9fPMbh3vsbXZ8VseTjZFT7fI9wh363BosTbUwhGwHwg/viewform' target='_blank' class='btn btn-success'>Apply to this job</a>
                    <?php else: ?>
                        <a href="<?php echo $loginUrl; ?>" class="btn btn-primary ml-2">Sign In to apply</a>
                    <?php endif; ?>


						<?php


			echo "

                        </div>
                    </div>";
                }
            } else {
                echo "<p>No job postings available at the moment.</p>";
            }
            ?>
        </section>


<!-- Candidate Profile Section -->
<?php if (isset($_SESSION['email'])): ?>

    <section id="candidate-profile" class="mt-5">
		    <h2>Candidate Profile</h2>
        <div class="container" style="border: 1px solid #ccc; padding: 20px; border-radius: 5px;">
            <?php if ($profile && !isset($_POST['delete_profile'])): ?>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="<?php echo isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'images/profile.jpg'; ?>" alt="Profile Photo" class="img-fluid rounded-circle" style="max-width: 150px;">
                    </div>
                    <div class="col-md-8">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($profile['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($profile['phone_number']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($profile['location']); ?></p>
                        <p><strong>English Level:</strong> <?php echo htmlspecialchars($profile['english_level']); ?></p>
                        <form method="post" action="index">
                            <button type="submit" name="delete_profile" class="btn btn-outline-primary mt-2">Update Profile</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" action="index" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="english_level">English Level</label>
                        <select name="english_level" id="english_level" class="form-control">
                            <option value="A1">A1 - Beginner</option>
                            <option value="B1">B1 - Intermediate</option>
                            <option value="B2">B2 - Upper Intermediate</option>
                            <option value="C1">C1 - Advanced</option>
                            <option value="C2">C2 - Proficient</option>
                        </select>
                    </div>
                    <button type="submit" name="save_profile" class="btn btn-primary">Save Profile</button>
                </form>
            <?php endif; ?>
        </div>

    </section>
<?php endif; ?>
<!-- End of candidate profile section-->


<!-- Candidate Certifications Section -->
<?php if ($isLoggedIn && $hasCertifications): ?>
<section id="candidate-certifications" class="mt-5">
    <div class="container" style="border: 1px solid #ccc; padding: 20px; border-radius: 5px;">
        <h2 class="mt-2">Candidate Certifications</h2>
        <ul class="list-group  list-group-flush">
            <?php foreach ($certifications as $certification): ?>
                <li class="list-group-item d-flex justify-content-between align-items-right">
                    <?php echo htmlspecialchars($certification); ?>
                    <form method="post" action="index" style="display: inline;">
                        <input type="hidden" name="remove_certification" value="<?php echo htmlspecialchars($certification); ?>">
                        <button type="submit" class="btn btn-link" style="color: red;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>

		<form action="admin/user_certifications" method="GET" class="d-flex">
						<input type="hidden" name="candidate_email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
						<button type="submit" class="btn btn-info">Add Certifications</button>
						</form>
    </div>
</section>
<?php endif; ?>
		<!--End of profile certification section-->



	</div>




    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <p>&copy; 2024 Samana Group LLC | <a style="color:white" href="https://www.samanagroup.com/privacy-policy/">Privacy Policy</a></p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
