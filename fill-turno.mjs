export default async function run(page, ui) {
  // Select worker
  await ui.click('@e19');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select shift type - Día
  await page.selectOption('[data-ab-ref="e20"]', { label: 'Día' });
  await page.waitForTimeout(500);
  
  // Select state - Programado
  await page.selectOption('[data-ab-ref="e21"]', { label: 'Programado' });
  await page.waitForTimeout(500);
  
  // Set start date/time - tomorrow at 08:00 (Día auto-adjusts but let's set it)
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split('T')[0] + 'T08:00';
  await page.fill('[data-ab-ref="e22"]', tomorrowStr);
  await page.waitForTimeout(500);
  
  // Set end date/time - tomorrow at 20:00
  const tomorrowEndStr = tomorrow.toISOString().split('T')[0] + 'T20:00';
  await page.fill('[data-ab-ref="e23"]', tomorrowEndStr);
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('[data-ab-ref="e25"]');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}