export default async function run(page, ui) {
  // Select worker
  await page.click('[data-ab-ref="e30"]');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select month - click and type
  await page.click('[data-ab-ref="e32"]');
  await page.waitForTimeout(500);
  await page.keyboard.type('Octubre');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Year - 2026
  await page.fill('[data-ab-ref="e33"]', '2026');
  await page.waitForTimeout(500);
  
  // Days worked - click and select 30
  await page.click('[data-ab-ref="e34"]');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter'); // 30 is default
  await page.waitForTimeout(1000);
  
  // Click Enviar
  await page.click('[data-ab-ref="e35"]');
  await page.waitForTimeout(8000);
  
  return await ui.snapshot();
}