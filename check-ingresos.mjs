export default async function run(page, ui) {
  await ui.click('@e18'); // Ingresos link
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}