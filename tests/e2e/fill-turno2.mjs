export default async function run(page, ui) {
  // Select shift type - Día (using click + keyboard)
  await ui.click('@e21');
  await page.waitForTimeout(500);
  await page.keyboard.type('Día');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select state - Programado
  await ui.click('@e22');
  await page.waitForTimeout(500);
  await page.keyboard.type('Programado');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Set start date/time - tomorrow at 08:00
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split('T')[0] + 'T08:00';
  await page.fill('[data-ab-ref="e23"]', tomorrowStr);
  await page.waitForTimeout(500);
  
  // Set end date/time - tomorrow at 20:00
  const tomorrowEndStr = tomorrow.toISOString().split('T')[0] + 'T20:00';
  await page.fill('[data-ab-ref="e24"]', tomorrowEndStr);
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('[data-ab-ref="e26"]');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}