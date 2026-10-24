export default async function run(page, ui) {
  // Select worker
  await ui.click('@e19');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select shift
  await ui.click('@e20');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Set entry time - today at 07:55
  const today = new Date();
  const todayStr = today.toISOString().split('T')[0] + 'T07:55';
  await page.fill('[data-ab-ref="e21"]', todayStr);
  await page.waitForTimeout(500);
  
  // Set exit time - today at 20:05
  const todayEndStr = today.toISOString().split('T')[0] + 'T20:05';
  await page.fill('[data-ab-ref="e22"]', todayEndStr);
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('[data-ab-ref="e24"]');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}