// Frontend validace pro registraci – žádný backend, jen upozornění
function $(sel,el=document){ return el.querySelector(sel); }
function showErrors(errors){ if (errors.length) alert(errors.join('\n')); }


const rePhone = /^\+?[0-9\s-]{7,20}$/;
const reLogin = /^[A-Za-z0-9_]{4,30}$/;


function checkImageType(file){
if (!file) return 'Profilová fotka je povinná';
const ok = ['image/jpeg','image/png','image/gif','image/bmp'];
if (!ok.includes(file.type)) return 'Podporované formáty: JPEG/PNG/GIF/BMP';
return null;
}


document.addEventListener('DOMContentLoaded', () => {
const form = document.getElementById('registerForm');
if (!form) return;
form.addEventListener('submit', (e) => {
e.preventDefault();
const errors = [];
const first = form.first_name.value.trim();
const last = form.last_name.value.trim();
const email = form.email.value.trim();
const phone = form.phone.value.trim();
const gender = form.gender.value;
const login = form.login.value.trim();
const pass = form.password.value;
const pass2 = form.password2.value;
const photo = form.photo.files[0];


if (!first) errors.push('Jméno je povinné');
if (!last) errors.push('Příjmení je povinné');
if (!email || !form.email.checkValidity()) errors.push('Neplatný email');
if (!rePhone.test(phone)) errors.push('Neplatný telefon');
if (!gender) errors.push('Zvolte pohlaví');
if (!reLogin.test(login)) errors.push('Login: 4–30 znaků, písmena/čísla/_');
if (pass.length < 10) errors.push('Heslo musí mít alespoň 10 znaků');
if (pass !== pass2) errors.push('Hesla se neshodují');


const imgErr = checkImageType(photo);
if (imgErr) errors.push(imgErr);


if (errors.length) { showErrors(errors); return; }


// Simulace úspěšné registrace (frontend-only)
alert('Registrace OK (frontend-only).');
window.location.href = 'login.html';
});
});