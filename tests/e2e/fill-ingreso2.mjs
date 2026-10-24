export default async function run(page, ui) {
  // Set date - today
  const today = new Date();
  const todayStr = today.toISOString().split('T')[0];
  await ui.fill('@e19', todayStr);
  await page.waitForTimeout(500);
  
  // Category
  await ui.fill('@e20', 'Subsidio SENAMA');
  await page.waitForTimeout(500);
  
  // Description
  await ui.fill('@e21', 'Aporte mensual estatal para residentes');
  await page.waitForTimeout(500);
  
  // Amount
  await ui.fill('@e22', '2500000');
  await page.waitForTimeout(500);
  
  // Click Crear
  await ui.click('@e24');
  await page.waitForTimeout(4000);
  
  return await ui.snapshot();
}