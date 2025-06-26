<?php
session_start();
require_once("../../db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['create_community'])) {
    // Create new community
    $name = $_POST['community_name'];
    $description = $_POST['community_description'];
    $privacy = $_POST['privacy'];

    // Handle file upload
    $cover_image_url = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BookHeaven2.0/assets/community_images/';
      
      // Create directory if it doesn't exist
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      
      $file_name = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
      $target_file = $upload_dir . $file_name;

      // Check if image file is a actual image
      $check = getimagesize($_FILES['cover_image']['tmp_name']);
      if ($check !== false) {
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
          $cover_image_url = '/BookHeaven2.0/assets/community_images/' . $file_name;
        } else {
          $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
        }
      } else {
        $_SESSION['error_message'] = "File is not an image.";
      }
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO communities (name, description, created_by, cover_image_url, privacy) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $name, $description, $user_id, $cover_image_url, $privacy);
    if ($stmt->execute()) {
      $community_id = $stmt->insert_id;
      $stmt->close();

      // Add creator as admin
      $stmt = $conn->prepare("INSERT INTO community_members (community_id, user_id, role) VALUES (?, ?, 'admin')");
      $stmt->bind_param("ii", $community_id, $user_id);
      $stmt->execute();
      $stmt->close();

      $_SESSION['success_message'] = "Community created successfully!";
    } else {
      $_SESSION['error_message'] = "Error creating community: " . $conn->error;
    }
    
    header("Location: community_dashboard.php");
    exit();
  } elseif (isset($_POST['join_community'])) {
    // Join existing community
    $community_id = $_POST['community_id'];

    // Check if already a member
    $stmt = $conn->prepare("SELECT * FROM community_members WHERE community_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $community_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      // Add as member
      $stmt = $conn->prepare("INSERT INTO community_members (community_id, user_id, role) VALUES (?, ?, 'member')");
      $stmt->bind_param("ii", $community_id, $user_id);
      $stmt->execute();
      $stmt->close();

      $_SESSION['success_message'] = "You have joined the community!";
    } else {
      $_SESSION['error_message'] = "You are already a member of this community.";
    }

    header("Location: community_dashboard.php");
    exit();
  }
}

// Get all communities (for discovery section)
$discover_communities = [];
$stmt = $conn->prepare("
    SELECT c.*, COUNT(cm.user_id) as member_count 
    FROM communities c
    LEFT JOIN community_members cm ON c.community_id = cm.community_id
    WHERE c.community_id NOT IN (
        SELECT community_id FROM community_members WHERE user_id = ?
    )
    AND c.status = 'active'
    GROUP BY c.community_id
    ORDER BY member_count DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $discover_communities[] = $row;
}
$stmt->close();

// Get user's communities
$user_communities = [];
$stmt = $conn->prepare("
    SELECT c.*, cm.role, COUNT(cm2.user_id) as member_count 
    FROM communities c
    JOIN community_members cm ON c.community_id = cm.community_id
    LEFT JOIN community_members cm2 ON c.community_id = cm2.community_id
    WHERE cm.user_id = ? AND cm.status = 'active' AND c.status = 'active'
    GROUP BY c.community_id
    ORDER BY cm.role DESC, c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $user_communities[] = $row;
}
$stmt->close();

// Get stats for dashboard
$stats = [
  'joined' => 0,
  'created' => 0,
  'members' => 0,
  'posts' => 0
];

// Count joined communities
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM community_members WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['joined'] = $result->fetch_assoc()['count'];
$stmt->close();

// Count created communities
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM communities WHERE created_by = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['created'] = $result->fetch_assoc()['count'];
$stmt->close();

// Count total members in user's communities
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM community_members cm
    JOIN communities c ON cm.community_id = c.community_id
    JOIN community_members cm2 ON c.community_id = cm2.community_id
    WHERE cm.user_id = ? AND cm.status = 'active' AND c.status = 'active'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['members'] = $result->fetch_assoc()['count'];
$stmt->close();

// Count total posts in user's communities
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM community_posts cp
    JOIN community_members cm ON cp.community_id = cm.community_id
    WHERE cm.user_id = ? AND cm.status = 'active' AND cp.status = 'active'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['posts'] = $result->fetch_assoc()['count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #57abd2;
      --primary-dark: #3d8eb4;
      --secondary-color: #f8f5fc;
      --accent-color: rgb(223, 219, 227);
      --text-color: #333;
      --text-light: #666;
      --light-purple: #e6d9f2;
      --dark-text: #212529;
      --light-text: #f8f9fa;
      --card-bg: #ffffff;
      --aside-bg: #f0f2f5;
      --nav-hover: #e0e0e0;
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
      --border-color: #e0e0e0;
      --hover-bg:rgb(144, 195, 190);
      --even-row-bg: #f9f9f9;
      --header-bg: #f0f0f0;
      --header-text: #333;
      --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }

    .dark-mode {
      --primary-color: #57abd2;
      --primary-dark: #4a9bc1;
      --secondary-color: #2d3748;
      --accent-color: #4a5568;
      --text-color: #f8f9fa;
      --text-light: #a0aec0;
      --light-purple: #4a5568;
      --dark-text: #f8f9fa;
      --light-text: #212529;
      --card-bg: #1a202c;
      --aside-bg: #1a202c;
      --nav-hover: #4a5568;
      --border-color: #4a5568;
      --hover-bg:rgb(37, 62, 71);
      --even-row-bg: #2d3748;
      --header-bg:rgb(25, 68, 68);
      --header-text: #f8f9fa;
      --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: var(--secondary-color);
      color: var(--text-color);
      transition: var(--transition);
    }

    main {
      display: flex;
      min-height: calc(100vh - 120px);
      padding: 20px;
      gap: 20px;
    }

    aside {
      flex: 1;
      background-color: var(--card-bg);
      border-radius: 10px;
      padding: 20px;
      box-shadow: var(--card-shadow);
      display: flex;
      flex-direction: column;
      max-width: 350px;
    }

    .dashboard-content {
      flex: 3;
      background-color: var(--card-bg);
      border-radius: 10px;
      padding: 20px;
      box-shadow: var(--card-shadow);
    }

    .community-list {
      flex: 1;
      overflow-y: auto;
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 20px;
    }

    .community-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 15px;
      margin-bottom: 10px;
      background-color: var(--card-bg);
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      transition: var(--transition);
    }

    .community-item:hover {
      background-color: var(--hover-bg);
      transform: translateY(-2px);
    }

    .community-info {
      flex: 1;
    }

    .community-name {
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--text-color);
    }

    .community-members {
      font-size: 0.8rem;
      color: var(--text-light);
    }

    .join-btn {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: var(--transition);
      font-weight: 500;
    }

    .join-btn:hover {
      background-color: var(--primary-dark);
    }

    .create-community-btn {
      width: 100%;
      padding: 12px;
      background-color: var(--success-color);
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .create-community-btn:hover {
      background-color: #218838;
    }

    /* Dashboard Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background-color: var(--card-bg);
      border-radius: 10px;
      padding: 20px;
      box-shadow: var(--card-shadow);
      text-align: center;
      transition: var(--transition);
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-color);
      margin: 10px 0;
    }

    .stat-label {
      font-size: 1rem;
      color: var(--text-light);
    }

    /* Joined Communities Table */
    .joined-communities {
      margin-top: 30px;
    }

    .section-title {
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: var(--text-color);
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border-color);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th,
    td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    th {
      background-color: var(--header-bg);
      color: var(--header-text);
      font-weight: 600;
    }

    tr:nth-child(even) {
      background-color: var(--even-row-bg);
    }

    tr:hover {
      background-color: var(--hover-bg);
    }

    .view-btn {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      transition: var(--transition);
    }

    .view-btn:hover {
      background-color: var(--primary-dark);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: var(--card-bg);
      border-radius: 10px;
      width: 90%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      padding: 25px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
      position: relative;
    }

    .modal-header {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border-color);
    }

    .modal-title {
      font-size: 1.5rem;
      color: var(--text-color);
    }

    .close-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-light);
      background: none;
      border: none;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-color);
    }

    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid var(--border-color);
      border-radius: 5px;
      background-color: var(--card-bg);
      color: var(--text-color);
      transition: var(--transition);
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgba(87, 171, 210, 0.2);
    }

    textarea.form-control {
      min-height: 100px;
      resize: vertical;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid var(--border-color);
    }

    .btn {
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      transition: var(--transition);
      font-weight: 500;
      border: none;
    }

    .btn-primary {
      background-color: var(--primary-color);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
    }

    .btn-secondary {
      background-color: var(--accent-color);
      color: var(--text-color);
    }

    .btn-secondary:hover {
      background-color: var(--nav-hover);
    }

    .rules-content {
      max-height: 300px;
      overflow-y: auto;
      padding: 15px;
      background-color: var(--aside-bg);
      border-radius: 5px;
      margin-bottom: 20px;
    }

    .rules-content p {
      margin-bottom: 10px;
    }

    .agree-checkbox {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .agree-checkbox input {
      margin-right: 10px;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      main {
        flex-direction: column;
      }

      aside {
        max-width: 100%;
        margin-bottom: 20px;
      }
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      table {
        display: block;
        overflow-x: auto;
      }
    }

    @media (max-width: 576px) {
      .modal-content {
        width: 95%;
        padding: 15px;
      }

      .community-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .join-btn {
        width: 100%;
        margin-top: 10px;
      }
    }
  </style>
</head>

<body>
  <?php include_once("../../header.php"); ?>

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
      <?= $_SESSION['success_message']; ?>
      <?php unset($_SESSION['success_message']); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
      <?= $_SESSION['error_message']; ?>
      <?php unset($_SESSION['error_message']); ?>
    </div>
  <?php endif; ?>

  <main>
    <aside>
      <h2>Discover Communities</h2>
      <div class="community-list">
        <?php foreach ($discover_communities as $community): ?>
          <div class="community-item">
            <div class="community-info">
              <div class="community-name"><?= htmlspecialchars($community['name']); ?></div>
              <div class="community-members"><?= $community['member_count']; ?> members</div>
            </div>
            <button class="join-btn"
              onclick="openJoinModal(<?= $community['community_id']; ?>, '<?= addslashes($community['name']); ?>')">Join</button>
          </div>
        <?php endforeach; ?>

        <?php if (empty($discover_communities)): ?>
          <p>No communities to discover at the moment.</p>
        <?php endif; ?>
      </div>
      <button class="create-community-btn" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Create New Community
      </button>
    </aside>

    <div class="dashboard-content">
      <h1>Community Dashboard</h1>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value"><?= $stats['joined']; ?></div>
          <div class="stat-label">Communities Joined</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $stats['created']; ?></div>
          <div class="stat-label">Communities Created</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $stats['members']; ?></div>
          <div class="stat-label">Total Members</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $stats['posts']; ?></div>
          <div class="stat-label">Total Posts</div>
        </div>
      </div>

      <div class="joined-communities">
        <h2 class="section-title">Your Communities</h2>
        <?php if (!empty($user_communities)): ?>
          <table>
            <thead>
              <tr>
                <th>Community Name</th>
                <th>Members</th>
                <th>Role</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($user_communities as $community): ?>
                <tr>
                  <td><?= htmlspecialchars($community['name']); ?></td>
                  <td><?= $community['member_count']; ?></td>
                  <td><?= ucfirst($community['role']); ?></td>
                  <td>
                    <button class="view-btn"
                      onclick="window.location.href='community_dashboard.php?id=<?= $community['community_id']; ?>'">View</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>You haven't joined any communities yet. Join or create one to get started!</p>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Join Community Modal -->
  <div class="modal" id="joinModal">
    <div class="modal-content">
      <button class="close-btn" onclick="closeModal('joinModal')">&times;</button>
      <div class="modal-header">
        <h2 class="modal-title">Join Community</h2>
      </div>
      <form id="joinCommunityForm" method="post">
        <input type="hidden" name="community_id" id="joinCommunityId">
        <input type="hidden" name="join_community" value="1">

        <div class="rules-content">
          <h3 id="communityRulesTitle">Community Rules</h3>
          <p>1. Be respectful to all members. Harassment, hate speech, or discrimination of any kind will not be
            tolerated.</p>
          <p>2. Stay on topic. Posts should be relevant to the community's purpose.</p>
          <p>3. No spam or self-promotion without permission from the moderators.</p>
          <p>4. Keep discussions civil. Disagreements are fine, but personal attacks are not.</p>
          <p>5. Respect privacy. Do not share personal information about yourself or others.</p>
          <p>6. Follow all applicable laws and regulations.</p>
          <p>7. The moderators reserve the right to remove any content or members that violate these rules.</p>
          <p>By joining this community, you agree to abide by these rules. Violations may result in removal from the
            community.</p>
        </div>
        <div class="agree-checkbox">
          <input type="checkbox" id="agreeRules" name="agree_rules" required>
          <label for="agreeRules">I have read and agree to the community rules</label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('joinModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Join Community</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Create Community Modal -->
  <div class="modal" id="createModal">
    <div class="modal-content">
      <button class="close-btn" onclick="closeModal('createModal')">&times;</button>
      <div class="modal-header">
        <h2 class="modal-title">Create New Community</h2>
      </div>
      <form id="createCommunityForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="create_community" value="1">

        <div class="form-group">
          <label for="communityName" class="form-label">Community Name *</label>
          <input type="text" id="communityName" name="community_name" class="form-control" required maxlength="100">
        </div>
        <div class="form-group">
          <label for="communityDescription" class="form-label">Description *</label>
          <textarea id="communityDescription" name="community_description" class="form-control" required></textarea>
        </div>
        <div class="form-group">
          <label for="coverImage" class="form-label">Cover Image</label>
          <input type="file" id="coverImage" name="cover_image" class="form-control" accept="image/*">
        </div>

        <div class="form-group">
          <label class="form-label">Privacy *</label>
          <div>
            <input type="radio" id="public" name="privacy" value="public" checked>
            <label for="public">Public (Anyone can join)</label>
          </div>
          <div>
            <input type="radio" id="private" name="privacy" value="private">
            <label for="private">Private (Requires approval to join)</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Community</button>
        </div>
      </form>
    </div>
  </div>

  <?php include_once("../../footer.php"); ?>

  <script>
    // Modal functions
    function openJoinModal(communityId, communityName) {
      document.getElementById('joinCommunityId').value = communityId;
      document.getElementById('communityRulesTitle').textContent = communityName + " Rules";
      document.getElementById('joinModal').style.display = 'flex';
    }

    function openCreateModal() {
      document.getElementById('createModal').style.display = 'flex';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function (event) {
      if (event.target.className === 'modal') {
        event.target.style.display = 'none';
      }
    }

    // Form validation for create community
    document.getElementById('createCommunityForm').addEventListener('submit', function (e) {
      const name = document.getElementById('communityName').value.trim();
      const description = document.getElementById('communityDescription').value.trim();

      if (!name || !description) {
        e.preventDefault();
        alert('Please fill in all required fields.');
      }
    });

    // Form validation for join community
    document.getElementById('joinCommunityForm').addEventListener('submit', function (e) {
      if (!document.getElementById('agreeRules').checked) {
        e.preventDefault();
        alert('Please agree to the community rules before joining.');
      }
    });
  </script>
</body>
</html>