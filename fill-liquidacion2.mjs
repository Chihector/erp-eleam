export default async function run(page, ui) {
  // Select worker
  await ui.click('@e30');
  await page.waitForTimeout(500);
  await page.keyboard.type('María González');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Select month - October
  await ui.click('@e32');
  await page.waitForTimeout(500);
  await page.keyboard.type('Octubre');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1000);
  
  // Year - 2026
  await ui.fill('@e33', '2026');
  await page.waitForTimeout(500);
  
  // Days worked - 30
  await ui.click('@e34');
  await page.waitForTimeout(500);
  await page.keyboard.press('Enter'); // Default is 30
  await page.waitForTimeout(1000);
  
  // Click Enviar
  await ui.click('@e35');
  await page.waitForTimeout(5000);
  
  return await ui.snapshot();
}