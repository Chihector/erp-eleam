export default async function run(page, ui) {
  // Select worker (combobox)
  await ui.click('@e19');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select contract type
  await page.selectOption('[data-ab-ref="e20"]', { label: 'Indefinido' });
  await page.waitForTimeout(500);
  
  // Fill salary
  await page.fill('[data-ab-ref="e21"]', '550000');
  await page.waitForTimeout(500);
  
  // Fill hours
  await page.fill('[data-ab-ref="e22"]', '45');
  await page.waitForTimeout(500);
  
  // Fill start date
  await page.fill('[data-ab-ref="e24"]', '2024-03-15');
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('[data-ab-ref="e26"]');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}