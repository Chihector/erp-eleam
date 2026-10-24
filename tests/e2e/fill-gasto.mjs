export default async function run(page, ui) {
  // Set date - today
  const today = new Date();
  const todayStr = today.toISOString().split('T')[0];
  await page.fill('input[id="form.fecha"]', todayStr);
  await page.waitForTimeout(500);
  
  // Select category
  await ui.click('@e20');
  await page.waitForTimeout(500);
  await page.keyboard.type('Comida');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Description
  await page.fill('textarea[id="form.descripcion"]', 'Compra de alimentos para residentes - semana 1');
  await page.waitForTimeout(500);
  
  // Amount
  await page.fill('input[id="form.monto"]', '150000');
  await page.waitForTimeout(500);
  
  // Click Crear
  await page.click('button:has-text("Crear")');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}