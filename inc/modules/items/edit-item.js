/**
 * Edit Item JS — Clean Row Meta (v2.1)
 * - Muestra meta centrada y cambia FREE → $0.00
 */

document.addEventListener("DOMContentLoaded", () => {
  const wrap = document.querySelector(".knx-edit-item-wrapper");
  if (!wrap) return;

  const api = {
    get: wrap.dataset.apiGet,
    update: wrap.dataset.apiUpdate,
    cats: wrap.dataset.apiCats,
    list: wrap.dataset.apiModifiers,
    globals: wrap.dataset.apiGlobalModifiers,
    clone: wrap.dataset.apiCloneModifier,
    saveMod: wrap.dataset.apiSaveModifier,
    delMod: wrap.dataset.apiDeleteModifier,
    reMod: wrap.dataset.apiReorderModifier,
    saveOpt: wrap.dataset.apiSaveOption,
    delOpt: wrap.dataset.apiDeleteOption,
    reOpt: wrap.dataset.apiReorderOption,
  };

  const state = {
    hubId: wrap.dataset.hubId,
    itemId: wrap.dataset.itemId,
    nonce: wrap.dataset.nonce,
    modifiers: [],
  };

  // Elements
  const nameInput = document.getElementById("knxItemName");
  const descInput = document.getElementById("knxItemDescription");
  const priceInput = document.getElementById("knxItemPrice");
  const catSelect = document.getElementById("knxItemCategory");
  const imageInput = document.getElementById("knxItemImage");
  const imagePreview = document.getElementById("knxItemPreview");
  const modifiersList = document.getElementById("knxModifiersList");

  const toast = (m, t) => (typeof knxToast === "function" ? knxToast(m, t || "success") : alert(m));
  const esc = (s) => (s || "").toString().replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const setPreview = (url) => (imagePreview.innerHTML = `<img src="${url}" alt="Item image">`);

  /* ===== Extra helpers (Nexus-consistent) ===== */
  function priceTextUSD(n){
    const val = parseFloat(n || 0);
    return val === 0 ? '<span class="knx-free-badge">FREE</span>' : `<span class="knx-option-price">+$${val.toFixed(2)}</span>`;
  }

  function badgesForModifier(mod){
    const type = mod.type === 'single' ? 'Single' : 'Multiple';
    const req = mod.required == 1 
      ? '<span class="knx-badge knx-badge-red">Required</span>' 
      : '<span class="knx-badge">Optional</span>';
    
    let range = '';
    if (mod.type === 'multiple' && (mod.min_selection > 0 || mod.max_selection)){
      const min = mod.min_selection > 0 ? mod.min_selection : 0;
      const max = mod.max_selection ? mod.max_selection : '∞';
      range = `<span class="knx-badge">${min}-${max}</span>`;
    }
    
    const global = mod.is_global == 1 ? '<span class="knx-badge">🌍 Global</span>' : '';
    
    return `<span class="knx-badge">${type}</span> ${req} ${range} ${global}`;
  }

  /* Lightweight confirm modal using Nexus modal style */
  function knxConfirm(title, message, onConfirm){
    const triggerEl = document.activeElement; // Guardar elemento que abrió el modal
    const modal = document.createElement('div');
    modal.className = 'knx-modal-overlay';
    modal.innerHTML = `
      <div class="knx-modal-content" style="max-width:520px">
        <div class="knx-modal-header">
          <h3><i class="fas fa-exclamation-triangle" style="color:#dc3545"></i> ${esc(title)}</h3>
          <button class="knx-modal-close" aria-label="Close">&times;</button>
        </div>
        <div style="padding:14px;">
          <p style="margin:0; color:#374151; line-height:1.5">${esc(message)}</p>
        </div>
        <div class="knx-modal-actions">
          <button type="button" class="knx-btn-secondary knx-btn-xl knx-modal-close">Cancel</button>
          <button type="button" class="knx-btn knx-btn-xl" id="knxDoConfirm" style="background:#dc3545"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    
    const close = ()=> {
      modal.remove();
      // Devolver foco al elemento que abrió el modal
      if (triggerEl && triggerEl.focus) {
        triggerEl.focus();
      }
    };
    
    // Focus en el botón de cancelar al abrir
    setTimeout(()=>{
      const cancelBtn = modal.querySelector('.knx-modal-close');
      if (cancelBtn) cancelBtn.focus();
    }, 50);
    
    modal.querySelectorAll('.knx-modal-close').forEach(b=> b.addEventListener('click', close));
    modal.addEventListener('click', (e)=>{ if(e.target===modal) close(); });
    
    // Cerrar con Esc
    const handleEsc = (e)=>{
      if (e.key === 'Escape') {
        close();
        document.removeEventListener('keydown', handleEsc);
      }
    };
    document.addEventListener('keydown', handleEsc);
    
    modal.querySelector('#knxDoConfirm').addEventListener('click', ()=> { 
      document.removeEventListener('keydown', handleEsc);
      close(); 
      onConfirm && onConfirm(); 
    });
  }

  (async function init(){ await loadItem(); })();

  async function loadItem() {
    try {
      const r = await fetch(`${api.get}?hub_id=${state.hubId}&id=${state.itemId}`);
      const j = await r.json();
      if (!j.success || !j.item) return toast("Item not found", "error");
      const it = j.item;
      nameInput.value = it.name || "";
      descInput.value = it.description || "";
      priceInput.value = it.price || "0.00";
      setPreview(it.image_url || "https://via.placeholder.com/420x260?text=No+Image");
      await loadCategories(it.category_id);
      await loadModifiers();
    } catch { toast("Error loading item", "error"); }
  }

  async function loadCategories(sel) {
    try {
      const r = await fetch(`${api.cats}?hub_id=${state.hubId}`);
      const j = await r.json();
      catSelect.innerHTML = "";
      if (!j.success || !j.categories) return (catSelect.innerHTML = '<option value="">No categories</option>');
      j.categories.forEach((c) => {
        if (c.status === "active") {
          const o = document.createElement("option");
          o.value = c.id; o.textContent = c.name;
          if (sel && +sel === +c.id) o.selected = true;
          catSelect.appendChild(o);
        }
      });
    } catch { /* noop */ }
  }

  async function loadModifiers() {
    try {
      const r = await fetch(`${api.list}?item_id=${state.itemId}`);
      const j = await r.json();
      state.modifiers = j.success ? (j.modifiers || []) : [];
      renderModifiers();
    } catch {
      modifiersList.innerHTML = '<div class="knx-error-small">Error loading</div>';
    }
  }

  function metaText(mod) {
    const type = mod.type === "multiple" ? "Multiple" : "Single";
    const req  = mod.required == 1 ? "Required" : "Optional";
    let range = "";
    if (mod.type === "multiple") {
      const min = mod.min_selection > 0 ? mod.min_selection : 0;
      const max = mod.max_selection ? mod.max_selection : "∞";
      range = `${min}–${max}`;
    }
    return [type, req, range].filter(Boolean).join(" • ");
  }

  function renderModifiers() {
    if (!state.modifiers.length) {
      modifiersList.innerHTML = `
        <div class="knx-empty-state">
          <i class="fas fa-box-open fa-3x" style="color:#9ca3af;margin-bottom:12px;"></i>
          <h3 style="margin:8px 0;color:#6b7280;font-size:1.1rem;">No groups yet</h3>
          <p style="margin:4px 0 12px;color:#9ca3af;font-size:0.95rem;">Add your first group or browse the global library</p>
        </div>`;
      return;
    }
    modifiersList.innerHTML = state.modifiers.map(renderCard).join("");
    wireCardEvents();
  }

  function renderCard(mod) {
    const optionsHTML =
      mod.options && mod.options.length
        ? `<div class="knx-options-list">${mod.options.map((o) => renderOption(o)).join("")}</div>`
        : `<div class="knx-options-list"></div>`;

    return `
      <div class="knx-modifier-card" data-id="${mod.id}" data-sort="${mod.sort_order || 0}">
        <div class="knx-modifier-card-header" role="button" tabindex="0" aria-expanded="true">
          <button class="knx-chevron-btn" data-action="collapse" aria-label="Collapse group" title="Collapse"><i class="fas fa-chevron-up knx-chevron"></i></button>
          <div class="knx-modifier-title">
            <h4>${esc(mod.name)}</h4>
            <div class="knx-meta knx-meta-collapsed">${badgesForModifier(mod)}</div>
          </div>
          <div class="knx-modifier-actions">
            <button class="knx-icon-btn" data-action="add-option" aria-label="Add option" title="Add option"><i class="fas fa-plus"></i></button>
            <button class="knx-icon-btn" data-action="edit" aria-label="Edit group" title="Edit"><i class="fas fa-pen"></i></button>
            <button class="knx-icon-btn delete" data-action="delete" aria-label="Delete group" title="Delete"><i class="fas fa-trash"></i></button>
            <div class="knx-reorder">
              <button class="knx-icon-btn sort-up" data-action="sort-up" aria-label="Move up" title="Move up"><i class="fas fa-chevron-up"></i></button>
              <button class="knx-icon-btn sort-down" data-action="sort-down" aria-label="Move down" title="Move down"><i class="fas fa-chevron-down"></i></button>
            </div>
          </div>
        </div>
        ${optionsHTML}
      </div>`;
  }

  function renderOption(opt){
    const priceText = priceTextUSD(opt.price_adjustment);
    return `
      <div class="knx-option-item" data-option-id="${opt.id}">
        <div class="knx-option-name">${esc(opt.name)}</div>
        <div class="knx-option-price">${priceText}</div>
        <div class="knx-option-actions">
          <button class="knx-icon-btn" data-action="edit-option" aria-label="Edit option" title="Edit"><i class="fas fa-pen"></i></button>
          <button class="knx-icon-btn delete" data-action="delete-option" aria-label="Delete option" title="Delete"><i class="fas fa-trash"></i></button>
        </div>
      </div>`;
  }

  // ========== Auto-collapse groups (Task 3) ==========
  function initCollapsibleGroups() {
    const cards = Array.from(document.querySelectorAll(".knx-modifier-card"));
    
    // Auto-collapse groups starting from index 3 (4th group onwards)
    if (cards.length >= 4) {
      cards.forEach((card, index) => {
        if (index >= 3) {
          const list = card.querySelector(".knx-options-list");
          const collapseBtn = card.querySelector(".knx-collapse-btn");
          
          if (list && collapseBtn) {
            card.classList.add("is-collapsed");
            list.style.maxHeight = "0px";
            collapseBtn.querySelector("i").className = "fas fa-chevron-down";
            collapseBtn.querySelector(".knx-collapse-text").textContent = "Expand";
          }
        }
      });
    }
  }

  function wireCardEvents(){
    document.querySelectorAll(".knx-modifier-card").forEach(card=>{
      const id = parseInt(card.dataset.id,10);
      const list = card.querySelector(".knx-options-list");
      const header = card.querySelector('.knx-modifier-card-header');
      const collapseBtn = card.querySelector('[data-action="collapse"]');

      requestAnimationFrame(()=>{ list.style.maxHeight = list.scrollHeight + "px"; });

      const toggle = (e)=>{
        // Evitar toggle si el click fue en un botón de acción
        if (e && e.target.closest('.knx-modifier-actions')) return;
        
        const collapsed = card.classList.toggle("is-collapsed");
        const chevron = collapseBtn.querySelector('.knx-chevron');
        
        if (collapsed){
          chevron.style.transform = 'rotate(180deg)';
          list.style.maxHeight = "0px";
          header.setAttribute('aria-expanded', 'false');
        }else{
          chevron.style.transform = 'rotate(0deg)';
          list.style.maxHeight = list.scrollHeight + "px";
          header.setAttribute('aria-expanded', 'true');
        }
      };

      // Header completo clickable
      header.addEventListener("click", toggle);
      
      // Soporte de teclado para header
      header.addEventListener("keydown", (e)=>{
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggle(e);
        }
      });

      // Botón de collapse también funciona
      collapseBtn.addEventListener("click", (e)=>{
        e.stopPropagation();
        toggle(e);
      });

      card.querySelector('[data-action="add-option"]').addEventListener("click",(e)=>{
        e.stopPropagation();
        openOptionModal(id);
      });
      card.querySelector('[data-action="edit"]').addEventListener("click",(e)=>{
        e.stopPropagation();
        const mod = state.modifiers.find(m=>+m.id===id);
        openModifierModal(mod);
      });
      card.querySelector('[data-action="delete"]').addEventListener("click",(e)=>{
        e.stopPropagation();
        deleteModifier(id);
      });

      // Reorder buttons
      const sortUpBtn = card.querySelector('[data-action="sort-up"]');
      const sortDownBtn = card.querySelector('[data-action="sort-down"]');
      
      if (sortUpBtn) {
        sortUpBtn.addEventListener("click", (e)=>{
          e.stopPropagation();
          reorderModifier(id, 'up');
        });
      }
      
      if (sortDownBtn) {
        sortDownBtn.addEventListener("click", (e)=>{
          e.stopPropagation();
          reorderModifier(id, 'down');
        });
      }

      card.querySelectorAll('[data-action="edit-option"]').forEach(b=>{
        b.addEventListener("click", (e)=>{
          const optId = e.currentTarget.closest(".knx-option-item").dataset.optionId;
          const mod = state.modifiers.find(m=>+m.id===id);
          const opt = (mod.options||[]).find(o=>+o.id===+optId);
          openOptionModal(id, opt);
        });
      });
      card.querySelectorAll('[data-action="delete-option"]').forEach(b=>{
        b.addEventListener("click",(e)=>{
          const optId = e.currentTarget.closest(".knx-option-item").dataset.optionId;
          deleteOption(+optId);
        });
      });
    });
  }

  // Image preview
  imageInput.addEventListener("change",(e)=>{
    const f = e.target.files[0]; if(!f) return;
    const rd = new FileReader(); rd.onload = ev => setPreview(ev.target.result); rd.readAsDataURL(f);
  });

  // Save item
  document.getElementById("knxEditItemForm").addEventListener("submit", async (e)=>{
    e.preventDefault();
    const fd = new FormData();
    fd.append("hub_id", state.hubId);
    fd.append("id", state.itemId);
    fd.append("name", nameInput.value.trim());
    fd.append("description", descInput.value.trim());
    fd.append("category_id", catSelect.value);
    fd.append("price", priceInput.value.trim());
    fd.append("knx_nonce", state.nonce);
    if (imageInput.files.length) fd.append("item_image", imageInput.files[0]);

    try{
      const r = await fetch(api.update,{method:"POST",body:fd});
      const j = await r.json();
      j.success ? toast("Item updated") : toast(j.error||"Error updating","error");
    }catch{ toast("Network error","error"); }
  });

  document.getElementById("knxBrowseGlobalBtn")?.addEventListener("click", openGlobalLibrary);
  document.getElementById("knxAddModifierBtn")?.addEventListener("click", ()=> openModifierModal(null));

  /* ---------- Modals (Group / Option) ---------- */
  function openModifierModal(mod){
    const isEdit = !!mod;
    const modal = document.createElement("div");
    modal.className = "knx-modal-overlay";
    modal.innerHTML = `
      <div class="knx-modal-content">
        <div class="knx-modal-header">
          <h3><i class="fas fa-sliders-h"></i> ${isEdit?"Edit group":"New group"}</h3>
          <button class="knx-modal-close">&times;</button>
        </div>
        <form id="mmForm">
          <div class="knx-form-row">
            <div class="knx-form-group">
              <label>Group name <span class="knx-required">*</span></label>
              <input id="mmName" value="${isEdit?esc(mod.name):""}" required>
            </div>
            <div class="knx-form-group">
              <label>Type</label>
              <select id="mmType">
                <option value="single" ${isEdit&&mod.type==="single"?"selected":""}>Single</option>
                <option value="multiple" ${isEdit&&mod.type==="multiple"?"selected":""}>Multiple</option>
              </select>
            </div>
          </div>

          <div class="knx-form-row">
            <label><input type="checkbox" id="mmRequired" ${isEdit&&mod.required==1?"checked":""}> Required</label>
            <label><input type="checkbox" id="mmGlobal" ${isEdit&&mod.is_global==1?"checked":""}> <i class="fas fa-globe"></i> Make this global</label>
          </div>

          <div class="knx-form-row" id="mmMultiRow" style="display:${isEdit&&mod.type==="multiple"?"grid":"none"}">
            <div class="knx-form-group"><label>Min</label><input type="number" id="mmMin" min="0" value="${isEdit?(mod.min_selection||0):0}"></div>
            <div class="knx-form-group"><label>Max</label><input type="number" id="mmMax" min="1" value="${isEdit&&mod.max_selection?mod.max_selection:""}"></div>
          </div>

          <div class="knx-form-group" style="margin-top:8px;">
            <strong>Options</strong>
            <div id="mmOptions"></div>
            <div style="margin-top:8px;"><button type="button" class="knx-btn knx-btn-outline" id="mmAddOpt"><i class="fas fa-plus"></i> Add option</button></div>
          </div>

          <div style="display:flex;gap:10px;align-items:center;margin-top:12px;justify-content:center;">
            <button class="knx-btn knx-btn-xl" type="submit"><i class="fas fa-save"></i> ${isEdit?"Update":"Create"}</button>
          </div>
        </form>
      </div>`;
    document.body.appendChild(modal);

    const close = ()=> modal.remove();
    modal.querySelector(".knx-modal-close").addEventListener("click", close);
    modal.addEventListener("click",(e)=>{ if(e.target===modal) close(); });

    const mmType = modal.querySelector("#mmType");
    const mmMultiRow = modal.querySelector("#mmMultiRow");
    mmType.addEventListener("change", ()=> mmMultiRow.style.display = mmType.value==="multiple" ? "grid" : "none");

    const list = modal.querySelector("#mmOptions");
    const addRow = (o)=>{
      const row = document.createElement("div");
      row.className = "knx-option-row";
      row.dataset.optId = o?.id || "";
      row.innerHTML = `
        <div class="knx-opt-drag"><i class="fas fa-grip-vertical"></i></div>
        <input type="text" class="mmOptName" placeholder="Option name" value="${o?esc(o.name):""}">
        <input type="number" step="0.01" class="mmOptPrice" value="${o?o.price_adjustment:0}">
        <label><input type="checkbox" class="mmOptDef" ${o&&o.is_default==1?"checked":""}> Default</label>
        <button type="button" class="knx-icon-btn delete mmDel"><i class="fas fa-trash"></i> Remove</button>`;
      row.querySelector(".mmDel").addEventListener("click", ()=> row.remove());
      list.appendChild(row);
    };

    if (isEdit && mod.options) mod.options.forEach(addRow);
    modal.querySelector("#mmAddOpt").addEventListener("click", ()=> addRow(null));

    modal.querySelector("#mmForm").addEventListener("submit", async (e)=>{
      e.preventDefault();
      const name = modal.querySelector("#mmName").value.trim();
      const type = mmType.value;
      const required = modal.querySelector("#mmRequired").checked ? 1 : 0;
      const isGlobal = modal.querySelector("#mmGlobal").checked ? 1 : 0;
      const minSel = type==="multiple" ? (parseInt(modal.querySelector("#mmMin").value)||0) : 0;
      const maxSel = type==="multiple" && modal.querySelector("#mmMax").value ? parseInt(modal.querySelector("#mmMax").value) : null;

      const rows = Array.from(list.querySelectorAll(".knx-option-row"));
      if (isGlobal && rows.length === 0) { toast("Global groups must include at least one option","error"); return; }
      if (!name) { toast("Name is required","error"); return; }

      const payload = {
        id: isEdit ? mod.id : 0,
        item_id: isGlobal ? null : state.itemId,
        hub_id: state.hubId,
        name, type, required,
        min_selection: minSel, max_selection: maxSel,
        is_global: isGlobal, knx_nonce: state.nonce,
      };

      try{
        const r = await fetch(api.saveMod,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const j = await r.json();
        if (!j.success){ toast(j.error||"Error saving group","error"); return; }
        const savedId = j.id || j.ID || (mod && mod.id);

        const origIds = isEdit && mod.options ? mod.options.map(o=>+o.id) : [];
        const curIds  = rows.map(r=> r.dataset.optId ? +r.dataset.optId : 0).filter(Boolean);
        const toDel = origIds.filter(id=> !curIds.includes(id));
        for (const id of toDel){
          await fetch(api.delOpt,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({id,knx_nonce:state.nonce})});
        }
        for (const row of rows){
          const oId = row.dataset.optId ? +row.dataset.optId : 0;
          const oName = row.querySelector(".mmOptName").value.trim();
          const oPrice = parseFloat(row.querySelector(".mmOptPrice").value)||0;
          const oDef = row.querySelector(".mmOptDef").checked ? 1 : 0;
          if (!oName) continue;
          await fetch(api.saveOpt,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({
            id:oId,modifier_id:savedId,name:oName,price_adjustment:oPrice,is_default:oDef,knx_nonce:state.nonce
          })});
        }

        toast(isEdit?"Group updated":"Group created");
        close(); await loadModifiers();
      }catch{ toast("Network error","error"); }
    });
  }

  function openOptionModal(modifierId, option){
    const isEdit = !!option;
    const modal = document.createElement("div");
    modal.className = "knx-modal-overlay";
    modal.innerHTML = `
      <div class="knx-modal-content" style="max-width:520px">
        <div class="knx-modal-header">
          <h3>${isEdit?"Edit option":"New option"}</h3>
          <button class="knx-modal-close">&times;</button>
        </div>
        <form id="optForm">
          <div class="knx-form-group">
            <label>Name <span class="knx-required">*</span></label>
            <input id="opName" value="${isEdit?esc(option.name):""}" required>
          </div>
          <div class="knx-form-group">
            <label>Price adjustment (USD)</label>
            <input id="opPrice" type="number" step="0.01" value="${isEdit?option.price_adjustment:0}">
            <small>0.00 = FREE</small>
          </div>
          <label><input type="checkbox" id="opDef" ${isEdit&&option.is_default==1?"checked":""}> Default</label>

          <div class="knx-actions-centered">
            <button class="knx-btn knx-btn-xl" type="submit"><i class="fas fa-save"></i> ${isEdit?"Update":"Create"}</button>
            <button class="knx-btn-secondary knx-btn-xl" type="button" id="opCancel">Cancel</button>
          </div>
        </form>
      </div>`;
    document.body.appendChild(modal);
    const close = ()=> modal.remove();
    modal.querySelector(".knx-modal-close").addEventListener("click", close);
    modal.addEventListener("click",(e)=>{ if(e.target===modal) close(); });
    modal.querySelector("#opCancel").addEventListener("click", close);

    modal.querySelector("#optForm").addEventListener("submit", async (e)=>{
      e.preventDefault();
      const name = modal.querySelector("#opName").value.trim();
      const price = parseFloat(modal.querySelector("#opPrice").value)||0;
      const def = modal.querySelector("#opDef").checked ? 1 : 0;
      if (!name){ toast("Name is required","error"); return; }
      try{
        const r = await fetch(api.saveOpt,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({
          id:isEdit?option.id:0, modifier_id:modifierId, name, price_adjustment:price, is_default:def, knx_nonce:state.nonce
        })});
        const j = await r.json();
        j.success ? (toast(isEdit?"Option updated":"Option created"), close(), loadModifiers())
                  : toast(j.error||"Error saving option","error");
      }catch{ toast("Network error","error"); }
    });
  }

  async function reorderModifier(id, direction){
    // Encontrar la card actual
    const cards = Array.from(document.querySelectorAll('.knx-modifier-card'));
    const currentCard = cards.find(c => +c.dataset.id === id);
    if (!currentCard) return;
    
    const currentIndex = cards.indexOf(currentCard);
    const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
    
    // Verificar límites
    if (targetIndex < 0 || targetIndex >= cards.length) {
      toast("Can't move further", "info");
      return;
    }
    
    // Deshabilitar botones durante la operación
    const sortBtns = currentCard.querySelectorAll('.sort-up, .sort-down');
    sortBtns.forEach(btn => btn.disabled = true);
    
    try{
      const r = await fetch(api.reMod, {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          id,
          direction,
          knx_nonce: state.nonce
        })
      });
      const j = await r.json();
      
      if (j.success) {
        // Animación suave: fade out -> recargar -> fade in
        modifiersList.style.opacity = '0.5';
        setTimeout(async ()=>{
          await loadModifiers();
          modifiersList.style.opacity = '1';
        }, 150);
        toast("Order updated", "success");
      } else {
        toast(j.error || "Reorder failed", "error");
      }
    } catch {
      toast("Network error", "error");
    } finally {
      sortBtns.forEach(btn => btn.disabled = false);
    }
  }

  async function deleteModifier(id){
    knxConfirm('Delete this group?', 'This will delete the group and its options.', async ()=>{
      try{
        const r = await fetch(api.delMod,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({id,knx_nonce:state.nonce})});
        const j = await r.json();
        j.success ? (toast("Group deleted"), loadModifiers()) : toast(j.error||"Delete failed","error");
      }catch{ toast("Network error","error"); }
    });
  }
  async function deleteOption(id){
    knxConfirm('Delete this option?', 'This action cannot be undone.', async ()=>{
      try{
        const r = await fetch(api.delOpt,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({id,knx_nonce:state.nonce})});
        const j = await r.json();
        j.success ? (toast("Option deleted"), loadModifiers()) : toast(j.error||"Delete failed","error");
      }catch{ toast("Network error","error"); }
    });
  }

  /* ---------- Global library ---------- */
  async function openGlobalLibrary(){
    try{
      const r = await fetch(`${api.globals}?hub_id=${state.hubId}`);
      const j = await r.json();

      const inner = (!j.success || !j.modifiers || !j.modifiers.length)
        ? `<div class="knx-global-empty" style="padding:32px;text-align:center;color:#6b7280">
             <i class="fas fa-box-open fa-3x" style="color:#9ca3af;margin-bottom:16px;display:block;"></i>
             <h3 style="margin:8px 0;font-size:1.2rem;">No global groups yet</h3>
             <p style="margin:4px 0;color:#9ca3af;">Create one and mark "Make this global".</p>
           </div>`
        : `
          <input id="knxGlobalSearch" class="knx-global-search" placeholder="Search groups…" style="margin:0 14px 12px;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;width:calc(100% - 28px);">
          <div class="knx-global-library-list">
            ${j.modifiers.map(m=>`
              <div class="knx-global-item" data-id="${m.id}" data-name="${esc(m.name).toLowerCase()}">
                <div class="knx-global-head">
                  <div class="knx-global-title">
                    <h4 class="knx-global-item-name">${esc(m.name)}</h4>
                    <div class="knx-global-meta">${esc(metaText(m))} • Used in ${m.usage_count||0} item${(m.usage_count||0)===1?"":"s"}</div>
                  </div>
                  <div class="knx-global-actions">
                    <button class="knx-icon-btn" data-act="collapse" aria-label="Show/Hide options" title="Toggle options"><i class="fas fa-chevron-up"></i></button>
                    <button class="knx-icon-btn" data-act="edit" aria-label="Edit group" title="Edit"><i class="fas fa-pen"></i></button>
                    <button class="knx-icon-btn delete" data-act="delete" aria-label="Delete group" title="Delete"><i class="fas fa-trash-alt"></i></button>
                    <button class="knx-btn knx-btn-sm" data-act="add" style="padding:8px 14px;height:38px;"><i class="fas fa-plus"></i> Add</button>
                  </div>
                </div>
                ${(m.options&&m.options.length)?`
                  <div class="knx-global-options">
                    ${m.options.map(o=>`<div class="knx-global-line"><span>${esc(o.name)}</span><span>${priceTextUSD(o.price_adjustment)}</span></div>`).join("")}
                  </div>`:""}
              </div>`).join("")}
          </div>`;

      const modal = document.createElement("div");
      modal.className = "knx-modal-overlay";
      modal.id = "knxGlobalLibraryModal";
      modal.innerHTML = `
        <div class="knx-modal-content knx-modal-lg">
          <div class="knx-modal-header">
            <h3><i class="fas fa-globe"></i> Global library</h3>
            <button class="knx-modal-close">&times;</button>
          </div>
          ${inner}
        </div>`;
      document.body.appendChild(modal);

      const close = ()=> {
        modal.remove();
        document.removeEventListener('keydown', handleEsc);
      };
      
      // Cerrar con Esc
      const handleEsc = (e)=>{
        if (e.key === 'Escape') close();
      };
      document.addEventListener('keydown', handleEsc);
      
      modal.querySelector(".knx-modal-close").addEventListener("click", close);
      modal.addEventListener("click",(e)=>{ if(e.target===modal) close(); });

      // Debounce para búsqueda (200ms) + destacar coincidencias
      let debounceTimer;
      modal.querySelector("#knxGlobalSearch")?.addEventListener("input",(e)=>{
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(()=>{
          const q = e.target.value.toLowerCase();
          modal.querySelectorAll(".knx-global-item").forEach(el=>{
            const name = el.dataset.name || "";
            const visible = name.includes(q);
            el.style.display = visible ? "" : "none";
            
            // Destacar coincidencias en el título
            const h4 = el.querySelector(".knx-global-item-name");
            if (h4) {
              const originalText = el.dataset.originalName || h4.textContent;
              if (!el.dataset.originalName) el.dataset.originalName = originalText;
              
              if (q && visible) {
                const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                h4.innerHTML = originalText.replace(regex, '<mark style="background:#fef08a;padding:2px 4px;border-radius:3px;">$1</mark>');
              } else {
                h4.textContent = originalText;
              }
            }
          });
        }, 200);
      });

      modal.querySelectorAll(".knx-global-item").forEach(el=>{
        const id = +el.dataset.id;
        const options = el.querySelector(".knx-global-options");
        const collapseBtn = el.querySelector('[data-act="collapse"]');
        if (options){
          requestAnimationFrame(()=> options.style.maxHeight = options.scrollHeight + "px");
          collapseBtn?.addEventListener("click", ()=>{
            const collapsed = el.classList.toggle("is-collapsed");
            const icon = collapseBtn.querySelector('i');
            if (collapsed){
              icon.className = 'fas fa-chevron-down';
              options.style.maxHeight = "0px";
            }else{
              icon.className = 'fas fa-chevron-up';
              options.style.maxHeight = options.scrollHeight + "px";
            }
          });
        }

        el.querySelector('[data-act="add"]')?.addEventListener("click", async ()=>{
          const btn = el.querySelector('[data-act="add"]');
          btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding…';
          try{
            const r = await fetch(api.clone,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({
              global_modifier_id:id,item_id:state.itemId,knx_nonce:state.nonce
            })});
            const j = await r.json();
            if (j.success){ toast("Group added to this item"); close(); loadModifiers(); }
            else if (j.error === "already_cloned"){ toast("This group already exists in this item","warning"); }
            else { toast(j.error||"Error adding","error"); }
          }catch{ toast("Network error","error"); }
          btn.disabled = false; btn.innerHTML = '<i class="fas fa-plus"></i> Add';
        });

        el.querySelector('[data-act="edit"]')?.addEventListener("click", ()=>{
          const mod = (j.modifiers||[]).find(m=>+m.id===id);
          openModifierModal(mod);
        });

        el.querySelector('[data-act="delete"]')?.addEventListener("click", async ()=>{
          knxConfirm('Delete this global group?', 'This will remove the group from the global library.', async ()=>{
            try{
              const r = await fetch(api.delMod,{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({id,knx_nonce:state.nonce})});
              const jj = await r.json();
              jj.success ? (toast("Global group deleted"), el.remove()) : toast(jj.error||"Error deleting","error");
            }catch{ toast("Network error","error"); }
          });
        });
      });

    }catch{ toast("Error loading global library","error"); }
  }
});
