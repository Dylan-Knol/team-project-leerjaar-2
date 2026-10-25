const goals = JSON.parse(localStorage.getItem("goals")) || [
  { name: "Japan trip", saved: 2840, target: 5000, icon: "✈️" },
  { name: "Emergency fund", saved: 6200, target: 10000, icon: "🛡️" },
];
const savedThisMonth = 620;
let hidden = false;

const $ = (id) => document.getElementById(id);
const set = (id, html) => {
  if ($(id)) $(id).innerHTML = html;
};
const money = (n, d = 0) => "$" + n.toLocaleString("en-US", { minimumFractionDigits: d, maximumFractionDigits: d });
const percent = (goal) => Math.round((goal.saved / goal.target) * 100);

const eyeIcon = (slash) => `<svg width="20" height="20" viewBox="0 0 24 24"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>${slash ? '<path d="M3 3l18 18"/>' : ""}</svg>`;

function goalHTML(goal, showRemaining) {
  return `
    <li class="goal">
      <span class="goal-icon">${goal.icon}</span>
      <div class="goal-body">
        <div class="goal-top">
          <span class="goal-name">${goal.name}</span>
          <span class="goal-percent">${percent(goal)}%</span>
        </div>
        <div class="bar"><i style="width: ${percent(goal)}%"></i></div>
        <p class="goal-amounts">
          <span>${money(goal.saved)} of ${money(goal.target)}</span>
          ${showRemaining ? `<span>${money(goal.target - goal.saved)} to go</span>` : ""}
        </p>
      </div>
    </li>`;
}

function render() {
  const total = goals.reduce((sum, goal) => sum + goal.saved, 0);
  const hour = new Date().getHours();

  set("greeting", hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening");
  set("amount", hidden ? "$ ••••••" : money(total, 2));
  set("month", `${money(savedThisMonth)} this month`);
  set("count", `${goals.length} active goals`);
  set("eye", eyeIcon(hidden));
  set(
    "home-goals",
    goals
      .slice(0, 3)
      .map((g) => goalHTML(g, false))
      .join(""),
  );
  set("all-goals", goals.map((g) => goalHTML(g, true)).join(""));
}

document.onclick = (event) => {
  if (event.target.closest("#eye")) {
    hidden = !hidden;
    render();
  }
};

render();
