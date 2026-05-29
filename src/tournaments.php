<?php
// tournaments.php

include 'db.php';
include 'includes/header.php';

// Fetch tournaments from the database
$query = "SELECT * FROM tournaments WHERE status = 'upcoming' ORDER BY date, time";
$result = mysqli_query($conn, $query);

?>

<div class="container">
    <h1 class="mt-5">Upcoming Tournaments</h1>
    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Entry Fee</th>
                <th>Prize Pool</th>
                <th>Date</th>
                <th>Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($tournament = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tournament['title']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['description']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['entry_fee']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['prize_pool']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['date']); ?></td>
                    <td><?php echo htmlspecialchars($tournament['time']); ?></td>
                    <td>
                        <a href="join_tournament.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">Join</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
include 'includes/footer.php';
?>