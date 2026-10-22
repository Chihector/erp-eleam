export default async function run(page, ui) {
  // Select worker
  await page.click('[data-ab-ref="e30"]');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select month - October (value 10)
  await page.selectOption('select[name="mes"]', '10');
  await page.waitForTimeout(500);
  
  // Year - 2026
  await page.fill('input[name="anio"]', '2026');
  await page.waitForTimeout(500);
  
  // Days worked - 30
  await page.selectOption('select[name="dias_trabajados"]', '30');
  await page.waitForTimeout(500);
  
  // Click Enviar
  await page.click('button:has-text("Enviar")');
  await page.waitForTimeout(5000);
  
  return await ui.snapshot();
}