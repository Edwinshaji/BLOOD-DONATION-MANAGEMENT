<?php
$required_role = "admin";
include '../includes/auth.php';
include '../config/db.php'; // for password check and update

$admin_name = $_SESSION['admin_name'] ?? "Admin User";
$admin_email = $_SESSION['admin_email'] ?? "admin@gmail.com";
$admin_role = $_SESSION['role'] ?? "Administrator";
$admin_id = $_SESSION['admin_id']; // assuming this is stored at login

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE main_admin SET admin_name = ?, email = ? WHERE admin_id = ?");
        $stmt->bind_param("ssi", $new_name, $new_email, $admin_id);
        $stmt->execute();

        $_SESSION['admin_name'] = $new_name;
        $_SESSION['admin_email'] = $new_email;
        header("Location: profile.php");
        exit;
    }

    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM main_admin WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (password_verify($old_pass, $result['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE main_admin SET password = ? WHERE admin_id = ?");
            $stmt->bind_param("si", $hashed, $admin_id);
            $stmt->execute();
            $password_message = "Password changed successfully!";
        } else {
            $password_error = "Old password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Montserrat', Arial, sans-serif;
        }

        .profile-card {
            max-width: 480px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 16px rgba(229, 115, 115, 0.08);
            padding: 2rem 2.5rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #e57373;
            color: #fff;
            border-radius: 50%;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
        }

        .profile-label {
            color: #e57373;
            font-weight: 600;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>

<body>
    <?php include 'admin_layout_start.php'; ?>

    <div class="profile-card text-center">
        <div class="profile-avatar mb-2">
            <i class="bi bi-person"></i>
        </div>
        <h4 class="mb-1"><?= htmlspecialchars($admin_name) ?></h4>
        <div class="mb-3 text-muted"><?= htmlspecialchars($admin_role) ?></div>
        <div class="mb-2">
            <span class="profile-label">Email:</span>
            <span><?= htmlspecialchars($admin_email) ?></span>
        </div>
        <div class="mb-2">
            <span class="profile-label">Role:</span>
            <span><?= htmlspecialchars($admin_role) ?></span>
        </div>

        <div class="btn-group mt-3">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">Edit Profile</button>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#passwordModal">Change Password</button>
            <a href="../logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" type="text" class="form-control" value="<?= htmlspecialchars($admin_name) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($admin_email) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="update_profile" type="submit" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Change Password</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($password_error)): ?>
                        <div class="alert alert-danger"><?= $password_error ?></div>
                    <?php elseif (isset($password_message)): ?>
                        <div class="alert alert-success"><?= $password_message ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Old Password</label>
                        <input name="old_password" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input name="new_password" type="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="change_password" type="submit" class="btn btn-warning">Change Password</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'admin_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>
</body>

</html>