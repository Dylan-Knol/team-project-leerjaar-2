<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";

$currency = '$';

function money($value, $decimals = 2)
{
  global $currency;
  return $currency . number_format((float) $value, $decimals);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Geen id meegegeven (bijv. direct na het inloggen): toon het eerstvolgende
// actieve doel van de ingelogde gebruiker.
if ($id <= 0 && isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT id FROM goals WHERE user_id = ? ORDER BY status = 'completed', target_date LIMIT 1");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $first = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $id = $first ? (int) $first['id'] : 0;
}

if ($id <= 0) {
  header("Location: login.php");
  exit();
}

// Doel + categorie ophalen.
// Als login.php $_SESSION['user_id'] opslaat, mag je alleen je eigen doelen zien.
$sql = "SELECT g.*, c.name AS category_name, c.icon AS category_icon
        FROM goals g
        JOIN categories c ON c.id = g.category_id
        WHERE g.id = ?";
if (isset($_SESSION['user_id'])) {
  $sql .= " AND g.user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id, $_SESSION['user_id']);
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
}
$stmt->execute();
$goal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$goal) {
  header("Location: goals.php");
  exit();
}

$error = "";

// Geld toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $amount = (float) str_replace(',', '.', $_POST['amount'] ?? '0');

  if ($amount <= 0) {
    $error = "Vul een bedrag in dat groter is dan 0.";
  } elseif ($amount > 99999999) {
    $error = "Dit bedrag is te groot.";
  } else {
    $stmt = $conn->prepare("INSERT INTO transactions (goal_id, amount, type, transaction_date) VALUES (?, ?, 'manual', CURDATE())");
    $stmt->bind_param("id", $id, $amount);
    $stmt->execute();
    $stmt->close();

    header("Location: goal.php?id=" . $id);
    exit();
  }
}

// Totaal gespaard
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS saved FROM transactions WHERE goal_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$saved = (float) $stmt->get_result()->fetch_assoc()['saved'];
$stmt->close();

// Laatste transacties
$stmt = $conn->prepare("SELECT amount, type, transaction_date FROM transactions WHERE goal_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 10");
$stmt->bind_param("i", $id);
$stmt->execute();
$activity = $stmt->get_result();

// Berekeningen
$target = (float) $goal['target_amount'];
$percent = $target > 0 ? min(100, (int) round($saved / $target * 100)) : 0;
$remaining = max(0, $target - $saved);

$now = time();
$start = strtotime($goal['created_at']);
$end = strtotime($goal['target_date'] . ' 23:59:59');
$daysLeft = max(0, (int) ceil(($end - $now) / 86400));
$monthsLeft = max(1, (int) ceil($daysLeft / 30.44));
$monthly = $remaining / $monthsLeft;

$fraction = $end > $start ? min(1, max(0, ($now - $start) / ($end - $start))) : 1;
$expected = $target * $fraction;

if ($saved >= $target) {
  $statusTitle = "Goal reached";
  $statusText = "You saved the full amount. Nice work!";
  $statusClass = "ok";
} elseif ($end < $now) {
  $statusTitle = "Past target date";
  $statusText = money($remaining) . " still to go.";
  $statusClass = "late";
} elseif ($saved >= $expected) {
  $statusTitle = "On track";
  $statusText = "Save " . money($monthly, 0) . " monthly to reach your goal.";
  $statusClass = "ok";
} else {
  $statusTitle = "Behind schedule";
  $statusText = "Save " . money($monthly, 0) . " monthly to catch up.";
  $statusClass = "late";
}

// Iconen per categorie (categories.icon)
$icons = [
  'plane' => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
  'house' => '<path d="M3 10.5 12 3l9 7.5V21H3z"/><path d="M9 21v-6h6v6"/>',
  'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/>',
  'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
];
$iconPath = $icons[$goal['category_icon']] ?? $icons['star'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($goal['name']); ?></title>
  <style>
    <?php include "password.css"; ?>
  </style>
</head>

<body>
  <div class="screen">

    <div class="topbar">
      <a class="circle-btn" href="saving_overview.php" aria-label="Back">
        <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6" /></svg>
      </a>
      <span>Goal details</span>
      <span class="circle-btn" aria-hidden="true">
        <svg viewBox="0 0 24 24"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
      </span>
    </div>

    <div class="hero">
      <div class="goal-icon">
        <svg viewBox="0 0 24 24"><?php echo $iconPath; ?></svg>
      </div>
      <h1><?php echo htmlspecialchars($goal['name']); ?></h1>
      <p class="target">Target · <?php echo date('F Y', strtotime($goal['target_date'])); ?></p>
    </div>

    <div class="card">
      <div class="amounts">
        <span class="saved"><?php echo money($saved, 0); ?></span>
        <span class="of">of <?php echo money($target, 0); ?></span>
      </div>
      <div class="bar" role="progressbar" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
        <span style="width: <?php echo $percent; ?>%"></span>
      </div>
      <div class="bar-labels">
        <b><?php echo $percent; ?>% complete</b>
        <span><?php echo money($remaining, 0); ?> to go</span>
      </div>
    </div>

    <details class="add" <?php if ($error) echo "open"; ?>>
      <summary>Add money</summary>
      <form method="POST" action="goal.php?id=<?php echo $id; ?>">
        <input type="number" name="amount" step="0.01" min="0.01" placeholder="Amount" required>
        <button type="submit">Save</button>
      </form>
      <?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
    </details>

    <div class="status <?php echo $statusClass; ?>">
      <div class="dot"></div>
      <div>
        <strong><?php echo $statusTitle; ?></strong>
        <small><?php echo $statusText; ?></small>
      </div>
      <span class="chev">›</span>
    </div>

    <h2>Recent activity</h2>
    <div class="activity">
      <?php if ($activity->num_rows === 0) { ?>
        <div class="empty">No deposits yet. Use "Add money" to make your first one.</div>
      <?php } ?>
      <?php while ($t = $activity->fetch_assoc()) { ?>
        <div class="row">
          <div class="avatar"></div>
          <div class="info">
            <strong><?php echo $t['type'] === 'auto' ? 'Weekly auto-save' : 'Added manually'; ?></strong>
            <small><?php echo date('M j', strtotime($t['transaction_date'])); ?></small>
          </div>
          <span class="plus">+<?php echo money($t['amount']); ?></span>
        </div>
      <?php } ?>
    </div>
    <?php $stmt->close(); ?>

  </div>
</body>

</html>