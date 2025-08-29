<?php
include '../config/db.php';

if (isset($_POST['blood_group'])) {
    $blood_group = "%" . $_POST['blood_group'] . "%";
    $stmt = $conn->prepare("SELECT u.user_id, u.name, u.phone, d.blood_group 
                            FROM donors d 
                            JOIN users u ON d.user_id = u.user_id 
                            WHERE d.blood_group LIKE ?");
    $stmt->bind_param("s", $blood_group);
    $stmt->execute();
    $result = $stmt->get_result();

    $donors = [];
    while ($row = $result->fetch_assoc()) {
        $donors[] = $row;
    }
    echo json_encode($donors);
}
