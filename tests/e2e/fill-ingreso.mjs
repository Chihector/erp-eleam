export default async function run(page, ui) {
  // Set date - today
  const today = new Date();
  const todayStr = today.toISOString().split('T')[0];
  await page.fill('input[id="form.fecha"]', todayStr);
  await page.waitForTimeout(500);
  
  // Category
  await page.fill('input[id="form.categoria"]', 'Subsidio SENAMA');
  await page.waitForTimeout(500);
  
  // Description
  await page.fill('input[id="form.descripcion"]', 'Aporte mensual estatal para residentes');
  await page.waitForTimeout(500);
  
  // Amount
  await page.fill('input[id="form.monto"]', '2500000');
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('button:has-text("Crear")');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}