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


document.addEventListener('DOMContentLoaded', function(){
  // custom select init
  document.querySelectorAll('.custom-select').forEach(function(sel){
    const hidden = sel.querySelector('input[type="hidden"]');
    const trigger = sel.querySelector('.select-trigger');
    const opts = sel.querySelectorAll('.select-options .opt');

    function closeAll(){ document.querySelectorAll('.custom-select[aria-expanded="true"]').forEach(s=>s.setAttribute('aria-expanded','false')); }
    sel.addEventListener('click', function(e){
      if(e.target.classList.contains('opt')) return;
      const expanded = sel.getAttribute('aria-expanded') === 'true';
      closeAll();
      sel.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    });
    sel.addEventListener('keydown', function(e){
      if(e.key === 'Escape') sel.setAttribute('aria-expanded','false');
    });
    opts.forEach(function(opt){
      opt.addEventListener('click', function(){
        const val = opt.getAttribute('data-value') || '';
        if(opt.classList.contains('disabled')) return;
        hidden.value = val;
        trigger.textContent = opt.textContent;
        sel.setAttribute('aria-expanded','false');
        // update aria-selected
        opts.forEach(o=>o.setAttribute('aria-selected','false'));
        opt.setAttribute('aria-selected','true');
      });
    });
    // close on outside click
    document.addEventListener('click', function(e){
      if(!sel.contains(e.target)) sel.setAttribute('aria-expanded','false');
    });
  });

  // custom file input
  const fileInput = document.getElementById('photoInput');
  if(fileInput){
    const parent = fileInput.closest('.file-wrap');
    const nameEl = parent.querySelector('.file-name');
    const preview = parent.querySelector('.file-preview');
    const clearBtn = parent.querySelector('.btn-clear');

    fileInput.addEventListener('change', function(){
      const f = this.files && this.files[0];
      if(!f){ nameEl.textContent = 'Soubor nevybrán'; preview.style.display='none'; clearBtn.style.display='none'; return; }
      nameEl.textContent = f.name;
      clearBtn.style.display = 'inline-block';
      // image preview
      if(f.type.startsWith('image/')){
        const reader = new FileReader();
        reader.onload = function(ev){
          preview.innerHTML = '<img src="'+ev.target.result+'" alt="náhled"/>';
          preview.style.display = 'flex';
        };
        reader.readAsDataURL(f);
      } else {
        preview.style.display='none';
      }
    });

    clearBtn.addEventListener('click', function(){
      fileInput.value = '';
      nameEl.textContent = 'Soubor nevybrán';
      preview.style.display='none';
      this.style.display='none';
    });
  }

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