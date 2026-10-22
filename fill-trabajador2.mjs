export default async function run(page, ui) {
  // Fill text fields
  await page.fill('[data-ab-ref="e19"]', '19486694-1');
  await page.fill('[data-ab-ref="e21"]', 'María');
  await page.fill('[data-ab-ref="e22"]', 'González');
  await page.fill('[data-ab-ref="e23"]', 'Cuidadora');
  await page.fill('[data-ab-ref="e20"]', '2024-03-15');
  await page.fill('[data-ab-ref="e24"]', '#FF6B6B');
  
  // Select AFP - use selectOption
  await page.selectOption('[data-ab-ref="e25"]', { label: 'Modelo' });
  await page.waitForTimeout(500);
  
  // Select Sistema de Salud
  await page.selectOption('[data-ab-ref="e27"]', { label: 'Fonasa' });
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('[data-ab-ref="e28"]');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}