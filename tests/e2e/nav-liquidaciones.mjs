export default async function run(page, ui) {
  await ui.click('@e2'); // Expand sidebar
  await page.waitForTimeout(500);
  await ui.click('@e14'); // Liquidaciones
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}