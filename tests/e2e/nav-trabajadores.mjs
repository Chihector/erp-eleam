export default async function run(page, ui) {
  // Try expanding sidebar first
  await ui.click('@e2'); // Expandir barra lateral
  await page.waitForTimeout(1000);
  await ui.click('@e10'); // Trabajadores link
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}