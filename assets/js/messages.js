// Mock zpráv pro frontend-only verzi – žádná skutečná bezpečnost/šifrování
// Struktury v localStorage:
// ohis_users: [{ login: 'player01' }, { login: 'admin' }]
// ohis_msgs_inbox: [{ id, sender, recipient, subject, body, createdAt, isRead }]
// ohis_msgs_sent: [{ id, sender, recipient, subject, body, createdAt }]


function uid(){ return Math.random().toString(36).slice(2); }
function load(key, def){ try { return JSON.parse(localStorage.getItem(key)) ?? def; } catch { return def; } }
function save(key, val){ localStorage.setItem(key, JSON.stringify(val)); }


export function seedUsers(){
const users = load('ohis_users', []);
if (users.length === 0){
save('ohis_users', [{login:'player01'},{login:'admin'},{login:'testuser'}]);
}
}


export function sendMessage({ recipient, subject, body }){
seedUsers();
const sender = 'player01'; // demo „přihlášený“ uživatel
const createdAt = Date.now();
const id = uid();
const inbox = load('ohis_msgs_inbox', []);
inbox.unshift({ id, sender, recipient, subject, body, createdAt, isRead:false });
save('ohis_msgs_inbox', inbox);
const sent = load('ohis_msgs_sent', []);
sent.unshift({ id, sender, recipient, subject, body, createdAt });
save('ohis_msgs_sent', sent);
}


export function listInbox(){
seedUsers();
return load('ohis_msgs_inbox', []);
}
export function listSent(){
seedUsers();
return load('ohis_msgs_sent', []);
}
export function markRead(id){
const inbox = load('ohis_msgs_inbox', []);
const i = inbox.findIndex(m => m.id === id);
if (i>=0){ inbox[i].isRead = true; save('ohis_msgs_inbox', inbox); }
}
export function getUnreadCount(){
const inbox = load('ohis_msgs_inbox', []);
return inbox.filter(m => !m.isRead).length;
}

export function filterMessages(items, filter) {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const week = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
  const month = new Date(now.getFullYear(), now.getMonth(), 1);

  return items.filter(m => {
    const date = new Date(m.createdAt);
    switch(filter) {
      case 'today': return date >= today;
      case 'week': return date >= week;
      case 'month': return date >= month;
      default: return true;
    }
  });
}