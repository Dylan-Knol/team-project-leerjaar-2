<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Savings Overview</title>
  <link rel="stylesheet" href="password.css">
  <script src="script.js" defer></script>
</head>
<body class="savings">

<div class="phone">
  <main class="content">

    <header class="header pad">
      <div>
        <p class="greeting" id="greeting"></p>
        <h1>Your savings</h1>
      </div>
      <a class="avatar" href="profile.php">👤</a>
    </header>

    <div class="pad">
      <div class="hero">
        <div class="hero-top">
          <p class="hero-label">Total saved</p>
          <button class="eye" id="eye"></button>
        </div>
        <p class="hero-amount" id="amount"></p>
        <div class="hero-foot">
          <span class="trend">↗ <span id="month"></span></span>
          <span id="count"></span>
        </div>
      </div>

      <div class="section-head">
        <h2>Savings goals</h2>
        <a class="link" href="goals.php">View all</a>
      </div>
      <ul class="goal-list" id="home-goals"></ul>

      <a class="btn-outline" href="createsaving.php">+ New savings goal</a>
    </div>

  </main>

  <nav class="tabbar pad">
    <a class="tab" href="saving_overview.php" aria-current>🏠<br>Home</a>
    <a class="tab" href="goal.php">🎯<br>Goals</a>
    <a class="tab" href="profile.php">👤<br>Profile</a>
  </nav>
</div>

</body>
</html>