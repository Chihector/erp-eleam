export default async function run(page, ui) {
  await ui.click('@e2'); // Expand sidebar
  await page.waitForTimeout(500);
  await ui.click('@e12'); // Ingresos
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}