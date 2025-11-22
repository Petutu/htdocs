async function fetchUnread() {
  const badge = document.getElementById("unreadCount");
  if (!badge) return; // stránka bez badge

  try {
    const resp = await fetch("/actions/unread_count.php", {
      cache: "no-store"
    });
    if (!resp.ok) return;

    const data = await resp.json();
    const n = Number(data.unread ?? 0);

    badge.textContent = n;

    // optické zvýraznění, když jsou nové zprávy
    if (n > 0) {
      badge.classList.add("has-unread");
    } else {
      badge.classList.remove("has-unread");
    }
  } catch (e) {
    // v případě chyby prostě nic – žádné alerty
    console.error("Unread fetch failed", e);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  // první načtení hned
  fetchUnread();
  // a pak každých 10 sekund
  setInterval(fetchUnread, 10000);
});
