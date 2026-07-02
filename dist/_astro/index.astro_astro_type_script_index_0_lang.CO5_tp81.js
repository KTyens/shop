var e=Array.isArray(window.__CATALOG__)?window.__CATALOG__:[],t=1200,n=`crtlu-cart-v1`,r=[],i=`all`,a=``,o=`featured`,s={},c=d();function l(e){return e.flatMap(e=>e.variants.map(t=>({...t,seriesId:e.id,seriesName:e.name,brand:e.brand,tier:e.tier,category:e.category,description:e.description})))}function u(e){return r.find(t=>t.id===e)}function d(){try{let e=JSON.parse(localStorage.getItem(n)||`[]`);return Array.isArray(e)?e:[]}catch{return[]}}function f(){localStorage.setItem(n,JSON.stringify(c))}function p(e){return Math.min(...e.variants.map(e=>e.price_cents))}function m(e){if(e===`all`)return`All`;if(e.startsWith(`category:`)){let t=e.slice(9);return t===`tv-box`?`TV Boxes`:t===`premium`?`Premium`:t===`projector`?`Projectors`:t}return e.startsWith(`brand:`)?e.slice(6):e.startsWith(`tier:`)?{budget:`Budget`,"best-value":`Best Value`,main:`Main`,performance:`Performance`,flagship:`Flagship`}[e.slice(5)]||e.slice(5):e}function h(e){return[e.id,e.name,e.brand,e.category,e.tier,e.description,...e.specs?Object.values(e.specs):[],...e.variants.flatMap(e=>[e.id,e.label,e.sku,String(e.price_cents)])].join(` `).toLowerCase()}function g(){let t=a.trim().toLowerCase().split(/\s+/).filter(Boolean),n=e;i.startsWith(`category:`)?n=n.filter(e=>e.category===i.slice(9)):i.startsWith(`brand:`)?n=n.filter(e=>e.brand===i.slice(6)):i.startsWith(`tier:`)&&(n=n.filter(e=>e.tier===i.slice(5))),t.length&&(n=n.filter(e=>t.every(t=>h(e).includes(t))));let r=[...n];return o===`price-asc`&&r.sort((e,t)=>p(e)-p(t)||e.name.localeCompare(t.name)),o===`price-desc`&&r.sort((e,t)=>p(t)-p(e)||e.name.localeCompare(t.name)),o===`name-asc`&&r.sort((e,t)=>e.name.localeCompare(t.name)),r}function _(){let e=g(),t=e.reduce((e,t)=>e+t.variants.length,0);if(document.getElementById(`summary`).innerHTML=`<strong>${e.length}</strong> models / <strong>${t}</strong> configs / Filter: <strong>${m(i)}</strong>`+(a.trim()?` / Search: "${a.trim()}"`:``),!e.length){document.getElementById(`productGrid`).innerHTML=`
          <div class="empty-state">
            <div>
              <strong>No matches found</strong><br />
              Try adjusting your search or filters.
            </div>
          </div>`;return}document.getElementById(`productGrid`).innerHTML=e.map(e=>{let t=s[e.id]||e.variants[0].id,n=e.variants.find(e=>e.id===t)||e.variants[0];return`
          <article class="product-card reveal" id="${e.id}">
            ${e.image?`<div class="product-media"><img src="${e.image}" alt="${e.name}" loading="lazy" decoding="async"></div>`:``}
            <div class="card-head">
              <div class="meta"><span>${e.brand}</span><span>${e.tier.replace(`-`,` `)}</span></div>
              <h2>${e.name}</h2>
              <p>${e.description}</p>
            </div>
            <div class="card-body">
              ${e.variants.map(t=>`
                <div class="variant-row ${t.id===n.id?`active`:``}">
                  <button type="button" data-select="${e.id}" data-variant="${t.id}">
                    <strong>${t.label}</strong>
                    <span>${t.sku}</span>
                  </button>
                  <div class="price">$${(t.price_cents/100).toFixed(2)}${t.compare_at_cents&&t.compare_at_cents!==t.price_cents?`<span class="compare">$${(t.compare_at_cents/100).toFixed(2)}</span>`:``}</div>
                </div>
              `).join(``)}
              <div class="card-foot">
                <span class="sku">Selected: ${n.sku}</span>
                <span class="card-actions">
                  <a class="secondary-button" href="/products/${e.id}/" style="padding:0 12px;font-size:12px;">Details</a>
                  <button class="primary-button" type="button" data-add="${n.id}" style="padding:0 12px;font-size:12px;">Add to Cart</button>
                </span>
              </div>
            </div>
          </article>`}).join(``),v()}function v(){let e=new IntersectionObserver(e=>{e.forEach(e=>{e.isIntersecting&&e.target.classList.add(`visible`)})},{threshold:.12});document.querySelectorAll(`.reveal:not(.visible)`).forEach(t=>e.observe(t))}function y(){let t=[...new Set(e.map(e=>e.brand))].sort(),n={"tv-box":`TV Boxes`,premium:`Premium`,projector:`Projectors`},r=[`tv-box`,`premium`,`projector`].filter(t=>e.some(e=>e.category===t)).map(e=>[`category:${e}`,n[e]||e]),a=[[`budget`,`Budget`],[`best-value`,`Best Value`],[`main`,`Main`],[`performance`,`Performance`],[`flagship`,`Flagship`]].filter(([t])=>e.some(e=>e.tier===t)),o=[[`all`,`All`],...r,...t.map(e=>[`brand:${e}`,e]),...a.map(([e,t])=>[`tier:${e}`,t])];document.getElementById(`filters`).innerHTML=o.map(([e,t])=>`<button class="filter ${e===i?`active`:``}" type="button" data-filter="${e}">${t}</button>`).join(``)}function b(){c=c.filter(e=>u(e.id)),f();let e=c.reduce((e,t)=>e+t.qty,0),n=c.reduce((e,t)=>e+u(t.id).price_cents*t.qty,0),r=n>0?t:0;document.getElementById(`cartCount`).textContent=String(e),document.getElementById(`cartSubtotal`).textContent=`$`+(n/100).toFixed(2),document.getElementById(`cartShipping`).textContent=n>0?`$12.00`:`$0.00`,document.getElementById(`cartTotal`).textContent=`$`+((n+r)/100).toFixed(2),document.getElementById(`checkoutButton`).disabled=e===0,document.getElementById(`cartItems`).innerHTML=c.length?c.map(e=>{let t=u(e.id);return t?`
              <div class="cart-item">
                <div>
                  <h3>${t.seriesName}</h3>
                  <p>${t.label} / ${t.sku}</p>
                  <div class="quantity">
                    <button class="qty-btn" type="button" data-qty="${t.id}" data-change="-1">-</button>
                    <span>${e.qty}</span>
                    <button class="qty-btn" type="button" data-qty="${t.id}" data-change="1">+</button>
                  </div>
                  <button class="remove" type="button" data-remove="${t.id}">Remove</button>
                </div>
                <strong class="cart-price">$${(t.price_cents*e.qty/100).toFixed(2)}</strong>
              </div>`:``}).join(``):`<div class="empty">Your cart is empty.</div>`,document.querySelectorAll(`[data-qty]`).forEach(e=>{e.addEventListener(`click`,()=>{let t=e.getAttribute(`data-qty`),n=parseInt(e.getAttribute(`data-change`)),r=c.find(e=>e.id===t);r&&(r.qty=Math.max(1,r.qty+n),f(),b())})}),document.querySelectorAll(`[data-remove]`).forEach(e=>{e.addEventListener(`click`,()=>{let t=e.getAttribute(`data-remove`);c=c.filter(e=>e.id!==t),f(),b()})})}function x(){document.body.style.overflow=`hidden`,document.getElementById(`cartDrawer`).classList.add(`open`),document.getElementById(`cartDrawer`).setAttribute(`aria-hidden`,`false`),document.getElementById(`cartBackdrop`).classList.add(`open`)}function S(){document.body.style.overflow=``,document.getElementById(`cartDrawer`).classList.remove(`open`),document.getElementById(`cartDrawer`).setAttribute(`aria-hidden`,`true`),document.getElementById(`cartBackdrop`).classList.remove(`open`)}function C(e){let t=c.find(t=>t.id===e);t?t.qty+=1:c.push({id:e,qty:1}),f(),b(),x()}async function w(){let e=document.getElementById(`checkoutStatus`);e.className=`status`,e.textContent=`Creating checkout session...`;let t=e=>window.crtluApiUrl?window.crtluApiUrl(e):e;try{let e=await fetch(t(`/api/create-checkout-session.php`),{method:`POST`,credentials:`include`,headers:{"Content-Type":`application/json`},body:JSON.stringify({items:c})}),n=await e.json();if(!e.ok||!n.url)throw Error(n.error||`Checkout not configured`);window.location.href=n.url}catch(t){e.className=`status error`,e.textContent=t instanceof Error?t.message:`Something went wrong`}}function T(){r=l(e),y(),_(),b(),v(),document.getElementById(`filters`).addEventListener(`click`,e=>{let t=e.target.closest(`[data-filter]`);t&&(i=t.dataset.filter,y(),_())});let t=document.getElementById(`catalogSearch`);t.addEventListener(`input`,e=>{a=e.target.value,_()}),document.getElementById(`clearSearch`).addEventListener(`click`,()=>{a=``,t.value=``,t.focus(),_()}),document.getElementById(`catalogSort`).addEventListener(`change`,e=>{o=e.target.value,_()}),document.getElementById(`productGrid`).addEventListener(`click`,e=>{let t=e.target.closest(`[data-select]`);t&&(s[t.dataset.select]=t.dataset.variant,_());let n=e.target.closest(`[data-add]`);n&&C(n.dataset.add)}),document.getElementById(`openCart`).addEventListener(`click`,x),document.getElementById(`closeCart`).addEventListener(`click`,S),document.getElementById(`cartBackdrop`).addEventListener(`click`,S),document.getElementById(`checkoutButton`).addEventListener(`click`,w);let n=document.getElementById(`mobileOpenCart`);n&&n.addEventListener(`click`,x)}T();