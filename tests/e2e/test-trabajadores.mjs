export default async function run(page, ui) {
  // Navigate to Trabajadores
  await ui.click('@e10'); // Trabajadores link
  await page.waitForTimeout(2000);
  return await ui.snapshot();
}