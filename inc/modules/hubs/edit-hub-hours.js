/**
 * Kingdom Nexus - Edit Hub Hours JS (v8.9)
 * ------------------------------------------------
 * - Global knxToast() feedback
 * - Sunday visually locked
 * - Clean 'to' label handling
 */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('knxHoursContainer');
  const saveBtn = document.getElementById('knxSaveHoursBtn');
  if (!container || !saveBtn) return;

  /** 🕊️ Sunday lock (visual only) */
  const sundayRow = container.querySelector('.knx-hours-row[data-day="sunday"]');
  if (sundayRow) {
    sundayRow.querySelectorAll('input').forEach(el => {
      el.disabled = true;
      el.classList.add('knx-disabled');
    });
    sundayRow.classList.add('sunday-locked');
  }

  /** 💾 Save hours */
  saveBtn.addEventListener('click', async () => {
    const hubId = saveBtn.dataset.hubId;
    const nonce = saveBtn.dataset.nonce;
    const payload = { hub_id: hubId, knx_nonce: nonce, hours: {} };

    container.querySelectorAll('.knx-hours-row').forEach(row => {
      const day = row.dataset.day;
      if (day === 'sunday') return; // locked

      const open1 = row.querySelector('.open1')?.value || '';
      const close1 = row.querySelector('.close1')?.value || '';
      const open2 = row.querySelector('.open2')?.value || '';
      const close2 = row.querySelector('.close2')?.value || '';
      const dayCheck = row.querySelector('.day-check')?.checked || false;
      const secondCheck = row.querySelector('.second-check')?.checked || false;

      const intervals = [];
      if (dayCheck && open1 && close1) intervals.push({ open: open1, close: close1 });
      if (secondCheck && open2 && close2) intervals.push({ open: open2, close: close2 });

      payload.hours[day] = intervals;
    });

    try {
      const res = await fetch(`${knx_api.root}knx/v1/save-hours`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (data.success) {
        knxToast('✅ Working hours saved successfully!', 'success');
      } else {
        knxToast('❌ Failed to save hours.', 'error');
      }
    } catch (err) {
      console.error(err);
      knxToast('⚠️ Connection error while saving.', 'warning');
    }
  });
});
