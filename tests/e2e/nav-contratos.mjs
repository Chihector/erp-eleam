export default async function run(page, ui) {
  await ui.click('@e11'); // Contratos link
  await page.waitForTimeout(3000);
  return await ui.snapshot();
}