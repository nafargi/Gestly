<?php
require_once 'config.php';

// Initialize variables
$message = '';
$error = '';
$guests = array();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_guest'])) {
        // Add new guest
        $firstname = sanitize_input($_POST['firstname']);
        $lastname = sanitize_input($_POST['lastname']);
        $email = sanitize_input($_POST['email']);
        
        // Using prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO guests (firstname, lastname, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $firstname, $lastname, $email);
        
        if ($stmt->execute()) {
            $last_id = $conn->insert_id;
            $message = "New guest added successfully! Guest ID: " . $last_id;
        } else {
            $error = "Error adding guest: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_guest'])) {
        // Update guest
        $id = intval($_POST['guest_id']);
        $firstname = sanitize_input($_POST['firstname']);
        $lastname = sanitize_input($_POST['lastname']);
        $email = sanitize_input($_POST['email']);
        
        $stmt = $conn->prepare("UPDATE guests SET firstname=?, lastname=?, email=? WHERE id=?");
        $stmt->bind_param("sssi", $firstname, $lastname, $email, $id);
        
        if ($stmt->execute()) {
            $message = "Guest updated successfully!";
        } else {
            $error = "Error updating guest: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_guest'])) {
        // Delete guest
        $id = intval($_POST['guest_id']);
        
        $stmt = $conn->prepare("DELETE FROM guests WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Guest deleted successfully!";
        } else {
            $error = "Error deleting guest: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['add_multiple'])) {
        // Add multiple guests (transaction example)
        $conn->autocommit(FALSE); // Start transaction
        
        try {
            $stmt = $conn->prepare("INSERT INTO guests (firstname, lastname, email) VALUES (?, ?, ?)");
            
            // Guest 1
            $firstname = "John";
            $lastname = "Smith";
            $email = "john.smith@example.com";
            $stmt->bind_param("sss", $firstname, $lastname, $email);
            $stmt->execute();
            
            // Guest 2
            $firstname = "Sarah";
            $lastname = "Johnson";
            $email = "sarah.j@example.com";
            $stmt->bind_param("sss", $firstname, $lastname, $email);
            $stmt->execute();
            
            // Guest 3
            $firstname = "Michael";
            $lastname = "Brown";
            $email = "michael.b@example.com";
            $stmt->bind_param("sss", $firstname, $lastname, $email);
            $stmt->execute();
            
            $conn->commit();
            $message = "Multiple guests added successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding multiple guests: " . $e->getMessage();
        }
        $stmt->close();
        $conn->autocommit(TRUE); // Restore autocommit
    }
}

// Pagination setup
$records_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_pages_sql = "SELECT COUNT(*) FROM guests";
$result = $conn->query($total_pages_sql);
$total_rows = $result->fetch_array()[0];
$total_pages = ceil($total_rows / $records_per_page);

// Get guests for current page
$sql = "SELECT * FROM guests ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $guests = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all guests for dropdowns
$all_guests_sql = "SELECT id, firstname, lastname FROM guests ORDER BY lastname, firstname";
$all_guests_result = $conn->query($all_guests_sql);
$all_guests = $all_guests_result->fetch_all(MYSQLI_ASSOC);

require_once 'header.php';
?>

<!-- View Guests Tab -->
<div class="tab-pane fade show active" id="view" role="tabpanel">
    <div class="card">
        <div class="card-header">
            Guest List
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($guests) > 0): ?>
                            <?php foreach ($guests as $guest): ?>
                                <tr>
                                    <td><?php echo $guest['id']; ?></td>
                                    <td><?php echo htmlspecialchars($guest['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['email']); ?></td>
                                    <td><?php echo $guest['reg_date']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No guests found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Guest Tab -->
<div class="tab-pane fade" id="add" role="tabpanel">
    <div class="card">
        <div class="card-header">
            Add New Guest
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                <button type="submit" name="add_guest" class="btn btn-primary">Add Guest</button>
            </form>
            
            <hr>
            
            <h5>Add Multiple Guests (Example)</h5>
            <form method="POST" action="">
                <p>This will add 3 sample guests in a transaction:</p>
                <button type="submit" name="add_multiple" class="btn btn-secondary">Add Multiple Guests</button>
            </form>
        </div>
    </div>
</div>

<!-- Update Guest Tab -->
<div class="tab-pane fade" id="update" role="tabpanel">
    <div class="card">
        <div class="card-header">
            Update Guest
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="update_guest_id">Select Guest</label>
                    <select class="form-control" id="update_guest_id" name="guest_id" required>
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($all_guests as $guest): ?>
                            <option value="<?php echo $guest['id']; ?>">
                                <?php echo htmlspecialchars($guest['firstname'] . ' ' . $guest['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="update_firstname">First Name</label>
                    <input type="text" class="form-control" id="update_firstname" name="firstname" required>
                </div>
                <div class="form-group">
                    <label for="update_lastname">Last Name</label>
                    <input type="text" class="form-control" id="update_lastname" name="lastname" required>
                </div>
                <div class="form-group">
                    <label for="update_email">Email</label>
                    <input type="email" class="form-control" id="update_email" name="email">
                </div>
                <button type="submit" name="update_guest" class="btn btn-warning">Update Guest</button>
            </form>
        </div>
    </div>
</div>

<!-- Delete Guest Tab -->
<div class="tab-pane fade" id="delete" role="tabpanel">
    <div class="card">
        <div class="card-header">
            Delete Guest
        </div>
        <div class="card-body">
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this guest?');">
                <div class="form-group">
                    <label for="delete_guest_id">Select Guest</label>
                    <select class="form-control" id="delete_guest_id" name="guest_id" required>
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($all_guests as $guest): ?>
                            <option value="<?php echo $guest['id']; ?>">
                                <?php echo htmlspecialchars($guest['firstname'] . ' ' . $guest['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="delete_guest" class="btn btn-danger">Delete Guest</button>
            </form>
        </div>
    </div>
</div>

<?php
// Close connection
$conn->close();

// Footer
require_once 'footer.php';
?>