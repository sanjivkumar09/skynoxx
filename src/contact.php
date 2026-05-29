<?php
// contact.php

include 'includes/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle form submission
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Here you would typically send the message to an email or store it in the database
    // For demonstration, we'll just display a success message
    echo "<div class='alert alert-success'>Thank you, $name! Your message has been sent.</div>";
}
?>

<div class="container">
    <h2>Contact Us</h2>
    <form method="POST" action="contact.php">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
</div>

<?php
include 'includes/footer.php';
?>