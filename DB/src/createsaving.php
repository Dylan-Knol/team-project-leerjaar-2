<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

// Let op: login.php moet $_SESSION['user_id'] = $row['id']; zetten
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";
$errors = [];

// categorieën ophalen uit de database
$categories = [];
$result = $conn->query("SELECT id, name FROM categories ORDER BY id");
while ($row = $result->fetch_assoc()) {
  $categories[] = $row;
}

// standaardwaarden voor het formulier
$categoryId = $categories[0]['id'] ?? 0;
$name = "";
$amount = "";
$month = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoryId = (int) ($_POST['category_id'] ?? 0);
  $name = trim($_POST['name'] ?? "");
  $amount = $_POST['amount'] ?? "";
  $month = $_POST['target_month'] ?? ""; // formaat: 2027-03

  // categorie moet bestaan
  $validCategory = false;
  foreach ($categories as $c) {
    if ((int) $c['id'] === $categoryId) {
      $validCategory = true;
    }
  }
  if (!$validCategory) {
    $errors[] = "Kies een categorie.";
  }

  if ($name === "" || strlen($name) > 100) {
    $errors[] = "Geef je doel een naam (maximaal 100 tekens).";
  }

  if (!is_numeric($amount) || $amount <= 0) {
    $errors[] = "Vul een bedrag in dat groter is dan 0.";
  }

  if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $errors[] = "Kies een streefdatum.";
  } else {
    $targetDate = date('Y-m-t', strtotime($month . "-01")); // laatste dag van de maand
    if ($targetDate <= date('Y-m-d')) {
      $errors[] = "De streefdatum moet in de toekomst liggen.";
    }
  }

  if (empty($errors)) {
    $stmt = $conn->prepare(
      "INSERT INTO goals (user_id, category_id, name, target_amount, target_date) VALUES (?, ?, ?, ?, ?)"
    );
    $amountValue = (float) $amount;
    $stmt->bind_param("iisds", $_SESSION['user_id'], $categoryId, $name, $amountValue, $targetDate);
    $stmt->execute();
    $stmt->close();

    header("Location: saving_overview.php");
    exit();
  }
}

// icoontjes per categorie (emoji, geen extra bestanden nodig)
$icons = [
  'Travel' => '✈️',
  'Home' => '🏠',
  'Safety' => '🛡️',
  'Other' => '✨',
];
?>
<!DOCTYPE html>
<html lang="nl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Nieuw spaardoel</title>
    <link rel="stylesheet" href="password.css">
</head>

<body>
  <div class="screen">
    <div class="topbar">
      <a class="close" href="overview.php" aria-label="Sluiten">✕</a>
      <span>Nieuw doel</span>
    </div>

    <h1>Waar spaar je voor?</h1>
    <p class="subtitle">Stel een duidelijk doel en we helpen je met een simpel plan.</p>

    <?php if (!empty($errors)) { ?>
      <div class="errors">
        <?php foreach ($errors as $error) {
          echo "<p>" . htmlspecialchars($error) . "</p>";
        } ?>
      </div>
    <?php } ?>

    <form method="POST" action="createsaving.php" style="display:flex; flex-direction:column; flex:1;">

      <div class="field">
        <span class="label">Categorie</span>
        <div class="categories">
          <?php foreach ($categories as $c) { ?>
            <div>
              <input type="radio" name="category_id" id="cat<?php echo $c['id']; ?>"
                value="<?php echo $c['id']; ?>" <?php if ((int) $c['id'] === $categoryId) echo "checked"; ?>>
              <label class="category" for="cat<?php echo $c['id']; ?>">
                <span class="icon"><?php echo $icons[$c['name']] ?? '⭐'; ?></span>
                <?php echo htmlspecialchars($c['name']); ?>
              </label>
            </div>
          <?php } ?>
        </div>
      </div>

      <div class="field">
        <label for="name">Naam van je doel</label>
        <input class="input" type="text" id="name" name="name" placeholder="Bijv. Japan reis" maxlength="100"
          value="<?php echo htmlspecialchars($name); ?>" required>
      </div>

      <div class="field">
        <label for="amount">Streefbedrag</label>
        <div class="amount-wrap">
          <span class="prefix">€</span>
          <input class="input" type="number" id="amount" name="amount" placeholder="5000" min="1" step="0.01"
            value="<?php echo htmlspecialchars($amount); ?>" required>
          <span class="suffix">EUR</span>
        </div>
      </div>

      <div class="field">
        <label for="target_month">Streefdatum</label>
        <input class="input" type="month" id="target_month" name="target_month"
          min="<?php echo date('Y-m', strtotime('+1 month')); ?>"
          value="<?php echo htmlspecialchars($month); ?>" required>
      </div>

      <div class="plan">
        <div class="plan-icon">💡</div>
        <span id="planText">Vul een bedrag en datum in, dan berekenen we hoeveel je per maand moet sparen.</span>
      </div>

      <div class="spacer"></div>

      <button type="submit" class="submit">Doel toevoegen</button>
    </form>
  </div>

  <script>
    const amountInput = document.getElementById('amount');
    const monthInput = document.getElementById('target_month');
    const planText = document.getElementById('planText');

    function updatePlan() {
      const amount = parseFloat(amountInput.value);
      const target = monthInput.value; // "2027-03"

      if (!amount || amount <= 0 || !target) {
        planText.textContent = 'Vul een bedrag en datum in, dan berekenen we hoeveel je per maand moet sparen.';
        return;
      }

      const [year, month] = target.split('-').map(Number);
      const now = new Date();
      const months = (year - now.getFullYear()) * 12 + (month - (now.getMonth() + 1));

      if (months < 1) {
        planText.textContent = 'Kies een streefdatum in de toekomst.';
        return;
      }

      const perMonth = Math.ceil(amount / months);
      planText.textContent = 'Voorgesteld plan: spaar € ' + perMonth + ' per maand om op tijd je doel te halen.';
    }

    amountInput.addEventListener('input', updatePlan);
    monthInput.addEventListener('input', updatePlan);
    updatePlan();
  </script>
</body>

</html>